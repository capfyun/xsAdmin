<?php
/**
 * admin父控制器
 * @author 夏爽
 */
namespace app\common\controller;

class AdminBase extends Base{
	//当前用户ID
	protected $user_id = 0;
	//当前操作地址
	protected $url = '';
	
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
		$this->user_id = model('User')->isLogin();
		
		/* 权限验证 */
		$result = service('Auth')->check($this->url, $this->user_id);
		if(!$result){
			$this->redirect(url('open/login'));
		}
		
		/* 模板赋值 */
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
	 * 获取主菜单
	 * @return string
	 */
	private function getMainMenu(){
		/* 权限列表 */
		$where = [
			'menu_type' => 1, //menu[0隐藏-1主菜单-2按钮]
			'status'    => 1,
		];
		if(!in_array($this->user_id, config('auth_config.auth_exempt_user_id'))){
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
		if(!in_array($this->user_id, config('auth_config.auth_exempt_user_id'))){
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
		$rule     = db('auth_rule')->where(['name' => $this->url, 'status' => 1])->find();
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
