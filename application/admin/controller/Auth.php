<?php
/**
 * 控制器-权限
 * @author 夏爽
 */
namespace app\admin\controller;

class Auth extends \app\common\controller\BaseAdmin{
	
	/**
	 * 菜单-列表
	 * 参数 $pageNum=页码数，$numPerPage=每页数据条数 $search=搜索关键字
	 * @param int $pid 上级ID
	 */
	public function auth_rule_list($pageNum = 1, $numPerPage = null, $search = array(), $parent_id = 0){
		$db = db('auth_rule');
		if(!empty($search['keyword'])){
			$db->where(function($query) use ($search){
				$query->whereOr([
					'id'    => ['eq', $search['keyword']],
					'name'  => ['like', '%'.$search['keyword'].'%'],
					'title' => ['like', '%'.$search['keyword'].'%'],
				]);
			});
		}
		$where = ['parent_id' => $parent_id];
		if(isset($search['status']) && $search['status']!='') $where['status'] = $search['status']; //状态
		if(isset($search['menu']) && $search['menu']!='') $where['menu'] = $search['menu']; //菜单类型
		if(isset($search['button']) && $search['button']!='') $where['button'] = $search['button']; //按键类型
		
		$list = $this->dwzPaging($db->where($where)->field('*')->order('sort asc'), $pageNum, $numPerPage);
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list'      => $list,
			'id'        => $parent_id,
			'parent_id' => $db->where(['id' => $parent_id])->value('parent_id') ? : 0,
			'extend'    => ['parent_id' => $parent_id],
		],]);
	}
	
	/**
	 * 菜单-新增
	 * @param int $pid 上级ID
	 */
	public function auth_rule_add($parent_id = null){
		if($this->request->isPost()){
			$result = model('AuthRule')->allowField(true)->save($this->request->post());
			return $result>0 ? $this->dwzReturn(200) : $this->dwzReturn(300);
		}
		
		/* 权限列表 */
		$rule_list = db('auth_rule')->field(['id', 'parent_id', 'name', 'title'])->order('sort ASC')->limit(1000)->select();
		//递归排序
		$rule_list_format = service('Tool')->sortArraySon($rule_list);
		//换砖为html代码
		$html = service('Auth')->getAuthRuleListToHtmlSelect($rule_list_format, 1, '', $parent_id);
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'rule_html' => $html, //权限列表
		]]);
	}
	
	/**
	 * 菜单-编辑
	 * @param int $id 当前ID
	 * @param int $parent_id 上级ID
	 */
	public function auth_rule_edit($id = 0, $parent_id = null){
		if($this->request->isPost()){
			$result = model('AuthRule')->allowField(true)->save($this->request->post(), ['id' => $id]);
			return $result>0 ? $this->dwzReturn(200) : $this->dwzReturn(300);
		}
		/* 权限详情 */
		$rule_info = db('auth_rule')->where(['id' => $id])->field('*')->find();
		
		/* 权限列表 */
		$rule_list = db('auth_rule')->field(['id', 'parent_id', 'name', 'title'])->order('sort ASC')->limit(1000)->select();
		//递归排序
		$rule_list_format = service('Tool')->sortArraySon($rule_list);
		//换砖为html代码
		$html = service('Auth')->getAuthRuleListToHtmlSelect($rule_list_format, 1, '', $parent_id);
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'info'      => $rule_info, //权限详情
			'rule_html' => $html, //权限列表
		]]);
	}
	
	/**
	 * 权限组-列表
	 * 参数 $pageNum=页码数 $numPerPage=每页数据条数 $search=搜索
	 */
	public function auth_group_list($pageNum = 1, $numPerPage = null, $search = array()){
		$db = db('auth_group');
		if(!empty($search['keyword'])){
			$db->where(function($query) use ($search){
				$query->whereOr([
					'id'          => array('eq', $search['keyword']),
					'title'       => array('like', '%'.$search['keyword'].'%'),
					'description' => array('like', '%'.$search['keyword'].'%'),
				]);
			});
		}
		$where = [];
		if(isset($search['status']) && $search['status']!='') $where['status'] = $search['status']; //用户状态
		$list = $this->dwzPaging($db->where($where)->field('*')->order('sort ASC'), $pageNum, $numPerPage);
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list' => $list,
		]]);
	}
	
	/**
	 * 权限组-新增
	 */
	public function auth_group_add(){
		if($this->request->isPost()){
			$result = model('AuthGroup')->allowField(true)->isUpdate(false)->save($this->request->post());
			if(!$result) return $this->dwzReturn(300);
			return $this->dwzReturn(200);
		}
		/* 视图 */
		return $this->fetch();
	}
	
	/**
	 * 权限组-编辑
	 * @param int $id 当前ID
	 * @param array $rules 权限ID集
	 */
	public function auth_group_edit($id = 0, $rules = array()){
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
	
	/**
	 * 启用菜单
	 * @param string $ids
	 */
	public function auth_rule_status_on($ids = ''){
		return $this->status('auth_rule', $ids, 1);
	}
	
	/**
	 * 禁用菜单
	 * @param string $ids
	 */
	public function auth_rule_status_off($ids = ''){
		return $this->status('auth_rule', $ids, 0);
	}
	
	/**
	 * 删除菜单
	 * @param string $ids 数据集
	 */
	public function auth_rule_del($ids = ''){
		return $this->delete('auth_rule', $ids);
	}
	
	/**
	 * 启用权限组
	 * @param string $ids 数据集
	 */
	public function auth_group_status_on($ids = ''){
		return $this->status('auth_group', $ids, 1);
	}
	
	/**
	 * 禁用权限组
	 * @param string $ids 数据集
	 */
	public function auth_group_status_off($ids = ''){
		return $this->status('auth_group', $ids, 0);
	}
	
	/**
	 * 删除权限组
	 * @param string $ids 数据集
	 */
	public function auth_group_del($ids = ''){
		return $this->delete('auth_group', $ids);
	}
	
}