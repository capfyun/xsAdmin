<?php
/**
 * 插件
 * @author 夏爽
 */
namespace app\common\model;

use think\Hook;

class Addon extends Base{
	
	/* 自动完成 */
	//写入（包含新增、更新）时自动完成
	protected $auto = [];
	//新增时自动完成
	protected $insert = [
		'status' => 0, //状态[0禁用-1启用]
		'sort'   => 500,
	];
	//更新时自动完成
	protected $update = [
	];
	
	//自动写入时间
	protected $autoWriteTimestamp = true;    //模型中定义autoWriteTimestamp属性，true时间戳-datetime时间格式-false关闭写入
	
	//只读字段
	protected $readonly = [];    //模型中定义readonly属性，配置指定只读字段
	
	/**
	 * 初始化
	 */
	public function initialize(){
		parent::initialize();
	}
	
	/**
	 * 获取并缓存配置
	 * @param bool $is_enforce 是否强制初始化
	 */
	public function load($is_enforce = false){
		//缓存
		$plugin = cache('db_plugin_data');
		//重置缓存
		if(!$plugin || $is_enforce){
			$plugin = $this->where(['status' => 1])->order('sort DESC')->select();
			cache('db_addon_data', $plugin);
		}
	}
	
	/**
	 * 挂载插件
	 */
	public function mount(){
		$addon = cache('db_addon_data');
		if(!$addon){
			return false;
		}
		//注册插件
		foreach($addon as $k => $v){
			$class = $this->getClass($v['name']);
			if(!class_exists($class))
				throw new \Exception("插件类不存在：{$class}");
			$class::register();
		}
		return true;
	}
	
	/**
	 * 获取插件类的类名
	 * @param string $name 插件名
	 * @return string
	 */
	public function getClass($name){
		$class = "addon\\{$name}\\{$name}";
		return $class;
	}
	
}
