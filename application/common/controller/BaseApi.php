<?php
/**
 * 控制器-基类-api
 * @author 夏爽
 */
namespace app\common\controller;

class BaseApi extends Base{
	//返回的接口数据
	protected $api_param = [];
	//错误信息
	protected $api_error = [];
	//接口验证hash
	protected $api_hash = '';
	
	/**
	 * 构造
	 */
	protected function initialize(){
		/* API错误信息 */
		$this->api_error = service('Api')->getErrorMsg();
	}
	
	/**
	 * 返回JSON数据
	 * @param array $data
	 */
	protected function apiReturn($data = []){
		/* 参数 */
		$data = array_merge([
			'code'    => -1,
			'msg'     => '系统错误',
			'url'     => url('', '', false),
			'time'    => date('Y-m-d H:i:s'),
			'hash'    => $this->api_hash,
			'explain' => '',
			'data'    => new \stdClass(),
		], $data);
		//TODO：记录接口调用
		/* 返回参数 */
		return $this->ajaxReturn($data);
	}
	
	/**
	 * 数据安全校验
	 * @param array $rule 预定义接口参数
	 * @param bool $encode 是否加密
	 * @return array
	 */
	protected function param($rule, $encode = true){
		//获取接口信息
		if($this->request->param('getapiinfo')==='1'){
			echo $this->apiReturn(['code' => 0, 'msg' => 'ok', 'data' => ['param' => $rule]]);
			exit();
		}
		//校验加密
		if($encode){
			$keys = [];
			foreach($rule as $k => $v){
				$keys[] = $this->getParamKey($v[0]);
			}
			$result = $this->checkHash($keys);
			if(!$result){
				echo $this->apiReturn(['code' => 1002, 'msg' => $this->api_error[1002]]);
				exit();
			}
		}
		//校验数据
		$result = $this->validate($this->request->param(), $rule);
		if($result!==true){
			echo $this->apiReturn(['code' => 1003, 'msg' => $result]);
			exit();
		}
		//获取参数
		$param = [];
		foreach($rule as $k => $v){
			switch($this->getParamType($v[1])){
				case 'number':
					$type    = 'd';
					$default = 0;
					break;
				case 'float':
					$type    = 'f';
					$default = 0.00;
					break;
				case 'boolean':
					$type    = 'b';
					$default = false;
					break;
				case 'array':
					$type    = 'a';
					$default = [];
					break;
				case 'string':
				default:
					$type    = 's';
					$default = '';
			}
			$key         = $this->getParamKey($v[0]);
			$param[$key] = $this->request->param($key.'/'.$type, $default, 'trim');
		}
		return $param;
	}
	
	/**
	 * 校验加密
	 * @param array $keys 接口参数
	 * @return bool
	 */
	private function checkHash($keys){
		$_time          = $this->request->param('_time/d');
		$_hash          = $this->request->param('_hash/s');
		$this->api_hash = $_hash;
		return $_hash==service('Api')->encode($keys, $_time);
	}
	
	
	/**
	 * 获取参数名
	 * @param array $string 名称
	 * @return string
	 */
	private function getParamKey($string){
		if(strpos($string, '|')){
			list($key, $title) = explode('|', $string);
		}else{
			$key = $string;
		}
		return $key;
	}
	
	/**
	 * 获取参数类型
	 * @param array $rule 验证规则
	 * @return string
	 */
	private function getParamType($rule){
		foreach($rule as $k => $v){
			if(is_numeric($k) && in_array($v, ['number', 'float', 'boolean', 'array'])){
				return $v;
			}
		}
		return 'string';
	}
}
