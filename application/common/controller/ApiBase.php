<?php
/**
 * 控制器-基类-api
 * @author 夏爽
 */
namespace app\common\controller;

use think\exception\HttpResponseException;

class ApiBase extends Base{
	//返回的接口数据
	protected $api_param = [];
	//错误码
	protected $api_code = [];
	//接口验证hash
	protected $api_hash = '';
	//接口验证hash
	protected $api_message = '';
	//是否加密
	protected $is_encrypt = false;
	
	/**
	 * 构造
	 */
	public function _initialize(){
		/* API错误信息 */
		$this->api_code = model('Api')->getErrorCode();
	}
	
	/**
	 * 返回JSON数据
	 * @param array $data
	 */
	protected function apiReturn($data = []){
		/* 参数 */
		$result = array_merge([
			'code'    => -1,
			'msg'     => '系统错误',
			'url'     => url('', '', false),
			'time'    => date('Y-m-d H:i:s'),
			'hash'    => $this->api_hash,
			'explain' => '',
			'page'    => [
				'current' => 1,
				'last'    => 0,
			],
			'data'    => new \stdClass(),
		], $data);
		//TODO：记录接口调用
		
		/* 返回参数 */
		return json($result);
	}
	
	/**
	 * 数据安全校验
	 * @param array $rule 预定义接口参数
	 * @param bool $encode 是否加密
	 * @return array
	 */
	protected function param($rule){
		//获取接口信息
		if($this->request->param('getapiinfo')==='1'){
			$result = [
				'code' => 0,
				'msg'  => 'ok',
				'data' => [
					'param'      => $rule,
					'is_encrypt' => $this->is_encrypt ? 1 : 0,
				]];
			abort($this->apiReturn($result));
		}
		
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
		$result = $this->validate($param, $rule, $this->api_message);
		if($result!==true){
			$result = ['code' => 1002, 'msg' => $result];
			abort($this->apiReturn($result));
		}
		$this->api_param = $param;
		return $param;
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
