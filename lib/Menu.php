<?php
/**
 * 菜单
 * @author xs
 */
namespace lib;

use think\Db;

class Menu{
	
	/**
	 * 自定义主菜单
	 * @var array
	 */
	private static $menu = [];
	/**
	 * 自定义
	 * @var array
	 */
	private static $custom = [];
	
	/**
	 * 获取主菜单
	 * @param array $checked 路径id集
	 * @return array
	 */
	public static function getMain($checked = []){
		static $menu;
		if($menu){
			return $menu;
		}
		//权限列表 type[0隐藏-1主菜单-2按钮]
		$where = ['type' => 1, 'status' => 1,];
		if(!model('User')->isAdministrator()){
			$where['id'] = ['in', \lib\Auth::instance()->getAuthIds(model('User')->isLogin()) ? : ''];
		}
		$menu = Db::name('auth_rule')->where($where)->order('sort DESC')->select();
		//加入插件菜单
		$menu = array_merge($menu, self::$menu);
		foreach($menu as $k => $v){
			$menu[$k]['is_checked'] = in_array($v['id'], $checked) ? true : false;
		}
		//进行递归排序
		$menu = Helper::sortArrayRecursio($menu);
		return $menu;
	}
	
	/**
	 * 获取选项
	 * @param string $url 当前url
	 * @return array
	 */
	public static function getOption($url){
		//获取选项列表
		$parent_id = Db::name('auth_rule')->where(['name' => $url])->value('id');
		//type[0隐藏-1主菜单-2按钮]
		$where = ['parent_id' => $parent_id, 'type' => 2, 'status' => 1,];
		if(!model('User')->isAdministrator()){
			$where['id'] = ['in', \lib\Auth::instance()->getAuthIds(model('User')->isLogin()) ? : ''];
		}
		$option = Db::name('auth_rule')->where($where)->order('sort DESC')->select();
		return $option;
	}
	
	/**
	 * 获取选中路径
	 * @param string $url 当前url
	 * @return array
	 */
	public static function getChecked($url){
		$checked = [];
		//自定义菜单
		if(self::isCustom($url)){
			$defined                 = self::$custom[$url];
			$checked[$defined['id']] = $defined;
			isset(self::$menu[$defined['parent_id']]) && $checked[self::$menu[$defined['parent_id']]['id']] = self::$menu[$defined['parent_id']];
			if(isset($defined['top_id']) && $defined['top_id']){
				$id = $defined['top_id'];
			}
		}else{
			//权限菜单
			$rule = Db::name('auth_rule')->where(['name' => $url, 'status' => 1])->find();
			if($rule){
				$checked[$rule['id']] = $rule;
				$id                   = $rule['parent_id'];
			}
		}
		while(isset($id) && $id && $rule = Db::name('auth_rule')->where(['id' => $id, 'type' => 1, 'status' => 1])->find()){
			$checked[$rule['id']] = $rule;
			$id                   = $rule['parent_id'];
		}
		return $checked;
	}
	
	/**
	 * 新增自定义菜单
	 * @param array $menu 菜单数组
	 * @param int $parent_id 上级ID
	 * @return bool
	 */
	public static function push($menu, $parent_id = 0){
		$parent_id = $parent_id ? : 13;
		$addon     = array_merge([
			'id' => uniqid(), 'parent_id' => $parent_id, 'name' => '', 'title' => '未知', 'icon' => 'fa-link', 'top_id' => $parent_id, 'show' => true, 'module' => 'admin',
		], $menu);
		//自定义菜单
		self::$custom[$addon['name']] = $addon;
		$addon['show'] && self::$menu[$addon['id']] = $addon;
		//子菜单
		if(isset($addon['sublist']) && is_array($addon['sublist'])){
			foreach($addon['sublist'] as $k1 => $v1){
				self::$custom[$v1['name']] = array_merge([
					'id' => uniqid(), 'parent_id' => $addon['id'], 'name' => '', 'title' => '未命名', 'icon' => 'fa-link', 'top_id' => $parent_id, 'module' => 'admin',
				], $v1);
			}
		}
		return true;
	}
	
	/**
	 * 校验访问权限
	 * @param string $url 地址
	 * @param int $user_id 用户ID
	 * @return bool
	 */
	public static function check($url, $user_id){
		//默认持有2基本权限既可
		$auth_id = isset(self::$custom[$url]['auth'])
			? self::$custom[$url]['auth'] : 2;
		$ids     = Auth::instance()->getAuthIds($user_id);
		if($auth_id && !in_array($auth_id, $ids)){
			return false;
		}
		return true;
	}
	
	/**
	 * 是否自定义菜单
	 * @param string $url 地址
	 * @return bool
	 */
	public static function isCustom($url){
		if(!isset(self::$custom[$url]) || !self::$custom[$url]){
			return false;
		}
		return true;
	}
	
}