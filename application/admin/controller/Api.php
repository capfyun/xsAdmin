<?php
/**
 * 控制器-接口
 * @author 夏爽
 */
namespace app\admin\controller;

class Api extends \app\common\controller\BaseAdmin{
	
	/**
	 * 接口列表
	 * 参数 $pageNum=页码数 $numPerPage=每页数据条数 $search=搜索
	 */
	public function api_list($pageNum = 1, $numPerPage = null, $search = array()){
		/* 获取数据 */
		$m_api = model('Api');
		if(!empty($search['keyword'])){
			$m_api->where(function($query) use ($search){
				$query->whereOr([
					'id'     => ['eq', $search['keyword']],
					'url'    => ['like', '%'.$search['keyword'].'%'],
					'name'   => ['like', '%'.$search['keyword'].'%'],
					'author' => ['like', '%'.$search['keyword'].'%'],
				]);
			});
		}
		$where = [];
		if(isset($search['status']) && $search['status']!='') $where['status'] = $search['status']; //状态
		$list = $this->dwzPaging($m_api->where($where)->order('id ASC'), $pageNum, $numPerPage);
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list' => $list,
		]]);
	}
	
	/**
	 * 接口调试
	 * @param int $id 数据ID
	 */
	public function api_debug($api_id = 0){
		if($this->request->isPost()){
			$result = service('Api')->apiDebug($api_id, $this->request->post());
			if(!$result){
				return 'error:'.service('Api')->getError();
			}
			return $result;
		}
		/* 获取详情 */
		$info            = model('Api')->get($api_id)->toArray();
		$info['url']     = url($info['url'], '', false, service('Api')->api_url);
		$info['param']   = !empty($info['param']) ? explode(',', $info['param']) : [];
		$info['explain'] = $info['explain'] ? json_decode($info['explain'],true) : [];
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'info' => $info,
		]]);
	}
	
	/**
	 * 错误码列表
	 */
	public function api_error(){
		/* 获取全部错误码 */
		$error_msg = service('Api')->getErrorMsg();
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list' => $error_msg,
		]]);
	}
	
	/**
	 * 接口添加
	 */
	public function api_add(){
		if($this->request->isPost()){
			$result = service('Api')->apiUpdate($this->request->post());
			if(!$result) return $this->dwzReturn(500, service('Api')->getError());
			return $this->dwzReturn(200);
		}
		/* 视图 */
		return $this->fetch();
	}
	
	/**
	 * 接口编辑
	 * @param int $id 数据ID
	 */
	public function api_edit($id = 0){
		if($this->request->isPost()){
			$result = service('Api')->apiUpdate($this->request->post());
			if(!$result) return $this->dwzReturn(500, service('Api')->getError());
			return $this->dwzReturn(200);
		}
		/* 获取详情 */
		$info = model('Api')->get($id);
		/* 视图 */
		return $this->fetch('', ['data' => [
			'info' => $info,
		]]);
	}
	
	/**
	 * 删除接口
	 * @param string $ids 数据集
	 */
	public function api_del($ids = ''){
		return $this->delete('api', $ids);
	}
	
}