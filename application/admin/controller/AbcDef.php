<?php
/**
 * 调试
 * @author 夏爽
 */
namespace app\admin\controller;


class AbcDef extends \app\common\controller\AdminBase{
	
	public function index(){
	}
	
	/**
	 * 测试
	 */
	public function test(){
		
		halt('AbcDef');
		
		return $this->fetch();
	}
	
}
