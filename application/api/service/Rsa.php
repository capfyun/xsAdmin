<?php
/**
 * 服务层-Rsa加密
 * @author 夏爽
 */
namespace app\api\service;

class Rsa extends \app\common\service\Base{
	
	/*
	$rsa = new Rsa(); //放项目的PHP目录
	$rsa->setPath("/var/www/sdk/opensslkey");
	$rsa->createKey();
	 
	//私钥加密，公钥解密
	echo 'source：Testing:Hello World!<br />';
	$pre = $rsa->privEncrypt('Testing:Hello World!');
	echo 'Private Encrypted:' . $pre . '<br />';
	 
	$pud = $rsa->pubDecrypt($pre);
	echo 'Public Decrypted:' . $pud . '<br />';
	 
	//公钥加密，私钥解密
	echo 'source:working in here!<br />';
	$pue = $rsa->pubEncrypt('working in here!');
	echo 'Public Encrypt:' . $pue . '<br />';
	 
	$prd = $rsa->privDecrypt($pue);
	echo 'Private Decrypt:' . $prd;
		
	}
	*/
	
	//private key
	private $_privKey;
	//public key
	private $_pubKey;
	//he keys saving path
	private $_keyPath;
	
	/**
	 * the construtor,the param $path is the keys saving path
	 */
	public function __construct(){
		parent::__construct();
		$this->setPath("/var/www/sdk/opensslkey");
	}
	
	public function setPath($path){
		if(empty($path) || !is_dir($path)){
			throw new \Exception('Must set the keys save path');
		}
		$this->_keyPath = $path;
	}
	
	/**
	 * create the key pair,save the key to $this->_keyPath
	 */
	public function createKey(){
		$r = openssl_pkey_new();
		openssl_pkey_export($r, $privKey);
		file_put_contents($this->_keyPath.DIRECTORY_SEPARATOR.'rsa_private_key.pem', $privKey);
		$this->_privKey = openssl_pkey_get_public($privKey);
		
		$rp     = openssl_pkey_get_details($r);
		$pubKey = $rp['key'];
		file_put_contents($this->_keyPath.DIRECTORY_SEPARATOR.'client_public_key.pem', $pubKey);
		$this->_pubKey = openssl_pkey_get_public($pubKey);
	}
	
	/**
	 * setup the private key
	 */
	public function setupPrivKey(){
		if(is_resource($this->_privKey)){
			return true;
		}
		$file           = $this->_keyPath.DIRECTORY_SEPARATOR.'rsa_private_key.pem';
		$prk            = file_get_contents($file);
		$this->_privKey = openssl_pkey_get_private($prk);
		return true;
	}
	
	/**
	 * setup the public key
	 */
	public function setupPubKey(){
		if(is_resource($this->_pubKey)){
			return true;
		}
		$file          = $this->_keyPath.DIRECTORY_SEPARATOR.'rsa_public_key.pem';
		$puk           = file_get_contents($file);
		$this->_pubKey = openssl_pkey_get_public($puk);
		return true;
	}
	
	/**
	 * encrypt with the private key
	 */
	public function privEncrypt($data){
		if(!is_string($data)){
			return null;
		}
		
		$this->setupPubKey();
		$r = openssl_public_encrypt($data, $encrypted, $this->_pubKey);
		if($r){
			return base64_encode($encrypted);
		}
		return null;
	}
	
	/**
	 * decrypt with the private key
	 */
	public function privDecrypt($encrypted){
		
		if(!is_string($encrypted)){
			return null;
		}
		$this->setupPrivKey();
		
		$encrypted = base64_decode($encrypted);
		
		$r = openssl_private_decrypt($encrypted, $decrypted, $this->_privKey);
		
		if($r){
			return $decrypted;
		}
		return null;
	}
	
	/**
	 * encrypt with public key
	 */
	public function pubEncrypt($data){
		if(!is_string($data)){
			return null;
		}
		
		$this->setupPubKey();
		
		$r = openssl_public_encrypt($data, $encrypted, $this->_pubKey);
		if($r){
			return base64_encode($encrypted);
		}
		return null;
	}
	
	/**
	 * decrypt with the public key
	 */
	public function pubDecrypt($crypted){
		if(!is_string($crypted)){
			return null;
		}
		
		$this->setupPubKey();
		
		$crypted = base64_decode($crypted);
		
		$r = openssl_public_decrypt($crypted, $decrypted, $this->_pubKey);
		if($r){
			return $decrypted;
		}
		return null;
	}
	
	public function __destruct(){
		@ fclose($this->_privKey);
		@ fclose($this->_pubKey);
	}
}
