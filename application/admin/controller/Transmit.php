<?php
/**
 * 传输
 * @author 夏爽
 */
namespace app\admin\controller;

class Transmit extends \app\common\controller\AdminBase{
	
	/**
	 * 上传，支持多文件多类型
	 */
	public function upload(){
		//进行上传
		$data = model('File')->upload();
		!$data && $this->apiReturn(['code' => 1000, 'msg' => model('File')->getError()]);
		foreach($data as $k => $v){
			$data[$k]['url'] = model('File')->url($v['id']);
		}
		//上传成功
		$this->apiReturn(['code' => 0, 'msg' => '上传成功！', 'data' => $data]);
	}
	
	/**
	 * 下载
	 */
	public function download(){
		$result = model('File')->download(input('name'));
		$result || abort(404, model('File')->getError());
	}
	
}
