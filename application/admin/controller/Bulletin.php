<?php
/**
 * 控制器-公告
 * @author 夏爽
 */
namespace app\admin\controller;

use think\Db;
use think\Session;

class Bulletin extends \app\common\controller\BaseAdmin{
	
	/**
	 * 公告-列表
	 * 参数 $pageNum=页码数 $numPerPage=每页数据条数 $search=搜索
	 */
	public function bulletin_list($pageNum = 1, $numPerPage = null, $search = []){
		/* 获取数据 */
		$db_bulletin = db('bulletin')->alias('m')
			->join(config('database.prefix').'bulletin_category c', 'm.category_id=c.id', 'LEFT')
			->join(config('database.prefix').'user u', 'u.id=m.user_id', 'LEFT');
		if(!empty($search['keyword'])){
			$db_bulletin->where(function($query) use ($search){
				$query->whereOr([
					'm.id'    => ['eq', $search['keyword']],
					'm.title' => ['like', '%'.$search['keyword'].'%'],
				]);
			});
		}
		$where       = [];
		$db_bulletin = $db_bulletin->field(['m.*,c.name AS category_name,u.realname'])->where($where)->order('m.top DESC,m.id DESC');
		$list        = $this->dwzPaging($db_bulletin, $pageNum, $numPerPage);
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list' => $list,
		]]);
	}
	
	/**
	 * 公告-新增
	 */
	public function bulletin_add(){
		if($this->request->isPost()){
			$this->request->post(['user_id' => $this->user_id]);
			$result = model('Bulletin')->allowField(true)->save($this->request->post(), ['id' => $id]);
			if(!$result) return $this->dwzReturn(300);
			return $this->dwzReturn(200, '', ['url' => 'bulletin_list']);
		}
		//分类
		$category_list = db('bulletin_category')->where(['status' => 1])->column('name', 'id');
		/* 视图 */
		return $this->fetch('', ['data' => [
			'category_list' => $category_list,
		]]);
	}
	
	/**
	 * 公告-编辑
	 */
	public function bulletin_edit($id = 0){
		if($this->request->isPost()){
			$this->request->post(['user_id' => $this->user_id]);
			$result = model('Bulletin')->allowField(true)->save($this->request->post(), ['id' => $id]);
			if(!$result) return $this->dwzReturn(300);
			return $this->dwzReturn(200, '', ['url' => 'bulletin_list']);
		}
		//详情
		$info = model('Bulletin')->get(['id' => $id]);
		//分类
		$category_list = db('bulletin_category')->where(['status' => 1])->column('name', 'id');
		/* 视图 */
		return $this->fetch('', ['data' => [
			'info'          => $info,
			'category_list' => $category_list,
		]]);
	}
	
	/**
	 * 公告-详情
	 */
	public function bulletin_info($id = 0){
		//详情
		$info = db('bulletin', '', false)->alias('m')
			->join(config('database.prefix').'bulletin_category c', 'm.category_id=c.id', 'LEFT')
			->join(config('database.prefix').'user u', 'm.user_id=u.id', 'LEFT')
			->where(['m.id' => $id])
			->field('m.*,c.name AS category_name,u.realname')
			->find();
		/* 视图 */
		return $this->fetch('', ['data' => [
			'info' => $info,
		]]);
	}
	
	/**
	 * 公告-详情内容
	 */
	public function bulletin_info_content($id = 0){
		//详情
		return db('bulletin', '', false)->where(['id' => $id])->value('content');
	}
	
	/**
	 * 公告-分类
	 * 参数 $pageNum=页码数 $numPerPage=每页数据条数 $search=搜索
	 */
	public function category_list($pageNum = 1, $numPerPage = null, $search = []){
		/* 获取数据 */
		$db_bulletin = db('bulletin_category');
		if(!empty($search['keyword'])){
			$db_bulletin->where(function($query) use ($search){
				$query->whereOr([
					'name' => ['like', '%'.$search['keyword'].'%'],
				]);
			});
		}
		$where       = [];
		$db_bulletin = $db_bulletin->field('*')->where($where)->order('id ASC');
		$list        = $this->dwzPaging($db_bulletin, $pageNum, $numPerPage);
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list' => $list,
		]]);
	}
	
	/**
	 * 分类-新增
	 */
	public function category_add(){
		if($this->request->isPost()){
			$result = model('BulletinCategory')->allowField(true)->save($this->request->post());
			if(!$result) return $this->dwzReturn(300);
			return $this->dwzReturn(200, '', ['url' => 'category_list']);
		}
		/* 视图 */
		return $this->fetch('', ['data' => []]);
	}
	
	/**
	 * 分类-编辑
	 */
	public function category_edit($id = 0){
		if($this->request->isPost()){
			$result = model('BulletinCategory')->allowField(true)->save($this->request->post(), ['id' => $id]);
			if(!$result) return $this->dwzReturn(300);
			return $this->dwzReturn(200, '', ['url' => 'category_list']);
		}
		//详情
		$info = db('BulletinCategory')->where(['id' => $id])->find();
		/* 视图 */
		return $this->fetch('', ['data' => [
			'info' => $info,
		]]);
	}
	
	/**
	 * 删除公告
	 * @return array
	 */
	public function bulletin_del(){
		$ids = $this->request->param('ids', '');
		if(empty($ids)) return $this->dwzReturn(300, '请选择要操作的数据!');
		$result = \think\Db::name('bulletin')->where(['id' => ['in', $ids]])->delete();
		if(!$result) return $this->dwzReturn(300);
		return $this->dwzReturn(200, null, ['callbackType' => '']);
	}
	
}