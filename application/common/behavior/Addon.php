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
		define('ADDON_PATH', ROOT_PATH.'addon'.DS);
		is_dir(ADDON_PATH) || @mkdir(ADDON_PATH, 0777, true);
		\think\Loader::addNamespace('addon', ADDON_PATH);
		//挂载插件
		\lib\Addon::mount();
	}

	
}
