<?php
/**
 * 控制器-系统配置
 * @author 夏爽
 */
namespace app\admin\controller;

class Config extends \app\common\controller\AdminBase{
	
	/**
	 * 配置-列表
	 * 参数 $pageNum=页码数 $numPerPage=每页数据条数 $search=搜索
	 */
	public function config_list($pageNum = 1, $numPerPage = null, $search = []){
		/* 获取数据 */
		$m_config = model('Config');
		if(!empty($search['keyword'])){
			$m_config->where(function($query) use ($search){
				$query->whereOr([
					'id'    => ['eq', $search['keyword']],
					'name'  => ['like', '%'.$search['keyword'].'%'],
					'title' => ['like', '%'.$search['keyword'].'%'],
				]);
			});
		}
		$where = [];
		if(isset($search['status']) && $search['status']!='') $where['status'] = $search['status']; //状态
		$list = $this->dwzPaging($m_config->where($where)->order(['sort' => 'asc', 'id' => 'asc']), $pageNum, $numPerPage);
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list'       => $list,
			'group_list' => config('config_group_list'), //分组配置
		]]);
	}
	
	/**
	 * 配置-新增
	 */
	public function config_add(){
		if($this->request->isPost()){
			$result = model('Config')->allowField(true)->save($this->request->post());
			if(!$result) return $this->dwzReturn(300);
			//操作成功
			service('Config')->saveCache(true); //重置配置
			return $this->dwzReturn(200);
		}
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'group_list' => config('config_group_list') ? : [], //分组配置
		]]);
	}
	
	/**
	 * 配置-编辑
	 * @param int $id 数据ID
	 */
	public function config_edit($id = 0){
		if($this->request->isPost()){
			$result = model('Config')->allowField(true)->save($this->request->post(), ['id' => $id]);
			if(!$result) return $this->dwzReturn(300);
			//操作成功
			service('Config')->saveCache(true); //重置配置
			return $this->dwzReturn(200);
		}
		$info = model('Config')->get($id);
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'info'       => $info, //权限列表
			'group_list' => config('config_group_list') ? : [], //分组配置
		]]);
	}
	
	/**
	 * 配置-设置
	 * @param int $group_id 分组ID
	 */
	public function config_set($group_id = 1, $config = []){
		if($this->request->isPost()){
			if($config && is_array($config)){
				foreach($config as $k => $v){
					model('Config')->allowField(true)->save(['value' => $v], ['name' => $k]);
				}
			}
			//操作成功
			service('Config')->saveCache(true); //重置配置
			return $this->dwzReturn(200);
		}
		$list = model("Config")->where(['status' => 1, 'group' => $group_id])->order('sort')->select();
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list'       => $list, //权限列表
			'group_list' => config('config_group_list'), //分组配置
		]]);
	}
	
	/**
	 * 启用配置
	 * @return array
	 */
	public function config_status_on($ids = ''){
		if(!$ids) return $this->dwzReturn(300, '请选择要操作的数据!');
		$result = db('config')->where(['id' => ['in', $ids]])->update(['status' => 1]);
		if(!$result) return $this->dwzReturn(300);
		//重置配置
		service('Config')->saveCache(true);
		return $this->dwzReturn(200, null, ['callbackType' => '']);
	}
	
	/**
	 * 禁用配置
	 * @return array
	 */
	public function config_status_off($ids = ''){
		if(!$ids) return $this->dwzReturn(300, '请选择要操作的数据!');
		$result = db('config')->where(['id' => ['in', $ids]])->update(['status' => 0]);
		if(!$result) return $this->dwzReturn(300);
		//重置配置
		service('Config')->saveCache(true);
		return $this->dwzReturn(200, null, ['callbackType' => '']);
	}
	
	/**
	 * 删除配置
	 * @return array
	 */
	public function config_del($ids = ''){
		if(!$ids) return $this->dwzReturn(300, '请选择要操作的数据!');
		$result = db('config')
			->where(['id' => ['in', $ids]])
			->delete();
		if(!$result) return $this->dwzReturn(300);
		service('Config')->saveCache(true); //重置配置
		return $this->dwzReturn(200, null, ['callbackType' => '']);
	}
	
}