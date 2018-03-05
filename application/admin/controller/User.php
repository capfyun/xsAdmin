<?php
/**
 * 控制器-用户
 * @author 夏爽
 */
namespace app\admin\controller;

class User extends \app\common\controller\BaseAdmin{
	
	/**
	 * 用户列表
	 * 参数 $pageNum=页码数 $numPerPage=每页数据条数 $search=搜索
	 */
	public function user_list($pageNum = 1, $numPerPage = null, $search = []){
		$db = db('user');
		if(!empty($search['keyword'])){
			$db->where(function($query) use ($search){
				$query->whereOr([
					'username' => ['like', '%'.$search['keyword'].'%'],
					'realname' => ['like', '%'.$search['keyword'].'%'],
				]);
			});
		}
		$where = [];
		if(isset($search['status']) && $search['status']!='') $where['status'] = $search['status']; //用户状态
		$list = $this->dwzPaging($db->where($where)->field('*')->order('id DESC'), $pageNum, $numPerPage);
		foreach($list as $k => $v){
			$groups     = service('Auth')->getGroups($v['id']);
			$auth_group = [];
			foreach($groups as $k1 => $v1) $auth_group[] = $v1['title'];
			$list[$k]['auth_groups'] = implode('、', $auth_group);
		}
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list' => $list,
		]]);
	}
	
	/*
	 * 用户权限设置
	 * @param int $id 用户ID
	 * @param array $group_id 配置后的权限ID列表
	 */
	public function user_auth($id = 0, $group_id = array()){
		if($this->request->isPost()){
			$db = db('auth_group_access');
			//拥有权限
			$group_id_old = $db->where(array('user_id' => $id))->column('group_id');
			//变更权限
			$arr_add = array_diff($group_id, $group_id_old);
			$arr_del = array_diff($group_id_old, $group_id);
			//无变更
			if(empty($arr_add) && empty($arr_del)) return $this->dwzReturn(300, '无变化!');
			//添加权限
			if(!empty($arr_add)){
				$data_add = array();
				foreach($arr_add as $k => $v) $data_add[] = array('user_id' => $id, 'group_id' => $v);
				$result1 = $db->insertAll($data_add);
			}
			//删除权限
			if(!empty($arr_del)){
				$result2 = $db->where(['user_id' => $id, 'group_id' => array('in', $arr_del)])->delete();
			}
			if(isset($result1) && !$result1 && isset($result2) && !$result2) return $this->dwzReturn(300);
			return $this->dwzReturn(200);
		}
		//拥有的权限
		$auth_group_user = db('auth_group_access')->where(['user_id' => $id])->column('group_id');
		
		//权限组列表
		$auth_group_list = db('auth_group')->where(['status' => 1])->field(['id', 'title'])->order('sort ASC')->select();
		
		return $this->fetch('', ['data' => [
			'auth_group_user' => $auth_group_user,
			'auth_group_list' => $auth_group_list,
		]]);
	}
	
	/**
	 * 用户资料修改
	 */
	public function user_info(){
		if($this->request->isPost()){
			//获取用户信息
			$info = model('User')->get(['id' => $this->user_id]);
			//数据校验
			$data = [];
			foreach($this->request->post() as $k => $v){
				if(!empty($v) && $info[$k]!=$v) $data[$k] = $v;
			}
			//更新
			$s_user = service('User');
			$result = $s_user->userUpdate($this->user_id, $data);
			if(!$result) return $this->dwzReturn(300, $s_user->getError());
			return $this->dwzReturn(200);
		}
		/* 获取用户信息 */
		$info = model('User')->with('user_info')->where(['id' => $this->user_id, 'status' => 1])->find()->toArray();
		if($info){
			$info['face_image'] = service('Transmit')->file($info['face']);
		}
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'info' => $info,
		]]);
	}
	
	/**
	 * 密码修改
	 */
	public function password_edit(){
		if($this->request->isPost()){
			//对比重复密码
			if($this->request->param('new_password')!==$this->request->param('new_password_re'))
				return $this->dwzReturn(300, '两次密码输入不一致');
			//更改新密码
			$s_user = service('User');
			$result = $s_user->userUpdate($this->user_id, ['password' => $this->request->param('new_password')], $this->request->param('old_password'));
			if(!$result) return $this->dwzReturn(300, $s_user->getError());
			//更改成功
			return $this->dwzReturn(200, '密码修改成功');
		}
		/* 视图 */
		return $this->fetch();
	}
	
	/**
	 * 删除用户
	 * @return array
	 */
	public function user_del($ids = ''){
		return $this->delete('user', $ids);
	}
	
	/**
	 * 启用用户
	 * @param string $ids 数据集
	 */
	public function user_status_on($ids = ''){
		return $this->status('user', $ids, 1);
	}
	
	/**
	 * 禁用用户
	 * @param string $ids 数据集
	 */
	public function user_status_off($ids = ''){
		return $this->status('user', $ids, 0);
	}
	
}