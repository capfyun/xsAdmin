<?php
/**
 * 服务层-图片
 * @author 夏爽
 */
namespace app\common\service;

class Image extends Base{
	
	protected $ext = [
		'gif'  => 1,
		'jpg'  => 2,
		'jpeg' => 2,
		'png'  => 3,
		'bmp'  => 6,
	];
	
	/**
	 * 打印图片
	 * @param string $path 图片地址
	 * @return bool
	 */
	public function printImage($path = ''){
		$ext = $this->checkImg($path);
		if(!$ext) return false;
		
		switch($ext){
			case 'gif':
				header("Content-type: image/gif");
				break;
			case 'jpg':
				header("Content-type: image/jpg");
				break;
			case 'jpeg':
				header("Content-type: image/jpeg");
				break;
			case 'png':
				header("Content-type: image/png");
				break;
			case 'bmp':
				header("Content-type: image/bmp");
				break;
			default:
				return false;
		}
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
		if(!file_exists($path)) return false;
		/* 对图像文件进行严格检测 */
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		if(!isset($this->ext[$ext]) || $this->ext[$ext] != $this->getImageType($path)){
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
	
}
