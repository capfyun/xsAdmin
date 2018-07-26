<?php
/**
 * 二维码
 * @author xs
 */
namespace lib;

class QRCode{
	/**
	 * 生成二维码
	 * @param string $string 二维码内容
	 * @param resource $logo logo图资源（需要带logo时传该参数）
	 * @param string $level 纠错级别：L、M、Q、H
	 * @param int $size 点的大小：1到10,用于手机端4就可以了
	 * @param int $margin 边框距
	 * @return resource
	 */
	public static function create($string, $logo = false, $level = 'H', $size = 5, $margin = 1){
		//生成二维码图片 无logo
		$image = \lib\qrcode\QRcode::png($string, false,$level, $size, $margin);
		//带logo
		$logo && self::createLogo($image, $logo);
		return $image;
	}
	
	/**
	 * 生成logo（二维码中间，带白色边框）
	 * @param resource $image 图片资源
	 * @param resource $logo logo图资源
	 * @return void
	 */
	private static function createLogo(&$image, $logo){
		$logo_size   = 0.3; //logo总占比（含白边）
		$logo_border = 0.28; //logo实际占比
		
		//二维码尺寸
		$image_width  = imagesx($image);
		$image_height = imagesy($image);
		
		//处理圆角图片
		$corner = self::createRadiusCorner();
		//圆角图尺寸
		$corner_width  = imagesx($corner);
		$corner_height = imagesy($corner);
		//调整后尺寸
		$corner_width_after  = $image_width*$logo_size;
		$corner_height_after = $corner_width_after;
		$from_width          = ($image_width-$corner_width_after)/2;
		//合并
		imagecopyresampled($image, $corner, $from_width, $from_width, 0, 0, $corner_width_after, $corner_height_after, $corner_width, $corner_height);
		
		//logo尺寸
		$logo_width  = imagesx($logo); //logo宽度
		$logo_height = imagesy($logo); //logo高度
		//logo调整后尺寸
		$scale             = ($logo_width>$logo_height ? $logo_width : $logo_height)/($image_width*$logo_border); //缩放比例
		$logo_width_after  = $logo_width/$scale; //调整后logo宽度
		$logo_height_after = $logo_height/$scale; //调整后logo高度
		//logo位置
		$from_width  = ($image_width-$logo_width_after)/2;
		$from_height = ($image_height-$logo_height_after)/2;
		//合并图片
		imagecopyresampled($image, $logo, $from_width, $from_height, 0, 0, $logo_width_after, $logo_height_after, $logo_width, $logo_height);
	}
	
	/**
	 * 生成圆角图（白色）
	 * @param int $image_width 图片宽度
	 * @param int $image_height 图片高度
	 * @param int $radius 角半径
	 * @return resource
	 */
	private static function createRadiusCorner($image_width = 300, $image_height = 300, $radius = 30){
		//创建图片
		$image = imagecreatetruecolor($image_width, $image_height);
		//颜色
		$white = imagecolorallocate($image, 255, 255, 255);
		$black = imagecolorallocate($image, 0, 0, 0);
		//背景
		imagefill($image, 0, 0, $white);
		
		//生成圆角图
		$corner = imagecreatetruecolor($radius, $radius);
		imagefill($corner, 0, 0, $black);
		imagefilledarc($corner, $radius, $radius, $radius*2, $radius*2, 0, 0, $white, IMG_ARC_PIE);
		//拼到4个角
		imagecopymerge($image, $corner, 0, 0, 0, 0, $radius, $radius, 100);
		imagecopymerge($image, imagerotate($corner, 90, 0), 0, $image_height-$radius, 0, 0, $radius, $radius, 100);
		imagecopymerge($image, imagerotate($corner, 180, 0), $image_width-$radius, $image_height-$radius, 0, 0, $radius, $radius, 100);
		imagecopymerge($image, imagerotate($corner, 270, 0), $image_width-$radius, 0, 0, 0, $radius, $radius, 100);
		//透明化
		imagecolortransparent($image, $black);
		//返回图片资源
		return $image;
	}
	
}