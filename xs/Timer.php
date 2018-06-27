<?php
/**
 * 定时器
 * @author xs
 */
namespace xs;

class Timer{
	
	/**
	 * Timer Class
	 * 依赖swoole扩展 https://wiki.swoole.com/wiki/index/prid-1
	 */
	
	/**
	 * 注册定时器，keep run
	 * @param string $name 名称
	 * @param \Closure|null $callback 回调
	 * @param int $second 执行间隔秒
	 * @return bool
	 */
	public static function tick($name, \Closure $callback = null, $second = 60){
		//标记
		$result = self::set($name);
		if(!$result){
			return false;
		}
		//运行
		$timer_id = swoole_timer_tick($second*1000, function($timer_id) use ($name, $callback){
			//关闭定时器
			if(!self::has($name)){
				swoole_timer_clear($timer_id);
				return;
			}
			is_callable($callback) && $callback();
		});
	}
	
	/**
	 * 关闭定时器
	 * @param string $name 名称
	 * @return bool
	 */
	public static function  clear($name){
		return @unlink( self::get($name) );
	}
	
	/**
	 * 是否已存在标记
	 * @param string $name 名称
	 * @return bool
	 */
	private static function has($name){
		$file = self::get($name);
		return is_file($file);
	}
	
	/**
	 * 获取标记
	 * @param string $name 名称
	 * @return string
	 */
	private static function get($name){
		return RUNTIME_PATH.'timer'.DS.$name;
	}
	
	/**
	 * 设置标记
	 * @param string $name 名称
	 * @return bool
	 */
	private static function set($name){
		if(!$name){
			return false;
		}
		if(self::has($name)){
			return true;
		}
		$file = self::get($name);
		$dir  = dirname($file);
		if(!is_dir($dir) && !mkdir($dir, 0777, true)){
			return false;
		}
		$result   = file_put_contents($file, '');
		if(!$result){
			return false;
		}
		return true;
	}
}