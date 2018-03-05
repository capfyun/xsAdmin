<?php
/**
 * 控制器-公共
 * @author 夏爽
 */
namespace app\admin\controller;

class Open extends \app\common\controller\BaseAdmin{
	
	/**
	 * 后台用户登录
	 * @param string $username 用户名
	 * @param string $password 密码
	 */
	public function login($username = null, $password = null){
		if($this->request->isPost()){
			/* 登录 */
			$s_user  = service('User');
			$user_id = $s_user->login($username, $password);
			//失败
			if(!$user_id) return $this->ajaxReturn(['code' => 100, 'msg' => $s_user->getError()]);
			//成功
			return $this->ajaxReturn(['code' => 0, 'msg' => '登录成功！', 'data' => ['url' => url('index/index')]]);
		}
		/* 视图 */
		return $this->fetch();
	}
	
	/**
	 * 退出登录
	 */
	public function logout(){
		$s_user = service('User');
		if($s_user->isLogin()) $s_user->logout();
		return $this->ajaxReturn(['code' => 0, 'msg' => '退出成功！', 'data' => ['url' => url('login')]]);
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
			return $this->ajaxReturn(['code' => 1000, 'msg' => service('Thread')->getError()]);
		}
		return $this->ajaxReturn(['code' => 0, 'msg' => 'ok']);
	}
	
	
}