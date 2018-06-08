<?php
/**
 * 配置
 * @author xs
 */
namespace app\common\behavior;

class Config {
	
	public function __construct(){
	}
	
	/**
	 * 默认入口
	 */
	public function run(&$param){
		//读取配置
		\xs\Config::load(\think\Config::get('app_debug'));
	}
}
