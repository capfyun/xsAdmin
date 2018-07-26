<?php
/**
 * 插件
 * @author xs
 */
namespace lib;

use think\Cache;
use think\Db;

class Addon {
	
	/**
	 * 获取并缓存配置
	 * @param bool $force 是否强制初始化
	 */
	public static function load($force = false){
		//缓存
		$addon = Cache::get('db_addon_data');
		//重置缓存
		if(!$addon || $force){
			$addon = Db::name('addon')->where(['status' => 1])->order('sort DESC')->select();
			Cache::set('db_addon_data', $addon);
		}
	}
	
	/**
	 * 挂载插件
	 */
	public static function mount(){
		$addon = Cache::get('db_addon_data');
		if(!$addon || !is_array($addon)){
			return false;
		}
		//注册插件
		foreach($addon as $k => $v){
			$class = self::getClass($v['name']);
			if(!class_exists($class))
				throw new \Exception("插件类不存在：{$class}");
			$class::register();
		}
		return true;
	}
	
	/**
	 * 获取插件类的类名
	 * @param string $name 插件名
	 * @return string
	 */
	public static function getClass($name){
		$class = 'addon\\'.self::convertHump($name).'\\'.self::convertHump($name,1);
		return $class;
	}
	
	
	/**
	 * 字符串命名风格转换
	 * @param  string $name 字符串
	 * @param  integer $type 转换类型 type 0 将 Java 风格转换为 C 的风格 1 将 C 风格转换为 Java 的风格
	 * @param  bool $ucfirst 首字母是否大写（驼峰规则）
	 * @return string
	 */
	private static function convertHump($name, $type = 0, $ucfirst = true){
		if($type){
			$name = preg_replace_callback('/_([a-zA-Z])/', function($match){
				return strtoupper($match[1]);
			}, $name);
			return $ucfirst ? ucfirst($name) : lcfirst($name);
		}
		return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
	}
}
