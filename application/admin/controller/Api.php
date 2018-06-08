<?php
/**
 * 接口
 * @author xs
 */
namespace app\admin\controller;

class Api extends \app\common\controller\AdminBase{
	
	/**
	 * 接口列表
	 */
	public function api_list(){
		$keyword = [
			'url'  => ['url' => ['LIKE', '%'.input('keyword').'%']],
			'name' => ['name' => ['LIKE', '%'.input('keyword').'%']],
		];
		$where   = [];
		input('keyword')!='' && isset($keyword[input('target')]) && $where = array_merge($where, $keyword[input('target')]);
		input('status')!='' && $where['status'] = input('status');
		
		$paging = model('Api')
			->where($where)
			->order(input('order') ? : 'id DESC')
			->paginate();
		
		$status_format     = [0 => '禁用', 1 => '启用'];
		$is_encrypt_format = [0 => '否', 1 => '是'];
		foreach($paging as $k => $v){
			$v['status_format']     = isset($status_format[$v['status']]) ? $status_format[$v['status']] : '-';
			$v['is_encrypt_format'] = isset($is_encrypt_format[$v['is_encrypt']]) ? $is_encrypt_format[$v['is_encrypt']] : '-';
			$paging->offsetSet($k, $v);
		}
		
		//视图
		cookie('forward', request()->url());
		return $this->fetch('', [
			'paging' => $paging,
		]);
	}
	
	/**
	 * 新增编辑接口
	 */
	public function api_addedit(){
		if(!$this->request->isPost()){
			$info = model('Api')->get(input('id'));
			//视图
			return $this->fetch('', [
				'info' => $info,
			]);
		}
		$param = $this->param([
			'id'             => ['integer', 'egt' => 0],
			'name|名称'        => ['require', 'length' => '1,20'],
			'url|地址'         => ['require', 'length' => '1,50'],
			'author|作者'      => ['length' => '1,16'],
			'description|描述' => [],
			'init|初始化'       => ['integer', 'between' => '0,1'],
		]);
		$param===false && $this->error($this->getError());
		$result = model('Api')->apiUpdate($param);
		$result || $this->error(model('Api')->getError());
		$this->success('操作成功', cookie('forward'));
	}
	
	/**
	 * 接口调试
	 */
	public function debug($api_id = 0){
		if(!$this->request->isPost()){
			$api = model('Api')->get($api_id);
			$api || $this->error('接口不存在');
			$api->param   = $api->param ? explode(',', $api->param) : [];
			$api->explain = $api->explain ? json_decode($api->explain, true) : [];
			
			//视图
			return $this->fetch('', [
				'info' => $api,
			]);
		}
		$result = model('Api')->apiDebug($api_id, input());
		if(!$result){
			return 'error:'.model('Api')->getError();
		}
		return $result;
	}
	
	/**
	 * 错误码列表
	 */
	public function code_list(){
		//状态码
		$code = model('Api')->getErrorCode();
		
		//视图
		return $this->fetch('', [
			'list' => $code,
		]);
	}
	
}