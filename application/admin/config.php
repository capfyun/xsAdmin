<?php
/**
 * admin模块配置文件
 * @auth 夏爽
 */
return [
	/* 权限 */
	//免验证用户ID
	'auth_exempt_user_id'    => [1],
	//免权限验证地址
	'auth_exempt_url'        => [
//		'transmit/upload', //上传入口
	],
	//免权限验证控制器
	'auth_exempt_controller' => [
		'open',
	],
	
	

];
