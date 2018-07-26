<?php
/**
 * 配置
 * @author xs
 */
namespace lib;

use think\Cache;
use think\Db;

class Config {
	
	/**
	 * 类型属性
	 */
	public static function typeAttr($key = null){
		$attr = [
			'string'   => '字符串',
			'integer'  => '整数',
			'float'    => '小数',
			'text'     => '文本',
			'editor'   => '编辑器',
			'list'     => '列表',
			'dict'     => '字典',
			'radio'    => '单选',
			'checkbox' => '多选',
			'select'   => '下拉框',
			'selects'  => '下拉框（多选）',
			'time'     => '时间',
			'date'     => '日期',
			'datetime' => '日期时间',
		];
		return $key===null ? $attr : (isset($attr[$key]) ? $attr[$key] : '');
	}
	
	/**
	 * 获取并缓存配置
	 * @param bool $force 强制初始化
	 */
	public static function load($force = false){
		//获取缓存配置
		$config = Cache::get('db_config_data');
		//获取数据库配置
		if(!$config || $force){
			$config = self::getConfigList();
			Cache::get('db_config_data', $config);
		}
		//添加配置
		\think\Config::set($config);
	}
	
	/**
	 * 配置列表
	 * @return array 配置数组
	 */
	public static function getConfigList(){
		static $config = [];
		if($config){
			return $config;
		}
		foreach(Db::name('config')->where(['status' => 1])->select() as $v){
			$config[$v['name']] = self::parse($v['type'], $v['value']);
		}
		return $config;
	}
	
	/**
	 * 根据配置类型解析配置
	 * @param integer $type 配置类型
	 * @param string $value 配置值
	 */
	private static function parse($type, $value){
		switch($type){
			//列表
			case 'list':
				$value = self::strToArray($value);
				break;
			//列表
			case 'dict':
				$value = self::strToArray($value);
				break;
		}
		return $value;
	}
	
	/**
	 * 字符串转为数组
	 * @param string $string 解析格式（a:名称1,b:名称2）
	 * @return array
	 */
	private static function strToArray($string){
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
