<?php
/**
 * admin基类
 * @author xs
 */
namespace app\common\controller;


abstract class AdminBase extends Base{
	//当前用户ID
	protected $user_id = 0;
	
	/**
	 * 构造函数
	 */
	public function _initialize(){
		parent::_initialize();
		//当前用户ID
		$this->user_id = model('User')->isLogin();
	}
	
	/**
	 * 数据安全校验
	 * @param array $rule 预定义接口参数
	 * @return array|false
	 */
	protected function param($rule = [], $message = []){
		$param = $this->request->param();
		//校验数据
		$result = $this->validate($param, array_filter($rule), $message);
		if($result!==true){
			return $result;
		}
		$data = [];
		foreach($rule as $k => $v){
			list($key) = explode('|', $k);
			$data[$key] = isset($param[$key]) ? $param[$key] : null;
		}
		return $data;
	}
	
	/**
	 * 是否管理员用户
	 */
	protected function isAdministrator($user_id){
		return in_array($user_id, config('administrator_id'));
	}
	
	
	
}
