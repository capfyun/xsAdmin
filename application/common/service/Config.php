<?php
/**
 * 服务层-系统配置
 * @author 夏爽
 */
namespace app\common\service;


class Config extends Base{
	
	/**
	 * 获取并缓存配置
	 * @param bool $is_enforce 是否强制初始化
	 */
	public function saveCache($is_enforce = false){
		//获取缓存配置
		$config = cache('db_config_data');
		//获取数据库配置
		if(!$config || $is_enforce){
			$config = $this->configList();
			cache('db_config_data', $config);
		}
		//添加配置
		config($config);
	}
	
	/**
	 * 配置列表
	 * @return array 配置数组
	 */
	public function configList(){
		$list = db('config')->where(['status' => 1])->select();
		
		$config = [];
		if($list && is_array($list)){
			foreach($list as $v){
				$config[$v['name']] = $this->parse($v['type'], $v['value']);
			}
		}
		return $config;
	}
	
	/**
	 * 根据配置类型解析配置
	 * @param  integer $type 配置类型
	 * @param  string $value 配置值
	 */
	private function parse($type, $value){
		switch($type){
			case 4: //解析数组
				$value = $this->parse_config_attr($value);
				break;
		}
		return $value;
	}
	
	/**
	 * 解析格式（a:名称1,b:名称2）
	 * @param $string
	 * @return array
	 */
	public function parse_config_attr($string) {
		$array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
		if(strpos($string,':')){
			$value  =   [];
			foreach ($array as $val) {
				list($k, $v) = explode(':', $val);
				$value[$k]   = $v;
			}
		}else{
			$value  =   $array;
		}
		return $value;
	}
	
}
