<?php
/**
 * 用户详情
 * @author 夏爽
 */
namespace app\common\model;

class UserInfo extends Base{
	/* 自动完成 */
	//写入（包含新增、更新）时自动完成
	protected $auto = [];
	//新增时自动完成
	protected $insert = [
		'gender' => 1,
		'age'    => 18,
	];
	//更新时自动完成
	protected $update = [
	];
	
	//自动写入时间
	protected $autoWriteTimestamp = true;    //模型中定义autoWriteTimestamp属性，true时间戳-datetime时间格式-false关闭写入\
	
	//只读字段
	protected $readonly = [];    //模型中定义readonly属性，配置指定只读字段
	
	/**
	 * 一对一关联
	 * @return $this
	 */
	public function user(){
		return $this->belongsTo('user', 'user_id', 'user_id', 'INNER');    //field()指定关联模型查询的字段
	}
	
	/**
	 * 绑定七果ID
	 */
	public function bindQg($user_id){
		$user = model('User')->get($user_id);
		if(!$user->mobile || $user->user_info->qg_id){
			return false;
		}
		$result = service('Curl')->curl('http://sdk.7xz.com/our_brush/phoneToRegUser?cellphone='.$user->mobile);
		$result = json_decode($result, true);
		$qg_id  = $result && isset($result['data']['user_id']) ? $result['data']['user_id'] : 0;
		if(!$qg_id){
			return false;
		}
		return model('UserInfo')->save(['qg_id'=>$qg_id],['user_id'=>$user_id]);
	}
	
}
