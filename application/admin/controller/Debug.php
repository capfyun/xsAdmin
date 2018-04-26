<?php
/**
 * 调试
 * @author 夏爽
 */
namespace app\admin\controller;

use DatabaseBackup\DatabaseBackup;
use Uploader\Uploader;

class Debug extends \app\common\controller\AdminBase{
	
	public function index(){
	}
	
	/**
	 * 测试
	 */
	public function test(){
		$result = service('ExecLock')->open();
		db_debug('open',$result);
		
		sleep(10);
		
		service('ExecLock')->close();
		db_debug('close');
		
		exit();
		
		
		
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
