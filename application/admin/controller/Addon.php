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
		//获取文件列
		$files = new \FilesystemIterator(ADDON_PATH, \FilesystemIterator::KEY_AS_FILENAME);
		
		$list      = [];
		$has_class = [];
		foreach($files as $k => $v){
			if(!$v->isDir()){
				continue;
			}
			$class = \lib\Addon::getClass($k);
			if(!class_exists($class)){
				throw new \Exception("插件类不存在：{$class}");
			}
			$addon = model('Addon')->get(['name' => $k]);
			if($addon){
				$addon->status_format = $addon->status ? '启用【已安装】' : '禁用【已安装】';
				$list[]               = $addon->toArray();
			}else{
				$list[] = [
					'id'            => 0,
					'name'          => $k,
					'title'         => $class::title(),
					'description'   => $class::description(),
					'author'        => $class::author(),
					'version'       => $class::version(),
					'sort'          => 0,
					'status_format' => '禁用【未安装】',
				];
			}
			$has_class[] = $k;
		}
		//排序
		array_multisort(array_column($list, 'sort'), SORT_DESC, $list);
		//异常插件
		$addons = model('Addon')
			->where(['name' => ['NOT IN', $has_class]])
			->order('sort DESC')
			->select();
		
		foreach($addons as $k => $v){
			$v['status_format'] = '包不存在【异常】';
			$list[]             = $v;
		}
		
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
			$addon = model('Addon')->get(['name' => input('name')]);
			$addon || $this->error('该插件还未安装');
			$class = \lib\Addon::getClass($addon->name);
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
			'id'             => ['require', 'integer', 'egt' => 0],
			'title|名称'       => ['require', 'max' => 50],
			'author|作者'      => ['max' => 50],
			'version|版本'     => ['max' => 50],
			'description|描述' => [],
			'sort|排序'        => ['integer', 'between' => '0,9999'],
			'status|状态'      => ['require', 'integer', 'between' => '0,1'],
			'config|配置'      => ['array'],
		]);
		$param===false && $this->error($this->getError());
		//校验
		$addon = model('Addon')->get($param['id']);
		$addon || $this->error('插件不存在');
		$class = \lib\Addon::getClass($addon->name);
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
	 * 安装
	 */
	public function install(){
		//类不存在
		$class = \lib\Addon::getClass(input('name'));
		!class_exists($class) && $this->apiReturn(['msg' => "插件类不存在： {$class}"]);
		//已安装
		$addon = model('Addon')->get(['name' => input('name')]);
		$addon && $this->apiReturn(['msg' => '该插件已安装']);
		//安装
		db()->startTrans();
		$result = $class::install();
		if(!$result){
			db()->rollback();
			$this->apiReturn(['msg' => $class::getError()]);
		}
		$result = model('Addon')->allowField(true)->isUpdate(false)
			->save([
				'name'        => input('name'),
				'title'       => $class::title(),
				'description' => $class::description(),
				'author'      => $class::author(),
				'version'     => $class::version(),
			]);
		if(!$result){
			db()->rollback();
			$this->apiReturn(['msg' => model('Addon')->getError()]);
		}
		db()->commit();
		$this->apiReturn(['code' => 0, 'msg' => '安装成功']);
	}
	
	/**
	 * 卸载
	 */
	public function uninstall(){
		//未安装
		$addon = model('Addon')->get(['name' => input('name')]);
		!$addon && $this->apiReturn(['msg' => '该插件未安装']);
		//卸载
		db()->startTrans();
		$class = \lib\Addon::getClass(input('name'));
		if(class_exists($class)){
			$result = $class::uninstall();
			if(!$result){
				db()->rollback();
				$this->apiReturn(['msg' => $class::getError()]);
			}
		}
		$result = model('Addon')->destroy(['name' => input('name')]);
		if(!$result){
			db()->rollback();
			$this->apiReturn(['msg' => model('Addon')->getError()]);
		}
		db()->commit();
		$this->apiReturn(['code' => 0, 'msg' => '卸载成功']);
	}
}