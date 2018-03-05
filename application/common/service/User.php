<?php
/**
 * 服务层-用户
 * @author 夏爽
 */
namespace app\common\service;

class User extends Base{
	
	/**
	 * 用户登录认证
	 * @param  string $username 用户名
	 * @param  string $password 用户密码
	 * @param  integer $type 用户名类型 （1-用户名，2-手机，3-邮箱，4-UID）
	 * @return int|false 登录成功-用户ID
	 */
	public function login($username, $password, $type = 1){
		$where = [];
		switch($type){
			case 1:
				$where['username'] = $username;
				break;
			case 2:
				$where['mobile'] = $username;
				break;
			case 3:
				$where['email'] = $username;
				break;
			case 4:
				$where['id'] = $username;
				break;
			default:
				$this->error = '参数错误！';
				return false;
		}
		/* 获取用户信息 */
		$info = db('user')->where($where)->find();
		//登陆校验
		if(empty($info) || $info['status']!=1){
			$this->error = '用户不存在或被禁用！';
			return false;
		}
		if($info['password']!=$this->encode($password)){
			$this->error = '密码错误！';
			return false;
		}
		//登陆成功
		$this->updateLogin($info); //更新用户登录信息
		return $info['id']; //登录成功，返回用户ID
	}
	
	/**
	 * 字符串加密（密码加密）
	 * @param  string $str 需要加密的字符串
	 * @return string 加密后的字符串
	 */
	public function encode($string){
		return md5(sha1($string).config('auth_key_user'));
	}
	
	/**
	 * 更新登录信息
	 * @param array $user 用户信息
	 */
	protected function updateLogin($user){
		/* 记录登录SESSION和COOKIES */
		$auth = [
			'user_id'         => $user['id'],
			'username'        => $user['username'],
			'last_login_time' => $user['last_login_time'],
		];
		session('user_id', $user['id']);
		session('user_auth', $auth);
		session('user_auth_sign', service('Tool')->safeSignature($auth));
		/* 更新数据库 */
		db('user')->where(['id' => $user['id']])->update([
			'last_login_time' => time(),
			'last_login_ip'   => service('Tool')->getClientIp(1),
			'login_nums'      => ['exp', 'login_nums+1'],
		]);
	}
	
	/**
	 * 注销当前用户
	 * @return void
	 */
	public function logout(){
		//删除session
		session([
			'user_id',
			'username',
			'user_auth',
			'user_auth_sign',
		], null);
		//清空session
		session(null);
	}
	
	/**
	 * 检测用户是否登录
	 * @return int 用户ID，失败时返回0
	 */
	public function isLogin(){
		$auth = session('user_auth');
		if(empty($auth) || session('user_auth_sign')!=service('Tool')->safeSignature($auth)) return 0;
		return $auth['user_id'];
	}
	
	/**
	 * 更新用户信息
	 * @param int $user_id 用户id
	 * @param array $data 修改的字段数组
	 * @param string $password 密码，用来验证
	 * @return bool true 修改成功，false 修改失败
	 */
	public function userUpdate($user_id, $data, $password = ''){
		if(empty($data)){
			$this->error = '无更新！';
			return false;
		}
		//更新前检查用户密码
		if(!empty($password)){
			$password_check = db('user')->where(['id' => $user_id])->value('password');
			if($password_check!=$this->encode($password)){
				$this->error = '密码错误！';
				return false;
			}
		}
		//更新信息
		$m_user = model('User');
		$result = $m_user->allowField(true)->validate(true)->save($data, ['id' => $user_id]);
		if(!$result){
			$this->error = $m_user->getError();
			return false;
		}
		return $result;
	}
	
	/**
	 * 获取用户所属职位
	 * @param  int $user_id 用户ID
	 * @return int 职位ID
	 */
	public function getPositionId($user_id){
		static $position_ids = [];
		if(isset($position_ids[$user_id])) return $position_ids[$user_id];
		$position_id            = db('user_info')->where(['user_id' => $user_id])->value('position_id');
		$position_ids[$user_id] = $position_id ? : 0;
		return $position_ids[$user_id];
	}
	
	/**
	 * 注册一个新用户
	 * @param  string $username 用户名
	 * @param  string $password 用户密码
	 * @param  string $email 用户邮箱
	 * @param  string $mobile 用户手机号码
	 * @return int 注册成功-用户信息，注册失败-错误编号
	 */
	public function register($username, $password, $email = '', $mobile = ''){
		$data = [
			'username' => $username,
			'password' => $this->encode($password), //密码加密
			'email'    => $email,
			'mobile'   => $mobile,
		];
		model('user');
	}
	
	
}
