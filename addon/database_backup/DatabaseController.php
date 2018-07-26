<?php
/**
 * 数据库
 * @author xs
 */
namespace app\admin\controller;


use lib\Addon;
use lib\Helper;

class Database extends \app\common\controller\AdminBase{
	
	/**
	 * 数据库-列表
	 */
	public function database_list(){
		$list = db()->query('SHOW TABLE STATUS');
		foreach($list as $k => $v){
			$list[$k]['Data_length_format'] = Helper::formatBytes($v['Data_length']);
		}
		
		//视图
		cookie('forward', request()->url());
		return $this->fetch(__DIR__.'/view/database_list.html', [
			'paging' => $list,
		]);
	}
	
	/**
	 * 优化表
	 * @param string $name 表名（多个表，分隔）
	 */
	public function optimize($name = ''){
		empty($name) && $this->apiReturn(['code' => 1000, 'msg' => '请选择要操作的数据!']);
		
		//优化
		$tables = '';
		foreach(explode(',', $name) as $k => $v){
			$tables .= ($k==0 ? '' : ',').'`'.$v.'`';
		}
		$result = db()->query('OPTIMIZE TABLE '.$tables);
		!$result && $this->apiReturn(['code' => 1000, 'msg' => '操作失败!']);
		
		$this->apiReturn(['code' => 0, 'msg' => '操作成功!']);
	}
	
	/**
	 * 备份文件列表
	 */
	public function backup_list(){
		$class  = Addon::getClass('DatabaseBackup');
		$config = $class::config();
		$path   = isset($config['backup_path']) ? $config['backup_path'] : '.';
		//创建目录
		if(!is_dir($path) && !mkdir($path, 0777, true)){
			$this->error("目录 {$path} 创建失败！");
		}
		//检测目录是否可写
		if(!is_writable($path)){
			$this->error("目录 {$path} 不可写！");
		}
		//获取文件列
		$files = new \FilesystemIterator($path, \FilesystemIterator::KEY_AS_FILENAME);
		
		$list = [];
		foreach($files as $k => $v){
			//是否分卷文件
			$result  = preg_match('/.+\.part(\d+)$/', $k, $part);
			$is_part = $result ? true : false;
			$part    = $result ? $part[1] : 1;
			
			$name = $is_part ? preg_replace('/\.part(\d+)$/', "", $k) : $k;
			
			if(isset($list[$name])){
				$list[$name]['part'] = max($list[$name]['part'], $part);
				$list[$name]['size'] = $list[$name]['size']+$v->getSize();
			}else{
				$list[$name] = [
					'name'               => $name,
					'part'               => 1,
					'size'               => $v->getSize(),
					'create_time_format' => date('Y-m-d H:i:s', $v->getMTime()),
					'create_time'        => $v->getMTime(),
				];
			}
		}
		//时间倒序，排序
		array_multisort(array_column($list, 'create_time'), SORT_DESC, $list);
		
		//视图
		return $this->fetch(__DIR__.'/view/backup_list.html', [
			'paging' => $list,
		]);
	}
	
	/**
	 * 还原
	 */
	public function import(){
		$name = input('name');
		!$name && $this->apiReturn(['code' => 1000, 'msg' => '无文件']);
		
		//程序锁
		$result = service('Lock')->open('', 60*60);
		!$result && $this->apiReturn(['code' => 1000, 'msg' => '检测到有一个还原任务正在执行，请稍后再试！']);
		
		//开始还原
		require_once __DIR__.'/library/DatabaseBackup.php';
		$backup = new \DatabaseBackup([
			'type'     => config('database.type'),
			'hostname' => config('database.hostname'),
			'database' => config('database.database'),
			'username' => config('database.username'),
			'password' => config('database.password'),
			'hostport' => config('database.hostport'),
			'charset'  => config('database.charset'),
		]);
		$class  = Addon::getClass('DatabaseBackup');
		$config = $class::config();
		$path   = isset($config['backup_path']) ? $config['backup_path'] : '.';
		$result = $backup->import(rtrim($path, '/').'/'.$name);
		service('Lock')->close();
		!$result && $this->apiReturn(['code' => 1000, 'msg' => $backup->getError()]);
		
		$this->apiReturn(['code' => 0, 'msg' => '还原成功']);
	}
	
	/**
	 * 备份数据库
	 * @param  String $tables 表名
	 * @param  Integer $id 表ID
	 * @param  Integer $start 起始行数
	 */
	public function export(){
		//程序锁
		$result = service('Lock')->open('', 60*60);
		!$result && $this->apiReturn(['code' => 1000, 'msg' => '检测到有一个备份任务正在执行，请稍后再试！']);
		
		//开始备份
		require_once __DIR__.'/library/DatabaseBackup.php';
		$backup = new \DatabaseBackup([
			'type'     => config('database.type'),
			'hostname' => config('database.hostname'),
			'database' => config('database.database'),
			'username' => config('database.username'),
			'password' => config('database.password'),
			'hostport' => config('database.hostport'),
			'charset'  => config('database.charset'),
		]);
		//生成文件名
		$class  = Addon::getClass('DatabaseBackup');
		$config = $class::config();
		$path   = isset($config['backup_path']) ? $config['backup_path'] : '.';
		$file   = rtrim($path, '/').'/'.date('YmdHis').'_'.config('database.database').'.sql';
		$result = $backup->export($file, input('name'));
		service('Lock')->close();
		!$result && $this->apiReturn(['code' => 1000, 'msg' => $backup->getError()]);
		
		$this->apiReturn(['code' => 0, 'msg' => '备份成功']);
	}
	
}