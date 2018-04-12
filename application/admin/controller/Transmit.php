<?php
/**
 * 控制器-传输
 * @author 夏爽
 */
namespace app\admin\controller;

class Transmit extends \app\common\controller\AdminBase{
	
	/**
	 * 上传，支持多文件多类型
	 * @return array [['type' => '类型', 'url' => '路径', 'id' => 'ID']]
	 */
	public function upload(){
		$s_transmit = service('Transmit');
		//校验
		$result = $s_transmit->checkFile();
		if(!$result){
			return $this->ajaxReturn(['code' => 100, 'msg' => $s_transmit->getError()]);
		}
		//进行上传
		$data = $s_transmit->upload();
		if(!$data){
			return $this->ajaxReturn(['code' => 100, 'msg' => $s_transmit->getError()]);
		}
		/* 上传成功 */
		return $this->ajaxReturn(['code' => 0, 'msg' => '上传成功！', 'data' => $data]);
	}
	
	/**
	 * 下载
	 * @param string $savename 文件名
	 * @param string $type 下载类型
	 */
	public function download($name = '', $type = ''){
		$s_transmit = service('Transmit');
		//自定义查询条件
		if(!empty($type)) $name = [$type => $name];
		$result = $s_transmit->download($name);
		if(!$result){
			$this->error($s_transmit->getError(), url('admin/index/index'));
		}
	}
	
	/**
	 * 编辑器上传
	 * @param string $type 上传类型
	 */
	public function upload_editor($type = ''){
		if(!$type){
			return $this->ajaxReturn(['err' => '类型不能为空', 'msg' => '']);
		}
		//校验
		$s_transmit = service('Transmit');
		$result     = $s_transmit->checkType($type);
		if(!$result){
			return $this->ajaxReturn(['err' => $s_transmit->getError(), 'msg' => '']);
		}
		//进行上传
		$files = $this->request->file('filedata'); //获取表单上传文件
		$data  = $s_transmit->upLocalFile($files, $type);
		if(!$data){
			return $this->ajaxReturn(['err' => $s_transmit->getError(), 'msg' => '']);
		}
		//上传成功
		return $this->ajaxReturn(['err' => '', 'msg' => $data['url']]);
	}
	
}
