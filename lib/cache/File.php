<?php
/**
 * 文件类型缓存类
 * @author xs 改自thinkphp5.0 liu21st <liu21st@gmail.com>
 */
namespace lib\cache;

class File extends Driver{
	
	protected $options = [
		'path'   => RUNTIME_PATH.'caches'.DS,
		'subdir' => true, //子目录
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
	 * 取得变量的存储文件名
	 * @access protected
	 * @param  string $name 缓存变量名
	 * @return string
	 */
	protected function getCacheKey($name){
		$name = md5($name);
		if($this->options['subdir']){
			// 使用子目录
			$name = substr($name, 0, 2).DS.substr($name, 2);
		}
		if($this->prefix){
			$name = $this->prefix.DS.$name;
		}
		$filename = $this->options['path'].$name.'.php';
		
		return $filename;
	}
	
	
	
	/**
	 * 读取缓存
	 * @access public
	 * @param string $name 缓存变量名
	 * @param mixed $default 默认值
	 * @return mixed
	 */
	public function get($name, $default = false){
		$filename = $this->getCacheKey($name);
		if(!is_file($filename)){
			return $default;
		}
		$content = file_get_contents($filename);
		if($content===false){
			return $default;
		}
		$expire = (int)substr($content, 8, 12);
		if(0!=$expire && time()>filemtime($filename)+$expire){
			return $default;
		}
		$content = substr($content, 32);
		$content = unserialize($content);
		return $content;
	}
	
	/**
	 * 写入缓存
	 * @access public
	 * @param string $name 缓存变量名
	 * @param mixed $value 存储数据
	 * @param integer|\DateTime $expire 有效时间（秒）
	 * @return boolean
	 */
	public function set($name, $value, $expire = null){
		//有效时间
		is_null($expire) && $expire = $this->expire;
		if($expire instanceof \DateTime){
			$expire = $expire->getTimestamp()-time();
		}
		//文件目录
		$filename = $this->getCacheKey($name);
		$dir      = dirname($filename);
		!is_dir($dir) && @mkdir($dir, 0755, true);
		//写入
		$data   = "<?php\n//".sprintf('%012d', $expire)."\n exit();?>\n".serialize($value);
		$result = file_put_contents($filename, $data);
		if(!$result){
			return false;
		}
		clearstatcache();
		return true;
	}
	
	/**
	 * 删除缓存
	 * @access public
	 * @param string $name 缓存变量名
	 * @return boolean
	 */
	public function rm($name){
		$filename = $this->getCacheKey($name);
		try{
			return is_file($filename) && unlink($filename);
		}catch(\Exception $e){
			return false;
		}
	}
	
	/**
	 * 清除缓存
	 * @access public
	 * @param string $tag 标签名
	 * @return boolean
	 */
	public function clear(){
		$files = (array)glob($this->options['path'].($this->prefix ? $this->prefix.DS : '').'*');
		foreach($files as $path){
			if(is_dir($path)){
				$matches = glob($path.'/*.php');
				if(is_array($matches)){
					array_map('unlink', $matches);
				}
				rmdir($path);
			}else{
				unlink($path);
			}
		}
		return true;
	}
}
