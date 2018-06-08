<?php
/**
 * 基类
 * @author xs
 */
namespace app\common\controller;

abstract class Base extends \think\Controller{
	
	/**
	 * 初始化
	 */
	public function _initialize(){
		parent::_initialize();
		
		//跨域
		$allow_origin = [
			'http://xs.local',
			'http://admin.xs.local',
		];
		$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
		in_array($origin, $allow_origin)
			&& header('Access-Control-Allow-Origin:' . $origin);
	}
	
	/**
	 * 空操作
	 */
//	public function _empty(){
//		abort(404,'error');
//	}
	
	/**
	 * 返回接口数据
	 * @param array $data
	 */
	protected function apiReturn($data = []){
		$result = array_merge([
			'code'    => 1000,
			'msg'     => '系统错误',
			'url'     => url('', '', false),
			'time'    => $this->request->server('REQUEST_TIME'),
			'explain' => '',
			'page'    => [
				'current' => 1,
				'last'    => 0,
			],
			'data'    => new \stdClass(),
		], $data);
		//TODO：记录接口调用
		
		//返回
		abort(json($result));
	}
	
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
