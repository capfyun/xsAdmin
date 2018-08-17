<?php
/**
 * 请求
 * @author xs
 */
namespace lib;

class Curl{
	/**
	 * @var array 缓存的实例
	 */
	public static $instance = [];
	//连接资源句柄的信息
	private $curl_info = [];
	//错误信息
	private $curl_error = '';
	//错误编号
	private $curl_errno = 0;
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
	 * 初始化
	 */
	private function __construct($option = []){
		//Curl 设置参数
		foreach($option as $k => $v){
			$this->option[$k] = $v;
		}
	}
	
	/**
	 * 连接驱动
	 * @param array $option 配置数组
	 * @return static
	 */
	public static function instance(array $option = []){
		ksort($option);
		$name = md5(serialize($option));
		if(!isset(self::$instance[$name])){
			self::$instance[$name] = new static($option);
		}
		return self::$instance[$name];
	}
	
	/**
	 * 请求url
	 * @param string $url 请求地址
	 * @param array $body 传输内容
	 * @param string $method 传输方式
	 * @return string|false 失败返回false
	 */
	public function request($url, $body = [], $method = 'POST'){
		//初始化curl会话
		$ch = curl_init();
		
		curl_setopt_array($ch, $this->option);
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
		$this->curl_info  = curl_getinfo($ch);
		$this->curl_error = curl_error($ch);
		$this->curl_errno = curl_errno($ch);
		//关闭curl会话
		curl_close($ch);
		return $response;
	}
	
	/**
	 * 获取最后一次会话信息
	 * @return array
	 */
	public function getLastInfo(){
		return $this->curl_info;
	}
	
	public function getLastError(){
		return $this->curl_error;
	}
	
	public function getLastErrno(){
		return $this->curl_errno;
	}
	
	/**
	 * 判断远程地址是否可访问
	 * @param string $url 远程地址
	 * @return boolean
	 */
	public static function ping($url){
		$instance = self::instance([
			CURLOPT_RETURNTRANSFER => true, //文件流的形式返回，而不是直接输出
			CURLOPT_NOBODY         => true, //不取回数据
			CURLOPT_CONNECTTIMEOUT => 5, //最长等待时间
		]);
		$instance->request($url, [], 'GET');
		$info = $instance->getLastInfo();
		return isset($info['http_code']) && $info['http_code']==200 ? true : false;
	}
}