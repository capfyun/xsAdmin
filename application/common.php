<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * 实例化服务层
 * @param mixed $string 第一个参数是类名，第二个开始都是构造函数的参数
 * @return object
 */
function service(){
	$arguments  = func_get_args();
	return call_user_func_array([\Loader::class,'service'],$arguments);
}

/**
 * 实例化mongo数据库
 * @param string $name 操作的数据表名称（不含前缀）
 * @param array|string $config 数据库配置参数
 * @param bool $force 是否强制重新连接
 * @return \think\db\Query
 */
function mongo($name = '', $config = [], $force = false){
	$config = array_merge(config('mongo'), $config);
	return \think\Db::connect($config, $force)->name($name);
}

/**
 * 数据库写入，快捷调试
 */
function db_debug(){
	$data = func_get_args();
	//写入数据库
	db('log_debug')->insert([
		'data' => json_encode($data),
		'date' => date('Y-m-d H:i:s'),
		'url'  => request()->module().'/'.request()->controller().'/'.request()->action(),
	]);
}

/**
 * 文件写入，快捷调试
 * @param mixed $data
 * @param string $file
 */
function file_debug($data, $file = 'debug.txt'){
	$path    = $_SERVER['DOCUMENT_ROOT'].'/resource/debug/'.$file;
	$content = (is_string($data) ? $data : var_export($data, true))."\r\n";
	file_put_contents($path, $content, FILE_APPEND|LOCK_EX);
}


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
		$uid   = $name.$layer;
		if(isset(self::$instance[$uid])){
			return self::$instance[$uid];
		}
		//是否添加类名后缀
		$appendSuffix = false;
		//公共模块名
		$common = 'common';
		
		list($module, $class) = self::getModuleAndClass($name, $layer, $appendSuffix);
		
		if(class_exists($class)){
			$reflection = new \ReflectionClass($class);
			$service    = $reflection->newInstanceArgs($arguments);
		}else{
			$class = str_replace('\\'.$module.'\\', '\\'.$common.'\\', $class);
			if(class_exists($class)){
				$reflection = new \ReflectionClass($class);
				$service    = $reflection->newInstanceArgs($arguments);
			}else{
				throw new \think\exception\ClassNotFoundException('class not exists:'.$class, $class);
			}
		}
		
		return self::$instance[$uid] = $service;
	}
}


