<?php
/**
 * 控制器-基类
 * @author 夏爽
 */
namespace app\common\controller;

class Base extends \think\Controller{
	
	//错误信息
	protected $error     = '';
	//分页信息
	protected $page = [
		'page'  => 1,
		'total' => 0,
		'limit' => 20,
	];
	//分页html
	protected $render = '';
	
	/**
	 * 获取错误信息
	 */
	public function getError(){
		return $this->error;
	}
	
	/**
	 * Ajax方式返回数据到客户端
	 * @access protected
	 * @param mixed $data 要返回的数据
	 * @param String $type AJAX返回数据格式
	 * @return mixed
	 */
	protected function ajaxReturn($data, $type = 'JSON'){
		switch(strtoupper($type)){
			case 'JSON' :
				// 返回JSON数据格式到客户端 包含状态信息
				header('Content-Type:application/json; charset=utf-8');
				return json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
			case 'XML'  :
				// 返回xml格式数据
				header('Content-Type:text/xml; charset=utf-8');
				return $this->xml_encode($data);
			case 'JSONP':
				$varJsonpHandler = config('var_jsonp_handler');
				// 返回JSON数据格式到客户端 包含状态信息
				header('Content-Type:application/json; charset=utf-8');
				$handler = isset($_GET[$varJsonpHandler]) ? $_GET[$varJsonpHandler] : $varJsonpHandler;
				return $handler.'('.json_encode($data).');';
			case 'EVAL' :
				// 返回可执行的js脚本
				header('Content-Type:text/html; charset=utf-8');
				return $data;
			default:
				return '';
		}
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
	
	/**
	 * 获取分页数据
	 * @param \think\db\Query $db 模型
	 * @param int $param 分页参数
	 * @param int|bool $simple 是否简洁模式或者总记录数
	 * @param bool $html 是否显示分页html
	 * @return array
	 */
	protected function paging($db, $param = [], $simple = false, $html = false){
		//数据对象初始化
		$paginator = $db->paginate($param, $simple);
		//分页html
		$this->render = $html ? $paginator->render() : '';
		//保存分页信息
		$data       = $paginator->toArray();
		$this->page = [
			'page'  => $data['current_page'],
			'total' => $data['total'],
			'limit' => $data['per_page'],
		];
		return $data['data'];
	}
	
}
