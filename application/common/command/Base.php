<?php
/**
 * 命令行-基类
 * @auth 夏爽
 */
namespace app\common\command;


abstract class Base extends \think\console\Command{
	
	//当前模块
	protected $module = 'common';
	
	/**
	 * 初始化
	 */
	public function __construct(){
		parent::__construct();
		//设置当前模块
		request()->module($this->module);
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
				return json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
			case 'XML'  :
				// 返回xml格式数据
				return $this->xml_encode($data);
			case 'JSONP':
				$varJsonpHandler = config('var_jsonp_handler');
				// 返回JSON数据格式到客户端 包含状态信息
				$handler = isset($_GET[$varJsonpHandler]) ? $_GET[$varJsonpHandler] : $varJsonpHandler;
				return $handler.'('.json_encode($data).');';
			case 'EVAL' :
				// 返回可执行的js脚本
				return $data;
			default:
				return '';
		}
	}
	
}