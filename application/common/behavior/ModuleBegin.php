<?php
/**
 * 模块开始钩子
 * @author 夏爽
 */
namespace app\common\behavior;

use think\Hook;

class ModuleBegin {
	
	public function __construct(){
	}
	
	/**
	 * 默认入口
	 */
	public function run(&$param){
		//监听钩子
		Hook::listen('module_begin',$param);
	}
}
