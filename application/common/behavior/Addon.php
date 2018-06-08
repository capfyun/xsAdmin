<?php
/**
 * 插件注册
 * @author xs
 */
namespace app\common\behavior;


class Addon {
	
	/**
	 * 默认入口
	 */
	public function run(&$param){
		//读取并挂载插件
		\xs\Addon::load(\think\Config::get('app_debug'));
		\xs\Addon::mount();
	}

	
}
