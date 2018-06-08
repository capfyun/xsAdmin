<?php
/**
 * api基类
 * @author xs
 */
namespace app\common\controller;

abstract class ApiBase extends Base{
	//返回的接口数据
	protected $api_param = [];
	//错误码
	protected $api_code = [];
	//接口验证hash
	protected $api_message = '';
	//是否加密
	protected $is_encrypt = false;
	
	/**
	 * 构造
	 */
	public function _initialize(){
		parent::_initialize();
		
		/* API错误信息 */
		$this->api_code = model('Api')->getErrorCode();
	}
	
	
	
	/**
	 * 数据安全校验
	 * @param array $rule 预定义接口参数
	 * @return array|false
	 */
	protected function param($rule){
		$param = $this->request->param();
		//校验加密
		if($this->is_encrypt){
			$param = model('Api')->decrypt($param);
			if($param===false){
				$result = ['code' => 1001, 'msg' => model('Api')->getError()];
				abort($this->apiReturn($result));
			}
		}
		//校验数据
		$result = $this->validate($param, array_filter($rule), $this->api_message);
		if($result!==true){
			$result = ['code' => 1002, 'msg' => $result];
			abort($this->apiReturn($result));
		}
		$data = [];
		foreach($rule as $k => $v){
			list($key) = explode('|', $k);
			$data[$key] = isset($param[$key]) ? $param[$key] : null;
		}
		$this->api_param = $data;
		return $data;
	}
	
	/**
	 * 设置提示信息
	 * @param array $message
	 * @return $this
	 */
	protected function message($message = []){
		$this->api_message = $message;
		return $this;
	}
	
	/**
	 * 接口是否加密
	 * @param bool $is_encode
	 * @return $this
	 */
	protected function encrypt($is_encode = true){
		$this->is_encrypt = $is_encode;
		return $this;
	}
	
}
