<?php
/**
 * 系统配置
 * @author 夏爽
 */
namespace app\admin\controller;

class Config extends \app\common\controller\AdminBase{
	
	/**
	 * 配置-列表
	 */
	public function config_list(){
		$keyword = [
			'title' => ['title' => ['LIKE', '%'.input('keyword').'%']],
			'name'  => ['name' => ['LIKE', '%'.input('keyword').'%']],
		];
		$where   = [];
		input('keyword')!='' && isset($keyword[input('target')]) && $where = array_merge($where, $keyword[input('target')]);
		input('status')!='' && $where['status'] = input('status');
		
		$paging = model('Config')
			->where($where)
			->order(input('order') ? : 'sort DESC,id DESC')
			->paginate(['query' => array_filter(input()),]);
		
		$group_list    = config('config_group');
		$type_format   = [1 => '字符串', 2 => '数组', 3 => '枚举'];
		$status_format = [0 => '禁用', 1 => '启用'];
		foreach($paging as $k => $v){
			$v['group_format']  = isset($group_list[$v['group']]) ? $group_list[$v['group']] : '-';
			$v['type_format']   = isset($type_format[$v['type']]) ? $type_format[$v['type']] : '-';
			$v['status_format'] = isset($status_format[$v['status']]) ? $status_format[$v['status']] : '-';
			$paging->offsetSet($k, $v);
		}
		
		//视图
		cookie('forward', request()->url());
		return $this->fetch('', [
			'paging' => $paging,
		]);
	}
	
	/**
	 * 新增编辑配置
	 */
	public function config_addedit(){
		if(!$this->request->isPost()){
			$config = model('Config')->get(input('id'));
			//视图
			return $this->fetch('', [
				'info' => $config,
			]);
		}
		$param = $this->param([
			'id'             => ['number', 'min' => 0],
			'title|名称'       => ['require', 'length' => '1,20'],
			'name|键'         => ['require', 'length' => '1,50', 'unique:config'],
			'group|分组'       => ['require', 'number', 'min' => 0],
			'type|键类型'       => ['require', 'number', 'between' => '1,3'],
			'value|值'        => [],
			'extra|额外参数'     => [],
			'description|描述' => [],
			'sort|排序'        => ['number', 'between' => '0,9999'],
			'status|状态'      => ['require', 'number', 'between' => '0,1'],
		]);
		$param===false && $this->error($this->getError());
		$result = model('Config')
			->allowField(true)
			->isUpdate($param['id'] ? true : false)
			->save($param);
		$result || $this->error();
		//初始化配置
		model('Config')->load(true);
		$this->success('操作成功', cookie('forward'));
	}
	
	/**
	 * 简易设定
	 */
	public function simple_setting(){
		if(!$this->request->isPost()){
			$list = model("Config")
				->where(['status' => 1, 'group' => input('group', 1)])
				->order('sort DESC')
				->select();
			//视图
			return $this->fetch('', [
				'list' => $list,
			]);
		}
		$param = $this->param([
			'config' => ['require', 'array'],
		]);
		//更新
		$sql = " CASE `name` ";
		foreach($param['config'] as $k => $v){
			$value = '';
			if(is_array($v)){
				$i = 0;
				foreach($v as $k1 => $v1){
					if(isset($v1['value']) && $v1['value']){
						$value .= ($i++==0 ? '' : ',').(isset($v1['key']) && $v1['key'] ? $v1['key'].':' : '').$v1['value'];
					}
				}
			}else{
				$value = $v;
			}
			$sql .= " WHEN '{$k}' THEN '{$value}' ";
		}
		$sql .= " END ";
		$result = model('Config')
			->where(['name' => ['IN', array_keys($param['config'])]])
			->update(['value' => db()->raw($sql)]);
		$result || $this->error('操作失败');
		//初始化配置
		model('Config')->load(true);
		$this->success('操作成功');
	}
	
}