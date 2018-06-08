<?php
/**
 * 上传
 * @author xs
 */
namespace xs;

use xs\upload\Driver;

class Upload{
	/**
	 * 默认上传配置
	 * @var array
	 */
	private $config = array(
		'mimes'     => array(), //允许上传的文件MiMe类型
		'max_size'  => 0, //上传的文件大小限制 (0-不做限制)
		'exts'      => array(), //允许上传的文件后缀
		'auto_sub'  => true, //自动子目录保存文件
		'sub_name'  => array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
		'save_path' => '', //保存路径
		'save_name' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
		'save_ext'  => '', //文件保存后缀，空则使用原后缀
		'replace'   => false, //存在同名是否覆盖
		'hash'      => true, //是否生成hash编码
		'callback'  => false, //false不检测，检测文件是否存在回调，如果存在返回文件信息数组
		'location'  => 1, //文件存储位置标识
	);
	/**
	 * 错误信息
	 * @var string
	 */
	private $error = ''; //上传错误信息
	/**
	 * 驱动实例
	 * @var Driver
	 */
	private $driver;
	
	/**
	 * 构造方法，用于构造上传实例
	 * @param array $config 配置
	 * @param string $driver 要使用的上传驱动 LOCAL-本地上传驱动，FTP-FTP上传驱动
	 */
	public function __construct($config = array(), $driver = '', $driver_config = null){
		/* 获取配置 */
		$this->config = array_merge($this->config, $config);
		
		/* 设置上传驱动 */
		$this->setDriver($driver, $driver_config);
		
		/* 调整配置，把字符串配置参数转换为数组 */
		if(!empty($this->config['mimes'])){
			if(is_string($this->config['mimes'])){
				$this->config['mimes'] = explode(',', $this->config['mimes']);
			}
			$this->config['mimes'] = array_map('strtolower', $this->config['mimes']);
		}
		if(!empty($this->config['exts'])){
			if(is_string($this->config['exts'])){
				$this->config['exts'] = explode(',', $this->config['exts']);
			}
			$this->config['exts'] = array_map('strtolower', $this->config['exts']);
		}
	}
	
	/**
	 * 设置上传驱动
	 * @param string $driver 驱动名称
	 * @param array $config 驱动配置
	 */
	private function setDriver($driver = null, $config = null){
		$driver = $driver ? : 'Local';
		$config = $config ? : array();
		$class  = strpos($driver, '\\')===false
			? '\\xs\\upload\\'.ucwords($driver)
			: $driver;
		if(!class_exists($class)){
			throw new \Exception('class not exists:'.$class);
		}
		$this->driver = new $class($config);
	}
	
	/**
	 * 获取最后一次上传错误信息
	 * @return string 错误信息
	 */
	public function getError(){
		return $this->error;
	}
	
	/**
	 * 上传单个文件
	 * @param  array $file 文件数组
	 * @return array 上传成功后的文件信息
	 */
	public function uploadOne($file){
		$info = $this->upload(array($file));
		return $info ? $info[0] : $info;
	}
	
	/**
	 * 上传文件
	 * @param array $files 通常是 $_FILES数组，type=remote时为URL地址
	 * @param string $type upload通常上传，remote远程文件
	 */
	public function upload($files = '', $type = 'upload'){
		switch($type){
			//下载远程资源进行上传
			case 'remote':
				if(empty($files)){
					$this->error = '没有上传的文件！';
					return false;
				}
				is_string($files) && $files = array($files);
				foreach($files as $k => $v){
					$files[$k] = array('name' => $v);
				}
				break;
			//通常上传
			case 'upload':
			default:
				''===$files && $files = $_FILES;
				if(empty($files)){
					$this->error = '没有上传的文件！';
					return false;
				}
				// 对上传文件数组信息处理
				$files = $this->dealFiles($files);
		}
		//检查上传目录
		if($this->config['save_path']){
			if(!$this->driver->checkSavePath($this->config['save_path'])){
				$this->error = $this->driver->getError();
				return false;
			}
		}
		//finfo扩展
		if(function_exists('finfo_open')){
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
		}
		$info = array();
		foreach($files as $key => $file){
			//获取上传文件后缀，允许上传无后缀文件
			$file['ext'] = pathinfo($file['name'], PATHINFO_EXTENSION);
			switch($type){
				case 'remote':
					//下载文件
					if(!$file['tmp_name'] = $this->curlDownFile($file['name'])){
						continue;
					}
					//文件名
					$file['name'] = strip_tags($file['name']);
					//文件大小
					$file['size'] = filesize($file['tmp_name']);
					//文件类型
					$file['type'] = isset($finfo) ? finfo_file($finfo, $file['tmp_name']) : '';
					//检查文件
					if(!$this->checkFile($file)){
						continue;
					}
					break;
				case 'upload':
				default:
					$file['name'] = strip_tags($file['name']);
					//通过扩展获取文件类型，可解决FLASH上传$FILES数组返回文件类型错误的问题
					isset($finfo) && $file['type'] = finfo_file($finfo, $file['tmp_name']);
					//文件上传检测
					if(!$this->checkUpload($file)){
						continue;
					}
					if(!$this->checkFile($file)){
						continue;
					}
					unset($file['error'], $file['key']);
			}
			//进行上传
			if(!$file = $this->move($file)){
				continue;
			}
			$info[] = $file;
		}
		if(isset($finfo)){
			finfo_close($finfo);
		}
		return empty($info) ? false : $info;
	}
	
	/**
	 * 进行上传
	 * @param array $file
	 * @return false|array
	 */
	private function move(array $file){
		//存储位置标识
		$file['location'] = $this->config['location'];
		//获取文件hash
		if($this->config['hash']){
			$file['md5']  = md5_file($file['tmp_name']);
			$file['sha1'] = sha1_file($file['tmp_name']);
		}
		//调用回调函数检测文件是否存在
		if($this->config['callback'] && is_callable($this->config['callback'])
			&& $data = call_user_func($this->config['callback'], $file)
		){
			if($this->driver->exist($data)){
				return $data;
			}elseif($this->config['removeTrash'] && is_callable($this->config['removeTrash'])){
				//删除垃圾据
				call_user_func($this->config['removeTrash'], $data);
			}
		}
		//生成保存文件名
		$save_name = $this->getSaveName($file);
		if(false==$save_name){
			return false;
		}else{
			$file['save_name'] = $save_name;
		}
		//检测并创建子目录
		$sub_path = $this->getSubPath($file['name']);
		if(false===$sub_path){
			return false;
		}else{
			$file['save_path'] = $this->config['save_path'].$sub_path;
		}
		//对图像文件进行严格检测
		$ext = strtolower($file['ext']);
		if(in_array($ext, array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'))){
			$img_info = getimagesize($file['tmp_name']);
			if(empty($img_info) || ($ext=='gif' && empty($img_info['bits']))){
				$this->error = '非法图像文件！';
				return false;
			}
		}
		//保存文件 并记录保存成功的文件
		if(!$this->driver->save($file, $this->config['replace'])){
			$this->error = $this->driver->getError();
			return false;
		}
		unset($file['tmp_name']);
		return $file;
	}
	
	/**
	 * 转换上传文件数组变量为正确的方式
	 * @access private
	 * @param array $files 上传的文件变量
	 * @return array
	 */
	private function dealFiles($files){
		$file_array = [];
		foreach($files as $k => $v){
			if(is_array($v['name'])){
				$keys  = array_keys($v);
				$count = count($v['name']);
				for($i = 0; $i<$count; $i++){
					$file = ['key' => $k];
					foreach($keys as $_key){
						$file[$_key] = $v[$_key][$i];
					}
					$file_array[] = $file;
				}
			}else{
				$v['key']     = $k;
				$file_array[] = $v;
			}
		}
		return $file_array;
	}
	
	/**
	 * 检查上传的文件
	 * @param array $file 文件信息
	 * @return bool
	 */
	private function checkUpload($file){
		//文件上传失败，捕获错误代码
		if($file['error']){
			$this->error($file['error']);
			return false;
		}
		//无效上传
		if(empty($file['name'])){
			$this->error = '未知上传错误！';
		}
		//检查是否合法上传
		if(!is_uploaded_file($file['tmp_name'])){
			$this->error = '非法上传文件！';
			return false;
		}
		
		//通过检测
		return true;
	}
	
	/**
	 * 检查文件
	 * @param array $file 文件信息
	 * @return bool
	 */
	private function checkFile($file){
		//检查文件大小
		if(!$this->checkSize($file['size'])){
			$this->error = '上传文件大小不符！';
			return false;
		}
		//检查文件Mime类型
		//TODO:FLASH上传的文件获取到的mime类型都为application/octet-stream
		if(!$this->checkMime($file['type'])){
			$this->error = '上传文件MIME类型不允许！';
			return false;
		}
		//检查文件后缀
		if(!$this->checkExt($file['ext'])){
			$this->error = '上传文件后缀不允许';
			return false;
		}
		return true;
	}
	
	/**
	 * 获取错误代码信息
	 * @param string $error_no 错误号
	 */
	private function error($error_no){
		switch($error_no){
			case 1:
				$this->error = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值！';
				break;
			case 2:
				$this->error = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值！';
				break;
			case 3:
				$this->error = '文件只有部分被上传！';
				break;
			case 4:
				$this->error = '没有文件被上传！';
				break;
			case 6:
				$this->error = '找不到临时文件夹！';
				break;
			case 7:
				$this->error = '文件写入失败！';
				break;
			default:
				$this->error = '未知上传错误！';
		}
	}
	
	/**
	 * 检查文件大小是否合法
	 * @param integer $size 数据
	 */
	private function checkSize($size){
		return !($size>$this->config['max_size']) || (0==$this->config['max_size']);
	}
	
	/**
	 * 检查上传的文件MIME类型是否合法
	 * @param string $mime 数据
	 */
	private function checkMime($mime){
		return empty($this->config['mimes']) ? true : in_array(strtolower($mime), $this->config['mimes']);
	}
	
	/**
	 * 检查上传的文件后缀是否合法
	 * @param string $ext 后缀
	 */
	private function checkExt($ext){
		return empty($this->config['exts']) ? true : in_array(strtolower($ext), $this->config['exts']);
	}
	
	/**
	 * 根据上传文件命名规则取得保存文件名
	 * @param string $file 文件信息
	 */
	private function getSaveName($file){
		$rule = $this->config['save_name'];
		if(empty($rule)){ //保持文件名不变
			/* 解决pathinfo中文文件名BUG */
			$filename  = substr(pathinfo("_{$file['name']}", PATHINFO_FILENAME), 1);
			$save_name = $filename;
		}else{
			$save_name = $this->getName($rule, $file['name']);
			if(empty($save_name)){
				$this->error = '文件命名规则错误！';
				return false;
			}
		}
		
		/* 文件保存后缀，支持强制更改文件后缀 */
		$ext = empty($this->config['save_ext']) ? $file['ext'] : $this->config['save_ext'];
		
		return $save_name.'.'.$ext;
	}
	
	/**
	 * 获取子目录的名称
	 * @param array $file 上传的文件信息
	 */
	private function getSubPath($file_name){
		$sub_path = '';
		$rule     = $this->config['sub_name'];
		if($this->config['auto_sub'] && !empty($rule)){
			$sub_path = $this->getName($rule, $file_name).'/';
			
			if(!empty($sub_path)
				&& method_exists($this->driver, 'mkdir')
				&& !$this->driver->mkdir($this->save_path.$sub_path)
			){
				$this->error = $this->driver->getError();
				return false;
			}
		}
		return $sub_path;
	}
	
	/**
	 * 根据指定的规则获取文件或目录名称
	 * @param  array $rule 规则
	 * @param  string $filename 原文件名
	 * @return string           文件或目录名称
	 */
	private function getName($rule, $filename){
		$name = '';
		if(is_array($rule)){ //数组规则
			$func  = $rule[0];
			$param = (array)$rule[1];
			foreach($param as &$value){
				$value = str_replace('__FILE__', $filename, $value);
			}
			$name = call_user_func_array($func, $param);
		}elseif(is_string($rule)){ //字符串规则
			if(function_exists($rule)){
				$name = call_user_func($rule);
			}else{
				$name = $rule;
			}
		}
		return $name;
	}
	
	/**
	 * 下载文件
	 * @param string $url 文件地址
	 * @return string|false 成功返回临时文件路径
	 */
	private function curlDownFile($url){
		// 判断是否是合法 url
		if(!filter_var($url, FILTER_VALIDATE_URL)){
			$this->error = '无效的文件地址';
			return false;
		}
		//初始化curl会话
		$ch = curl_init();
		//Curl 设置参数
		curl_setopt_array($ch, [
			CURLOPT_URL            => $url, //请求的url地址
			CURLOPT_CUSTOMREQUEST  => 'GET', //请求传输方式
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_0, //强制使用 HTTP/1.0
			CURLOPT_USERAGENT      => 'toqi.net', //伪装浏览器
			CURLOPT_CONNECTTIMEOUT => 30, //最长等待时间
			CURLOPT_TIMEOUT        => 30, //执行的最长秒数
			CURLOPT_RETURNTRANSFER => true, //文件流的形式返回，而不是直接输出
			CURLOPT_ENCODING       => '',  //发送所有支持的编码类型
			CURLOPT_SSL_VERIFYPEER => false, //返回SSL证书验证请求的结果
			CURLOPT_HEADER         => false, //把头文件的信息作为数据流输出
			CURLOPT_HTTPHEADER     => [], //设置http头信息
			CURLINFO_HEADER_OUT    => true, //发送请求的字符串
			CURLOPT_NOBODY         => false, //不取回数据
		]);
		//执行会话
		$response = curl_exec($ch);
		//保存会话信息
		$curl_info = curl_getinfo($ch);
		//关闭curl会话
		curl_close($ch);
		//请求失败
		if($curl_info['http_code']!=200){
			$this->error = '请求失败';
			return false;
		}
		if(!$response){
			$this->error = '无文件数据';
			return false;
		}
		// 保存文件到制定路径
		$temp_file = tempnam(sys_get_temp_dir(), 'download');
		if(!file_put_contents($temp_file, $response)){
			$this->error = '下载失败';
			return false;
		}
		unset($response, $url);
		return $temp_file;
	}
}
