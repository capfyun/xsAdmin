<?php
/**
 * admin模块行为扩展定义文件
 * @author 夏爽
 */

return [
	/* 模块初始化 */
	'module_init'  => [
		'app\\admin\\behavior\\Base',
	],
	
	/* 操作开始执行 */
	'action_begin' => [
		'app\\admin\\behavior\\Base',
	],
	
	/* 视图内容过滤 */
	'view_filter'  => [
		'app\\admin\\behavior\\Base',
	],
	
	/* 日志写入 */
	'log_write'    => [
		'app\\admin\\behavior\\Base',
	],
	
	/* 应用结束 */
	'app_end'      => [
		'app\\admin\\behavior\\Base',
	],
	
	/* 输出结束 */
	'response_end' => [
		'app\\admin\\behavior\\Base',
	],
];
