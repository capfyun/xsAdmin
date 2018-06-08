<?php
/**
 * 锁驱动
 * @author xs
 */
namespace xs\lock;

abstract class Driver{
	/**
	 * 锁有效时间，0永远有效
	 * @var int
	 */
	protected $expire = 0;
	/**
	 * 锁名前缀
	 * @var string
	 */
	protected $prefix = 'lock_';
	
	/**
	 * 获得一个锁
	 * @param string $name 锁名
	 * @param integer|\DateTime $expire 有效时间（秒）
	 * @return boolean
	 */
	abstract public function acquire($name, $expire = null);
	
	/**
	 * 释放锁
	 * @param string $name 锁名
	 * @return boolean
	 */
	abstract public function release($name);
	
	/**
	 * 取得锁键
	 * @param string $name 锁名
	 * @return string 文件地址
	 */
	protected function getLockKey($name){
		return $this->prefix.$name;
	}
}