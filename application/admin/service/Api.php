<?php
/**
 * 服务层-接口
 * @author 夏爽
 */
namespace app\admin\service;

class Api extends \app\common\service\Api{
	
	/**
	 * 更新数据
	 */
	public function apiUpdate($data = []){
		/* 初始化 */
		if(isset($data['isinit']) && $data['isinit']==1){
			//获取接口参数
			$api_url = url($data['url'], [], true, $this->api_url);
			$result  = service('Curl')->curl($api_url, ['getapiinfo' => 1], 'POST');
			$result  = json_decode($result, true);
			if($result===null){
				$this->error = model('Api')->getError() ? : '更新失败';
				return false;
			}
			if(isset($result['code']) && $result['code']==0 && isset($result['data']['param'])){
				//更新接口参数、参数说明['username|用户名', [ 'length' => '1,16','boolean'],['用户名必填','哈哈用户名长度为6~16位']]
				$param   = [];
				$explain = [];
				foreach($result['data']['param'] as $k => $v){
					if(strpos($v[0], '|')){
						list($key, $title) = explode('|', $v[0]);
					}else{
						$key   = $v[0];
						$title = '';
					}
					$param[]       = $key;
					$explain[$key] = [
						'title'   => $title,
						'type'    => service('Api')->checkType($v[1]),
						'require' => in_array('require', $v[1]) ? true : false,
						'explain' => isset($v[3]) ? $v[3] : '',
					];
				}
				$data['param']   = implode(',', $param);
				$data['explain'] = json_encode($explain);
			}
		}
		/* 执行更新 */
		$is_update = isset($data['id']) ? true : false;
		$result    = model('Api')->allowField(true)->isUpdate($is_update)->save($data);
		if(!$result){
			$this->error = model('Api')->getError() ? : '更新失败';
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
		$info = model('Api')->get($api_id)->toArray();
		if(!$info){
			$this->error = '接口不存在';
			return false;
		}
		$param = [];
		foreach(explode(',', $info['param']) as $v){
			$param[$v] = isset($data[$v]) ? $data[$v] : '';
		}
		$param_save = $param; //保存当前参数
		
		$param['_time'] = time();
		$param['_hash'] = $this->encode(explode(',', $info['param']), $param['_time']); //加密hash
		
		//发送请求
		$result = service('Curl')->curl(url($info['url'], '', false, $this->api_url), $param, 'POST');
		
		/* 请求成功，保存参数 */
		$array = json_decode($result, true);
		if($array!==null && isset($array['code']) && $array['code']==0){
			$this->apiParamSave($param_save, $info['url'], $array['data']);
		}
		
		return $result;
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
			\think\Session::set($k, $v, 'api');
		}
		//返回参数
		switch($url){
			case 'user/login' :
				if(isset($data['token'])) \think\Session::set('token', $data['token'], 'api'); //保存token
				break;
			default:
		}
	}
	
}