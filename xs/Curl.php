<?php
/**
 * 请求
 * @author xs
 */
namespace xs;

class Curl {
	//连接资源句柄的信息
	private static $curl_info = [];
	//错误信息
	private static $curl_error = '';
	//错误编号
	private static $curl_errno = 0;
	//默认配置
	private static $option = [
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
	public static function request($url, $body = [], $method = 'POST', $option = []){
		//初始化curl会话
		$ch = curl_init();
		//Curl 设置参数
		$option_merge = self::$option;
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
		self::$curl_info  = curl_getinfo($ch);
		self::$curl_error = curl_error($ch);
		self::$curl_errno = curl_errno($ch);
		//关闭curl会话
		curl_close($ch);
		return $response;
	}
	
	/**
	 * 获取最后一次会话信息
	 * @return array
	 */
	public static function getLastInfo(){
		return self::$curl_error;
	}
	public static function getLastError(){
		return self::$curl_error;
	}
	public static function getLastErrno(){
		return self::$curl_errno;
	}
	
	/**
	 * 判断远程地址是否可访问
	 * @param string $url 远程地址
	 * @return boolean
	 */
	public static function ping($url){
		self::request($url, [], 'GET', [
			CURLOPT_RETURNTRANSFER => true, //文件流的形式返回，而不是直接输出
			CURLOPT_NOBODY         => true, //不取回数据
			CURLOPT_CONNECTTIMEOUT => 5, //最长等待时间
		]);
		return isset(self::$curl_info['http_code']) && self::$curl_info['http_code']==200 ? true : false;
	}
}