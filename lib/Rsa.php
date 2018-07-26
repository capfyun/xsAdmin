<?php
/**
 * RSA加密
 * @author xs
 */
namespace lib;

use lib\rsa\Publics;
use lib\rsa\Privates;

/**
 * Class Rsa
 * @method Publics publics(string $key) static
 * @method Privates privates(string $key) static
 */
class Rsa{
	/**
	 * @var array 实例
	 */
	public static $instance = [];
	
	/**
	 * 实例化
	 * @param string $type publics或privates
	 * @param string $key 密钥，可以是文件路径或密钥内容
	 * @return mixed
	 */
	public static function instance($type, $key){
		$name = md5($type.$key);
		if(!isset(self::$instance[$name])){
			$class = false===strpos($type, '\\')
				? '\\lib\\rsa\\'.ucwords($type)
				: $type;
			if(!class_exists($class)){
				throw new \Exception('class not exists:'.$class);
			}
			self::$instance[$name] = new $class($key);
		}
		return self::$instance[$name];
	}
	
	/**
	 * 调用类的方法
	 * @param string $method 方法名
	 * @param array $params 参数
	 * @return mixed
	 */
	public static function __callStatic($method, $params){
		array_unshift($params, $method);
		return call_user_func_array([self::class, 'instance'], $params);
	}
	
}

/**
 * 私钥
 * @author xs
 */
namespace lib\rsa;

class Privates{
	//私钥
	private $key = null;
	
	/**
	 * 构造
	 * @param string $key 密钥，可以是文件路径或密钥内容
	 */
	public function __construct($key){
		is_file($key) && $key = file_get_contents($key);
		$this->key = openssl_pkey_get_private($key);
	}
	
	/**
	 * 私钥加密
	 * @param string $string
	 * @return string
	 */
	public function encrypt($string){
		$return = '';
		foreach(str_split($string, 117) as $v){
			openssl_private_encrypt($v, $encrypted, $this->key);
			$return .= $encrypted;
		}
		return base64_encode($return);
	}
	
	/**
	 * 私钥解密
	 * @param string $string
	 * @return string
	 */
	public function decrypt($string){
		$string = base64_decode($string);
		$return = '';
		foreach(str_split($string, 128) as $v){
			openssl_private_decrypt($v, $decrypted, $this->key);
			$return .= $decrypted;
		}
		return $return;
	}
	
	/**
	 * 生成签名
	 * @param string $string
	 * @return string
	 */
	public function sign($string){
		//读取私钥文件
		openssl_sign($string, $sign, $this->key);
		return base64_encode($sign);
	}
	
	/**
	 * 生成公钥
	 * @return string
	 */
	public function createKey(){
		//生成私钥
		$resource = openssl_pkey_new();
		openssl_pkey_export($resource, $this->key);
		//生成公钥
		$detail = openssl_pkey_get_details($resource);
		return $detail['key'];
	}
	
	/**
	 * 析构
	 */
	public function __destruct(){
		@fclose($this->key);
	}
}

/**
 * 公钥
 * @author xs
 */
namespace lib\rsa;

class Publics{
	
	//密钥
	private $key = null;
	
	/**
	 * 构造
	 * @param string $key 密钥，可以是文件路径或密钥内容
	 */
	public function __construct($key){
		is_file($key) && $key = file_get_contents($key);
		$this->key = openssl_pkey_get_public($key);
	}
	
	/**
	 * 加密
	 * @param string $string
	 * @return string
	 */
	public function encrypt($string){
		$return = '';
		foreach(str_split($string, 117) as $v){
			openssl_public_encrypt($v, $encrypted, $this->key);
			$return .= $encrypted;
		}
		return base64_encode($return);
	}
	
	/**
	 * 解密
	 * @param string $string
	 * @return string
	 */
	public function decrypt($string){
		$string = base64_decode($string);
		$return = '';
		foreach(str_split($string, 128) as $v){
			openssl_public_decrypt($v, $decrypted, $this->key);
			$return .= $decrypted;
		}
		return $return;
	}
	
	/**
	 * 校验签名
	 * @param string $data
	 * @param string $sign
	 * @return bool
	 */
	public function verify($string, $sign){
		$sign = base64_decode($sign);
		return (bool)openssl_verify($string, $sign, $this->key);
	}
	
	/**
	 * 析构
	 */
	public function __destruct(){
		@fclose($this->key);
	}
}
