<?php
/**
 * 图片
 * @author 夏爽
 */
namespace app\common\model;


class Image extends Base{
	
	/* 自动完成 */
	//写入（包含新增、更新）时自动完成
	protected $auto = [];
	//新增时自动完成
	protected $insert = [
		'status' => 1, //状态[0禁用-1启用]
	];
	//更新时自动完成
	protected $update = [
	];
	
	//自动写入时间
	protected $autoWriteTimestamp = true;    //模型中定义autoWriteTimestamp属性，true时间戳-datetime时间格式-false关闭写入
	protected $updateTime         = false;    //更新时间字段，默认为update_time，false关闭写入
	
	//只读字段
	protected $readonly = ['md5', 'sha1'];    //模型中定义readonly属性，配置指定只读字段
	
	/**
	 * 初始化
	 */
	public function initialize(){
		parent::initialize();
	}
	
	/**
	 * 文件上传
	 * @param  array $files 要上传的文件列表（通常是$_FILES数组）
	 * @return false|array 文件上传成功后的信息
	 */
	public function upload($files = '', $config = [], $driver = 'Local', $driver_config = null){
		//初始化
		$config   = array_merge([
			'callback'    => [$this, 'isExist'],
			'removeTrash' => [$this, 'removeTrash'],
		], $config);
		$uploader = new Uploader($config, $driver, $driver_config);
		//上传
		$data = $uploader->upload($files);
		if(!$data){
			$this->error = $uploader->getError();
			return false;
		}
		//文件上传成功，记录文件信息
		foreach($data as $k => $v){
			//已经存在文件记录
			if(isset($v['id']) && is_numeric($v['id'])){
				continue;
			}
			//文件入库
			$result = $this->allowField(true)->isUpdate(false)->save($v);
			if(!$result){
				//TODO: 文件上传成功，但是记录文件信息失败，需记录日志
				unset($data[$k]);
			}
			$data[$k]['id'] = $this->id;
		}
		return $data; //文件上传成功
	}
	
	/**
	 * 抓取网络资源保存到本地
	 */
	public function grab($url = '', $config = []){
		//初始化
		$config   = array_merge([
			'callback'    => [$this, 'isExist'],
			'removeTrash' => [$this, 'removeTrash'],
		], $config);
		$downloader = new Downloader($config);
		$file = $downloader->download($url);
		
		if(!$file){
			$this->error = $downloader->getError();
			return false;
		}
		//文件入库
		if(!isset($file['id'])){
			$result = $this->allowField(true)->isUpdate(false)->save($file);
			if(!$result){
				$this->error = '入库失败';
				return false;
			}
		}
		
		return $this;
	}
	
	/**
	 * 下载指定文件
	 * @param  integer $id 文件ID
	 * @param  string $args 回调函数参数
	 * @return boolean       false-下载失败，否则输出下载文件
	 */
	public function download($id, $filed = 'id'){
		//获取下载文件信息
		$file = $this->get([$filed => $id]);
		if(!$file){
			$this->error = '不存在该文件！';
			return false;
		}
		//擦除缓冲区
		ob_end_clean();
		ini_set('memory_limit', '2048M');
		//下载文件
		switch($file->location){
			//本地文件
			case 1:
				return $this->downLocalFile($file);
			//FTP文件
			case 2:
				return $this->downFtpFile($file);
				break;
			default:
				$this->error = '不支持的文件存储类型！';
				return false;
		}
	}
	
	/**
	 * 下载本地文件
	 * @param  array $file 文件信息数组
	 * @param  callable $callback 下载回调函数，一般用于增加下载次数
	 * @param  string $args 回调函数参数
	 * @return boolean            下载失败返回false
	 */
	private function downLocalFile($file){
		if(!is_file($file['save_path'].$file['save_name'])){
			$this->error = '文件已被删除！';
			return false;
		}
		
		//执行下载 //TODO: 大文件断点续传
		header("Content-Description: File Transfer");
		header('Content-type: '.$file['type']);
		header('Content-Length:'.$file['size']);
		if(preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])){ //for IE
			header('Content-Disposition: attachment; filename="'.rawurlencode($file['save_name']).'"');
		}else{
			header('Content-Disposition: attachment; filename="'.$file['save_name'].'"');
		}
		readfile($file['save_path'].$file['save_name']);
		exit;
	}
	
	/**
	 * 下载ftp文件
	 * @param  array $file 文件信息数组
	 * @param  callable $callback 下载回调函数，一般用于增加下载次数
	 * @param  string $args 回调函数参数
	 * @return boolean            下载失败返回false
	 */
	private function downFtpFile($file){
		$host = '';
		header("Location:http://{$host}/onethink.php?");
	}
	
	/**
	 * 检测当前上传的文件是否已经存在
	 * @param array $file 文件上传数组
	 * @return false|array 文件信息
	 */
	public function isExist($file){
		if(empty($file['md5'])){
			throw new \Exception('缺少参数:md5');
		}
		//查找文件
		return $this->where(['md5' => $file['md5'], 'sha1' => $file['sha1']])->find();
	}
	
	/**
	 * 清除数据库存在但本地不存在的数据
	 * @param $data
	 */
	public function removeTrash($data){
		$this->where(['id' => $data['id']])->delete();
	}
	
	/**
	 * 获取文件地址
	 * @param int $id 文件ID
	 * @return string
	 */
	public function url($id){
		static $url = [];
		if(isset($url[$id])){
			return $url[$id];
		}
		//查询
		$file = $this->get(['id' => $id, 'status' => 1]);
		if(!$file){
			$url[$id] = '';
			return $url[$id];
		}
		//文件存储位置
		switch($file['location']){
			//本地
			case 1:
				$path = trim(config('web_domain'), '/').'/'.$file['save_path'].$file['save_name'];
				break;
			case 2:
				$path = '';
				break;
			default:
				$path = '';
		}
		$url[$id] = $path;
		return $url[$id];
	}
	
}
