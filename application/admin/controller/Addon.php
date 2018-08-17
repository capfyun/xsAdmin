<?php
/**
 * 插件
 * @author xs
 */
namespace app\admin\controller;

class Addon extends \app\common\controller\AdminBase{
	
	/**
	 * 备份文件列表
	 */
	public function lists(){
		$list = \lib\Addon::getList();
		//视图
		cookie('forward', request()->url());
		return $this->fetch('', [
			'paging' => $list,
		]);
	}
	
	/**
	 * 配置插件
	 */
	public function edit(){
		if(!$this->request->isPost()){
			$info = \lib\Addon::getInfo(input('name'));
			!$info && $this->error('该插件不存在');
			$info['install']==0 && $this->error('该插件还未安装');
			//视图
			return $this->fetch('', [
				'info'   => $info,
				'config' => $info['config'],
			]);
		}
		$param = $this->param([
			'name|package'   => ['require', 'length' => '1,50'],
			'sort|排序'        => ['integer', 'between' => '0,9999'],
			'status|状态'      => ['require', 'integer', 'between' => '0,1'],
			'config|配置'      => ['array'],
		]);
		is_string($param) && $this->error($param);
		//校验
		$info = \lib\Addon::getInfo($param['name']);
		!$info && $this->error('该插件不存在');
		$info['install']==0 && $this->error('该插件还未安装');
		
		//校验配置
		if($param['config'] && $info['option']){
			$validate = [];
			foreach($info['option'] as $k => $v){
				isset($v['validate']) && $validate[$k.(isset($v['name']) ? '|'.$v['name'] : '')] = $v['validate'];
			}
			$result = $this->validate($param['config'], $validate);
			$result!==true && $this->error($result);
		}
		$result = \lib\Addon::setCache($param['name'], $param);
		!$result && $this->error(\lib\Addon::getError());
		$this->success('操作成功', cookie('forward'));
	}
	
	/**
	 * 安装
	 */
	public function install(){
		$name = input('name');
		$info = \lib\Addon::getInfo($name);
		!$info && $this->apiReturn(['msg' => "插件类不存在： {$name}"]);
		$info['install']==1 && $this->error('该插件已安装');
		//安装
		$result = \lib\Addon::install($name);
		!$result && $this->apiReturn(['msg' => \lib\Addon::getError()]);
		$this->apiReturn(['code' => 0, 'msg' => '安装成功']);
	}
	
	/**
	 * 卸载
	 */
	public function uninstall(){
		$name = input('name');
		$info = \lib\Addon::getInfo($name);
		!$info && $this->apiReturn(['msg' => "插件类不存在： {$name}"]);
		$info['install']==0 && $this->error('该插件未安装');
		//卸载
		$result = \lib\Addon::uninstall($name);
		!$result && $this->apiReturn(['msg' => \lib\Addon::getError()]);
		$this->apiReturn(['code' => 0, 'msg' => '卸载成功']);
	}
}