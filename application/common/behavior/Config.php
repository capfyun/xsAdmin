<?php
/**
 * 配置
 * @author 夏爽
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
		model('Config')->load(true);
	}
}
