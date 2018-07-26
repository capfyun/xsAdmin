<?php
/**
 * 图片
 * @author xs
 */
namespace lib;

use lib\Upload;

class Image{
	
	/**
	 * 生成缩略图
	 * @param string $path 图片路径，可以是远程图
	 * @param int $width 图片宽度
	 * @param int $height 图片高度
	 * @return string WEB图片路径名称
	 */
	public static function createThumb($file, $width = 0, $height = 0){
		if($file=='' || $width<=0 || $height<=0){
			throw new \Exception('参数错误');
		}
		if(!class_exists('\think\Image')){
			throw new \Exception('缺少\think\Image类库');
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
			$info     = $uploader->upload($file,'remote');
			if(!$info){
				throw new \Exception($uploader->getError());
			}
			$info  = array_shift($info);
			$file  = $info['save_path'].$info['save_name'];
			$is_rm = 1;
		}
		//原文件不存在
		if(is_file($file)==false){
			throw new \Exception('原始文件不存在');
		}
		//创建目录
		$dir = $base_path.$dir_name;
		if(!is_dir($dir) && !mkdir($dir, 0777, true)){
			throw new \Exception("目录 {$dir} 创建失败！");
		}
		//生成
		$image = \think\Image::open($file);
		$image->thumb($width, $height, \think\Image::THUMB_CENTER)->save($full_name);
		//删除原始文件
		$is_rm && @unlink($file);
		return $full_name;
	}
	
	/**
	 * 打印图片
	 * @param string $path 图片地址，可以是远程图片
	 * @param string $mime 图片mime格式
	 * @return void
	 */
	public static function output($path = '', $mime = ''){
		if(!$mime){
			$image = @getimagesize($path);
			!$image && exit('无效的图像文件');
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
	 * @return false|string
	 */
	public static function checkImg($path = ''){
		if(!file_exists($path)){
			return false;
		}
		//对图像文件进行严格检测
		$ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		$exts = ['gif' => 1, 'jpg' => 2, 'jpeg' => 2, 'png' => 3, 'bmp' => 6,];
		if(!isset($exts[$ext])){
			return false;
		}
		if(function_exists('exif_imagetype')){
			$image_type = exif_imagetype($path);
		}else{
			$info       = getimagesize($path);
			$image_type = $info[2];
		}
		if($exts[$ext]!=$image_type){
			return false;
		}
		return $ext;
	}
	
}
