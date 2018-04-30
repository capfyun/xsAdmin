<?php
/**
 * 模型-配置
 * @author 夏爽
 */
namespace app\common\model;

class Config extends Base{
	/* 自动完成 */
	//写入（包含新增、更新）时自动完成
	protected $auto = [];
	//新增时自动完成
	protected $insert = [
		'status' => 1, //状态[0禁用-1启用]
	];
	//更新时自动完成
	protected $update = [];
	
	//自动写入时间
	protected $autoWriteTimestamp = true;    //模型中定义autoWriteTimestamp属性，true时间戳-datetime时间格式-false关闭写入
	//只读字段
	protected $readonly = [];    //模型中定义readonly属性，配置指定只读字段
	
	/**
	 * 获取并缓存配置
	 * @param bool $is_enforce 是否强制初始化
	 */
	public function load($is_enforce = false){
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
		$list   = db('config')->where(['status' => 1])->select();
		$config = [];
		foreach($list as $v){
			$config[$v['name']] = $this->parse($v['type'], $v['value']);
		}
		return $config;
	}
	
	/**
	 * 根据配置类型解析配置
	 * @param integer $type 配置类型
	 * @param string $value 配置值
	 */
	private function parse($type, $value){
		switch($type){
			case 2: //解析数组
				$value = $this->strToArray($value);
				break;
		}
		return $value;
	}
	
	/**
	 * 字符串转为数组
	 * @param string $string 解析格式（a:名称1,b:名称2）
	 * @return array
	 */
	private function strToArray($string){
		$array = $string ? preg_split('/[,;\r\n]+/', trim($string, ",;\r\n")) : [];
		$data  = [];
		foreach($array as $v){
			if(strpos($v, ':')){
				list($key, $value) = explode(':', $v);
				$data[$key] = $value;
			}else{
				$data[] = $v;
			}
		}
		return $data;
	}
	
}
