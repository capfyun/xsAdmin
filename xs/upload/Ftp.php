<?php
/**
 * ftp
 */
namespace xs\upload;

class Ftp extends Driver{
	/**
	 * 上传文件根目录
	 * @var string
	 */
	private $root_path;
	
	/**
	 * FTP连接
	 * @var resource
	 */
	private $link;
	
	private $config = array(
		'host'     => '', //服务器
		'port'     => 21, //端口
		'timeout'  => 90, //超时时间
		'username' => '', //用户名
		'password' => '', //密码
	);
	
	/**
	 * 构造函数，用于设置上传根路径
	 * @param array $config FTP配置
	 */
	public function __construct($config = array()){
		//配置
		$this->config = array_merge($this->config, $config);
		
		//登录FTP服务器
		if(!$this->login()){
			throw new \Exception($this->error);
		}
		//根目录
		$this->root_path = ftp_pwd($this->link).'/';
	}
	
	/**
	 * 检测上传目录
	 * @param  string $savepath 上传目录
	 * @return boolean          检测结果，true-通过，false-失败
	 */
	public function checkSavePath($save_path){
		//检测并创建目录
		if(!$this->mkdir($save_path)){
			return false;
		}else{
			//TODO:检测目录是否可写
			return true;
		}
	}
	
	/**
	 * 保存指定文件
	 * @param array $file 保存的文件信息
	 * @param boolean $replace 同名文件是否覆盖
	 * @return boolean 保存状态，true-成功，false-失败
	 */
	public function save($file, $replace = true){
		$file_name = $this->root_path.$file['save_path'].$file['save_name'];
		
		/* 不覆盖同名文件 */
		// if (!$replace && is_file($file_name)) {
		//     $this->error = '存在同名文件' . $file['save_name'];
		//     return false;
		// }
		
		/* 移动文件 */
		if(!ftp_put($this->link, $file_name, $file['tmp_name'], FTP_BINARY)){
			$this->error = '文件上传保存错误！';
			return false;
		}
		return true;
	}
	
	/**
	 * 创建目录
	 * @param  string $save_path 要创建的目录
	 * @return boolean          创建状态，true-成功，false-失败
	 */
	public function mkdir($save_path){
		$dir = $this->root_path.$save_path;
		if(ftp_chdir($this->link, $dir)){
			return true;
		}
		
		if(ftp_mkdir($this->link, $dir)){
			return true;
		}elseif($this->mkdir(dirname($save_path)) && ftp_mkdir($this->link, $dir)){
			return true;
		}else{
			$this->error = "目录 {$save_path} 创建失败！";
			return false;
		}
	}
	
	/**
	 * 登录到FTP服务器
	 * @return boolean true-登录成功，false-登录失败
	 */
	private function login(){
		extract($this->config);
		$this->link = ftp_connect($host, $port, $timeout);
		if($this->link){
			if(ftp_login($this->link, $username, $password)){
				return true;
			}else{
				$this->error = "无法登录到FTP服务器：username - {$username}";
			}
		}else{
			$this->error = "无法连接到FTP服务器：{$host}";
		}
		return false;
	}
	
	/**
	 * 析构方法，用于断开当前FTP连接
	 */
	public function __destruct(){
		ftp_close($this->link);
	}
	
}
