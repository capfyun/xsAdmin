<?php
/**
 * 程序锁（非阻塞锁）
 * @author xs
 */
namespace xs;

use xs\lock\Driver;

class Lock{
	/**
	 * @var array 实例
	 */
	public static $instance = [];
	
	/**
	 * @var object 操作句柄
	 */
	public static $handler;
	
	/**
	 * 构造
	 */
	private function __construct(){}
	
	/**
	 * 连接驱动
	 * @param array $options 配置数组
	 * @param bool|string $name 连接标识 true 强制重新连接
	 * @return Driver
	 */
	public static function connect(array $options = [], $name = false){
		$type = !empty($options['type']) ? $options['type'] : 'File';
		if(false===$name){
			ksort($options);
			$name = md5(serialize($options));
		}
		if(true===$name || !isset(self::$instance[$name])){
			$class = false===strpos($type, '\\')
				? '\\xs\\lock\\'.ucwords($type)
				: $type;
			if(!class_exists($class)){
				throw new \Exception('class not exists:'.$class);
			}
			if(true===$name){
				return new $class($options);
			}
			self::$instance[$name] = new $class($options);
		}
		return self::$instance[$name];
	}
	
	/**
	 * 初始化
	 * @param array $options 配置数组
	 * @return Driver
	 */
	public static function init(array $options = []){
		is_null(self::$handler) && self::$handler = self::connect($options);
		return self::$handler;
	}
	
	/**
	 * 开启锁
	 * @param string $name 锁名称
	 * @param int $time 时长（秒），0为永久
	 * @return bool
	 */
	public static function acquire($name, $time = 10){
		return self::init()->acquire($name, $time);
	}
	
	/**
	 * 关闭锁
	 * @param string $name 锁名称
	 * @return bool
	 */
	public static function release($name){
		return self::init()->release($name);
	}
}
