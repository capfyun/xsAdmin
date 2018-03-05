<?php
/**
 * 服务层-基类
 * @author 夏爽
 */
namespace app\common\service;

class Base{
	
	//错误信息
	protected $error = '';
	//分页信息
	public $page = [
		'page'  => 1, //当前页
		'total' => 0, //总数据数
		'limit' => 0, //每页数据数
	];
	
	/**
	 * 初始化过的模型.
	 * @var array
	 */
	protected static $initialized = [];
	
	/**
	 * 构造方法
	 * @access public
	 * @param array|object $data 数据
	 */
	public function __construct(){
		// 执行初始化操作
		$this->initialize();
	}
	
	/**
	 *  初始化模型
	 * @access protected
	 * @return void
	 */
	protected function initialize(){ }
	
	/**
	 * 初始化处理
	 * @access protected
	 * @return void
	 */
	protected static function init(){
	}
	
	/**
	 * 获取错误信息
	 * @return string
	 */
	public function getError(){
		return $this->error;
	}
	
	
}
