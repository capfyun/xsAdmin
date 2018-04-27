<?php
/**
 * 行为层-基类
 * @author 夏爽
 */
namespace app\common\behavior;

class Base {
	
	public function __construct(){
		$this->url = strtolower(\think\Request::instance()->controller().'/'.\think\Request::instance()->action()); //当前请求地址
		
	}
	
	/**
	 * 默认入口
	 */
	public function run(&$param){
	}

	/**
	 * 应用初始化
	 */
	public function appInit(&$param){
	}
	
	/**
	 * 应用开始
	 */
	public function appBegin(&$param){
		
	}
	
	/**
	 * 模块初始化
	 * @param $param
	 */
	public function moduleInit(&$param){
		
		
		//读取并挂载插件
		model('Plugin')->load(true);
		model('Plugin')->mount();
	}
	
	/**
	 * 控制器开始
	 * @param $param
	 */
	public function actionBegin(&$param){
	}
	
	/**
	 * 视图输出过滤
	 * @param $param
	 */
	public function viewFilter(&$param){
	}
	
	/**
	 * 应用结束
	 * @param $param
	 */
	public function appEnd(&$param){
	}
	
	/**
	 * 日志write方法
	 * @param $param
	 */
	public function logWrite(&$param){
	}
	
	/**
	 * 输出结束
	 * @param $param
	 */
	public function responseEnd(&$param){
	}
	
}
