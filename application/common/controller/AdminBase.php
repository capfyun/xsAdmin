<?php
/**
 * admin基类
 * @author xs
 */
namespace app\common\controller;

use think\Hook;
use lib\Auth;
use lib\Helper;

abstract class AdminBase extends Base{
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
			Helper::convertHump($this->request->controller())
			.'/'.$this->request->action()
		);

//		halt([
//			$this->request->module(),
//			$this->request->controller(),
//			$this->request->action()
//		]);
		
		//当前用户ID
		$this->user_id = model('User')->isLogin();
		
		//模板赋值
		$checked = $this->getCheckedMenu();
		//创建选中路径之后
		Hook::listen('create_checked_after', $checked);
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
		$badge = [
			'addon/addon_list' => [
				'red' => 'hot',
			],
		];
		
		//权限列表 type[0隐藏-1主菜单-2按钮]
		$where = ['type' => 1, 'status' => 1,];
		if(!$this->isAdministrator($this->user_id)){
			$where['id'] = ['in', Auth::instance()->getAuthIds($this->user_id) ? : ''];
		}
		$rule_list = db('auth_rule')->where($where)->order('sort DESC')->select();
		foreach($rule_list as $k => $v){
			$rule_list[$k]['badge'] = isset($badge[$v['name']]) ? $badge[$v['name']] : [];
		}
		
		//创建菜单之后
		Hook::listen('create_menu_after', $rule_list);
		//进行递归排序
		return Helper::sortArrayRecursio($rule_list);
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
			'type'      => 2, //menu[0隐藏-1主菜单-2按钮]
			'status'    => 1,
		];
		if(!$this->isAdministrator($this->user_id)){
			$where['id'] = ['in', Auth::instance()->getAuthIds($this->user_id) ? : ''];
		}
		$result = db('auth_rule')
			->where($where)
			->order('sort DESC')
			->select();
		//创建选项之后
		Hook::listen('create_option_after', $result);
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
				->where(['id' => $id, 'type' => 1, 'status' => 1])
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
