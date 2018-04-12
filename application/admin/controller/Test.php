<?php
/**
 * 调试
 * @author 夏爽
 */
namespace app\admin\controller;

use Uploader\Uploader;

class Test extends \app\common\controller\AdminBase{
	
	public function index(){
	}
	
	/**
	 * 测试
	 */
	public function test(){
		if(request()->isPost()){
			$uploader = new Uploader();
			
			halt($uploader->upload());
		}
		
		
		return $this->fetch();
	}
	
	
}
