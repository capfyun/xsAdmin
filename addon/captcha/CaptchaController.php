<?php
/**
 * 验证码
 * @author xs
 */
namespace app\admin\controller;

class Captcha extends \app\common\controller\AdminBase{
	
	/**
	 * 输出验证码
	 */
	public function image(){
		require_once __DIR__.'/library/Captcha.php';
		
		$class = model('Addon')->getClass('Captcha');
		
		$captcha = new \Captcha($class::config());
		$code    = $captcha->create();
		session('captcha', $code);
		$captcha->entry();
	}
	
}