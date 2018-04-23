<?php
/**
 * 空控制器
 * @author 夏爽
 */
namespace app\admin\controller;

class Error extends \app\common\controller\AdminBase{
	
	/**
	 * 默认
	 */
	public function index(){
		$this->_empty();
	}
	
	/**
	 * 空操作
	 */
	public function _empty(){
		abort(404,'error');
	}
	
}

