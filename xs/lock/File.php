<?php
/**
 * 文件驱动
 * @author xs
 */
namespace xs\lock;

class File extends Driver{
	//配置
	protected $options = [
		'path' => RUNTIME_PATH.'lock'.DS, //锁目录
	];
	
	/**
	 * 构造函数
	 * @param array $options
	 */
	public function __construct($options = []){
		if(!empty($options)){
			isset($options['expire']) && $this->expire = (int)$options['expire'];
			isset($options['prefix']) && $this->prefix = (string)$options['prefix'];
			$this->options = array_merge($this->options, $options);
		}
		if(substr($this->options['path'], -1)!=DS){
			$this->options['path'] .= DS;
		}
		!is_dir($this->options['path']) && @mkdir($this->options['path'], 0755, true);
	}
	
	/**
	 * 获得一个锁
	 * @param string $name 锁名
	 * @param integer|\DateTime $expire 有效时间（秒）
	 * @return boolean
	 */
	public function acquire($name, $expire = null){
		if($this->has($name)){
			return false;
		}
		is_null($expire) && $expire = $this->expire;
		($expire instanceof \DateTime) && $expire = $expire->getTimestamp()-time();
		$filename = $this->getLockKey($name);
		$data     = "<?php\n//".sprintf('%012d', $expire)."\n exit();?>\n";
		$result   = file_put_contents($filename, $data);
		if(!$result){
			return false;
		}
		clearstatcache();
		return true;
	}
	
	/**
	 * 释放锁
	 * @param string $name 锁名
	 * @return boolean
	 */
	public function release($name){
		$filename = $this->getLockKey($name);
		try{
			return is_file($filename) && unlink($filename);
		}catch(\Exception $e){
			return false;
		}
	}
	
	/**
	 * 清除全部锁
	 * @return boolean
	 */
	public function clear(){
		$files = (array)glob($this->options['path'].'*');
		foreach($files as $path){
			!is_dir($path) && unlink($path);
		}
		return true;
	}
	
	/**
	 * 是否已存在锁
	 * @param string $name 锁名
	 * @return mixed
	 */
	protected function has($name){
		$filename = $this->getLockKey($name);
		if(!is_file($filename)){
			return false;
		}
		$content = file_get_contents($filename);
		if(false===$content){
			return false;
		}
		$expire = (int)substr($content, 8, 12);
		if(0!=$expire && time()>filemtime($filename)+$expire){
			return false;
		}
		return true;
	}
	
	/**
	 * 取得锁文件名
	 * @param string $name 锁名
	 * @return string 文件地址
	 */
	protected function getLockKey($name){
		$name     = $this->prefix.md5($name);
		$filename = $this->options['path'].$name.'.php';
		return $filename;
	}
}