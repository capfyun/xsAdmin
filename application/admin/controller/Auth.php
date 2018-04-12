<?php
/**
 * 权限
 * @author 夏爽
 */
namespace app\admin\controller;

class Auth extends \app\common\controller\AdminBase{
	
	/**
	 * 菜单-列表
	 * @param int $pid 上级ID
	 */
	public function rule_list($parent_id = 0){
		$keyword = [
			'title' => ['title' => ['LIKE', '%'.input('search.keyword').'%']],
			'name'  => ['name' => ['LIKE', '%'.input('search.keyword').'%']],
		];
		$where   = ['parent_id' => $parent_id];
		input('search.keyword')=='' || $where = array_merge($where, $keyword[input('search.keyword_type')]);
		input('search.status')=='' || $where['status'] = input('search.status');
		input('search.menu_type')=='' || $where['menu_type'] = input('search.menu_type');
		input('search.request_type')=='' || $where['request_type'] = input('search.request_type');
		
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
		
		cookie('forward', request()->url());
		/* 视图 */
		return $this->fetch('', [
			'paging'    => $paging,
			'parent_id' => $parent_id ? db('auth_rule')->where(['id' => $parent_id])->value('parent_id') : 0,
		]);
	}
	
	/**
	 * 新增编辑菜单
	 */
	public function rule_addedit($id = 0){
		if($this->request->isPost()){
			if($id){
				$result = model('AuthRule')->allowField(true)->save(input(), ['id' => $id]);
			}else{
				$result = model('AuthRule')->allowField(true)->save(input());
			}
			$result || $this->error();
			$this->success('操作成功', cookie('forward'));
		}
		/* 权限详情 */
		$rule = $id ? model('authRule')->get($id) : [];
		
		/* 权限列表 */
		$rule_list = db('auth_rule')->order('sort DESC')->select();
		//递归排序
		$rule_list = service('Tool')->sortArrayRecursio($rule_list);
		
		/* 视图 */
		return $this->fetch('', [
			'info'      => $rule,
			'rule_list' => $rule_list,
		]);
	}
	
	/**
	 * 权限组-列表
	 */
	public function group_list(){
		$keyword = [
			'title' => ['title' => ['LIKE', '%'.input('search.keyword').'%']],
		];
		$where   = [];
		input('search.status')=='' || $where['status'] = input('search.status');
		input('search.keyword')=='' || $where = array_merge($where, $keyword[input('search.keyword_type')]);
		$paging = model('AuthGroup')
			->where($where)
			->order('sort DESC')
			->paginate()
			->each(function($item, $key){
				$status_format         = [0 => '禁用', 1 => '启用'];
				$item['status_format'] = isset($status_format[$item['status']]) ? $status_format[$item['status']] : '-';
				return $item;
			});
		
		/* 视图 */
		return $this->fetch('', [
			'paging' => $paging,
		]);
	}
	
	/**
	 * 新增编辑权限组
	 * @param array $rules 权限ID集
	 */
	public function group_addedit($id = 0, $rules = array()){
		if($this->request->isPost()){
			sort($rules);
			$this->request->post(['rules' => implode(',', $rules)]);
			$result = model('AuthGroup')->allowField(true)->save($this->request->post(), ['id' => $id]);
			if(!$result) return $this->dwzReturn(300);
			return $this->dwzReturn(200);
		}
		/* 用户组详情 */
		$info         = db('auth_group')->where(['id' => $id])->order('sort ASC')->find();
		$rule_possess = explode(',', $info['rules']); //拥有的权限
		/* 权限列表 */
		$rule_list = db('auth_rule')->field('*')->order('sort ASC')->limit(1000)->select();
		foreach($rule_list as $k => $v) $rule_list[$k]['isset'] = in_array($v['id'], $rule_possess) ? 1 : 0;
		$rule_html = service('Auth')->getAuthRuleListToHtmlCheckbox(service('Tool')->sortArraySon($rule_list));//递归排序、换砖为html代码
		return $this->fetch('', ['data' => [
			'info'      => $info,
			'rule_html' => $rule_html,
		]]);
	}
	
}