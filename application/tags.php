<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用行为扩展定义文件
return [
	'app_init' => [
		//自定义类库
		'app\\common\\behavior\\Lib',
		//配置
		'app\\common\\behavior\\Config',
		//插件
		'app\\common\\behavior\\Addon',
	],
	//模块初始化
	'module_init'  => [
	],
	
];
