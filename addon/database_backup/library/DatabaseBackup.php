<?php
/**
 * 数据库备份
 * @author xs
 */

class DatabaseBackup{
	
	//pdo对象
	private $db;
	
	//配置
	private $config = [
		// 数据库类型
		'type'     => 'mysql',
		// 服务器地址
		'hostname' => '127.0.0.1',
		// 数据库名
		'database' => 'test',
		// 用户名
		'username' => 'root',
		// 密码
		'password' => '',
		// 端口
		'hostport' => 3306,
		// 编码
		'charset'  => 'utf8',
		// 备份分卷，字节，0则不分卷
		'size'     => 1024*1024*20,
	];
	//错误信息
	private $error = '';
	
	/**
	 * 初始化
	 *
	 * @param array $host
	 */
	public function __construct($config = []){
		//配置数据库
		$this->config = array_merge($this->config, $config);
		//连接数据库
		$dsn      = "{$this->config['type']}:host={$this->config['hostname']}; port={$this->config['hostport']}; dbname={$this->config['database']}";
		$this->db = new \PDO($dsn, $this->config['username'], $this->config['password'], [
			\PDO::ATTR_PERSISTENT         => true, //长链接
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC, //默认键值对返回
			\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']}",
		]);
	}
	
	/**
	 * 获取错误信息
	 */
	public function getError(){
		return $this->error;
	}
	
	/*
	* 新增查询数据库表
	*/
	public function getTables(){
		$pdo  = $this->db->query("SHOW TABLES");
		$data = array();
		foreach($pdo->fetchAll() as $k => $v){
			$data[] = array_shift($v);
		}
		return $data;
	}
	
	/**
	 * 数据库备份
	 * @param string $file 文件路径
	 * @param string|array $table 表名，支持多表，数组或逗号分隔，为空时为全表备份
	 * @param string
	 */
	public function export($file, $tables = ''){
		// 创建目录
		$dir = dirname($file);
		if($dir){
			if(!is_dir($dir) && !mkdir($dir, 0777, true)){
				$this->error = "目录 {$dir} 创建失败！";
				return false;
			}
			//检测目录是否可写
			if(!is_writable($dir)){
				$this->error = "目录 {$dir} 不可写！";
				return false;
			}
		}
		//无时间限制
		set_time_limit(0);
		//擦除缓冲区
		ob_end_clean();
		
		//需要备份的表
		if($tables){
			$tables = is_array($tables) ? $tables : explode(',', $tables);
		}else{
			//全表
			$pdo    = $this->db->query("SHOW TABLE STATUS FROM `{$this->config['database']}`");
			$return = $pdo->fetchAll();
			if(!$return){
				$this->error = '没有可以备份的表';
				return false;
			}
			$tables = array_column($return, 'Name');
		}
		
		//分卷号
		$part    = 1;
		$is_part = 0; //是否有分页标识
		//头信息
		$sql = $this->createTitleSql();
		
		foreach($tables as $k => $table){
			$pdo = $this->db->query("SHOW TABLES LIKE '{$table}'");
			if(count($pdo->fetchAll())!=1){
				$this->error = "表'{$table}'不存在，请检查！";
				return false;
			}
			
			//表结构信息
			$sql .= $this->createTableSql($table);
			
			//数据总数
			$pdo    = $this->db->query("SELECT COUNT(*) AS count FROM `{$table}`");
			$result = $pdo->fetch();
			$count  = $result['count'];
			$batch  = ceil($count/1000);
			
			//读取写入
			for($i = 0; $i<$batch; $i++){
				$sql .= $this->createDataSql($table, $i);
				// 如果大于分卷大小或已经结尾，则写入文件
				$result = $this->config['size'] && strlen($sql)>=$this->config['size'];
				//备份文件需要分卷
				$result && !$is_part && $is_part = 1;
				if($result || (count($tables)==$k+1 && $batch==$i+1)){
					$file = $is_part ? preg_replace('/\.part(\d+)$/', "", $file).'.part'.$part :  $file;
					//写入
					$result = $this->write($file, $sql);
					if(!$result){
						return false;
					}
					//初始化
					$sql = '';
					$part++;
				}
			}
		}
		return true;
	}
	
	/**
	 * 数据库还原，自动获取分卷
	 * $param string 文件名，分卷格式以.part1结尾
	 * @return bool
	 */
	public function import($file = ''){
		//是否包含分卷
		$result  = preg_match('/.+\.part(\d+)$/', $file, $part);
		$is_part = $result ? true : false;
		//分卷号
		$part = $result ? $part[1] : 1;
		
		// 检测文件是否存在
		if(!file_exists($file)){
			if($result || !$is_part = file_exists($file = $file.'.part1')){
				$this->error = '文件不存在';
				return false;
			}
		}
		
		$files = array($file);
		if($is_part){
			while(true){
				$file_part = preg_replace('/\.part(\d+)$/', ".part".++$part, $file);
				if(!file_exists($file_part)){
					break;
				}
				$files[] = $file_part;
			}
		}
		//无时间限制
		set_time_limit(0);
		
		//开始导入
		foreach($files as $k => $v){
			$result = $this->importSql($v);
			if(!$result){
				return false;
			}
		}
		return true;
	}
	
	/**
	 * 生成头信息sql
	 * @return string
	 */
	private function createTitleSql(){
		$sql = "-- -----------------------------\n";
		$sql .= "-- MySQL database dump \n";
		$sql .= "-- \n";
		$sql .= "-- Host     : ".$this->config['hostname']."\n";
		$sql .= "-- Port     : ".$this->config['hostport']."\n";
		$sql .= "-- Database : ".$this->config['database']."\n";
		$sql .= "-- \n";
		$sql .= "-- Date : ".date("Y-m-d H:i:s")."\n";
		$sql .= "-- -----------------------------\n\n";
		$sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
		return $sql;
	}
	
	/**
	 * 生成表结构sql
	 * @param string $table 表名
	 * @return string
	 */
	private function createTableSql($table = ''){
		$pdo          = $this->db->query("SHOW CREATE TABLE `{$table}`");
		$result       = $pdo->fetch();
		$create_table = trim($result['Create Table']);
		//sql
		$sql = "-- -----------------------------\n";
		$sql .= "-- Table structure for `{$table}`\n";
		$sql .= "-- -----------------------------\n";
		$sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
		$sql .= "{$create_table};\n\n";
		return $sql;
	}
	
	/**
	 * 生成数据sql
	 * @param string $table 表名
	 * @param int $start 起始行数
	 * @return bool
	 */
	private function createDataSql($table = '', $start = 0){
		//备份数据记录
		$sql = '';
		$pdo = $this->db->query("SELECT * FROM `{$table}` LIMIT ".($start*1000).", 1000");
		foreach($pdo->fetchAll() as $row){
			$row = array_map('addslashes', $row);
			$sql .= "INSERT INTO `{$table}` VALUES ('".str_replace(array("\r", "\n"), array('\r', '\n'), implode("', '", $row))."');\n";
		}
		unset($pdo);
		return $sql;
	}
	
	/**
	 * 打开文件写入数据
	 * @param string $file 文件路径
	 * @param string $sql 内容
	 * @return bool
	 */
	private function write($file, $sql){
		$result = file_put_contents($file, $sql, LOCK_EX);
		if(!$result){
			$this->error = "文件 $file 写入失败";
			return false;
		}
		return true;
	}
	
	/**
	 * 将sql导入到数据库（普通导入）
	 * @param string $file
	 * @return boolean
	 */
	private function importSql($file){
		$fp  = fopen($file, "rb");
		$sql = '';
		$i   = 0;
		while(true){
			$sql .= fgets($fp);
			$i++;
			if(
				($i>1000 && preg_match('/.*;$/', trim($sql)))
				|| (feof($fp) && $sql)
			){
				$result = $this->db->exec($sql);
				if(false===$result){
					fclose($fp);
					$this->error = "{$file}还原失败";
					return false;
				}
				$sql = '';
				$i   = 0;
			}
			if(feof($fp)){
				break;
			}
		}
		fclose($fp);
		return true;
	}
	
}  