<?php
/**
 * 插件
 * @author 夏爽
 */
namespace app\common\model;

use think\Hook;

class Plugin extends Base{
	
	/* 自动完成 */
	//写入（包含新增、更新）时自动完成
	protected $auto = [];
	//新增时自动完成
	protected $insert = [
		'status' => 1, //状态[0禁用-1启用]
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
			cache('db_plugin_data', $plugin->toArray());
		}
	}
	
	/**
	 * 挂载插件
	 */
	public function mount(){
		//当前请求地址
		$action = strtolower(
			request()->module()
			.'/'.request()->controller()
			.'/'.request()->action()
		);
		$plugin = cache('db_plugin_data');
		if(!$plugin){
			return false;
		}
		db_debug($plugin);
		
		//绑定事件
		foreach($plugin as $k => $v){
			if(!$v['hook'] || ($v['action'] && strtolower($v['action'])!=$action)){
				continue;
			}
			$class = $this->getClass($v['name']);
			db_debug($class,class_exists($class));
			if(!class_exists($class)){
				continue;
			}
			db_debug($v['hook'],$class);
			Hook::add($v['hook'], $class);
		}
		return true;
	}
	
	/**
	 * 获取插件类的类名
	 * @param string $name 插件名
	 * @return string
	 */
	public function getClass($name){
		$class = "plugin\\{$name}\\{$name}";
		return $class;
	}
	
}
