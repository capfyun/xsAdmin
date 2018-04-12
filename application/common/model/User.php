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
	 */
	public function setRegisterIpAttr($value, $data){
		return service('Tool')->getClientIp(1);
	}
	
	/**
	 * 修改器-密码
	 */
	public function setPasswordAttr($value, $data){
		return $this->encode($value);
	}
	
	/**
	 * 关联
	 */
	public function userInfo(){
		return $this->hasOne('user_info', 'user_id', 'id', 'INNER');    //field()指定关联模型查询的字段
	}
	
	/**
	 * 关联
	 */
	public function userToken(){
		return $this->hasOne('user_token', 'user_id', 'id', 'INNER');    //field()指定关联模型查询的字段
	}
	
	/**
	 * 字符串加密（密码加密）
	 * @param  string $str 需要加密的字符串
	 * @return string 加密后的字符串
	 */
	public function encode($string){
		return md5(sha1($string).config('password_secret_key'));
	}
	
	/**
	 * 注册一个新用户
	 * @param  string $username 用户名
	 * @param  string $password 用户密码
	 * @param  string $email 用户邮箱
	 * @param  string $mobile 用户手机号码
	 * @return int 用户ID
	 */
	public function register($username, $password, $email = '', $mobile = ''){
		//事务
		db()->startTrans();
		//生成用户
		$result = model('User')
			->allowField(true)
			->isUpdate(false)
			->save([
				'username' => $username,
				'password' => $password, //密码自动加密
				'email'    => $email,
				'mobile'   => $mobile,
			]);
		if(!$result){
			$this->error = '用户注册失败';
			db()->rollback();
			return false;
		}
		//生成用户详情
		$result = model('User')->userInfo()->save([]);
		if(!$result){
			$this->error = '用户注册失败';
			db()->rollback();
			return false;
		}
		//提交
		db()->commit();
		return model('User')->id;
	}
	
	/**
	 * 用户登录认证
	 * @param  string $username 用户名
	 * @param  integer $type 用户名类型 （1-用户名，2-手机，3-邮箱，4-UID）
	 * @return int|false 登录成功-用户ID
	 */
	public function login($username, $type = 'username'){
		$login_type = ['username', 'mobile', 'email', 'id'];
		if(!in_array($type, $login_type)){
			$this->error = '登录类型错误';
			return false;
		}
		/* 获取用户信息 */
		$user = model('user')->get([$type => $username]);
		//登陆校验
		if(!$user){
			$this->error = '用户不存在';
			return false;
		}
		if($user->status!=1){
			$this->error = '用户被禁用';
			return false;
		}
		//登录成功，返回用户ID
		return $user->id;
	}
	
	/**
	 * 写入session
	 * @param int $user 用户ID
	 */
	public function loginSession($user_id){
		$user = $this->get(['id' => $user_id]);
		/* 记录登录SESSION和COOKIES */
		$auth = [
			'user_id'         => $user->id,
			'username'        => $user->username,
			'last_login_time' => $user->last_login_time,
			'nickname'        => $user->nickname,
			'face'            => $user->user_info->face,
		];
		session('user_id', $user->id);
		session('user', $auth);
		session('user_sign', service('Tool')->safeSignature($auth));
	}
	
	/**
	 * 登录记录
	 * @param int $user 用户ID
	 */
	public function loginUpdate($user_id){
		$this->isUpdate(true)->save([
			'last_login_time' => time(),
			'last_login_ip'   => service('Tool')->getClientIp(1),
			'login_num'       => ['exp', '`login_num`+1'],
		], ['id' => $user_id]);
	}
	
	/**
	 * 注销当前用户
	 * @return void
	 */
	public function logout(){
		//删除session
		session(['user_id', 'user', 'user_sign',], null);
		//清空session
		session(null);
	}
	
	/**
	 * 检测用户是否登录
	 * @return int 用户ID，失败时返回0
	 */
	public function isLogin(){
		$user = session('user');
		if(!$user
			|| session('user_sign')!=service('Tool')->safeSignature($user)
		){
			return 0;
		}
		return $user['user_id'];
	}
	
	/**
	 * 密码校验
	 * @param int $user_id 用户ID
	 * @param string $password 密码
	 * @return bool
	 */
	public function checkPassword($user_id, $password){
		$user = model('user')->get(['id' => $user_id]);
		//密码校验
		if($user->password!=$this->encode($password)){
			$this->error = '密码错误';
			return false;
		}
		return true;
	}
	
	/**
	 * 密码校验
	 * @param int $user_id 用户ID
	 */
	public function getNickname($user_id){
		return $this->where(['id' => $user_id])->value('nickname');
	}
	
	
	
}
