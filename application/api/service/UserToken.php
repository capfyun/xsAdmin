<?php
/**
 * 服务层-用户令牌
 * @author 夏爽
 */
namespace app\api\service;

class UserToken extends \app\common\service\Base{
	
	/**
	 * 生成用户令牌 TODO:待修改
	 * @param  int $user_id 用户ID
	 * @return string 用户令牌
	 */
	protected function tokenCreate($user_id){
		//用户是否存在
		$model = D('User');
		$info  = $model->info($user_id);
		if(empty($info)) $this->apiReturn(array('code' => '001005', 'msg' => $model->getError()));
		//令牌生成
		$tokenModel = D('Token');
		$token      = $tokenModel->tokenCreate($user_id);
		if(empty($token)) $this->apiReturn(array('code' => '001005', 'msg' => $tokenModel->getError()));
		return $token;
	}
	
	/**
	 * 令牌合法性验证，并返回uid TODO:待修改
	 * @param  string $token 用户令牌
	 * @return int
	 */
	protected function tokenCheck($token){
		//数据库验证
		$model      = D('Token');
		$info       = $model->info($token);
		$allow_time = C('TOKEN_ALLOW_TIME'); //token过期时间
		//是否已过期
		if(empty($info) || empty($info['play_time']) || $info['play_time']>NOW_TIME || ($info['play_time']+$allow_time)<NOW_TIME){
			$this->apiReturn(array('code' => '001020', 'msg' => $this->api_error['001020'])); //令牌已过期，需重新登陆
		}
		//更新操作时间
		$model->where(array('uid' => $info['uid']))->save(array('play_time' => NOW_TIME));
		return $info['uid']; //返回用户id
	}
	
	/**
	 * 删除用户令牌 TODO:待修改
	 * @param string $token 用户令牌
	 */
	public function tokenDel($token){
		return M('token')->where(array('token' => $token))->delete();
	}
	
	
}