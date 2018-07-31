<?php
/**
 * 权限
 * @author xs
 */
namespace app\admin\controller;

use think\Hook;
use lib\Helper;

class Auth extends \app\common\controller\AdminBase{
	
	/**
	 * 菜单-列表
	 * @param int $parent_id 上级ID
	 */
	public function rule_list($parent_id = 0){
		$keyword = [
			'title' => ['title' => ['LIKE', '%'.input('keyword').'%']],
			'name'  => ['name' => ['LIKE', '%'.input('keyword').'%']],
		];
		$where   = ['parent_id' => $parent_id];
		input('keyword')!='' && isset($keyword[input('target')]) && $where = array_merge($where, $keyword[input('target')]);
		input('status')!='' && $where['status'] = input('status');
		
		$paging = db('auth_rule')
			->where($where)
			->order(input('order') ? : 'sort DESC')
			->paginate(['query' => array_filter(input()),]);
		
		$type_format   = [0 => '隐藏', 1 => '菜单', 2 => '选项'];
		$status_format = [0 => '禁用', 1 => '启用'];
		foreach($paging as $k => $v){
			$v['type_format']   = isset($type_format[$v['type']]) ? $type_format[$v['type']] : '';
			$v['status_format'] = isset($status_format[$v['status']]) ? $status_format[$v['status']] : '';
			$paging->offsetSet($k, $v);
		}
		
		//视图
		cookie('forward', request()->url());
		return $this->fetch('', [
			'paging'    => $paging,
			'parent_id' => $parent_id ? db('auth_rule')->where(['id' => $parent_id])->value('parent_id') : 0,
		]);
	}
	
	/**
	 * 新增编辑菜单
	 */
	public function rule_addedit(){
		if(!$this->request->isPost()){
			$rule = model('authRule')->get(input('id'));
			//权限列表
			$rule_list = db('auth_rule')->order('sort DESC')->select();
			$rule_list = Helper::sortArrayRecursio($rule_list);
			//视图
			return $this->fetch('', [
				'info'      => $rule,
				'rule_list' => $rule_list,
			]);
		}
		$param = $this->param([
			'id'               => ['integer', 'egt' => 0],
			'title|名称'         => ['require', 'length' => '1,20'],
			'name|链接地址'        => ['length' => '1,50'],
			'parent_id|上级ID'   => ['require', 'integer', 'egt' => 0],
			'icon'             => [],
			'type|类型'          => ['require', 'integer', 'between' => '0,2'],
			'request|请求类型'     => ['require', 'integer', 'between' => '0,1'],
			'status|状态'        => ['require', 'integer', 'between' => '0,1'],
			'sort|排序'          => ['integer', 'between' => '0,9999'],
			'param_name|参数名'   => ['length' => '1,20'],
			'param_num|参数数量'   => ['integer', 'between' => '0,3'],
			'label_color|标签颜色' => ['length' => '1,20'],
			'label_value|标签内容' => ['length' => '1,20'],
		]);
		$param===false && $this->error($this->getError());
		$param['param'] = $param['param_num']
			? $param['param_name'].':'.$param['param_num'] : '';
		$param['label'] = $param['label_value']
			? $param['label_color'].':'.$param['label_value'] : '';
		$result         = model('AuthRule')
			->allowField(true)
			->isUpdate($param['id'] ? true : false)
			->save($param);
		$result || $this->error('操作失败');
		$this->success('操作成功', cookie('forward'));
	}
	
	/**
	 * 权限组-列表
	 */
	public function group_list(){
		$keyword = [
			'title' => ['title' => ['LIKE', '%'.input('keyword').'%']],
		];
		$where   = [];
		input('keyword')!='' && isset($keyword[input('target')]) && $where = array_merge($where, $keyword[input('target')]);
		input('status')!='' && $where['status'] = input('status');
		
		$paging = model('AuthGroup')
			->where($where)
			->order('sort DESC')
			->paginate(['query' => array_filter(input())]);
		
		$status_format = [0 => '禁用', 1 => '启用'];
		foreach($paging as $k => $v){
			$v['status_format'] = isset($status_format[$v['status']]) ? $status_format[$v['status']] : '-';
			$paging->offsetSet($k, $v);
		}
		
		//视图
		cookie('forward', request()->url());
		return $this->fetch('', [
			'paging' => $paging,
		]);
	}
	
	/**
	 * 新增编辑权限组
	 */
	public function group_addedit(){
		if(!$this->request->isPost()){
			//用户组详情
			$group = model('AuthGroup')->get(input('id'));
			//权限列表
			$rule_list = db('auth_rule')->order('sort DESC')->select();
			$rule_list = Helper::sortArrayRecursio($rule_list);
			return $this->fetch('', [
				'info'      => $group,
				'rule_list' => $rule_list,
			]);
		}
		$param = $this->param([
			'id'             => ['integer', 'egt' => 0],
			'title|名称'       => ['require', 'length' => '1,10'],
			'description|描述' => [],
			'sort|排序'        => ['integer', 'between' => '0,9999'],
			'status|状态'      => ['require', 'integer', 'between' => '0,1'],
			'rule_ids|包含权限'  => ['array'],
		]);
		$param===false && $this->error($this->getError());
		$param['rule_ids'] = $param['rule_ids'] ? : [];
		sort($param['rule_ids']);
		$param['rules'] = implode(',', $param['rule_ids']);
		$result         = model('AuthGroup')
			->allowField(true)
			->isUpdate($param['id'] ? true : false)
			->save($param);
		$result || $this->error('操作失败');
		$this->success('操作成功', cookie('forward'));
	}
	
	/**
	 * 获取主菜单
	 * @return array
	 */
	public function get_menu(){
		$param = $this->param([
			'url|地址' => ['length' => '0,100'],
		]);
		$url = preg_match('/^\/?([\w]+\/{1}[\w]+[^\.\/])/',strtolower($param['url']),$result) ? $result[1] : '';
		
		
		$option = [];
		$checked = [];
		if($url){
			/* 获取选项列表 */
			$parent_id = db('auth_rule')->where(['name' => $url])->value('id');
			//type[0隐藏-1主菜单-2按钮]
			$where = ['parent_id' => $parent_id, 'type' => 2, 'status' => 1,];
			if(!$this->isAdministrator($this->user_id)){
				$where['id'] = ['in', \lib\Auth::instance()->getAuthIds($this->user_id) ? : ''];
			}
			$option = db('auth_rule')->where($where)->order('sort DESC')->select();
			//创建选项之后
			Hook::listen('create_option_after', $option);
			
			/* 获取选中 */
			$rule    = db('auth_rule')->where(['name' => $url, 'status' => 1])->find();
			$checked = [];
			if($rule){
				$data     = [$rule['id'] => $rule];
				$function = function($id) use (&$function, &$data){
					$rule = db('auth_rule')->where(['id' => $id, 'type' => 1, 'status' => 1])->find();
					if($rule){
						$data[$rule['id']] = $rule;
						$function($rule['parent_id']);
					}
					return $data;
				};
				$checked  = $function($rule['parent_id']);
			}
		}
		//创建选中路径之后
		Hook::listen('create_checked_after', $checked);
		$current = $checked ? current($checked) : [];
		
		//权限列表 type[0隐藏-1主菜单-2按钮]
		$badge = [
			'addon/addon_list' => ['red' => 'hot',],
		];
		$where = ['type' => 1, 'status' => 1,];
		if(!$this->isAdministrator($this->user_id)){
			$where['id'] = ['in', \lib\Auth::instance()->getAuthIds($this->user_id) ? : ''];
		}
		$menu = db('auth_rule')->where($where)->order('sort DESC')->select();
		foreach($menu as $k => $v){
			$menu[$k]['badge'] = isset($badge[$v['name']]) ? $badge[$v['name']] : [];
			$menu[$k]['is_checked'] = isset($checked[$v['id']]) ? true : false;
		}
		//创建菜单之后
		Hook::listen('create_menu_after', $menu);
		//进行递归排序
		$menu = Helper::sortArrayRecursio($menu);
		
		$this->apiReturn(['code' => 0, 'msg' => 'ok', 'data' => [
			'url'     => $url,
			'current' => $current,
			'checked' => $checked,
			'menu'    => $menu,
			'option'  => $option,
		]]);
	}
	
}