<?php
/**
 * 控制器-用户
 * @author 夏爽
 */
namespace app\admin\controller;

class Personnel extends \app\common\controller\BaseAdmin{
	
	/**
	 * 职员列表
	 * 参数 $pageNum=页码数 $numPerPage=每页数据条数 $search=搜索
	 */
	public function staff_list($pageNum = 1, $numPerPage = null, $search = []){
		$db = db('user')->alias('m')
			->join(config('database.prefix').'user_info i', 'm.id=i.user_id', 'LEFT')
			->join(config('database.prefix').'position p', 'p.id=i.position_id', 'LEFT');
		if(!empty($search['keyword'])){
			$db->where(function($query) use ($search){
				$query->whereOr([
					'm.realname' => ['like', '%'.$search['keyword'].'%'],
					'm.nickname' => ['like', '%'.$search['keyword'].'%'],
				]);
			});
		}
		$field = 'm.*,i.gender,i.age,p.name as position_name,i.file_id';
		$where = [];
		if(isset($search['i.status']) && $search['status']!='') $where['i.status'] = $search['status']; //用户状态
		$list = $this->dwzPaging($db->where($where)->field($field)->order('id DESC'), $pageNum, $numPerPage);
		foreach($list as $k => $v){
			$list[$k]['gender_format']  = model('UserInfo')->attrFormat('_gender', $v['gender']);
			$list[$k]['file_name']      = service('Transmit')->file($v['file_id'], 'name');
			$list[$k]['file_save_name'] = service('Transmit')->file($v['file_id'], 'save_name');
		}
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list' => $list,
		]]);
	}
	
	/**
	 * 新入职
	 * 参数 $pageNum=页码数 $numPerPage=每页数据条数 $search=搜索
	 */
	public function staff_add(){
		if($this->request->isPost()){
			$m_user = model('User');
			$result = $m_user->isUpdate(false)->save([
				'username' => $username,
				'password' => $this->encode($password), //密码加密
				'email'    => $email,
				'mobile'   => $mobile,
				'realname' => '',
				'nickname' => '',
			]);
			$result = model('UserInfo')->isUpdate(false)->save([
				'user_id'     => $m_user->id,
				'position_id' => '',
				'gender'      => 0,
				'age'         => '',
				'file_id'     => '',
			]);
			
			$this->request->post(['user_id' => $this->user_id]);
			$result = model('Customer')->allowField(true)->save($this->request->post());
			if(!$result) return $this->dwzReturn(300);
			return $this->dwzReturn(200, '', ['url' => 'bulletin_list']);
		}
		/* 视图 */
		return $this->fetch('', ['data' => []]);
	}
	
}