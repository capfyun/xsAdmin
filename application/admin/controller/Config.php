<?php
/**
 * 系统配置
 * @author xs
 */
namespace app\admin\controller;

use xs\Helper;

class Config extends \app\common\controller\AdminBase{
	
	/**
	 * 配置-列表
	 */
	public function lists(){
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
			->paginate(['query' => array_filter(input())]);
		
		$group_list    = config('config_group');
		$status_format = [0 => '禁用', 1 => '启用'];
		foreach($paging as $k => $v){
			$v['group_format']  = isset($group_list[$v['group']]) ? $group_list[$v['group']] : '-';
			$v['type_format']   = \xs\Config::typeAttr($v['type']);
			$v['status_format'] = isset($status_format[$v['status']]) ? $status_format[$v['status']] : '-';
			$v['value']         = htmlspecialchars(Helper::msubstr($v['value'], 0, 90));
			$paging->offsetSet($k, $v);
		}
		
		//视图
		cookie('forward', request()->url());
		return $this->fetch('', [
			'paging' => $paging
		]);
	}
	
	/**
	 * 新增编辑配置
	 */
	public function addedit(){
		if(!$this->request->isPost()){
			$config = model('Config')->get(input('id'));
			//视图
			return $this->fetch('', [
				'info' => $config,
			]);
		}
		$param = $this->param([
			'id'             => ['integer', 'egt' => 0],
			'title|名称'       => ['require', 'length' => '1,20'],
			'name|键'         => ['require', 'alphaDash', 'length' => '1,50', 'unique:config'],
			'group|分组'       => ['require', 'integer', 'egt' => 0],
			'type|键类型'       => ['require', 'in' => array_keys(\xs\Config::typeAttr())],
			'value|值'        => [],
			'validate|验证规则'  => ['length' => '1,250'],
			'extra|额外参数'     => [],
			'description|描述' => [],
			'sort|排序'        => ['integer', 'between' => '0,9999'],
			'status|状态'      => ['require', 'integer', 'between' => '0,1'],
		]);
		$param===false && $this->error($this->getError());
		$result = model('Config')
			->allowField(true)
			->isUpdate($param['id'] ? true : false)
			->save($param);
		$result || $this->error();
		//初始化配置
		\xs\Config::load(true);
		$this->success('操作成功', cookie('forward'));
	}
	
	/**
	 * 简易设定
	 */
	public function setting(){
		if(!$this->request->isPost()){
			$list = model('Config')
				->where(['status' => 1, 'group' => input('group', 1)])
				->order('sort DESC,id DESC')
				->select();
			foreach($list as $k => $v){
				$v['type']=='editor' && $list[$k]['value'] = htmlspecialchars_decode($v['value']);
			}
			//视图
			return $this->fetch('', [
				'list' => $list,
			]);
		}
		$param = $this->param([
			'config' => ['require', 'array'],
		]);
		//校验
		$rule   = model("Config")
			->where(['name' => ['IN', array_keys($param['config'])], 'validate' => db()->raw('!=""')])
			->column('validate', 'CONCAT(`name`,"|",`title`)');
		$result = $this->validate($param['config'], $rule);
		if($result!==true){
			$this->error($result);
		}
		//更新
		$sql = " CASE `name` ";
		foreach($param['config'] as $k => $v){
			$value = '';
			if(is_array($v)){
				$i = 0;
				foreach($v as $k1 => $v1){
					if(isset($v1['value']) && $v1['value']){
						$value .= ($i++==0 ? '' : ',').(isset($v1['key']) && $v1['key'] ? preg_replace('/[,:;\r\n]+/', '', $v1['key']).':' : '').preg_replace('/[,:;\r\n]+/', '', $v1['value']);
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
		\xs\Config::load(true);
		$this->success('操作成功');
	}
	
}