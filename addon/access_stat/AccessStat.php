<?php
/**
 * 访问统计
 * @author xs
 */
namespace addon\access_stat;

use addon\Base;

class AccessStat extends Base{
	
	/**
	 * 插件信息
	 */
	protected static $title       = '访问统计';
	protected static $description = '统计站点的基础信息';
	protected static $author      = 'xs';
	protected static $version     = '1.0';
	
	/**
	 * 注册
	 */
	public static function register(){
	}
	
	/**
	 * 配置项
	 */
	public static function option(){
		return [
			'color'  => [
				'name'    => '颜色风格',
				'type'    => 'selects',
				'value'   => ['蓝色' => 'blue', '红色' => 'red', '绿色' => 'green', '灰色' => 'gray'],
				'explain' => '颜色配置',
			],
			'abc'    => [
				'name'    => '口语',
				'type'    => 'radio',
				'value'   => ['哈哈' => 'haha', '呵呵' => 'hehe', '嘿嘿' => 'heihei', '吼吼' => 'hoho'],
				'explain' => '口语配置',
			],
			'chaohu' => [
				'name'    => '选择，多选',
				'type'    => 'checkbox',
				'value'   => ['你好' => 'hello', '你在哪' => 'where', '你是谁' => 'who'],
				'explain' => '打招呼',
			],
			
			'content' => [
				'name'    => '内容',
				'type'    => 'text',
//				'value' => '默认内容123',
				'explain' => '说出你想说的',
//				'validate' => ['max'=>5]
			],
		];
	}
	
}