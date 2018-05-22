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
	 * 类型属性
	 */
	public function typeAttr($key = null){
		$attr = [
			'string'   => '字符串',
			'number'   => '数值',
			'array'    => '数组',
			'radio'    => '单选',
			'checkbox' => '多选',
			'select'   => '列表',
			'selects'  => '列表（多选）',
			'text'     => '文本',
			'editor'   => '编辑器',
			'time'     => '时间',
			'date'     => '日期',
			'datetime' => '日期时间',
		];
		return $key===null ? $attr : (isset($attr[$key]) ? $attr[$key] : '');
	}
	
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
			//数组
			case 'array':
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
