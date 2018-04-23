<?php
/**
 * 开放
 * @author 夏爽
 */
namespace app\admin\controller;

class Open extends \app\common\controller\AdminBase{
	
	/**
	 * 后台用户登录
	 */
	public function login(){
		if(!$this->request->isPost()){
			//视图
			return $this->fetch();
		}
		$param = $this->param([
			'username|用户名' => ['require', 'length' => '2,16'],
			'password|密码'  => ['require', 'length' => '6,16'],
		]);
		if($param===false){
			return json(['code' => 1000, 'msg' => $param]);
		}
		//登录
		$user_id = model('User')->login($param['username'], 'username');
		if(!$user_id){
			return json(['code' => 1000, 'msg' => model('User')->getError()]);
		}
		//校验密码
		$result = model('User')->checkPassword($user_id, $param['password']);
		if(!$result){
			return json(['code' => 1000, 'msg' => model('User')->getError()]);
		}
		//成功，记入session
		model('User')->loginAfter($user_id);
		model('User')->loginUpdate($user_id);
		return json(['code' => 0, 'msg' => '登录成功！', 'data' => ['url' => url('index/index')]]);
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
		$i || service('Image')->printi(config('default_image'),'image/png');
		$name = "image_i{$i}w{$w}h{$h}";
		$etag = cache($name);
		
		//http 304缓存
		if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH']==$etag){
			header("HTTP/1.1 304 Not Modified");
			exit;
		}
		//查询数据库
		$file = model('File')->get($i);
		(!$file || strpos($file['type'], 'image/')!==0) && service('Image')->printi(config('default_image'),'image/png');
		
		$image = model('File')->url($i);
		$image = trim($image, '/');
		
		//缩略图
		if($w && $h){
			$image = service('Image')->createThumb($image, $w, $h);
			$image || service('Image')->printi(config('default_image'),'image/png');
		}
		
		//缓存
		$cache_time = 7*24*60*60;
		$pass_mtime = gmdate('D, d M Y H:i:s', time()+$cache_time).' GMT';
		cache($name, md5($name), $cache_time, 'image');
		header('Pragma: cache');
		header('Cache-Control: max-age='.$cache_time);
		header('Expires: '.$pass_mtime);
		header("Etag: ".$etag);
		//打印图片
		service('Image')->printi($image,$file['type']);
	}
	
}