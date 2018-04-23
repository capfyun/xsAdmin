<?php
/**
 * 权限
 * @author 夏爽
 */
namespace app\admin\controller;

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
			->paginate(['query' => array_filter(input()),])
			->each(function($item, $key){
				$menu_type_format         = [0 => '隐藏', 1 => '菜单', 2 => '选项'];
				$status_format            = [0 => '禁用', 1 => '启用'];
				$item['menu_type_format'] = isset($menu_type_format[$item['menu_type']]) ? $menu_type_format[$item['menu_type']] : '';
				$item['status_format']    = isset($status_format[$item['status']]) ? $status_format[$item['status']] : '';
				return $item;
			});
		
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
			$rule_list = service('Tool')->sortArrayRecursio($rule_list);
			//视图
			return $this->fetch('', [
				'info'      => $rule,
				'rule_list' => $rule_list,
			]);
		}
		$param  = $this->param([
			'id'                => ['number', 'min' => 0],
			'title|名称'          => ['require', 'length' => '1,20'],
			'name|链接地址'         => ['length' => '1,50'],
			'parent_id|上级ID'    => ['require', 'number', 'min' => 0],
			'icon'              => [],
			'menu_type|类型'      => ['require', 'number', 'between' => '0,2'],
			'request_type|请求类型' => ['require', 'number', 'between' => '0,1'],
			'status|状态'         => ['require', 'number', 'between' => '0,1'],
			'sort|排序'           => ['number', 'between' => '0,9999'],
			'param_name|参数名'    => [],
			'param_num|参数数量'    => ['number', 'between' => '0,3'],
		]);
		$param===false && $this->error($this->getError());
		$result = model('AuthRule')
			->allowField(true)
			->isUpdate($param['id'] ? true : false)
			->save($param);
		$result || $this->error();
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
			->paginate()
			->each(function($item, $key){
				$status_format         = [0 => '禁用', 1 => '启用'];
				$item['status_format'] = isset($status_format[$item['status']]) ? $status_format[$item['status']] : '-';
				return $item;
			});
		
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
			$rule_list = service('Tool')->sortArrayRecursio($rule_list);
			return $this->fetch('', [
				'info'      => $group,
				'rule_list' => $rule_list,
			]);
		}
		$param             = $this->param([
			'id'             => ['number', 'min' => 0],
			'title|名称'       => ['require', 'length' => '1,10'],
			'description|描述' => [],
			'sort|排序'        => ['number', 'between' => '0,9999'],
			'status|参数名'     => ['require', 'number', 'between' => '0,1'],
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
		$result || $this->error();
		$this->success('操作成功', cookie('forward'));
	}
	
}