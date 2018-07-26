<?php
/**
 * 模型-接口
 * @author xs
 */
namespace app\common\model;

use lib\Aes;
use lib\Curl;
use lib\Rsa;

class Api extends Base{
	
	/* 自动完成 */
	//写入（包含新增、更新）时自动完成
	protected $auto = [];
	//新增时自动完成
	protected $insert = [
		'status' => 1, //状态[0禁用-1启用]
	];
	//更新时自动完成
	protected $update = [
	];
	
	//自动写入时间
	protected $autoWriteTimestamp = true;    //模型中定义autoWriteTimestamp属性，true时间戳-datetime时间格式-false关闭写入
	
	//只读字段
	protected $readonly = [];    //模型中定义readonly属性，配置指定只读字段
	
	/**
	 * 构造
	 */
	public function initialize(){
	}
	
	/**
	 * @var array API错误码汇总
	 */
	protected $error_code = [
		0    => 'ok',
		1000 => '自定义错误',
		1001 => '加密校验失败',
		1002 => '参数校验失败',
		1010 => 'token无效，请重新登录',
	];
	
	/**
	 * 获取错误码
	 * @return array 错误码数组
	 */
	public function getErrorCode(){
		return $this->error_code;
	}
	
	/**
	 * 接口加密算法
	 * @param array $param 参数集
	 * @return string
	 */
	public function encode($param = [], $time = 0){
		//字典排序
		sort($param);
		$hash = config('api_secret_key').$time.implode('', $param);
		return md5($hash);
	}
	
	/**
	 * 获取接口地址
	 */
	public function getApiUrl($url = ''){
		return config('app_env')=='local'
			? 'http://xs.local/api/'.trim($url, '/')
			: 'http://api.qifanfan.cn/'.trim($url, '/');
	}
	
	/**
	 * 更新数据
	 */
	public function apiUpdate($data = []){
		//初始化
		if(isset($data['isinit']) && $data['isinit']==1){
			//获取接口参数
			$api_url = $this->getApiUrl($data['url']);
			$result  = Curl::request($api_url, ['getapiinfo' => 1], 'post');
			$result  = json_decode($result, true);
			if(!$result){
				$this->error = '初始化失败';
				return false;
			}
			if(isset($result['code']) && $result['code']==0 && isset($result['data']['param'])){
				//更新接口参数、参数说明['username|用户名', [ 'length' => '1,16','boolean'],['用户名必填','哈哈用户名长度为6~16位']]
				$param   = [];
				$explain = [];
				foreach($result['data']['param'] as $k => $v){
					//接口名称
					$key   = $k;
					$title = $description = '';
					if(strpos($k, '|')){
						$keys = explode('|', $k);
						if(count($keys)==3){
							list($key, $title, $description) = $keys;
						}elseif(count($keys)==2){
							list($key, $title) = $keys;
						}
					}
					//格式化接口规则
					if(is_array($v)){
						$rule = '';
						foreach($v as $k1 => $v1){
							$rule .= (is_numeric($k1) ? '' : $k1.':').$v1.'|';
						}
						$rule = trim($rule, '|');
					}else{
						$rule = $v;
					}
					$param[]       = $key;
					$explain[$key] = [
						'title'   => $title,
						'rule'    => $rule,
						'explain' => $description,
					];
				}
				$data['param']      = implode(',', $param);
				$data['explain']    = json_encode($explain);
				$data['is_encrypt'] = $result['data']['is_encrypt'];
			}
		}
		//执行更新
		$is_update = isset($data['id'])&&$data['id'] ? true : false;
		$result    = model('Api')->allowField(true)->isUpdate($is_update)->save($data);
		if(!$result){
			$this->error = model('Api')->getError() ? : '操作失败';
			return false;
		}
		return true;
	}
	
	/**
	 * 接口调试
	 * @param int $api_id 接口ID
	 * @param array $data 请求数据
	 * @return string
	 */
	public function apiDebug($api_id = 0, $data = []){
		$api = model('Api')->get($api_id);
		if(!$api){
			$this->error = '接口不存在';
			return false;
		}
		$param = [];
		foreach(explode(',', $api->param) as $v){
			if(isset($data[$v]) && $data[$v]!=''){
				$param[$v] = $data[$v];
			}
		}
		//保存当前参数
		$save_param = $param;
		
		$post = [];
		//加密
		if($api->is_encrypt){
			//客户端签名
			$param['_sign'] = Rsa::privates(config('client_private_key'))->sign(http_build_query($param));
			//客户端aes加密
			$aesKey        = '7guoyx'.time();
			$post['param'] = Aes::instance(['key'=>$aesKey])->encrypt(http_build_query($param));
			//客户端加密aes key
			$post['key'] = Rsa::publics(config('rsa_public_key'))->encrypt($aesKey);
		}else{
			$post = $param;
		}
		//调用接口
		$api_url = $this->getApiUrl($api->url);
		$result  = Curl::request($api_url, $post, 'post');
		
		if(!$result){
			$this->error = '请求失败';
			return false;
		}
		$array  = json_decode($result, true);
		
		//请求成功，保存参数
		if($array && isset($array['code']) && $array['code']==0){
			$this->apiParamSave($save_param, $api['url'], $array['data']);
		}
		
		return $result;
	}
	
	/**
	 * 解密参数
	 * @param $input
	 */
	public function decrypt($input){
		if(!isset($input['key']) || !isset($input['param'])){
			$this->error = '参数错误';
			return false;
		}
		//获取aes key
		$key = Rsa::privates(config('rsa_private_key'))->decrypt($input['key']);
		//校验aes key
		$result = preg_match('/^7guoyx(\d{10})$/', $key, $time);
		if(!$result){
			$this->error = '参数错误';
			return false;
		}
		//校验时间
		if(abs(time()-$time[1])>24*60*60){
			$this->error = '参数已过期';
			return false;
		}
		//解密参数
		
		$http = Aes::instance($key)->decrypt($input['param']);
		parse_str($http, $param);
		//校验签名
		if(!isset($param['_sign'])){
			$this->error = '签名错误';
			return false;
		}
		$sign = $param['_sign'];
		unset($param['_sign']);
		$result = Rsa::publics(config('client_public_key'))->verify(http_build_query($param), $sign);
		if(!$result){
			$this->error = '签名校验失败';
			return false;
		}
		return $param;
	}
	
	/**
	 * 保存参数
	 * @param array $param 请求参数
	 * @param string $url 当前接口地址
	 * @param array $data 返回参数
	 */
	private function apiParamSave($param = [], $url = '', $data = []){
		//请求参数
		foreach($param as $k => $v){
			session($k, $v, 'api');
		}
		//返回参数
		switch($url){
			case 'user/login' :
				//保存token
				!isset($data['token']) || session('token', $data['token'], 'api');
				break;
			default:
		}
	}
	
	/**
	 * 接口数据校验 TODO：废弃
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