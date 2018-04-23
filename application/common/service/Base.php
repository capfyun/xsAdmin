<?php
/**
 * 服务层基类
 * @author 夏爽
 */
namespace app\common\service;

class Base{
	
	/**
	 * 构造
	 */
	public function __construct(){
	}
	
	//错误信息
	protected $error = '';
	
	/**
	 * 获取错误信息
	 * @return string
	 */
	public function getError(){
		return $this->error;
	}
	
	
}
