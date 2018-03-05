<?php
/**
 * 服务层-传输
 * @author 夏爽
 */
namespace app\common\service;

class Transmit extends Base{
	
	/* 上传文件类型配置 */
	//上传配置
	protected $config = [];
	//文件保存路径
	private $path = '/static/upload/';
	//自动清除失效数据
	private $refine = false;
	//文件保存路径
	private $error_info = [
		1 => '上传的文件大小超过了系统限制',
		2 => '文件大小超过了表单的限制值',
		3 => '文件只有部分被上传',
		4 => '没有文件被上传',
		5 => '上传文件大小为0',
		6 => '找不到临时文件夹',
		7 => '文件写入失败',
	];
	
	/**
	 * 初始化
	 * @param array|object $data
	 */
	public function __construct(){
		parent::__construct();
		$this->config = \think\Config::get('uploadconfig') ? : []; //获取默认配置
	}
	
	/**
	 * 配置参数
	 * @param array $config
	 * @return $this
	 */
	public function setConfig($config = []){
		$this->config = array_merge($this->config, $config);
		return $this;
	}
	
	/**
	 * 校验上传类型
	 * @param string $type
	 * @return bool
	 */
	public function checkType($type = ''){
		if(!isset($this->config[$type])){
			$this->error = '上传类型错误';
			return false;
		}
		return true;
	}
	
	/**
	 * 校验上传文件
	 */
	public function checkFile(){
		//上传文件是否存在
		if(empty($_FILES)){
			$this->error = '上传文件为空';
			return false;
		}
		//上传文件检查
		foreach($_FILES as $k => $v){
			//检查文件类型
			if(!isset($this->config[$k])){
				$this->error = '上传类型错误';
				return false;
			}
			//检查php内置错误
			if($v['error']!=0){
				$this->error = isset($this->error_info[$v['error']]) ? $this->error_info[$v['error']] : '未知错误';
				return false;
			}
		}
		return true;
	}
	
	/**
	 * 上传
	 * @return array|bool [['type' => '类型', 'url' => '路径', 'id' => 'ID'],...]
	 */
	public function upload(){
		//进行上传
		$data = []; //上传后结果
		foreach($_FILES as $type => $value){
			$files = request()->file($type); //获取表单上传文件
			if(empty($files)) continue;
			//判断同一类型下是否有多个文件
			if(is_array($files)){
				foreach($files as $file){
					$result = $this->upLocalFile($file, $type);
					if(!$result) return false;
					$data[] = $result;
				}
			}else{
				$result = $this->upLocalFile($files, $type);
				if(!$result) return false;
				$data[] = $result;
			}
		}
		if(empty($data)){
			$this->error = '无上传文件';
			return false;
		}
		return $data;
	}
	
	/**
	 * 上传到本地
	 * @param \think\File $file 上传对象
	 * @param string $type 文件类型
	 * @return array|false
	 */
	public function upLocalFile($file, $type = 'file'){
		//检查是否已上传过
		$m_upload  = model('Upload');
		$file_info = $m_upload->get(['md5' => $file->hash('md5'), 'sha1' => $file->hash('sha1')]);
		if($file_info){
			if(file_exists('.'.$file_info->path)){
				return ['type' => $type, 'url' => $file_info->path, 'id' => $file_info->id];
			}
		}
		/* 进行上传 */
		$path = $this->path.$type.'/'; //上传地址
		if(isset($this->config[$type])) $file = $file->validate($this->config[$type]); //文件校验
		$data = $file->move(ROOT_PATH.'public'.$path); //移动到框架应用根目录/public/upload/ 目录下
		//上传失败
		if(!$data){
			$this->error = $file->getError();
			return false;
		}
		/* 文件数据入库 */
		$info = $data->getInfo();
		if($file_info){
			$result = $m_upload->isUpdate(true)->save([
				'name'      => $info['name'],
				'save_name' => $data->getFilename(),
				'path'      => $path.$data->getSaveName(),
			], ['id' => $file_info->id,]);
		}else{
			$result = $m_upload->isUpdate(false)->save([
				'name'      => $info['name'],
				'file_type' => $type,
				'save_name' => $data->getFilename(),
				'mime_type' => $info['type'],
				'size'      => $info['size'],
				'path'      => $path.$data->getSaveName(),
				'url'       => '',
				'md5'       => $data->hash('md5'),
				'sha1'      => $data->hash('sha1'),
				'location'  => 1, //[0无-1本地文件-2远程文件]
			]);
		}
		
		if(!$result){
			$this->error = '图片数据入库失败';
			return false;
		}
		/* 上传成功 */
		return [
			'id'   => $m_upload->id,
			'name' => $m_upload->name,
			'type' => $type,
			'url'  => $m_upload->path,
		];
	}
	
	/**
	 * 下载指定文件
	 * @param  int|array $param 主键值或者查询条件
	 * @param  string $args 回调函数参数
	 * @return boolean       false-下载失败，否则输出下载文件
	 */
	public function download($param){
		//获取下载文件信息
		$file = model('Upload')->get($param);
		if(!$file){
			$this->error = '不存在该文件！';
			return false;
		}
		//下载文件
		switch($file['location']){
			case 1: //下载本地文件
				return $this->downLocalFile($file);
			case 2: //TODO: 下载远程FTP文件
				break;
			default:
				$this->error = '不支持的文件存储类型！';
				return false;
		}
	}
	
	/**
	 * 下载本地文件
	 * @param  array $file 文件信息数组
	 * @return boolean 下载失败返回false
	 */
	private function downLocalFile($file){
		if(!is_file('.'.$file['path'])){
			$this->error = '文件已被删除！';
			return false;
		}
		//临时配置内存
		ini_set('memory_limit', '1024M');
		//擦除缓冲区
		ob_end_clean();
		//执行下载 		//TODO: 大文件断点续传
		header("Content-Description: File Transfer");
		header('Content-type: '.$file['mime_type']);
		header('Content-Length:'.$file['size']);
		if(preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])){ //for IE
			header('Content-Disposition: attachment; filename="'.rawurlencode($file['name']).'"');
		}else{
			header('Content-Disposition: attachment; filename="'.$file['name'].'"');
		}
		readfile('.'.$file['path']);
		exit;
	}
	
	/**
	 * 获取上传文件
	 * @param  int $id 文件ID
	 * @param  string $field 查询的字段
	 * @return string
	 */
	public function file($id, $field = 'path'){
		static $file = [];
		if(isset($file[$id])) return isset($file[$id][$field]) ? $file[$id][$field] : '';
		//获取数据
		$info = db('upload')->where(['id' => $id, 'status' => 1])->find();
		if($info){
			//检查文件是否存在
			if($this->refine($id)){
				$info = [];
			}else{
				//路径自动带上域名
				$info['path'] = config('web_domain').$info['path'];
			}
		}
		$file[$id] = $info;
		return isset($info[$field]) ? $info[$field] : '';
	}
	
	/**
	 * 获取上传图片
	 * @param int $id 图片ID
	 * @return string
	 */
	public function image($id){
		static $image = [];
		if(isset($image[$id])) return $image[$id];
		//获取数据
		$path = db('upload')->where(['id' => $id, 'status' => 1, 'file_type' => 'image'])->value('path');
		$url  = '';
		if($path){
			file_exists('.'.$path) ? $url = config('web_domain').$path : $this->removeTrash($id);
		}
		$image[$id] = $url;
		return $image[$id];
	}
	
	
	
	/**
	 * 获取上传参数
	 * @param string $type 上传类型
	 * @param string $field
	 * @return string
	 */
	public function option($type = 'file', $field = 'ext'){
		$config = \think\Config::get('uploadconfig.'.$type);
		if(!$config) return '';
		switch($field){
			case 'size':
				$data = $config['size'];
				break;
			case 'ext':
				$data = $config['ext'];
				break;
			case 'ext_format':
				$data = '';
				foreach(explode(',', $config['ext']) as $k => $v){
					$data .= '*.'.$v.';';
				}
				break;
			default:
				return '';
		}
		return $data;
	}
	
	/**
	 * 删除不存在的文件
	 * @param int $id 文件ID
	 * @return bool false正常 true删除
	 */
	public function refine($id){
		//是否开启
		if(!$this->refine) return false;
		//校验文件
		$path = db('upload')->where(['id' => $id])->value('path');
		if(!$path || file_exists('.'.$path)) return false;
		//删除
		return db('upload')->where(['id' => $id])->delete();
	}
	
}
