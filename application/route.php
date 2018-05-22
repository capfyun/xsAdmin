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

return [
	'__pattern__' => [
		'name' => '\w+',
	],
	'[hello]'     => [
		':id'   => ['api/index/index', ['method' => 'get'], ['id' => '\d+']],
		':name' => ['index/hello', ['method' => 'post']],
	],
	/* 域名路由 */
	'__domain__'  => [
		'admin.xs.local' => 'admin',
		'c.7guoyouxi.com' => 'admin',
		'api.xs.local'   => 'api',
		// 泛域名规则
	],

];
