<?php
/**
 * AES加密
 * @author xs
 */
namespace xs;

class Aes{
	//配置
	private static $config = [
		'secret_key' => '64617728a3150152', //密钥，16位
		'iv'         => '8105547186756005', //加密向量
		'cipher_alg' => MCRYPT_RIJNDAEL_128, //加密方式
	];
	
	
	/**
	 * 配置
	 * @param array $config
	 */
	public static function setConfig($config = []){
		self::$config = array_merge(self::$config, array_change_key_case($config, CASE_LOWER));
	}
	
	/**
	 * 加密
	 * @param string $string 需要加密的字符串
	 * @return string
	 */
	public static function encrypt($string = ''){
		return bin2hex(@mcrypt_encrypt(
			self::$config['cipher_alg'],
			self::$config['secret_key'],
			$string,
			MCRYPT_MODE_CBC,
			self::$config['iv']
		));
	}
	
	/*
	 * 解密
	 * @param string $string 需要解密的字符串
	 * @return string
	 */
	public static function decrypt($string = ''){
		return @mcrypt_decrypt(
			self::$config['cipher_alg'],
			self::$config['secret_key'],
			pack("H*", $string),
			MCRYPT_MODE_CBC,
			self::$config['iv']
		);
	}
	
}