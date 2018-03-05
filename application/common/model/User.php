<?php
/**
 * 模型-用户
 * @author 夏爽
 */
namespace app\common\model;

class User extends Base{
	/* 自动完成 */
	//写入（包含新增、更新）时自动完成
	protected $auto = [];
	//新增时自动完成
	protected $insert = [
		'status' => 1, //状态[0禁用-1启用]
		'register_ip',
	];
	//更新时自动完成
	protected $update = [
	];
	
	//自动写入时间
	protected $autoWriteTimestamp = true;    //模型中定义autoWriteTimestamp属性，true时间戳-datetime时间格式-false关闭写入
	protected $createTime         = 'register_time';    //创建时间字段，默认为create_time，false关闭写入
	protected $updateTime         = 'update_time';    //更新时间字段，默认为update_time，false关闭写入
	
	//只读字段
	protected $readonly = ['username'];    //模型中定义readonly属性，配置指定只读字段
	
	
	/**
	 * 修改器-注册IP
	 * @param string $value 当前值
	 * @param array $data 当前的所有数据数组
	 * @return string
	 */
	public function setRegisterIpAttr($value, $data){
		return service('Tool')->getClientIp(1);
	}
	
	/**
	 * 修改器-密码
	 */
	public function setPasswordAttr($value, $data){
		return service('User')->encode($value);
	}
	
	/**
	 * 一对一关联
	 * @return $this
	 */
	public function userInfo(){
		return $this->hasOne('user_info');    //field()指定关联模型查询的字段
	}
}
