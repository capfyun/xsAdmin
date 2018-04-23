<?php
namespace app\admin\behavior;
/**
 * 行为层-基础
 * @author 夏爽
 */
use app\admin\service;
use think\Request;

class Base {
	
	/**
	 * 初始化
	 */
	public function __construct(){
		$this->url = strtolower(Request::instance()->controller().'/'.Request::instance()->action()); //当前请求地址
	}
	
	/**
	 * 自动执行
	 */
	public function run(&$param){
	}

	/**
	 * 模块初始化
	 * @param $param
	 */
	public function moduleInit(&$param){
		/* 读取配置 */
		model('Config')->load(true);
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
