<?php
/**
 * AES加密
 * @author xs
 */
namespace app\common\service;

class Aes extends \app\common\service\Base{
	
	//密钥，16位
	private $secret_key = '64617728a3150152';
	//加密向量
	private $iv = '8105547186756005';
	//加密方式
	private $cipher_alg = MCRYPT_RIJNDAEL_128;
	
	/**
	 * 构造
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * 实现AES加密
	 * $str : 要加密的字符串
	 * $keys : 加密密钥
	 * $iv : 加密向量
	 * $cipher_alg : 加密方式
	 */
	public function encrypt($string = ''){
		return bin2hex(@mcrypt_encrypt(
			$this->cipher_alg,
			$this->secret_key,
			$string,
			MCRYPT_MODE_CBC,
			$this->iv
		));
	}
	
	/*
	 * 实现AES解密
	 * $str : 要解密的字符串
	 * $keys : 加密密钥
	 * $iv : 加密向量
	 * $cipher_alg : 加密方式
	 */
	public function decrypt($string = ''){
		return @mcrypt_decrypt(
			$this->cipher_alg,
			$this->secret_key,
			pack("H*", $string),
			MCRYPT_MODE_CBC,
			$this->iv
		);
	}
	
	/**
	 * 设置key
	 * @param string $key
	 */
	public function setKey($key = ''){
		$this->secret_key = $key;
		return $this;
	}
	
}