<?php
/**
 * 控制器-公共
 * @author 夏爽
 */
namespace app\admin\controller;

class Open extends \app\common\controller\AdminBase{
	
	/**
	 * 后台用户登录
	 * @param string $username 用户名
	 * @param string $password 密码
	 */
	public function login($username = null, $password = null){
		if($this->request->isPost()){
			//登录
			$user_id = model('User')->login($username, 'username');
			if(!$user_id){
				return json(['code' => 1000, 'msg' => model('User')->getError()]);
			}
			//校验密码
			$result = model('User')->checkPassword($user_id,$password);
			if(!$result){
				return json(['code' => 1000, 'msg' => model('User')->getError()]);
			}
			//成功，记入session
			model('User')->loginSession($user_id);
			model('User')->loginUpdate($user_id);
			return json(['code' => 0, 'msg' => '登录成功！', 'data' => ['url' => url('index/index')]]);
		}
		/* 视图 */
		return $this->fetch();
	}
	
	/**
	 * 退出登录
	 */
	public function logout(){
		!model('User')->isLogin() || model('User')->logout();
		return json(['code' => 0, 'msg' => '退出成功！', 'data' => ['url' => url('login')]]);
	}
	
	/**
	 * 验证码
	 */
	public function verify(){
//		$verify = new \Think\Verify();
//		$verify->entry(1);
	}
	
	/**
	 * 多线程入口
	 */
	public function thread(){
		//执行多线程任务
		$result = service('Thread')->portal();
		if(!$result){
			return json(['code' => 1000, 'msg' => service('Thread')->getError()]);
		}
		return json(['code' => 0, 'msg' => 'ok']);
	}
	
	
}