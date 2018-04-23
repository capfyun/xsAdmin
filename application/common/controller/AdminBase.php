<?php
/**
 * admin基类
 * @author 夏爽
 */
namespace app\common\controller;

class AdminBase extends Base{
	//当前用户ID
	protected $user_id = 0;
	//当前操作地址
	protected $url = '';
	//错误信息
	protected $error = '';
	
	/**
	 * 构造函数
	 */
	public function _initialize(){
		parent::_initialize();
		/* 定义属性 */
		//当前请求地址
		$this->url = strtolower(
			ltrim(service('Tool')->humpToLine($this->request->controller()), '_')
			.'/'.$this->request->action()
		);
		
		//当前用户ID
		$this->user_id = model('User')->isLogin() ? : model('User')->cookieLogin();
		
		
		//管理员帐号，不需要任何验证
		if(!$this->isAdministrator($this->user_id)){
			//校验IP
			$result = $this->checkLoginIp();
			$result || abort(404, '您的IP禁止操作');
			
			//权限验证
			if(!$this->isExempt()){
				if($this->user_id<=0){
					$this->redirect(url('open/login'));
				}
				$result = service('Auth')->check($this->url, $this->user_id);
				if(!$result){
					abort(404, '未授权');
				}
			}
		}
		
		//模板赋值
		$checked = $this->getCheckedMenu();
		$current = $checked ? current($checked) : [];
		$this->assign([
			'app' => [
				'menu'    => $this->getMainMenu(),
				'option'  => $this->getOptionMenu(),
				'checked' => $checked,
				'current' => $current,
			],
		]);
	}
	
	/**
	 * 获取错误信息
	 */
	public function getError(){
		return $this->error;
	}
	
	/**
	 * 数据安全校验
	 * @param array $rule 预定义接口参数
	 * @return array|false
	 */
	protected function param($rule = [], $message = []){
		$param = $this->request->param();
		//校验数据
		$result = $this->validate($param, array_filter($rule), $message);
		if($result!==true){
			$this->error = $result;
			return false;
		}
		$data = [];
		foreach($rule as $k => $v){
			list($key) = explode('|', $k);
			$data[$key] = isset($param[$key]) ? $param[$key] : null;
		}
		return $data;
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
	 * 是否管理员用户
	 */
	protected function isAdministrator($user_id){
		return in_array($user_id, config('administrator_id'));
	}
	
	/**
	 * 获取主菜单
	 * @return string
	 */
	private function getMainMenu(){
		/* 权限列表 */
		$where = [
			'menu_type' => 1, //menu[0隐藏-1主菜单-2按钮]
			'status'    => 1,
		];
		if(!$this->isAdministrator($this->user_id)){
			$where['id'] = ['in', service('Auth')->getAuthIds($this->user_id)];
		}
		$rule_list = db('auth_rule')
			->where($where)
			->order('sort DESC')
			->select();
		
		//进行递归排序
		return service('Tool')->sortArrayRecursio($rule_list);
	}
	
	/**
	 * 获取按键
	 * @return array
	 */
	private function getOptionMenu(){
		/* 获取按键列表 */
		$parent_id = db('auth_rule')
			->where(['name' => $this->url])
			->value('id');
		
		$where = [
			'parent_id' => $parent_id,
			'menu_type' => 2, //menu[0隐藏-1主菜单-2按钮]
			'status'    => 1,
		];
		if(!$this->isAdministrator($this->user_id)){
			$where['id'] = ['in', service('Auth')->getAuthIds($this->user_id)];
		}
		$result = db('auth_rule')
			->where($where)
			->order('sort DESC')
			->select();
		
		return $result;
	}
	
	/**
	 * 获取路径菜单
	 * @return array
	 */
	private function getCheckedMenu(){
		$rule = db('auth_rule')->where(['name' => $this->url, 'status' => 1])->find();
		if(!$rule){
			return [];
		}
		$data     = [$rule['id'] => $rule];
		$function = function($id) use (&$function, &$data){
			$rule = db('auth_rule')
				->where(['id' => $id, 'menu_type' => 1, 'status' => 1])
				->find();
			if($rule){
				$data[$rule['id']] = $rule;
				$function($rule['parent_id']);
			}
			return $data;
		};
		return $function($rule['parent_id']);
	}
	
}
