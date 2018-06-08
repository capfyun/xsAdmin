<?php
/**
 * 上传驱动基类
 * @author xs
 */
namespace xs\upload;

abstract class Driver{
	
	/**
	 * 错误信息
	 * @var string
	 */
	protected $error = '';
	
	/**
	 * 保存指定文件
	 * @param array $file 文件信息
	 * @param boolean $replace 同名文件是否覆盖
	 * @return bool 保存状态，true-成功，false-失败
	 */
	abstract public function save($file, $replace = true);
	
	/**
	 * 获取最后一次上传错误信息
	 * @return string 错误信息
	 */
	public function getError(){
		return $this->error;
	}
	
	/**
	 * 检测上传目录
	 * @param string $save_path 路径
	 * @return bool
	 */
	public function checkSavePath($save_path){
		return true;
	}
	
	/**
	 * 检测文件是否存在
	 * @param array $file 文件信息
	 * @return bool
	 */
	public function exist($file){
		return false;
	}
	
	/**
	 * 创建目录
	 * @param  string $save_path 要创建的目录
	 * @return bool 创建状态，true-成功，false-失败
	 */
	public function mkdir($save_path){
		return true;
	}
}