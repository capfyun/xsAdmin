<?php
/**
 * 首页
 * @author 夏爽
 */
namespace app\admin\controller;

class Index extends \app\common\controller\AdminBase{
	public function index(){
		/* 视图 */
		return $this->fetch('', ['data' => config('api_model'), '']);
	}
}
