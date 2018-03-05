<?php
/**
 * 服务层-Aes加密
 * @author 夏爽
 */
namespace app\api\service;

class Aes extends \app\common\service\Base{
	
	private $_secret_key = 'default_secret_key';
	
	public function setKey($key){
		$this->_secret_key = $key;
	}
	
	public function encode($data){
		$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
		$str  = $size-(strlen($data)%$size);
		$data = $data.str_repeat(chr($str), $str);
		$td   = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		$iv   = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, $this->_secret_key, $iv);
		$encrypted = mcrypt_generic($td, $data);
		mcrypt_generic_deinit($td);
		
		return $encrypted;
	}
	
	public function decode($data){
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		$iv = mb_substr($data, 0, mb_strlen($data, 'latin1'), 'latin1');
		mcrypt_generic_init($td, $this->_secret_key, $iv);
		$data = mb_substr($data, 0, mb_strlen($data, 'latin1'), 'latin1');
		$data = mdecrypt_generic($td, $data);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		
		return trim($data);
	}
	
	public function test(){
		
		echo "i am here aes!";
	}
	
}