<?php
/**
 * 模型-基类
 * @author 夏爽
 */
namespace app\common\model;

class Base extends \think\Model{
	
	protected $_status = [
		0 => '禁用',
		1 => '启用',
	];
	
	/**
	 * 获取属性
	 * @param string $attr 属性名
	 * @return mixed
	 */
	public function attr($attr = ''){
		return isset($this->$attr) ? $this->$attr : null;
	}
	
	/**
	 * 获取格式化属性
	 * @param string $attr 性名
	 * @param string $key 键
	 */
	public function attrFormat($attr = '', $key = ''){
		$data = $this->attr($attr) ? : [];
		return isset($data[$key]) ? $data[$key] : '';
	}
	
}
