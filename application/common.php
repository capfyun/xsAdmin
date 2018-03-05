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
 * 实例化service
 * @param string $name Model名称
 * @param string $layer 业务层名称
 * @param bool $appendSuffix 是否添加类名后缀
 * @return Object
 */
function service($name = '', $layer = 'service', $appendSuffix = false){
	return \think\Loader::model($name, $layer, $appendSuffix);
}

/**
 * 实例化mongo数据库
 * @param string $name 操作的数据表名称（不含前缀）
 * @param array|string $config 数据库配置参数
 * @param bool $force 是否强制重新连接
 * @return \think\db\Query
 */
function mongo($name = '',$config = [], $force = false){
	$config = array_merge(config('mongo'),$config);
	return \think\Db::connect($config, $force)->name($name);
}

//====================调试====================
//数据库写入，快捷调试
function _dblog(){
	$data = func_get_args();
	//写入数据库
	db('log_debug')->insert([
		'data' => json_encode($data),
		'url'  => request()->module().'/'.request()->controller().'/'.request()->action(),
	]);
}

//文件写入，快捷调试
function _wr($data, $file = 'debug.txt'){
	$path    = $_SERVER['DOCUMENT_ROOT'].'/static/debug/'.$file;
	$content = var_export($data, true)."\r\n";
	file_put_contents($path, $content, FILE_APPEND|LOCK_EX);
}





