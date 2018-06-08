<?php
/**
 * 调试
 * @author xs
 */
namespace app\admin\controller;



use think\Validate;
use xs\auth\Auth;
use xs\Rsa;
use xs\Upload;

class Debug extends \app\common\controller\AdminBase{
	
	public function index(){
	}
	
	/**
	 * 测试
	 */
	public function test(){
		
		$a = Rsa::publics('../openssl/rsa_public_key.pem')->encrypt('asdasd');
		
		halt($a);
		
		return $this->fetch();
	}
	
}
