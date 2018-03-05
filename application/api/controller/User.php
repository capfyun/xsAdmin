<?php
/**
 * 控制器-用户
 * @author 夏爽
 */
namespace app\api\controller;

class User extends \app\common\controller\BaseApi{
	
	/**
	 * 用户登录
	 */
	public function login(){
		$param = $this->param([
			['username|用户名', ['require', 'length' => '6,16', 'boolean'], ['用户名必填', '哈哈用户名长度为6~16位'], '用户名爱爱爱'],
			['password|密码', ['require', 'length' => '6,16']],
		]);
		
		$data = $param;
		
		return $this->apiReturn(['code' => 0, 'msg' => '登陆成功!', 'data' => $data]);
	}
	
	/**
	 * 用户注册
	 */
	public function register(){
		$param = $this->param([
			'username' => ['type' => 'int', 'require' => false, 'name' => '用户名', 'default' => -123123, 'enum' => ['1', '2', '3']],
			'password' => ['type' => 'int', 'require' => true, 'name' => '密码', 'enum' => ['1', '2', '3'], 'unsigned' => true],
		]);
		
		halt($param);
		$data = $param;
		
		return $this->apiReturn(['code' => 0, 'msg' => '登陆成功!', 'data' => $data]);
	}
	
}



