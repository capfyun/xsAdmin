<?php
/**
 * 用户
 * @author xs
 */
namespace app\admin\controller;

class User extends \app\common\controller\AdminBase{
	
	/**
	 * 用户列表
	 */
	public function lists(){
		$keyword = [
			'nickname' => ['m.nickname' => ['LIKE', '%'.input('keyword').'%']],
			'mobile'   => ['m.mobile' => input('keyword')],
			'username' => ['m.username' => input('keyword')],
		];
		$where   = [];
		input('keyword')!='' && isset($keyword[input('target')]) && $where = array_merge($where, $keyword[input('target')]);
		input('status')!='' && $where['m.status'] = input('status');
		input('is_auth')!='' && $where['a.group_id'] = db()->raw('IS '.(input('is_auth') ? 'NOT' : '').' NULL');
		
		$paging = db('user')->alias('m')
			->join('auth_group_access a', 'a.user_id=m.id', 'LEFT')
			->join('auth_group b', 'b.id=a.group_id', 'LEFT')
			->field('m.*,group_concat(b.title separator "|") AS auth_groups')
			->group('m.id')
			->where($where)
			->order('m.id DESC')
			->paginate(['query' => array_filter(input())]);
		
		$status_format = [0 => '禁用', 1 => '启用'];
		foreach($paging as $k => $v){
			$v['last_login_time_format'] = $v['last_login_time'] ? date('Y-m-d H:i', $v['last_login_time']) : '未登录过';
			$v['last_login_ip_format']   = long2ip($v['last_login_ip']);
			$v['status_format']          = isset($status_format[$v['status']]) ? $status_format[$v['status']] : '-';
			$paging->offsetSet($k, $v);
		}
		
		//视图
		cookie('forward', request()->url());
		return $this->fetch('', [
			'paging' => $paging,
		]);
	}
	
	/**
	 * 新增编辑用户
	 */
	public function addedit(){
		if(!$this->request->isPost()){
			$user = model('User')->get(input('id'));
			//权限组
			$group_list = model('AuthGroup')->where(['status' => 1])->order('sort DESC,id DESC')->select();
			$has_group  = $user && $user['id'] ? db('auth_group_access')->where(['user_id' => $user['id']])->column('group_id') : [];
			//视图
			return $this->fetch('', [
				'info'       => $user,
				'group_list' => $group_list,
				'has_group'  => $has_group,
			]);
		}
		$param = $this->param([
			'id'            => ['integer', '>=' => 0],
			'username|用户名'  => ['require', 'length' => '6,16'],
			'nickname|昵称'   => ['require', 'length' => '2,16'],
			'status|状态'     => ['require', 'between' => '0,1'],
			'password|密码'   => ['length' => '6,16'],
			'group_ids|权限租' => ['array'],
		]);
		$param===false && $this->error($this->getError());
		if($param['id']){
			//编辑
			unset($param['username']);
			//不修改密码
			if(!$param['password']){
				unset($param['password']);
			}
			$result  = model('User')->allowField(true)->isUpdate(true)->save($param);
			$user_id = model('User')->id;
		}else{
			//新增
			$param['password'] || $this->error('请输入密码');
			$result  = model('User')->register($param['username'], $param['password']);
			$user_id = $result;
		}
		$result || $this->error(model('User')->getError());
		//权限
		db('auth_group_access')->where(['user_id' => $user_id])->delete();
		if($param['group_ids']){
			$insert = [];
			foreach($param['group_ids'] as $v){
				$insert[] = ['user_id' => $user_id, 'group_id' => $v];
			}
			db('auth_group_access')->insertAll($insert);
		}
		$this->success('操作成功', cookie('forward'));
	}
	
	/**
	 * 用户资料修改
	 */
	public function info(){
		if(!$this->request->isPost()){
			//获取用户信息
			$user = model('User')->get($this->user_id);
			$user || $this->error('信息不存在');
			$user['face_image'] = model('File')->url($user['face']);//model('File')->fileUrl($user['face']);
			//视图
			return $this->fetch('', [
				'info' => $user,
			]);
		}
		$param = $this->param([
			'nickname|昵称'          => ['require', 'length' => '2,16'],
			'face|头像'              => ['integer', '>=' => 0],
			'gender|性别'            => ['integer', 'between' => '0,2'],
			'age|年龄'               => ['integer', 'between' => '0,100'],
			'old_password|密码'      => ['length' => '6,16'],
			'new_password|新密码'     => ['length' => '6,16'],
			'verify_password|重复密码' => ['length' => '6,16', 'confirm' => 'new_password'],
		]);
		$param===false && $this->error($this->getError());
		//修改密码
		if($param['old_password']){
			model('User')->checkPassword($this->user_id, $param['old_password'])
			|| $this->error(model('User')->getError());
			$param['new_password'] || $this->error('请填写新密码');
			$param['password'] = $param['new_password'];
		}
		//修改信息
		$result = model('User')->allowField(true)->isUpdate(true)
			->save($param, ['id' => $this->user_id]);
		$result || $this->error(model('User')->getError());
		$result = model('UserInfo')->allowField(true)->isUpdate(true)
			->save($param, ['user_id' => $this->user_id]);
		$result || $this->error(model('UserInfo')->getError());
		//更新session
		session('nickname', $param['nickname']);
		session('face', url('open/image', ['i' => $param['face'], 'w' => 150, 'h' => 150]));
		$this->success('操作成功');
	}
	
}