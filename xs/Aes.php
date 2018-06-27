<?php
/**
 * AES加密
 * @author xs
 */
namespace xs;

class Aes{
	/**
	 * @var array 实例
	 */
	public static $instance = [];
	/**
	 * 默认配置
	 * @var array
	 */
	private static $default_config = [
		'cipher' => MCRYPT_RIJNDAEL_128, //加密方式
		'key'    => '64617728a3150152', //密钥，16位
		'iv'     => '8105547186756005', //加密向量
	];
	/**
	 * 配置
	 * @var array
	 */
	private $config = [];
	
	/**
	 * 构造
	 */
	private function __construct($config){
		$this->config = $config;
	}
	
	/**
	 * 连接驱动
	 * @param array $config 配置
	 * @return static
	 */
	public static function instance(array $config = []){
		is_string($config) && $config = ['key' => $config];
		$config = array_merge(self::$default_config, $config);
		ksort($config);
		$name = md5(serialize($config));
		if(!isset(self::$instance[$name])){
			self::$instance[$name] = new static($config);
		}
		return self::$instance[$name];
	}
	
	/**
	 * 加密
	 * @param string $string 需要加密的字符串
	 * @return string
	 */
	public function encrypt($string){
		return bin2hex(@mcrypt_encrypt(
			$this->config['cipher'],
			$this->config['key'],
			$string,
			MCRYPT_MODE_CBC,
			$this->config['iv']
		));
	}
	
	/*
	 * 解密
	 * @param string $string 需要解密的字符串
	 * @return string
	 */
	public function decrypt($string){
		return @mcrypt_decrypt(
			$this->config['cipher'],
			$this->config['key'],
			pack("H*", $string),
			MCRYPT_MODE_CBC,
			$this->config['iv']
		);
	}
	
}