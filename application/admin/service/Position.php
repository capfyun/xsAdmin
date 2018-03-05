<?php
/**
 * 服务层-组织架构
 * @author 夏爽
 */
namespace app\admin\service;

class Position extends \app\common\service\Base{
	
	/**
	 * 格式化组织架构
	 * @param bool $auth
	 * @return array
	 */
	public function positionList($auth = false){
		$list = db('position')->field('*')->order('sort ASC')->limit(1000)->select();
		//整理为递归数组
		$list = service('Tool')->sortArraySon($list);
		return $this->positionNameFormat($list);
	}
	
	/**
	 * 职位名称格式化
	 * @param array $list 需整理传入数据
	 * @param int $i 标识
	 * @return array
	 */
	public function positionNameFormat($list, $i = 1){
		$data = [];
		foreach($list as $k => $v){
			$html = '';
			for($m = 1; $m<$i; $m++) $html .= '　　';
			if($i!=1) $html .= '├─';
			$v['name_format'] = $html.$v['name'];
			$vo               = $v;
			unset($vo['list']);
			$data[] = $vo;
			$data   = array_merge($data, $this->positionNameFormat($v['list'], $i+1));
		}
		return $data;
	}
	
	
}