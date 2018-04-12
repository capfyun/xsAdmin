<?php
/**
 * 服务层-传输
 * @author 夏爽
 */
namespace app\common\service;

class Transmit extends Base{
	
	//上传配置
	protected $config = [
		//允许上传的文件后缀
		'exts' => 'jpg,jpeg,gif,png,bmp,apk,xapk,mp4,avi,mp3,swf,zip,rar,7z,txt,pdf,doc,dot,docx,wps,wpt,xls,xlt,xlsx,xlsm,et,ett',
		//上传的文件大小限制
		'size' => 1048576000, //1GB
	];
	//远程上传地址
	private $remote_url = 'http://transmit.7guoyouxi.com/upload';
	//文件保存路径
	private $error_info = [
		1 => '上传的文件大小超过了系统限制',
		2 => '文件大小超过了表单的限制值',
		3 => '文件只有部分被上传',
		4 => '没有文件被上传',
		5 => '上传文件大小为0',
		6 => '找不到临时文件夹',
		7 => '文件写入失败',
	];
	
	/**
	 * 初始化
	 * @param array|object $data
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 校验上传文件
	 */
	public function checkFile(){
		//上传文件是否存在
		if(empty($_FILES)){
			$this->error = '上传文件为空';
			return false;
		}
		//整理数据
		$files = $this->dealFiles($_FILES);
		foreach($files as $k => $v){
			//检查php内置错误
			if($v['error']!=0){
				$this->error = isset($this->error_info[$v['error']]) ? $this->error_info[$v['error']] : '未知错误';
				return false;
			}
			//检查文件大小
			if($this->config['size'] && $v['size']>$this->config['size']){
				$this->error = '文件大小超过限制';
				return false;
			}
			//校验后缀
			if($this->config['exts']){
				preg_match('/\.\w+$/U', $v['name'], $result);
				$ext = ltrim($result[0], '.');
				if(!in_array($ext,explode(',',$this->config['exts']))){
					$this->error = '禁止的文件类型';
					return false;
				}
			}
		}
		return true;
	}
	
	/**
	 * 远程上传文件
	 * @param array|resource $file 文件
	 */
	public function remote_upload($file_input = []){
		if(empty($_FILES) && !$file_input){
			return [];
		}
		$file_input = $file_input ? : $_FILES;
		//获取有效文件
		$files = $this->dealFiles($file_input);
		
		//并入请求数据
		$post = array();
		foreach($files as $k => $v){
			$post[$k] = new \CURLFile($v['tmp_name'], $v['type'], $v['name']);
		}
		//发送
		$result = service('Curl')->curl(
			$this->remote_url,
			$post,
			'POST',
			[CURLOPT_VERBOSE => false]
		);
		return $result ? json_decode($result, true) : [];
	}
	
	/**
	 * 转换上传文件数组变量为正确的方式
	 * @access private
	 * @param array $files 上传的文件变量
	 * @return array
	 */
	private function dealFiles($files){
		$file_array = [];
		foreach($files as $k => $v){
			if(is_array($v['name'])){
				$keys  = array_keys($v);
				$count = count($v['name']);
				for($i = 0; $i<$count; $i++){
					$file = ['key' => $k];
					foreach($keys as $_key){
						$file[$_key] = $v[$_key][$i];
					}
					$file_array[] = $file;
				}
			}else{
				$v['key']     = $k;
				$file_array[] = $v;
			}
		}
		return $file_array;
	}
	
}


