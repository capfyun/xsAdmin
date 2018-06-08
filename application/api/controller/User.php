<?php
/**
 * 用户
 * @author xs
 */
namespace app\api\controller;

class User extends \app\common\controller\ApiBase{
	
	/**
	 * 用户登录
	 */
	public function login(){
		$param = $this->encrypt(false)->param([
			'type|登录类型|1手机号-2微信-3QQ' => ['require', 'number', 'between' => '1,3'],
			'username|用户名|手机号或唯一标识'  => ['require', 'length' => '6,40'],
			'verify_code|验证码'        => ['require', 'number'],
		]);
		
		switch($param['type']){
			//手机号登录
			case 1:
				//校验手机号
				$result = service('Tool')->isMobile($param['username']);
				if(!$result){
					return $this->apiReturn(['code' => 1000, 'msg' => '请输入一个有效的手机号']);
				}
				//校验验证码
				$result = model('VerifyCode')->check($param['username'], $param['verify_code'], 1);
				if(!$result){
					return $this->apiReturn(['code' => 1000, 'msg' => model('VerifyCode')->getError()]);
				}
				//查询用户
				$user_id = model('User')->login($param['username'], 'mobile');
				if(!$user_id){
					//手机号未被注册
					$result = $this->validate(['mobile' => $param['username'],], ['mobile|手机号' => 'unique:user',]);
					if($result!==true){
						return $this->apiReturn(['code' => 1000, 'msg' => $result]);
					}
					//开始注册
					$user_id = model('User')->register(md5(uniqid()), md5(uniqid()), '', $param['username']);
					if(!$user_id){
						return $this->apiReturn(['code' => 1000, 'msg' => model('User')->getError()]);
					}
					//设置默认昵称
					model('User')->save(['nickname' => '手机用户'.mb_substr($param['username'], -6, 6)], ['id' => $user_id]);
				}
				break;
			//微信号登录
			case 2:
				$user_id = model('UserOpenId')->openIdLogin($param['username'], 1);
				if(!$user_id){
					return $this->apiReturn(['code' => 1000, 'msg' => model('UserOpenId')->getError()]);
				}
				//设置默认昵称
				model('User')->save(['nickname' => '微信用户'.mb_substr($param['username'], -6, 6)], ['id' => $user_id]);
				break;
			//QQ
			case 3:
				$user_id = model('UserOpenId')->openIdLogin($param['username'], 1);
				if(!$user_id){
					return $this->apiReturn(['code' => 1000, 'msg' => model('UserOpenId')->getError()]);
				}
				//设置默认昵称
				model('User')->save(['nickname' => 'QQ用户'.mb_substr($param['username'], -6, 6)], ['id' => $user_id]);
				break;
				
		}
		//生成token
		$token = model('UserToken')->creater($user_id);
		if(!$token){
			return $this->apiReturn(['code' => 1000, 'msg' => model('UserToken')->getError()]);
		}
		//绑定七果用户ID
		model('UserInfo')->bindQg($user_id);
		//记录登录
		model('User')->loginUpdate($user_id);
		return $this->apiReturn(['code' => 0, 'msg' => '登陆成功!', 'data' => [
			'token' => $token,
		]]);
	}
	
	/**
	 * 绑定
	 */
	public function bind(){
		$param   = $this->param([
			'token|用户令牌'             => ['require'],
			'type|绑定类型|1手机号-2微信-3QQ' => ['require', 'number', 'between' => '1,2'],
			'verify_code|验证码'        => ['require', 'length' => '6,40'],
			'number|绑定号码|手机号或三方唯一标识' => ['require', 'length' => '6,40'],
		]);
		$user_id = model('UserToken')->check($param['token']);
		if(!$user_id){
			return $this->apiReturn(['code' => 1000, 'msg' => model('UserToken')->getError()]);
		}
		$user = model('User')->get($user_id);
		
		switch($param['type']){
			//手机号
			case 1:
				//校验手机号
				$result = service('Tool')->isMobile($param['number']);
				if(!$result){
					return $this->apiReturn(['code' => 1000, 'msg' => '请输入一个有效的手机号']);
				}
				//校验验证码
				$result = model('VerifyCode')->check($param['number'], $param['verify_code'], 2);
				if(!$result){
					return $this->apiReturn(['code' => 1000, 'msg' => model('VerifyCode')->getError()]);
				}
				//是否已被注册
				$rule   = [
					'mobile|手机号' => 'unique:user',
				];
				$result = $this->validate([
					'mobile' => $param['number'],
				], $rule);
				if($result!==true){
					return $this->apiReturn(['code' => 1000, 'msg' => $result]);
				}
				//绑定手机号
				$result = model('User')->save(['mobile' => $param['number']], ['id' => $user_id]);
				if(!$result){
					return $this->apiReturn(['code' => 1000, 'msg' => '绑定失败']);
				}
				break;
			//微信号
			case 2:
				$bind = 1;
			//QQ
			case 3:
				$bind = isset($bind) ? $bind : 2;
				//未绑定手机
				if(!$user->mobile){
					return $this->apiReturn(['code' => 1000, 'msg' => '请先绑定手机号']);
				}
				//校验验证码
				$result = model('VerifyCode')->check($user->mobile, $param['verify_code'], 2);
				if(!$result){
					return $this->apiReturn(['code' => 1000, 'msg' => model('VerifyCode')->getError()]);
				}
				//绑定
				$result = model('UserOpenId')->bind($user_id, $param['number'], $bind);
				if(!$result){
					return $this->apiReturn(['code' => 1000, 'msg' => model('UserOpenId')->getError()]);
				}
				break;
		}
		
		return $this->apiReturn(['code' => 0, 'msg' => '绑定成功']);
	}
	
	/**
	 * 修改用户资料
	 */
	public function update(){
		$param   = $this->param([
			'token|用户令牌'      => ['require', 'length' => '6,40'],
			'nickname|用户昵称'   => ['length' => '1,16'],
			'face|头像|图片地址'    => ['length' => '1,200'],
			'gender|性别|1男-2女' => ['number', 'between' => '1,2'],
			'age|年龄'          => ['number', 'between' => '1,100'],
		]);
		$user_id = model('UserToken')->check($param['token']);
		if(!$user_id){
			return $this->apiReturn(['code' => 1000, 'msg' => model('UserToken')->getError()]);
		}
		//更新数据
		$user_data = [];
		$info_data = [];
		if(!empty($param['nickname'])){
			//是否已被占用
			$result = model('User')->get(['nickname' => $param['nickname'], 'id' => ['neq', $user_id]]);
			if($result){
				return $this->apiReturn(['code' => 1000, 'msg' => $result]);
			}
			$user_data['nickname'] = $param['nickname'];
		}
		if(!empty($param['face'])){
			$info_data['face'] = $param['face'];
		}
		if(!empty($param['gender'])){
			$info_data['gender'] = $param['gender'];
		}
		if(!empty($param['age'])){
			$info_data['age'] = $param['age'];
		}
		//更新
		if($user_data){
			$result = model('User')->isUpdate(true)->save($user_data, ['id' => $user_id]);
			if(!$result){
				return $this->apiReturn(['code' => 1000, 'msg' => '更新失败']);
			}
		}
		if($info_data){
			$result = model('UserInfo')->isUpdate(true)->save($info_data, ['user_id' => $user_id]);
			if(!$result){
				return $this->apiReturn(['code' => 1000, 'msg' => '更新失败']);
			}
		}
		
		return $this->apiReturn(['code' => 0, 'msg' => '更新成功']);
	}
	
	/**
	 * 用户详情
	 */
	public function info(){
		$param = $this->param([
			'token|令牌|传令牌时查询自身' => ['length' => '6,40'],
			'user_id|用户ID'      => ['number'],
		]);
		if(empty($param['token']) && empty($param['user_id'])){
			return $this->apiReturn(['code' => 1000, 'msg' => '参数错误']);
		}
		//获取用户ID
		if(!empty($param['user_id'])){
			$user_id = $param['user_id'];
		}else{
			$user_id = model('UserToken')->check($param['token']);
			if(!$user_id){
				return $this->apiReturn(['code' => 1000, 'msg' => model('UserToken')->getError()]);
			}
		}
		
		$user = model('User')->get($user_id);
		if(!$user){
			return $this->apiReturn(['code' => 0, 'msg' => '用户不存在']);
		}
		
		$data = [
			'user_id'      => $user->id,
			'nickname'     => $user->nickname,
			'mobile'       => $user->mobile,
			'gender'       => $user->user_info->gender,
			'age'          => $user->user_info->age,
			'face'         => $user->user_info->face ? : config('default_face'),
			'follow_num'   => db('follow_user')->where(['user_id' => $user->id])->count(),
			'follower_num' => db('follow_user')->where(['to_user_id' => $user->id])->count(),
			//新消息数量
			'new_notified_num'=>$user->user_info->new_notified_num,
			'new_reply_num'	=>$user->user_info->new_reply_num,
			'new_declare_num' =>$user->user_info->new_declare_num,
			'new_msg_num'     =>$user->user_info->new_notified_num+$user->user_info->new_reply_num+$user->user_info->new_declare_num,
		];
		return $this->apiReturn(['code' => 0, 'msg' => 'ok', 'data' => $data]);
	}
}



