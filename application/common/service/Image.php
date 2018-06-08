<?php
/**
 * 图片
 * @author xs
 */
namespace app\common\service;

use xs\Upload;

class Image extends Base{
	
	protected $ext = [
		'gif'  => 1,
		'jpg'  => 2,
		'jpeg' => 2,
		'png'  => 3,
		'bmp'  => 6,
	];
	
	/**
	 * 生成缩略图
	 * @param string $path 图片路径，可以是远程图
	 * @param int $width 图片宽度
	 * @param int $height 图片高度
	 * @return string WEB图片路径名称
	 */
	public function createThumb($file, $width = 0, $height = 0){
		if($file=='' || $width<=0 || $height<=0){
			$this->error = '参数错误';
			return false;
		}
		if(!class_exists('\think\Image')){
			$this->error = '缺少\think\Image类库';
			return false;
		}
		//缩略图文件名
		$base_path = RUNTIME_PATH.'thumb'.DS;
		$dir_name  = "w{$width}h{$height}/";
		$file_name = md5($file).'.'.pathinfo($file, PATHINFO_EXTENSION);
		$full_name = $base_path.$dir_name.$file_name;
		
		//已存在文件时直接返回
		if(is_file($full_name)){
			return $full_name;
		}
		
		//生成缩略图
		$is_rm = 0;
		//缓存远程图片
		if(strpos($file, "http")===0){
			$uploader = new Upload([
				'save_path' => RUNTIME_PATH,
				'auto_sub'  => false,
				'hash'      => false,
				'replace'   => true,
				'save_name' => ['md5', $file],
			]);
			$info     = $uploader->grab($file);
			if(!$info){
				$this->error = $uploader->getError();
				return false;
			}
			$info  = array_shift($info);
			$file  = $info['save_path'].$info['save_name'];
			$is_rm = 1;
		}
		//原文件不存在
		if(is_file($file)==false){
			$this->error = '原始文件不存在';
			return false;
		}
		if(!$this->mkdir($base_path.$dir_name)){
			return false;
		}
		$image = \think\Image::open($file);
		$image->thumb($width, $height, \think\Image::THUMB_CENTER)->save($full_name);
		//删除原始文件
		$is_rm && @unlink($file);
		return $full_name;
	}
	
	/**
	 * 打印图片
	 * @param string $path 图片地址，可以是远程图片
	 * @return false
	 */
	public function output($path = '', $mime = ''){
		if(!$mime){
			$image = @getimagesize($path);
			if(!$image){
				$this->error = '无效的图像文件';
				return false;
			}
			$mime = $image['mime'];
		}
		header("Content-type: ".$mime);
		//擦除缓冲区
		ob_end_clean();
		//载入图片
		readfile($path);
		exit;
	}
	
	/**
	 * 检测图像文件
	 * @return bool
	 */
	public function checkImg($path = ''){
		if(!file_exists($path)){
			return false;
		}
		/* 对图像文件进行严格检测 */
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		if(!isset($this->ext[$ext]) || $this->ext[$ext]!=$this->getImageType($path)){
			return false;
		}
		return $ext;
	}
	
	/**
	 * 判断图像类型
	 * @param $image
	 * @return int
	 */
	protected function getImageType($image){
		if(function_exists('exif_imagetype')){
			return exif_imagetype($image);
		}else{
			$info = getimagesize($image);
			return $info[2];
		}
	}
	
	/**
	 * 创建目录
	 * @param  string $save_path 要创建的目录
	 * @return boolean 创建状态，true-成功，false-失败
	 */
	private function mkdir($dir){
		if(is_dir($dir)){
			return true;
		}
		if(mkdir($dir, 0777, true)){
			return true;
		}else{
			$this->error = "目录 {$dir} 创建失败！";
			return false;
		}
	}
}
