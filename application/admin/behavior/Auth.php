<?php
/**
 * 权限
 * @author xs
 */
namespace app\admin\behavior;

use lib\Menu;
use think\Config;
use lib\Helper;

class Auth {
	
	protected $url = '';
	
	/**
	 * 初始化
	 */
	public function __construct(){
		\lib\Auth::instance(Config::get('auth_config'));
		//当前请求地址
		$this->url = strtolower(
			Helper::convertHump(request()->controller())
			.'/'.request()->action()
		);
	}
	
	/**
	 * 自动执行
	 */
	public function run(&$param){
		//当前用户ID
		$user_id = model('User')->isLogin() ? : model('User')->cookieLogin();
		
		//校验IP
		!$this->checkLoginIp() && abort(404, '您的IP禁止操作');
		
		//管理员帐号，不需要任何验证
		if(!$this->isAdministrator($user_id)){
			//权限验证
			if(!$this->isExempt()){
				//自定义菜单
				if(Menu::isCustom($this->url)){
					if(!Menu::check($this->url, $user_id)){
						abort(redirect('error/e404',['msg'=>'未授权']));
					}
				}else{
					//权限
					if($user_id<=0){
						abort(redirect('open/login'));
					}
					if(!\lib\Auth::instance()->check($this->url, $user_id)){
						request()->isAjax()
							? abort(404, '未授权')
							: abort(redirect('open/login'));
					}
				}
				
			}
		}
	}
	
	/**
	 * 是否管理员用户
	 */
	private function isAdministrator($user_id){
		return in_array($user_id, Config::get('administrator_id') ? : []);
	}
	
	/**
	 * 校验IP是否允许登录
	 */
	private function checkLoginIp(){
		$ip = Helper::getClientIp();
		switch(Config::get('admin_id_type')){
			//禁止模式
			case '1':
				if(in_array($ip,Config::get('admin_ip_list') ? : [])){
					return false;
				}
				break;
			//允许模式
			case '2':
				if(!in_array($ip,Config::get('admin_ip_list') ? : [])){
					return false;
				}
				break;
			default:
		}
		return true;
	}
	
	/**
	 * 是否不需要验证
	 */
	private function isExempt(){
		//开放地址
		if(!Config::get('open_url') || !is_array(Config::get('open_url'))){
			return false;
		}
		foreach(Config::get('open_url') as $v){
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
