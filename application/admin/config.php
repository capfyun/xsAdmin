<?php
/**
 * admin模块配置文件
 * @auth 夏爽
 */
return [
	//权限
	'auth_config'           => [
		'auth_on'           => true, // 认证总开关
		'auth_type'         => 1, // 认证方式，1为实时认证；2为登录认证。
		'auth_rule'         => 'auth_rule', // 权限规则表
		'auth_group'        => 'auth_group', // 用户组数据表名
		'auth_group_access' => 'auth_group_access', // 用户-用户组关系表
		'auth_user'         => 'user', //用户表
	],
	//管理员用户id
	'administrator_id'      => [1],
	//免校验地址
	'open_url'              => [
		'transmit/upload',
		'debug/test',
		'open/*'
	],
	
	//跳转模版
	'dispatch_success_tmpl' => 'layout/dispatch_jump',
	'dispatch_error_tmpl'   => 'layout/dispatch_jump',
	
	//分页
	'paginate'              => [
		'type'      => 'bootstrap',
		'var_page'  => 'page',
		'list_rows' => 10,
	],

];
