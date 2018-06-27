<?php
/**
 * 服务层基类
 * @author xs
 */
namespace app\common\service;

abstract class Base{
	
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
