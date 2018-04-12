<?php
/**
 * 父控制器
 * @author 夏爽
 */
namespace app\common\controller;

class Base extends \think\Controller{
	
	/**
	 * XML编码
	 * @param mixed $data 数据
	 * @param string $root 根节点名
	 * @param string $item 数字索引的子节点名
	 * @param string $attr 根节点属性
	 * @param string $id 数字索引子节点key转换的属性名
	 * @param string $encoding 数据编码
	 * @return string
	 */
	function xml_encode($data, $root = 'think', $item = 'item', $attr = '', $id = 'id', $encoding = 'utf-8'){
		if(is_array($attr)){
			$_attr = array();
			foreach($attr as $key => $value){
				$_attr[] = "{$key}=\"{$value}\"";
			}
			$attr = implode(' ', $_attr);
		}
		$attr = trim($attr);
		$attr = empty($attr) ? '' : " {$attr}";
		$xml  = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
		$xml .= "<{$root}{$attr}>";
		$xml .= $this->data_to_xml($data, $item, $id);
		$xml .= "</{$root}>";
		return $xml;
	}
	
	/**
	 * 数据XML编码
	 * @param mixed $data 数据
	 * @param string $item 数字索引时的节点名称
	 * @param string $id 数字索引key转换为的属性名
	 * @return string
	 */
	function data_to_xml($data, $item = 'item', $id = 'id'){
		$xml = $attr = '';
		foreach($data as $key => $val){
			if(is_numeric($key)){
				$id && $attr = " {$id}=\"{$key}\"";
				$key = $item;
			}
			$xml .= "<{$key}{$attr}>";
			$xml .= (is_array($val) || is_object($val)) ? $this->data_to_xml($val, $item, $id) : $val;
			$xml .= "</{$key}>";
		}
		return $xml;
	}
	
}
