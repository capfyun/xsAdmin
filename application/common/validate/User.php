<?php
/**
 * 验证器-用户
 * @author xs
 */
namespace app\common\validate;

class User extends Base{
	//定义验证规则（必须）
	protected $rule = [
		//多规则
		'username' => ['alphaDash', 'length' => '6,16', 'unique' => 'user'], //用户名
		'password' => ['length' => '6,16'], //密码
		'mobile'   => ['number', 'length' => '11'], //手机号
		'email'    => ['email'], //邮箱
		'nickname' => ['length' => '2,16'], //昵称
	];
	//定义错误信息
	protected $message = [
	];
	//字段描述
	protected $field = [
		'username' => '用户名',
		'password' => '密码',
		'mobile'   => '手机号',
		'email'    => '邮箱',
		'nickname' => '昵称',
	];
	//定义场景
	protected $scene = [
	];
	
	//自定义验证规则
	protected function checkName($value, $rule, $data){    //参数共有5个，验证数据、验证规则、全部数据（数组）、字段名、字段描述
		return $rule==$value ? true : '名称错误';
	}
	
}
