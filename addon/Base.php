<?php
/**
 * 插件基类
 * @author xs
 */
namespace addon;

use lib\Addon;

abstract class Base{
	//错误信息
	protected static $error = '';
	/**
	 * 插件信息
	 */
	protected static $title       = ''; //名称
	protected static $description = ''; //描述
	protected static $explain     = ''; //使用说明
	protected static $author      = ''; //说明
	protected static $version     = '1.0'; //版本号
	
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
		$name = preg_replace('/^.*\\\/', '', static::class);
		$info = Addon::getInfo($name);
		if(!$info){
			return [];
		}
		return $info['config'];
	}
	
	/**
	 * 默认插件参数信息，写入到plugin表config_param字段
	 * @return ['字段名' => [
	 * 'type'    => '必须，数据类型【text,radio,checkbox,select,selects】', //checkbox、selects的值是数组
	 * 'name'    => '文字显示',
	 * 'validate' => '数据校验【int,float,date,datetime,require,正则表达式】',
	 * 'value'   => '[名字=>数据] //1,数组：非text的枚举项,radio和select时第一个参数为默认值; 2,字符串：【text】默认数据'
	 * 'explain' => '说明内容'
	 * ]]
	 */
	public static function option(){
		return [];
	}
	
	/**
	 * 名称
	 * @return string
	 */
	public static function title(){
		return static::$title;
	}
	
	/**
	 * 描述
	 * @return string
	 */
	public static function description(){
		return static::$description;
	}
	
	/**
	 * 使用说明
	 * @return string
	 */
	public static function explain(){
		return static::$explain;
	}
	
	/**
	 * 作者
	 * @return string
	 */
	public static function author(){
		return static::$author;
	}
	
	/**
	 * 版本号
	 * @return string
	 */
	public static function version(){
		return static::$version;
	}
	
	/**
	 * 返回错误信息
	 * @return string
	 */
	public static function getError(){
		return static::$error;
	}
	
}