<?php
/**
 * 插件注册
 * @author 夏爽
 */
namespace app\common\behavior;

class Addon {
	
	public function __construct(){
	}
	
	/**
	 * 默认入口
	 */
	public function run(&$param){
		//读取并挂载插件
		model('Addon')->load(true);
		model('Addon')->mount();
	}

	
}
