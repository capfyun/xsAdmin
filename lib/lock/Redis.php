<?php
/**
 * Redis驱动
 * @author xs
 * 要求安装phpredis扩展：https://github.com/nicolasff/phpredis
 */
namespace lib\lock;

class Redis extends Driver{
	protected $options = [
		'host'       => '127.0.0.1',
		'port'       => 6379,
		'password'   => '',
		'select'     => 0,
		'timeout'    => 0,
		'persistent' => false,
	];
	
	/**
	 * Redis实例
	 * @var null|\Redis
	 */
	protected $handler = null;
	
	/**
	 * 构造函数
	 * @param array $options 参数
	 */
	public function __construct($options = []){
		if(!extension_loaded('redis')){
			throw new \BadFunctionCallException('not support: redis');
		}
		if(!empty($options)){
			isset($options['expire']) && $this->expire = (int)$options['expire'];
			isset($options['prefix']) && $this->prefix = (string)$options['prefix'];
			$this->options = array_merge($this->options, $options);
		}
		$this->handler = new \Redis;
		if($this->options['persistent']){
			$this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_'.$this->options['select']);
		}else{
			$this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
		}
		
		if(''!=$this->options['password']){
			$this->handler->auth($this->options['password']);
		}
		
		if(0!=$this->options['select']){
			$this->handler->select($this->options['select']);
		}
	}
	
	/**
	 * 获得一个锁
	 * @param string $name 锁名
	 * @param integer|\DateTime $expire 有效时间（秒）
	 * @return boolean
	 */
	public function acquire($name, $expire = null){
		if($this->has($name)){
			return false;
		}
		is_null($expire) && $expire = $this->expire;
		($expire instanceof \DateTime) && $expire = $expire->getTimestamp()-time();
		$key = $this->getLockKey($name);
		return $expire ? $this->handler->setex($key, $expire, 1) : $this->handler->set($key, 1);
	}
	
	/**
	 * 释放锁
	 * @param string $name 锁名
	 * @return boolean
	 */
	public function release($name){
		$this->handler->delete($this->getLockKey($name));
		return true;
	}
	
	/**
	 * 判断缓存
	 * @param string $name 缓存变量名
	 * @return bool
	 */
	protected function has($name){
		return $this->handler->get($this->getLockKey($name)) ? true : false;
	}
}
