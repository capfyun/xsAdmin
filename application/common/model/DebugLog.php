<?php
/**
 * 调试日志
 * @author xs
 */
namespace app\common\model;

class DebugLog extends Base{
	
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
	protected $updateTime         = false;    //更新时间字段，默认为update_time，false关闭写入
	protected $createTime         = 'date';
	
	//只读字段
	protected $readonly = [''];    //模型中定义readonly属性，配置指定只读字段
	
}
