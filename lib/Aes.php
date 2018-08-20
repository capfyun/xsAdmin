<?php
/**
 * AES加密
 * @author xs
 */
namespace lib;

class Aes{
	/**
	 * @var array 实例
	 */
	private static $instance = [];
	/**
	 * 密钥，16位
	 * @var string
	 */
	private $key = '64617728a3150152';
	/**
	 * 加密向量
	 * @var string
	 */
	private $iv = '8105547186756005';
	
	/**
	 * 构造
	 */
	private function __construct(array $config){
		foreach($config as $k => $v){
			$this->$k = $v;
		}
	}
	
	/**
	 * 连接驱动
	 * @param array|string $config 配置
	 * @return static
	 */
	public static function instance($config = []){
		is_string($config) && $config = ['key' => $config];
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
		return openssl_encrypt($string, 'AES-128-CBC', $this->key, 0, $this->iv);
		//php-v5.6以下
		//bin2hex(@mcrypt_encrypt(MCRYPT_RIJNDAEL_128,$this->key,$string,MCRYPT_MODE_CBC,$this->iv));
	}
	
	/*
	 * 解密
	 * @param string $string 需要解密的字符串
	 * @return string
	 */
	public function decrypt($string){
		return openssl_decrypt($string, 'AES-128-CBC', $this->key, 0, $this->iv);
		//php-v5.6以下
		//@mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->key, pack("H*", $string), MCRYPT_MODE_CBC, $this->iv);
	}
	
}