<?php
/**
 * 控制器-组织架构
 * @author 夏爽
 */
namespace app\admin\controller;

class Position extends \app\common\controller\BaseAdmin{
	
	/**
	 * 职位列表
	 * 参数 $pageNum=页码数 $numPerPage=每页数据条数 $search=搜索
	 */
	public function position_list($pageNum = 1, $numPerPage = null, $search = []){
		$list = service('Position')->positionList();
		foreach($list as $k => $v){
			//拥有权限组列表
			$auth_group_list = service('Auth')->getGroupsPosition($v['id']);
			$auth_group      = '';
			foreach($auth_group_list as $k1 => $v2) $auth_group .= $k1==0 ? $v2['title'] : '｜'.$v2['title'];
			$list[$k]['auth_group_format'] = $auth_group;
		}
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list' => $list,
		]]);
	}
	
	/**
	 * 职位新增
	 */
	public function position_add(){
		if($this->request->isPost()){
			$result = model('Position')->allowField(true)->save($this->request->post());
			return $result>0 ? $this->dwzReturn(200) : $this->dwzReturn(300);
		}
		//职位列表
		$position_list = service('Position')->positionList();
		/* 视图 */
		return $this->fetch('', ['data' => [
			'position_list' => $position_list,
		]]);
	}
	
	/**
	 * 职位编辑
	 */
	public function position_edit($id = 0){
		if($this->request->isPost()){
			//删除原有权限
			db('auth_group_position')->where(['position_id' => $id])->delete();
			//重新设置权限
			$data = [];
			foreach($this->request->param('group_id/a', []) as $v){
				$data[] = ['position_id' => $id, 'group_id' => $v];
			}
			db('auth_group_position')->insertAll($data);
			//更新详情
			$result = model('Position')->allowField(true)->save($this->request->post(), ['id' => $id]);
			return $result>0 ? $this->dwzReturn(200) : $this->dwzReturn(300);
		}
		//职位列表
		$position_list = service('Position')->positionList();
		//职位详情
		$info = model('Position')->get(['id' => $id]);
		//拥有的权限
		$group_has = db('auth_group_position')->where(['position_id' => $id])->column('group_id');
		//权限组列表
		$group_list = db('auth_group')->where(['status' => 1])->field(array('id', 'title'))->order('sort ASC')->select();
		/* 视图 */
		return $this->fetch('', ['data' => [
			'info'          => $info,
			'position_list' => $position_list,
			'group_has'     => $group_has,
			'group_list'    => $group_list,
		]]);
	}
	
}