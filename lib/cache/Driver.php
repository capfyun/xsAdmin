<?php
/**
 * 缓存基础类
 */
namespace lib\cache;

abstract class Driver{
	
	/**
	 * 对象句柄
	 * @var object
	 */
	protected $handler = null;
	/**
	 * 锁有效时间，0永远有效
	 * @var int
	 */
	protected $expire = 0;
	/**
	 * 锁名前缀
	 * @var string
	 */
	protected $prefix = '';
	
	/**
	 * 判断缓存是否存在
	 * @access public
	 * @param string $name 缓存变量名
	 * @return bool
	 */
	public function has($name){
		return $this->get($name) ? true : false;
	}
	
	/**
	 * 读取缓存
	 * @access public
	 * @param string $name 缓存变量名
	 * @param mixed $default 默认值
	 * @return mixed
	 */
	abstract public function get($name, $default = false);
	
	/**
	 * 写入缓存
	 * @access public
	 * @param string $name 缓存变量名
	 * @param mixed $value 存储数据
	 * @param int $expire 有效时间 0为永久
	 * @return boolean
	 */
	abstract public function set($name, $value, $expire = null);
	
	/**
	 * 删除缓存
	 * @access public
	 * @param string $name 缓存变量名
	 * @return boolean
	 */
	abstract public function rm($name);
	
	/**
	 * 清除缓存
	 * @access public
	 * @return boolean
	 */
	abstract public function clear();
	
	/**
	 * 获取实际的缓存标识
	 * @access public
	 * @param string $name 缓存名
	 * @return string
	 */
	protected function getCacheKey($name){
		return $this->prefix.$name;
	}
	
	/**
	 * 返回句柄对象，可执行其它高级方法
	 * @access public
	 * @return object
	 */
	public function handler(){
		return $this->handler;
	}
}
