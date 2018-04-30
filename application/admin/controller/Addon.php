<?php
/**
 * 插件
 * @author 夏爽
 */
namespace app\admin\controller;

class Addon extends \app\common\controller\AdminBase{
	
	/**
	 * 备份文件列表
	 */
	public function addon_list(){
		$path = config('addon_path');
		//创建目录
		if(!is_dir($path) && !mkdir($path, 0777, true)){
			$this->error("目录 {$path} 创建失败！");
		}
		//检测目录是否可写
		if(!is_writable($path)){
			$this->error("目录 {$path} 不可写！");
		}
		//获取文件列
		$files = new \FilesystemIterator($path, \FilesystemIterator::KEY_AS_FILENAME);
		
		$list      = [];
		$has_class = [];
		foreach($files as $k => $v){
			if(!$v->isDir()){
				continue;
			}
			$class = model('Addon')->getClass($k);
			if(!class_exists($class)){
				throw new \Exception("插件类不存在：{$class}");
			}
			$addon = model('Addon')->get(['name' => $k]);
			if($addon){
				$addon->status_format = $addon->status ? '启用【已安装】' : '禁用【已安装】';
				$list[]               = $addon->toArray();
			}else{
				$info   = $class::info();
				$list[] = [
					'id'            => 0,
					'name'          => $k,
					'title'         => $info['title'],
					'description'   => $info['description'],
					'author'        => $info['author'],
					'version'       => $info['version'],
					'sort'          => 0,
					'status_format' => '禁用【未安装】',
				];
			}
			$has_class[] = $k;
		}
		//排序
		array_multisort(array_column($list, 'sort'), SORT_DESC, $list);
		//异常插件
		model('Addon')
			->where(['name' => ['NOT IN', $has_class]])
			->order('sort DESC')
			->select()
			->each(function($item) use (&$list){
				$item['status_format'] = '包不存在【异常】';
				$list[]                = $item;
				return $item;
			});
		//视图
		cookie('forward', request()->url());
		return $this->fetch('', [
			'paging' => $list,
		]);
	}
	
	/**
	 * 配置插件
	 */
	public function addon_edit(){
		if(!$this->request->isPost()){
			$addon = model('Addon')->get(['name' => input('name')]);
			$addon || $this->error('该插件还未安装');
			$class = model('Addon')->getClass($addon->name);
			class_exists($class) || $this->error("插件类不存在： {$class}");
			$option = $class::option();
			$config = $class::config();
			//视图
			return $this->fetch('', [
				'info'   => $addon,
				'option' => $option,
				'config' => $config,
			]);
		}
		$param = $this->param([
			'id'             => ['require', 'number', 'min' => 0],
			'title|名称'       => ['require', 'max' => 5],
			'author|作者'      => ['max' => 5],
			'version|版本'     => ['max' => 5],
			'description|描述' => [],
			'sort|排序'        => ['between' => '0,9999'],
			'status|状态'      => ['require', 'number', 'between' => '0,1'],
			'config|配置'      => ['array'],
		]);
		$param===false && $this->error($this->getError());
		//校验
		$addon = model('Addon')->get($param['id']);
		$addon || $this->error('插件不存在');
		$class = model('Addon')->getClass($addon->name);
		class_exists($class) || $this->error("插件类不存在： {$class}");
		//校验配置
		if($param['config'] && $coption = $class::option()){
			$validate = [];
			foreach($coption as $k => $v){
				isset($v['validate'])
					&& $validate[$k.(isset($v['name']) ? '|'.$v['name'] : '')] = $v['validate'];
			}
			$result = $this->validate($param['config'], $validate);
			$result!==true && $this->error($result);
		}
		$param['config'] = $param['config'] ? json_encode($param['config']) : '';
		//入库
		$result = model('Addon')
			->allowField(true)
			->isUpdate(true)
			->save($param, ['id' => $addon->id]);
		$result || $this->error();
		$this->success('操作成功', cookie('forward'));
	}
	
	/**
	 * 插件安装
	 */
	public function addon_install(){
		$class = model('Addon')->getClass(input('name'));
		!class_exists($class) && $this->apiReturn(['msg' => "插件类不存在： {$class}"]);
		
		$addon = model('Addon')->get(['name' => input('name')]);
		$addon && $this->apiReturn(['msg' => '该插件已安装']);
		
		$data         = $class::info();
		$data['name'] = input('name');
		//安装
		db()->startTrans();
		$result = model('Addon')->allowField(true)->isUpdate(false)->save($data);
		if(!$result){
			db()->rollback();
			$this->apiReturn(['msg' => model('Addon')->getError()]);
		}
		$result = $class::install();
		if(!$result){
			db()->rollback();
			$this->apiReturn(['msg' => $class::getError()]);
		}
		db()->commit();
		$this->apiReturn(['code' => 0, 'msg' => '安装成功']);
	}
	
	/**
	 * 插件卸载
	 */
	public function addon_uninstall(){
		$addon = model('Addon')->get(['name' => input('name')]);
		!$addon && $this->apiReturn(['msg' => '该插件未安装']);
		
		//卸载
		db()->startTrans();
		$result = model('Addon')->destroy(['name' => input('name')]);
		if(!$result){
			db()->rollback();
			$this->apiReturn(['msg' => model('Addon')->getError()]);
		}
		$class = model('Addon')->getClass(input('name'));
		if(class_exists($class)){
			$result = $class::uninstall();
			if(!$result){
				db()->rollback();
				$this->apiReturn(['msg' => $class::getError()]);
			}
		}
		db()->commit();
		$this->apiReturn(['code' => 0, 'msg' => '卸载成功']);
	}
}