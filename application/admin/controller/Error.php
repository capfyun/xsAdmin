<?php
/**
 * 空控制器
 * @author xs
 */
namespace app\admin\controller;

class Error extends \app\common\controller\AdminBase{
	
	/**
	 * 404错误
	 */
	public function e404(){
		return $this->fetch('layout/404',input());
	}
	
	
}

