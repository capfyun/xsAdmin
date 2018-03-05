<?php
/**
 * 服务层-请求
 * @author 夏爽
 */
namespace app\common\service;

class Curl extends Base{
	//连接资源句柄的信息
	public $info = [];
	//错误信息
	public $error = '';
	//错误编号
	public $errno = 0;
	//默认配置
	private $option = [
		CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_0, //强制使用 HTTP/1.0
		CURLOPT_USERAGENT      => 'toqi.net', //伪装浏览器
		CURLOPT_CONNECTTIMEOUT => 30, //最长等待时间
		CURLOPT_TIMEOUT        => 30, //执行的最长秒数
		CURLOPT_RETURNTRANSFER => true, //文件流的形式返回，而不是直接输出
		CURLOPT_ENCODING       => '',  //发送所有支持的编码类型
		CURLOPT_SSL_VERIFYPEER => false, //返回SSL证书验证请求的结果
		CURLOPT_HEADER         => false, //把头文件的信息作为数据流输出
		CURLOPT_HTTPHEADER     => [], //设置http头信息
		CURLINFO_HEADER_OUT    => true, //发送请求的字符串
	];
	
	/**
	 * 请求url
	 * @param string $url 请求地址
	 * @param array $body 传输内容
	 * @param string $method 传输方式
	 * @param array $option 配置参数
	 * @return string|false 失败返回false
	 */
	public function curl($url, $body = [], $method = 'POST', $option = []){
		//初始化curl会话
		$ch = curl_init();
		/* Curl 设置参数 */
		$option_merge = $this->option;
		foreach($option as $k => $v){
			$option_merge[$k] = $v;
		}
		curl_setopt_array($ch, $option_merge);
		//设置传输方式
		switch(strtoupper($method)){
			case 'POST':
				curl_setopt($ch, CURLOPT_URL, $url); //请求的url地址
				curl_setopt($ch, CURLOPT_POST, true); //post传输方式
				//传输内容
				if($body){
					curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
				}
				break;
			default:
				//传输内容
				if($body){
					$url = $url.'?'.str_replace('amp;', '', http_build_query($body));
				}
				curl_setopt($ch, CURLOPT_URL, $url); //请求的url地址
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method)); //请求传输方式
		}
		//执行会话
		$response = curl_exec($ch);
		//保存会话信息
		$this->requestInfo($ch);
		//关闭curl会话
		curl_close($ch);
		return $response;
	}
	
	/**
	 * 保存会话信息
	 */
	private function requestInfo($ch){
		$this->info  = curl_getinfo($ch);
		$this->error = curl_error($ch);
		$this->errno = curl_errno($ch);
	}
	
	/**
	 * 判断远程文件是否存在
	 * @param string $url 远程文件路径
	 * @return boolean 存在返回true
	 */
	public function fileExist($url){
		$this->curl($url, [], 'GET', [
			CURLOPT_RETURNTRANSFER => true, //文件流的形式返回，而不是直接输出
			CURLOPT_NOBODY         => true, //不取回数据
			CURLOPT_CONNECTTIMEOUT => 5, //最长等待时间
		]);
		return isset($this->info['http_code']) && $this->info['http_code']==200 ? true : false;
	}
	
	/**
	 * 下载文件并记录
	 * @param string $url 远程文件地址
	 * @param string $type 文件类型
	 * @return bool|string
	 */
	public function download($url, $type = 'file'){
		/* 下载文件 */
		$path = $this->downloadFile($url);
		if(!$path) return $path;
		/* 上传到接口 */
		$response = $this->uploadFile('.'.$path, $type);
		/* 删除临时图片 */
		@unlink('.'.$path);
		/* 处理结果 */
		$response = json_decode($response, true);
		if($response['code']!=0){
			$this->error = $response['msg'];
			return false;
		}
		return $response['data'];
	}
	
	/**
	 * 下载文件
	 * @param $url
	 * @param string $savePath
	 * @return bool|string
	 * @author Zou Yiliang
	 */
	public function downloadFile($url, $save_path = './static/upload/tmp/'){
		$response = $this->curl($url, [], 'GET', [
			CURLOPT_RETURNTRANSFER => true, //文件流的形式返回，而不是直接输出
			CURLOPT_NOBODY         => false, //不取回数据
			CURLOPT_HEADER         => true, //需要response header
		]);
		
		if($this->info['http_code']!=200){
			$this->error = '访问失败';
			return false;
		}
		
		//分离header与body
		$header = substr($response, 0, $this->info['header_size']);
		$body   = substr($response, $this->info['header_size']);
		
		//文件名
		$arr = [];
		if(!preg_match('/filename="(.*?)"/', $header, $arr)){
			$this->error = '文件不存在';
			return false;
		}
		$file_name = $arr[1]; //文件名
		$full_name = rtrim($save_path, '/').'/'.$file_name; //完整路径
		
		//创建目录并设置权限
		if(!file_exists($save_path)){
			@mkdir($save_path, 0777, true);
			@chmod($save_path, 0777);
		}
		
		if(!file_put_contents($full_name, $body)){
			$this->error = '写入失败';
			return false;
		}
		return ltrim($full_name, '.');
	}
	
	/**
	 * 上传到本地（通过接口）
	 * @param string $path 文件路径
	 * @param string $type 上传键名
	 * @return false|string
	 */
	public function uploadFile($path, $type = 'file'){
		if(!is_file(realpath($path))){
			$this->error = '文件不存在';
			return false;
		}
		$url = url('transmit/upload', '', false, true);
		\think\Session::boot();
		$result = $this->curl($url, [
			$type => new \CURLFile(realpath($path)),
		], 'POST', [
			CURLOPT_USERPWD        => 'joe:secret', //账号密码（按需配置）
			CURLOPT_HEADER         => false,
			CURLOPT_RETURNTRANSFER => true,
		]);
		return $result;
	}
	
}