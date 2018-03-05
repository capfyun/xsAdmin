<?php
/**
 * 服务层-程序锁
 * @author 夏爽
 */
namespace app\common\service;

class ExecLock extends Base{
	//前缀
	protected $prefix = '';
	//最大锁定时间（秒）
	protected $max_time = 10;
	
	/**
	 * 初始化
	 */
	public function __construct(){
		parent::__construct();
		//前缀
		$this->prefix = config('app_env').'_execlock_';
	}
	
	/**
	 * 设置锁时长
	 * @param int $time 时长（秒），0为永久
	 * @return $this
	 */
	public function setTime($time = 10){
		$this->max_time = $time;
		return $this;
	}
	
	/**
	 * 开启锁
	 * @param string $tag 锁标签
	 * @return bool
	 */
	public function open($tag = ''){
		//生成锁名
		$name = $this->getName($tag);
		//校验是否已锁
		if(cache($name) == 1){
			$this->error = '已锁';
			return false;
		}
		//上锁
		$result = cache($name, 1, $this->max_time);
		if(!$result){
			$this->error = '系统错误';
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
		//关闭
		$result = cache($name,null);
		if(!$result){
			$this->error = '系统错误';
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
		//生成名称
		$name = $this->prefix.
			request()->module().
			request()->controller().
			request()->action().
			$tag;
		return strtolower($name);
	}
}
