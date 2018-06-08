<?php
/**
 * RSA加密
 * @author xs
 */
namespace app\common\service;

class Rsa extends \app\common\service\Base{
	//私钥
	private $private_key = null;
	//公钥
	private $public_key = null;
	
	/**
	 * 构造
	 */
	public function __construct(){
		parent::__construct();
		//默认密钥
		$this->setPrivateKey(config('private_key'));
		$this->setPublicKey(config('public_key'));
	}
	
	/**
	 * 公钥加密
	 * @param string $string
	 * @return string
	 */
	public function publicEncrypt($string){
		$return = '';
		foreach(str_split($string, 117) as $v){
			openssl_public_encrypt($v, $encrypted, $this->public_key);
			$return .= $encrypted;
		}
		return base64_encode($return);
	}
	
	/**
	 * 公钥解密
	 * @param string $string
	 * @return string
	 */
	public function publicDecrypt($string){
		$string = base64_decode($string);
		$return = '';
		foreach(str_split($string, 128) as $v){
			openssl_public_decrypt($v, $decrypted, $this->public_key);
			$return .= $decrypted;
		}
		return $return;
	}
	
	/**
	 * 私钥加密
	 * @param string $string
	 * @return string
	 */
	public function privateEncrypt($string){
		$return = '';
		foreach(str_split($string, 117) as $v){
			openssl_private_encrypt($v, $encrypted, $this->private_key);
			$return .= $encrypted;
		}
		return base64_encode($return);
	}
	
	/**
	 * 私钥解密
	 * @param string $string
	 * @return string
	 */
	public function privateDecrypt($string){
		$string = base64_decode($string);
		$return = '';
		foreach(str_split($string, 128) as $v){
			openssl_private_decrypt($v, $decrypted, $this->private_key);
			$return .= $decrypted;
		}
		return $return;
	}
	
	/**
	 * 设置私钥
	 */
	public function setPrivateKey($key = ''){
		!is_file($key) || $key = file_get_contents($key);
		$this->private_key = openssl_pkey_get_private($key);
		return $this;
	}
	
	/**
	 * 设置公钥
	 */
	public function setPublicKey($key = ''){
		!is_file($key) || $key = file_get_contents($key);
		$this->public_key = openssl_pkey_get_public($key);
		return $this;
	}
	
	/**
	 * 生成私钥，并保存
	 */
	public function createKey(){
		//生成私钥
		$resource = openssl_pkey_new();
		openssl_pkey_export($resource, $this->private_key);
		//生成公钥
		$detail           = openssl_pkey_get_details($resource);
		$this->public_key = $detail['key'];
		//保存文件
	}
	
	/**
	 * 析构
	 */
	public function __destruct(){
		@ fclose($this->private_key);
		@ fclose($this->public_key);
	}
	
	/**
	 * 生成签名
	 * @param string $string
	 * @return string
	 */
	public function sign($string){
		//读取私钥文件
		openssl_sign($string, $sign, $this->private_key);
//		return $sign;
		return base64_encode($sign);
	}
	
	/**
	 * 校验签名
	 * @param string $data
	 * @param string $sign
	 * @return bool
	 */
	public function verify($string, $sign){
		$sign = base64_decode($sign);
		return (bool)openssl_verify($string, $sign, $this->public_key);
	}
	
}
