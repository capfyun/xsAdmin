<?php
/**
 * 加载
 * @author xs
 */
namespace xs;

class Loader extends \think\Loader{
	/**
	 * 实例化（分层）模型
	 * @param mixed $string 第一个参数是类名，第二个开始都是构造函数的参数
	 * @return object
	 * @throws \think\exception\ClassNotFoundException
	 */
	public static function service(){
		$arguments = func_get_args();
		$name      = array_shift($arguments);
		//业务层名称
		$layer = 'service';
		$guid  = $name.$layer;
		if(isset(static::$instance[$guid])){
			return static::$instance[$guid];
		}
		//是否添加类名后缀
		$appendSuffix = false;
		//公共模块名
		$common = 'common';
		if(strpos($name, '\\')){
			$class = $name;
		}else{
			if(strpos($name, '/')){
				list($module, $name) = explode('/', $name, 2);
			}else{
				$module = \think\Request::instance()->module();
			}
			$class = static::parseClass($module, $layer, $name, $appendSuffix);
		}
		if(class_exists($class)){
			$reflection = new \ReflectionClass($class);
			$model      = $reflection->newInstanceArgs($arguments);
		}else{
			$class = str_replace('\\'.$module.'\\', '\\'.$common.'\\', $class);
			if(class_exists($class)){
				$reflection = new \ReflectionClass($class);
				$model      = $reflection->newInstanceArgs($arguments);
			}else{
				throw new \think\exception\ClassNotFoundException('class not exists:'.$class, $class);
			}
		}
		static::$instance[$guid] = $model;
		return $model;
	}
}