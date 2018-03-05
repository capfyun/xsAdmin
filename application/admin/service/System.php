<?php
/**
 * 服务层-系统
 * @author 夏爽
 */
namespace app\admin\service;

class System extends \app\common\service\Base{
	
	/**
	 * 检测PHP设置参数
	 * @param string $var 参数
	 * @return string
	 */
	public function show($var){
		$result = get_cfg_var($var);
		switch($result){
			case 0:
				return '<font color="red">×</font>';
			case 1:
				return '<font color="green">√</font>';
			default:
				return $result;
		}
	}
	
	/**
	 * 检测函数支持
	 * @param string $fun_name 函数名
	 * @return string
	 */
	public function isfun($fun_name = ''){
		if(!$fun_name || trim($fun_name)=='' || preg_match('~[^a-z0-9\_]+~i', $fun_name, $tmp)) return '错误';
		return (false!==function_exists($fun_name)) ? '<font color="green">√</font>' : '<font color="red">×</font>';
	}
	
}
