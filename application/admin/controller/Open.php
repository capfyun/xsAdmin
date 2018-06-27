<?php
/**
 * 开放
 * @author xs
 */
namespace app\admin\controller;

class Open extends \app\common\controller\AdminBase{
	
	public function login1(){
		return $this->fetch();
	}
	
	/**
	 * 后台用户登录
	 */
	public function login(){
		if(!$this->request->isPost()){
			//视图
			return $this->fetch();
		}
		$param = $this->param([
			'username|用户名'  => ['require', 'length' => '2,16'],
			'password|密码'   => ['require', 'length' => '6,16'],
			'remember|记住帐号' => ['number', 'between' => '0,1'],
		]);
		$param===false && $this->apiReturn(['code' => 1000, 'msg' => $this->getError()]);
		
		//登录
		$user_id = model('User')->login($param['username'], 'username');
		!$user_id && $this->apiReturn(['code' => 1000, 'msg' => model('User')->getError()]);
		//校验密码
		$result = model('User')->checkPassword($user_id, $param['password']);
		!$result && $this->apiReturn(['code' => 1000, 'msg' => model('User')->getError()]);
		
		//成功，后置操作
		model('User')->loginUpdate($user_id);
		model('User')->loginAfter($user_id);
		//记住帐号
		if($param['remember']){
			model('User')->loginAfterCookie($user_id);
		}
		
		$this->apiReturn(['code' => 0, 'msg' => '登录成功！', 'data' => ['url' => url('index/index')]]);
	}
	
	/**
	 * 退出登录
	 */
	public function logout(){
		model('User')->logout();
		$this->redirect('login');
	}
	
	/**
	 * 验证码
	 */
	public function verify(){
//		$verify = new \Think\Verify();
//		$verify->entry(1);
	}
	
	/**
	 * 下载
	 */
	public function download($id = 0){
		$result = model('File')->download($id);
		$result || abort(404, model('File')->getError());
	}
	
	/**
	 * 图片浏览
	 */
	public function image($i = 0, $w = 0, $h = 0){
		$i || service('Image')->output(config('default_image'), 'image/png');
		
		//缓存
		$cache_time = 7*24*60*60;
		$pass_mtime = gmdate('D, d M Y H:i:s', time()+$cache_time).' GMT';
		$etag       = md5("image_i{$i}w{$w}h{$h}");
		
		//http 304缓存
		if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH']==$etag){
			return response('', 304)
				->cacheControl('');
		}
		dbDebug('imageThumb');
		//查询数据库
		$file = model('File')->get($i);
		(!$file || strpos($file['type'], 'image/')!==0) && service('Image')->output(config('default_image'), 'image/png');
		$image = trim(model('File')->url($i), '/');
		
		//缩略图
		if($w && $h){
			$image = service('Image')->createThumb($image, $w, $h);
			$image || service('Image')->output(config('default_image'), 'image/png');
		}
		
		header('Pragma: cache');
		header('Cache-Control: max-age='.$cache_time);
		header('Expires: '.$pass_mtime);
		header('Etag: '.$etag);
		//打印图片
		return service('Image')->output($image, $file['type']);
	}
	
}