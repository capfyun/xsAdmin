<?php
/**
 * curl上传
 * @author xs
 */
namespace xs\upload;

class Curl extends Driver{
	/**
	 * 配置
	 * @var array
	 */
	private $config = array(
		'url' => '', //远程地址
	);
	
	/**
	 * 构造
	 * @param array $config 配置
	 */
	public function __construct(array $config = array()){
		//配置
		$this->config = array_merge($this->config, $config);
	}
	
	/**
	 * 保存指定文件
	 * @param array $file 保存的文件信息
	 * @param boolean $replace 同名文件是否覆盖
	 * @return boolean 保存状态，true-成功，false-失败
	 */
	public function save($file, $replace = true){
		//并入请求数据
		$body = array(
			//文件名
			'file'       => $file['save_path'].$file['save_name'],
			$file['key'] => new \CURLFile($file['tmp_name'], $file['type'], $file['name']),
		);
		
		//初始化curl会话
		$ch = curl_init();
		//Curl 设置参数
		curl_setopt_array($ch, [
			CURLOPT_URL            => $this->config['url'],
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $body,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_0, //强制使用 HTTP/1.0
			CURLOPT_USERAGENT      => 'toqi.net', //伪装浏览器
			CURLOPT_CONNECTTIMEOUT => 30, //最长等待时间
			CURLOPT_TIMEOUT        => 120, //执行的最长秒数
			CURLOPT_RETURNTRANSFER => true, //文件流的形式返回，而不是直接输出
			CURLOPT_ENCODING       => '',  //发送所有支持的编码类型
			CURLOPT_SSL_VERIFYPEER => false, //返回SSL证书验证请求的结果
			CURLOPT_HEADER         => false, //把头文件的信息作为数据流输出
			CURLOPT_HTTPHEADER     => [], //设置http头信息
			CURLINFO_HEADER_OUT    => true, //发送请求的字符串
			CURLOPT_VERBOSE        => false,
		]);
		//执行会话
		$response = curl_exec($ch);
		//关闭curl会话
		curl_close($ch);
		
		$result = $response ? json_decode($response, true) : [];
		return $result ? true : false;
	}
	
}