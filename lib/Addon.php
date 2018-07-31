<?php
/**
 * 插件
 * @author xs
 */
namespace lib;

class Addon{
	
	/**
	 * @var string 错误信息
	 */
	private static $error = '';
	
	/**
	 * 挂载插件
	 */
	public static function mount(){
		//注册插件
		foreach(self::getList() as $k => $v){
			$v['status']==1 && self::register($v['name']);
		}
		return true;
	}
	
	/**
	 * 获取插件类的类名
	 * @param string $name 插件名
	 * @return string
	 */
	public static function getClass($name){
		$class = 'addon\\'.self::convertHump($name).'\\'.self::convertHump($name, 1);
		return $class;
	}
	
	/**
	 * 注册插件
	 * @param string $name 插件名
	 * @return bool
	 */
	public static function register($name){
		$class = self::getClass($name);
		return $class::register();
	}
	
	/**
	 * 安装插件
	 * @param string $name 插件名
	 * @return bool
	 */
	public static function install($name){
		$class  = self::getClass($name);
		$result = $class::install();
		if(!$result){
			self::$error = $class::getError();
			return false;
		}
		$result = self::setCache($name, self::getInfo($name));
		if(!$result){
			self::$error = '安装失败';
			return false;
		}
		return true;
	}
	
	/**
	 * 卸载插件
	 * @param string $name 插件名
	 * @return bool
	 */
	public static function uninstall($name){
		$class  = self::getClass($name);
		$result = $class::uninstall();
		if(!$result){
			self::$error = $class::getError();
			return false;
		}
		$result = self::setCache($name, null);
		if(!$result){
			self::$error = '安装失败';
			return false;
		}
		return true;
	}
	
	/**
	 * 获取插件详情
	 * @param string $name
	 * @return array
	 */
	public static function getInfo($name){
		$class = self::getClass($name);
		if(!class_exists($class)){
			return [];
		}
		$cache = self::getCache();
		if(isset($cache[$name]) && is_array($cache[$name])){
			$info            = $cache[$name];
			$info['install'] = 1;
		}else{
			$info = [
				'name'        => $name,
				'title'       => $class::title(),
				'description' => $class::description(),
				'author'      => $class::author(),
				'version'     => $class::version(),
				'option'      => $class::option(),
				'config'      => [],
				'status'      => 0,
				'sort'        => 0,
				'install'     => 0,
			];
		}
		return $info;
	}
	
	/**
	 * 获得插件列表
	 * @return array
	 */
	public static function getList(){
		static $list = [];
		if($list){
			return $list;
		}
		$files = new \FilesystemIterator(ADDON_PATH, \FilesystemIterator::KEY_AS_FILENAME);
		foreach($files as $k => $v){
			if(!$v->isDir())
				continue;
			$info = self::getInfo($k);
			if(!$info)
				continue;
			$list[$k] = $info;
		}
		//排序
		array_multisort(array_column($list, 'sort'), SORT_DESC, $list);
		return $list;
	}
	
	/**
	 * 获取错误信息
	 * @return string
	 */
	public static function getError(){
		return self::$error;
	}
	
	/**
	 * 获取插件缓存
	 * @param bool $force 强制更新
	 * @return array
	 */
	public static function getCache($force = false){
		static $cache;
		if(!is_null($cache) && !$force){
			return $cache;
		}
		$cache = Cache::get('addon_config') ? : [];
		return $cache;
	}
	
	/**
	 * 设置插件缓存
	 * @param bool $force 强制更新
	 * @return bool
	 */
	public static function setCache($name, $cache = null){
		$addon        = self::getCache();
		$addon[$name] = $cache;
		$result       = Cache::set('addon_config', $addon);
		if(!$result){
			return false;
		}
		self::getCache(true);
		return true;
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
