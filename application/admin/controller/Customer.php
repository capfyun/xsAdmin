<?php
/**
 * 控制器-客户
 * @author 夏爽
 */
namespace app\admin\controller;

class Customer extends \app\common\controller\BaseAdmin{
	
	/**
	 * 客户-列表
	 * 参数 $pageNum=页码数 $numPerPage=每页数据条数 $search=搜索
	 */
	public function customer_list($pageNum = 1, $numPerPage = null, $search = []){
		/* 获取数据 */
		$db_bulletin = db('customer')->alias('m')
			->join(config('database.prefix').'user u', 'u.id=m.user_id', 'LEFT');
		if(!empty($search['keyword'])){
			$db_bulletin->where(function($query) use ($search){
				$query->whereOr([
					'm.id'   => ['eq', $search['keyword']],
					'm.name' => ['like', '%'.$search['keyword'].'%'],
				]);
			});
		}
		$where       = [];
		$db_bulletin = $db_bulletin->field('m.*,u.realname')->where($where)->order('m.id DESC');
		$list        = $this->dwzPaging($db_bulletin, $pageNum, $numPerPage);
		foreach($list as $k => $v){
			$list[$k]['business_type_format'] = model('Customer')->attrFormat('_business_type', $v['business_type']);
		}
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list' => $list,
		]]);
	}
	
	/**
	 * 客户-新增
	 */
	public function customer_add(){
		if($this->request->isPost()){
			$this->request->post(['user_id' => $this->user_id]);
			$result = model('Customer')->allowField(true)->save($this->request->post());
			if(!$result) return $this->dwzReturn(300);
			return $this->dwzReturn(200, '', ['url' => 'bulletin_list']);
		}
		/* 视图 */
		return $this->fetch('', ['data' => []]);
	}
	
	/**
	 * 客户-编辑
	 */
	public function customer_edit($id = 0){
		if($this->request->isPost()){
			$result = model('Customer')->allowField(true)->save($this->request->post(), ['id' => $id]);
			if(!$result) return $this->dwzReturn(300);
			return $this->dwzReturn(200, '', ['url' => 'customer_list']);
		}
		//详情
		$info = db('customer')->find(['id' => $id]);
		/* 视图 */
		return $this->fetch('', ['data' => [
			'info' => $info,
		]]);
	}
	
	/**
	 * 跟进-列表
	 */
	public function follow_list($pageNum = 1, $numPerPage = null, $search = []){
		/* 获取数据 */
		$db_bulletin = db('customer_follow')->alias('m')
			->join(config('database.prefix').'user u', 'u.id=m.user_id', 'LEFT')
			->join(config('database.prefix').'customer c', 'c.id=m.customer_id', 'LEFT');
		if(!empty($search['keyword'])){
			$db_bulletin->where(function($query) use ($search){
				$query->whereOr([
					'm.title' => ['like', '%'.$search['keyword'].'%'],
					'c.name'  => ['like', '%'.$search['keyword'].'%'],
				]);
			});
		}
		$where       = [];
		$db_bulletin = $db_bulletin->field('m.*,u.realname,c.name')->where($where)->order('m.id DESC');
		$list        = $this->dwzPaging($db_bulletin, $pageNum, $numPerPage);
		foreach($list as $k => $v){
		}
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list' => $list,
		]]);
	}
	
	/**
	 * 跟进-新增
	 */
	public function follow_add(){
		if($this->request->isPost()){
			$this->request->post(['user_id' => $this->user_id]);
			$result = model('CustomerFollow')->allowField(true)->save($this->request->post());
			if(!$result) return $this->dwzReturn(300);
			return $this->dwzReturn(200, '', ['url' => 'follow_list']);
		}
		/* 视图 */
		return $this->fetch('', ['data' => []]);
	}
	
	/**
	 * 合同-列表
	 */
	public function contract_list($pageNum = 1, $numPerPage = null, $search = []){
		/* 获取数据 */
		$db_bulletin = db('customer_contract')->alias('m')
			->join(config('database.prefix').'customer c', 'c.id=m.customer_id', 'LEFT');
		if(!empty($search['keyword'])){
			$db_bulletin->where(function($query) use ($search){
				$query->whereOr([
					'm.title' => ['like', '%'.$search['keyword'].'%'],
					'c.name'  => ['like', '%'.$search['keyword'].'%'],
				]);
			});
		}
		$where       = [];
		$db_bulletin = $db_bulletin->field('m.*,c.name')->where($where)->order('m.id DESC');
		$list        = $this->dwzPaging($db_bulletin, $pageNum, $numPerPage);
		foreach($list as $k => $v){
			$list[$k]['file_name']      = service('Transmit')->file($v['file_id'], 'name');
			$list[$k]['file_save_name'] = service('Transmit')->file($v['file_id'], 'save_name');
		}
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list' => $list,
		]]);
	}
	
	/**
	 * 合同-新增
	 */
	public function contract_add(){
		if($this->request->isPost()){
			$this->request->post(['user_id' => $this->user_id]);
			$result = model('CustomerFollow')->allowField(true)->save($this->request->post());
			if(!$result) return $this->dwzReturn(300);
			return $this->dwzReturn(200, '', ['url' => 'follow_list']);
		}
		/* 视图 */
		return $this->fetch('', ['data' => []]);
	}
	
}