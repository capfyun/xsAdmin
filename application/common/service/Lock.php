<?php
/**
 * 程序锁
 * @author xs
 */
namespace app\common\service;

use think\Request;

class Lock extends Base{
	
	/**
	 * 开启锁
	 * @param string $tag 锁标签
	 * @param int $time 时长（秒），0为永久
	 * @return bool
	 */
	public function open($tag = '', $time = 10){
		//生成锁名
		$name = $this->getName($tag);
		//获取一个锁
		$result = \lib\Lock::connect()->acquire($name, $time);
		if(!$result){
			$this->error = '上锁失败';
			return false;
		}
		return true;
	}
	
	/**
	 * 关闭锁
	 * @param string $tag 锁标签
	 * @return bool
	 */
	public function close($tag = ''){
		//生成锁名
		$name = $this->getName($tag);
		//关闭锁
		$result = \lib\Lock::connect()->release($name);
		if(!$result){
			$this->error = '锁关闭失败';
			return false;
		}
		return true;
	}
	
	/**
	 * 生成名称
	 * @param string $tag
	 * @return string
	 */
	protected function getName($tag = ''){
		$request = Request::instance();
		return strtolower(
			config('app_env')
			.$request->module()
			.$request->controller()
			.$request->action()
			.$tag
		);
	}
}
