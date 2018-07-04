<?php
/**
 * 本地
 * @author xs
 */
namespace xs\upload;

class Local extends Driver{
	
	/**
	 * 保存指定文件
	 * @param array $file 保存的文件信息
	 * @param boolean $replace 同名文件是否覆盖
	 * @return bool 保存状态，true-成功，false-失败
	 */
	public function save($file, $replace = true){
		$file_name = $file['save_path'].$file['save_name'];
		
		//不覆盖同名文件
		if(!$replace && is_file($file_name)){
			$this->error = '存在同名文件'.$file['save_name'];
			return false;
		}
		
		//保存文件
		if(!copy($file['tmp_name'], $file_name)){
			$this->error = '文件上传保存错误！';
			return false;
		}
		
		return true;
	}
	
	/**
	 * 检测上传目录
	 * @param  string $save_path 上传目录
	 * @return boolean 检测结果，true-通过，false-失败
	 */
	public function checkSavePath($save_path){
		/* 检测并创建目录 */
		if(!$this->mkdir($save_path)){
			return false;
		}else{
			/* 检测目录是否可写 */
			if(!is_writable($save_path)){
				$this->error = '上传目录 '.$save_path.' 不可写！';
				return false;
			}else{
				return true;
			}
		}
	}
	
	/**
	 * 创建目录
	 * @param  string $save_path 要创建的穆里
	 * @return bool 创建状态，true-成功，false-失败
	 */
	public function mkdir($save_path){
		$dir = $save_path;
		if(!is_dir($dir) && !mkdir($dir, 0777, true)){
			$this->error = "目录 {$dir} 创建失败！";
			return false;
		}
		return true;
	}
	
	/**
	 * 文件是否存在
	 */
	public function exist($file){
		$file_name = $file['save_path'].$file['save_name'];
		return is_file($file_name);
	}
	
}
