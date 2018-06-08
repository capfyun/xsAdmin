<?php
/**
 * 插件基类
 * @author xs
 */
namespace addon;

abstract class Base{
	//错误信息
	protected static $error = '';
	
	/**
	 * 插件初始信息
	 */
	protected static $info = [
		'title'       => '', //名称
		'description' => '', //描述
		'explain'     => '', //使用说明
		'author'      => '', //作者
		'version'     => '1.0', //版本
	];
	
	/**
	 * 构造
	 */
	public function __construct(){
	}
	
	/**
	 * 插件注册
	 * @return bool
	 */
	public static function register(){
		return true;
	}
	
	/**
	 * 插件信息
	 * @return array
	 */
	public static function info(){
		return array_merge(self::$info,static::$info);
	}
	
	/**
	 * 插件安装
	 * @return boolean
	 */
	public static function install(){
		return true;
	}
	
	/**
	 * 插件卸载
	 * @return boolean
	 */
	public static function uninstall(){
		return true;
	}
	
	/**
	 * 获取DB中录入的配置参数
	 * @return array
	 */
	public static function config(){
		$addon = model('Addon')->get([
			'name' => preg_replace('/^.*\\\/','',static::class),
		]);
		if(!$addon || !$addon->config || !$config = json_decode($addon->config, true)){
			return [];
		}
		return $config;
	}
	
	/**
	 * 默认插件参数信息，写入到plugin表config_param字段
	 *  @return ['字段名' => [
			'type'    => '必须，数据类型【text,radio,checkbox,select,selects】', //checkbox、selects的值是数组
			'name'    => '文字显示',
			'validate' => '数据校验【int,float,date,datetime,require,正则表达式】',
			'value'   => '[名字=>数据] //1,数组：非text的枚举项,radio和select时第一个参数为默认值; 2,字符串：【text】默认数据'
	   		'explain' => '说明内容'
		]]
	 */
	public static function option(){
		return [];
	}
	
	/**
	 * 返回错误信息
	 * @return array
	 */
	public static function getError(){
		return static::$error;
	}
}