<?php
/**
 * Redis
 * @author xs
 */
namespace app\common\service;

class Redis extends \think\cache\driver\Redis{
	
	/**
	 * 初始化
	 */
	public function __construct(){
		//配置
		$option = [
			'prefix' => config('app_env').'_', //前缀
		];
		parent::__construct($option);
	}
	
	/**
	 * 插入列队的末尾
	 * @param string $name 名称
	 * @param string $value 值
	 * @return int|false 列队的长度
	 */
	public function rPush($name, $value){
		$key = $this->getCacheKey($name);
		//对数组/对象数据进行缓存处理
		if(is_object($value) || is_array($value)){
			$value = json_encode($value);
		}
		return $this->handler->rPush($key, $value);
	}
	
	/**
	 * 取出列队的开头值（返回并删除）
	 * @param string $name 名称
	 * @return string|false
	 */
	public function lPop($name){
		$key       = $this->getCacheKey($name);
		$value     = $this->handler->lPop($key);
		$json_data = json_decode($value, true);
		// 检测是否为JSON数据 true 返回JSON解析数组, false返回源数据 byron sampson<xiaobo.sun@qq.com>
		return (null===$json_data) ? $value : $json_data;
	}
}
