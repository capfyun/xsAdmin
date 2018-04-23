<?php
/**
 * 调试
 * @author 夏爽
 */
namespace app\admin\controller;

use Uploader\Uploader;

class Debug extends \app\common\controller\AdminBase{
	
	public function index(){
	}
	
	/**
	 * 测试
	 */
	public function test(){
		
		$a = 'http://img.7guoyouxi.com/file/2018-04-20/5ad9a133475a4.png';
		$b = 'resource/file/2018-04-21/5adaeaaed22b8.jpg';
		$c = 'http://img.7guoyouxi.com/file/2018-04-20/5ad9acce4f8e2.png';
		$image = 'resource\image\w200h50/2a5117050ceb6e224140485eeafd4d89.png';
		halt(getimagesize($c));
		
		
		$result = service('Image')->createThumb($c,200,50);
		halt([$result,service('Image')]);
		
		if(request()->isPost()){
			
			$param = $this->param([
				'nickname|昵称'          => ['require', 'length' => '6,16'],
				'gender|性别'            => ['require', 'number', 'between' => '1,10'],
				'age|年龄'               => ['number', 'between' => '0,100'],
				'old_password|密码'      => ['length' => '6,16'],
				'new_password|新密码'     => ['length' => '6,16'],
				'verify_password|重复密码' => ['length' => '6,16'],
			]);
			$param===false && $this->error($this->getError());
			
			halt($param);
		}
		
		return $this->fetch();
	}
	
	
	
}
