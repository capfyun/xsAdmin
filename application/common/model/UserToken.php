<?php
/**
 * 用户令牌
 * @author 夏爽
 */
namespace app\common\model;

class UserToken extends Base{
	/* 自动完成 */
	//写入（包含新增、更新）时自动完成
	protected $auto = [];
	//新增时自动完成
	protected $insert = [];
	//更新时自动完成
	protected $update = [];
	
	//自动写入时间
	protected $autoWriteTimestamp = true;    //模型中定义autoWriteTimestamp属性，true时间戳-datetime时间格式-false关闭写入\
	
	//只读字段
	protected $readonly = ['user_id'];    //模型中定义readonly属性，配置指定只读字段
	
	/**
	 * 一对一关联
	 * @return $this
	 */
	public function user(){
		return $this->belongsTo('user', 'user_id', 'user_id', 'INNER');    //field()指定关联模型查询的字段
	}
	
	/**
	 * 生成用户令牌
	 * @param  int $user_id 用户ID
	 * @return string 令牌
	 */
	public function creater($user_id){
		if($user_id<=0){
			$this->error = '无效的用户ID';
			return false;
		}
		//生成令牌
		$token = md5(uniqid($user_id.time()));
		//写入令牌
		$user_token = $this->get(['user_id' => $user_id]);
		if($user_token){
			$result = $this->isUpdate(true)->save([
				'token'       => $token,
				'play_time'   => time(),
			],['user_id'=>$user_id]);
		}else{
			$result = $this->isUpdate(false)->save([
				'user_id'     => $user_id,
				'token'       => $token,
				'play_time'   => time(),
			]);
		}
		if(!$result){
			$this->error = '生成失败';
			return false;
		}
		return $token;
	}
	
	/**
	 * 令牌合法性验证
	 * @param  string $token 用户令牌
	 * @return int 用户ID
	 */
	public function check($token){
		//有效时间
		$allow_time = config('token_allow_time');
		//校验
		$user_token = $this->get(['token' => $token]);
		if(!$user_token || $user_token->play_time+$allow_time<time()){
			$this->error = '登录过期，请重新登录';
			return false;
		}
		$user_token->save([
			'play_time' => time(),
			'check_num' => db()->raw('`check_num`+1'),
		]);
		return $user_token->user_id;
	}
	
}
