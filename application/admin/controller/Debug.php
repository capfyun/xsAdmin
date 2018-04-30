<?php
/**
 * 调试
 * @author 夏爽
 */
namespace app\admin\controller;

use DatabaseBackup\DatabaseBackup;


class Debug extends \app\common\controller\AdminBase{
	
	public function index(){
	}
	
	/**
	 * 测试
	 */
	public function test(){
		
		if($this->request->isAjax()){
			db_debug('ajax request');
			return json('success');
		}
		
		
		halt('adsssd');
		
		$a = service('Tool')->parseName('abc_def',1,false);
		$b = service('Tool')->parseName('DbcTdef');
		
		halt([
			$a,
			$b
		]);
		
		
		$pid_file = "resource/image/abc";
		$fp = fopen($pid_file, 'w+');
		if(flock($fp, LOCK_EX | LOCK_NB)){
			db_debug('ok');
			echo "got the lock \n";
			sleep(5); // long running process
			flock($fp, LOCK_UN);  // 释放锁定
		} else {
			db_debug('no');
			echo "Cannot get pid lock. The process is already up \n";
		}
		fclose($fp);
		exit();
		
		$result = service('ExecLock')->open();
		if($result){
			echo '这是成功的';
			sleep(10);
		}else{
			
			echo '这是失败的';
		}
		
		
		service('ExecLock')->close();
		db_debug('close');
		
		exit();
		
		
		$lock = new \Yurun\Until\Lock\File('asd');
		
//		$lock->lock(); // 阻塞锁
//// TODO:在这里做你的一些事情
//		$lock->unlock(); // 解锁
//
//// 带回调的阻塞锁，防止并发锁处理重复执行
//		$result = $lock->lock(
//			function(){
//				// TODO:在这里做你的加锁后处理的任务
//
//			},
//			function(){
//				// 判断是否其它并发已经处理过任务
//				return false;
//			}
//		);
//		switch($result)
//		{
//			case \Yurun\Until\Lock\LockConst::LOCK_RESULT_CONCURRENT_COMPLETE:
//				// 其它请求已处理
//				break;
//			case \Yurun\Until\Lock\LockConst::LOCK_RESULT_CONCURRENT_UNTREATED:
//				// 在当前请求处理
//				break;
//			case \Yurun\Until\Lock\LockConst::LOCK_RESULT_FAIL:
//				// 获取锁失败
//				break;
//		}

// 不阻塞锁，获取锁失败就返回false
		if($lock->unblockLock())
		{
			db_debug('ok');
			// TODO:在这里做你的一些事情
		}
		else
		{
			db_debug('no');
			// 获取锁失败
		}
		
		sleep(10);
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
