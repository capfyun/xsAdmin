<?php
/**
 * 服务层-接口
 * @author 夏爽
 */
namespace app\common\service;

class Api extends Base{
	//API密钥
	protected $secret_key = 'k+_b}yC2Hx~:uZ/O=a9g-0{6^B|LhfwFlG@I?1MY';
	//API地址
	public $api_url = 'api.kd_crm.local';
	
	/**
	 * @var array API错误码汇总
	 */
	protected $error_msg = [
		0    => 'ok',
		1000 => '自定义错误',
		1001 => '服务器时间异常',
		1002 => '签名错误',
		1003 => '参数校验失败',
		1004 => '参数不能为空',
		1010 => 'token无效，请重新登录',
	];
	
	/**
	 * 获取错误信息
	 * @return array 错误码数组
	 */
	public function getErrorMsg(){
		return $this->error_msg;
	}
	
	/**
	 * 接口加密算法
	 * @param array $param 参数集
	 * @return string
	 */
	public function encode($param = [], $time = 0){
		//字典排序
		sort($param);
		//规则
		$hash = $this->secret_key.$time.implode('', $param);
		return md5($hash);
	}
	
	/**
	 * 接口数据校验 TODO：弃用
	 * @param array $define 预定义参数
	 * @return array|bool
	 */
	public function checkParam($define = []){
		/* 获取接口参数 */
		$param = []; //返回参数
		foreach($define as $k => $v){
			//默认值
			$v = array_merge([
				'name'    => $k, //参数名
				'require' => false, //必填
				'type'    => 'string', //参数类型
				'default' => '', //默认值
				'enum'    => [], //枚举项
				'signed'  => false, //符号,负数
			], $v);
			//必填校验
			if($v['require']){
				if(!request()->has($k, 'param', true)){
					$this->error = $v['name'].'不能为空';
					return false;
				}
				//枚举校验
				if($v['enum']){
					if(is_array($v['enum']) && in_array(request()->param($k), $v['enum'])){
					}else if(is_string($v['enum']) && in_array(request()->param($k), explode(',', $v['enum']))){
					}else{
						$this->error = $v['name'].'参数错误';
						return false;
					}
				}
			}
			//数据类型
			switch(strtolower($v['type'])){
				//数值
				case 'int' :
					$default   = $v['default'] ? : 0;
					$param[$k] = request()->param($k.'/d', $default, 'trim');
					if(!$v['signed'] && $param[$k]<0){
						$this->error = $v['name'].'参数错误';
						return false;
					}
					break;
				//浮点
				case 'float' :
					$default   = $v['default'] ? : 0.00;
					$param[$k] = request()->param($k.'/f', $default, 'trim');
					if(!$v['signed'] && $param[$k]<0){
						$this->error = $v['name'].'参数错误';
						return false;
					}
					break;
				//布尔
				case 'bool' :
					$default   = $v['default'] ? true : false;
					$param[$k] = request()->param($k.'/b', $default, 'trim');
					break;
				//数组
				case 'array' :
					$default   = $v['default'] ? : [];
					$param[$k] = request()->param($k.'/a', $default, 'trim');
					break;
				//json自动转为数组
				case 'json' :
					$default = $v['default'] ? : '';
					$value   = request()->param($k.'/s', $default, 'trim');
					if($value){
						$value = json_decode($value, true);
						if(json_last_error()!=JSON_ERROR_NONE){
							$this->error = $v['name'].'参数错误';
							return false;
						}
					}
					$param[$k] = $value;
					break;
				//逗号分隔的字符串，自动转为数组
				case 'separate':
					$default   = $v['default'] ? : '';
					$value     = request()->param($k.'/s', $default, 'trim');
					$param[$k] = $value ? explode(',', $value) : [];
					break;
				case 'string' :
				default:
					$default   = $v['default'] ? : '';
					$param[$k] = request()->param($k.'/s', $default, 'trim');
			}
		}
		return $param;
	}
}