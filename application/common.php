<?php
/**
 * 应用公共文件
 * @author xs
 */

\think\Loader::addNamespace('lib', ROOT_PATH.'lib'.DS);
// 插件
define('ADDON_PATH', ROOT_PATH.'addon'.DS);
is_dir(ADDON_PATH) || @mkdir(ADDON_PATH, 0777, true);
\think\Loader::addNamespace('addon', ADDON_PATH);


if(!function_exists('service')){
	/**
	 * 实例化服务层
	 * @param mixed $string 第一个参数是类名，第二个开始都是构造函数的参数
	 * @return object
	 */
	function service($name = '', $layer = 'service', $appendSuffix = false){
		return \think\Loader::model($name, $layer, $appendSuffix);
	}
}

if(!function_exists('mongo')){
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
}

if(!function_exists('dbDebug')){
	/**
	 * 数据库写入，快捷调试
	 */
	function dbDebug(){
		$data = func_get_args();
		//写入数据库
		db('debug_log')->insert([
			'data' => json_encode($data),
			'date' => date('Y-m-d H:i:s'),
			'url'  => request()->module().'/'.request()->controller().'/'.request()->action(),
		]);
	}
}

if(!function_exists('fileDebug')){
	/**
	 * 文件写入，快捷调试
	 * @param mixed $data
	 * @param string $file
	 */
	function fileDebug($data, $file = 'debug.txt'){
		$path = RUNTIME_PATH.'debug'.DS;
		is_dir($path) || @mkdir($path, 0777, true);
		$content = (is_string($data) ? $data : var_export($data, true))."\r\n";
		file_put_contents($path.$file, $content, FILE_APPEND|LOCK_EX);
	}
}




