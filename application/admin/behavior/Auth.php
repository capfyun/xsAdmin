<?php
/**
 * 权限
 * @author 夏爽
 */
namespace app\admin\behavior;

class Auth {
	
	protected $url = '';
	
	/**
	 * 初始化
	 */
	public function __construct(){
		//当前请求地址
		$this->url = strtolower(
			service('Tool')->convertHump(request()->controller())
			.'/'.request()->action()
		);
	}
	
	/**
	 * 自动执行
	 */
	public function run(&$param){
		//当前用户ID
		$user_id = model('User')->isLogin() ? : model('User')->cookieLogin();
		
		//管理员帐号，不需要任何验证
		if(!$this->isAdministrator($user_id)){
			//校验IP
			$result = $this->checkLoginIp();
			$result || abort(404, '您的IP禁止操作');
			
			//权限验证
			if(!$this->isExempt()){
				if($user_id<=0){
					redirect(url('open/login'));
				}
				$result = service('Auth')->check($this->url, $user_id);
				if(!$result){
					abort(404, '未授权');
				}
			}
		}
	}
	
	/**
	 * 是否管理员用户
	 */
	private function isAdministrator($user_id){
		return in_array($user_id, config('administrator_id'));
	}
	
	/**
	 * 校验IP是否允许登录
	 */
	private function checkLoginIp(){
		$ip = service('Tool')->getClientIp();
		//黑名单IP
		if(config('admin_ban_ip') && in_array($ip,config('admin_ban_ip'))){
			return false;
		}
		//白名单IP
		if(config('admin_allow_ip') && !in_array($ip,config('admin_allow_ip'))){
			return false;
		}
		return true;
	}
	
	/**
	 * 是否不需要验证
	 */
	private function isExempt(){
		//开放地址
		if(!config('open_url') || !is_array(config('open_url'))){
			return false;
		}
		foreach(config('open_url') as $v){
			if($v==$this->url){
				return true;
			}
			if(preg_match('/\/*$/',$v) && strpos($this->url,rtrim($v,'*'))===0){
				return true;
			}
		}
		return false;
	}
	
}
