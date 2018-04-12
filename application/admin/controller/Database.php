<?php
/**
 * 控制器-用户
 * @author 夏爽
 */
namespace app\admin\controller;

class Database extends \app\common\controller\AdminBase{
	
	/**
	 * 数据库-列表
	 * 参数 $pageNum=页码数 $numPerPage=每页数据条数 $search=搜索
	 */
	public function database_list($pageNum = 1, $numPerPage = null, $search = []){
		$list = db()->query('SHOW TABLE STATUS');
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list' => $list,
		]]);
	}
	
	/**
	 * 优化表
	 * @param string $ids 表名（多个表，分隔）
	 */
	public function optimize($ids = ''){
		if(empty($ids)) return $this->dwzReturn(300, '请选择要操作的数据!');
		//表名格式化
		$tables = '';
		foreach(explode(',', $ids) as $k => $v){
			$tables .= $k==0 ? '`'.$v.'`' : ',`'.$v.'`';
		}
		//优化
		$result = db()->query('OPTIMIZE TABLE '.$tables);
		if(!$result) return $this->dwzReturn(300);
		return $this->dwzReturn(200, null, ['callbackType' => '']);
	}
	
	/**
	 * 备份文件列表
	 */
	public function backup_list(){
		//列出备份文件列表
		$path = config('db_backup_path');
		if(!is_dir($path)) mkdir($path, 0755, true);
		$path = realpath($path);
		$flag = \FilesystemIterator::KEY_AS_FILENAME;
		$glob = new \FilesystemIterator($path, $flag);
		
		$list = array();
		foreach($glob as $name => $file){
			if(preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql(?:\.gz)?$/', $name)){
				$name = sscanf($name, '%4s%2s%2s-%2s%2s%2s-%d');
				
				$date = "{$name[0]}-{$name[1]}-{$name[2]}";
				$time = "{$name[3]}:{$name[4]}:{$name[5]}";
				$part = $name[6];
				
				if(isset($list["{$date} {$time}"])){
					$info         = $list["{$date} {$time}"];
					$info['part'] = max($info['part'], $part);
					$info['size'] = $info['size']+$file->getSize();
				}else{
					$info['part'] = $part;
					$info['size'] = $file->getSize();
				}
				$extension        = strtoupper(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
				$info['compress'] = ($extension==='SQL') ? '-' : $extension;
				$info['time']     = strtotime("{$date} {$time}");
				
				$list["{$date} {$time}"] = $info;
			}
		}
	}
	
	/**
	 * 备份数据库
	 * @param  String $tables 表名
	 * @param  Integer $id 表ID
	 * @param  Integer $start 起始行数
	 */
	public function export($tables = null, $id = null, $start = null){
		if($this->request->isPost() && !empty($tables) && is_array($tables)){ //初始化
			$path = config('db_backup_path');
			if(!is_dir($path)) mkdir($path, 0755, true);
			//读取备份配置
			$config = [
				'path'     => realpath($path).'/',
				'part'     => config('db_backup_part_size'),
				'compress' => config('db_backup_compress'),
				'level'    => config('db_backup_compress_level'),
			];
			
			//开启程序锁
			$result = service('ExecLock')->setTime(120)->open();
			if(!$result) return $this->dwzReturn(300, '检测到有一个备份任务正在执行，请稍后再试！');
			
			//检查备份目录是否可写
			if(!is_writeable($config['path'])) return $this->dwzReturn(300, '备份目录不存在或不可写，请检查后重试！');
			session('backup_config', $config);
			
			//生成备份文件信息
			$file = array(
				'name' => date('Ymd-His'),
				'part' => 1,
			);
			session('backup_file', $file);
			
			//缓存要备份的表
			session('backup_tables', $tables);
			
			//创建备份文件
			service('DatabaseBackup')->create();
			$Database = new Database($file, $config);
			if(false!==$Database->create()){
				$tab = array('id' => 0, 'start' => 0);
				$this->success('初始化成功！', '', array('tables' => $tables, 'tab' => $tab));
			}else{
				$this->error('初始化失败，备份文件创建失败！');
			}
		}elseif(IS_GET && is_numeric($id) && is_numeric($start)){ //备份数据
			$tables = session('backup_tables');
			//备份指定表
			$Database = new Database(session('backup_file'), session('backup_config'));
			$start    = $Database->backup($tables[$id], $start);
			if(false===$start){ //出错
				$this->error('备份出错！');
			}elseif(0===$start){ //下一表
				if(isset($tables[++$id])){
					$tab = array('id' => $id, 'start' => 0);
					$this->success('备份完成！', '', array('tab' => $tab));
				}else{ //备份完成，清空缓存
					unlink(session('backup_config.path').'backup.lock');
					session('backup_tables', null);
					session('backup_file', null);
					session('backup_config', null);
					$this->success('备份完成！');
				}
			}else{
				$tab  = array('id' => $id, 'start' => $start[0]);
				$rate = floor(100*($start[0]/$start[1]));
				$this->success("正在备份...({$rate}%)", '', array('tab' => $tab));
			}
		}else{ //出错
			$this->error('参数错误！');
		}
	}
	
}