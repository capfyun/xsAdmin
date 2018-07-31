<?php
/**
 * 自定义类库注册
 * @author xs
 */
namespace app\common\behavior;


class Lib {
	
	/**
	 * 默认入口
	 */
	public function run(&$param){
		\think\Loader::addNamespace('lib', ROOT_PATH.'lib'.DS);
	}
	
	
}
