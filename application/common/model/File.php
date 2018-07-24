<?php
/**
 * 文件
 * @author xs
 */
namespace app\common\model;

use xs\Upload;

class File extends Base{
	
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
		], config('upload_config'), $config);
		$uploader = new Upload($config, $driver, $driver_config);
		//上传
		$type = isset($config['type']) && $config['type']=='remote' ? 'remote' : 'upload';
		$data = $uploader->upload($files,$type);
		if(!$data){
			$this->error = $uploader->getError();
			return false;
		}
		//文件上传成功，记录文件信息
		foreach($data as $k => $v){
			//已经存在文件记录
			if(!isset($v['id']) || !is_numeric($v['id'])){
				//文件入库
				$this->id = null;
				$result   = $this->allowField(true)->isUpdate(false)->save($v);
				if($result){
					$data[$k]['id'] = $this->id;
				}else{
					//TODO: 文件上传成功，但是记录文件信息失败，需记录日志
					unset($data[$k]);
					continue;
				}
			}
			$data[$k]['url'] = $this->url($data[$k]['id']);
		}
		return $data; //文件上传成功
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
		//下载文件
		switch($file->location){
			//本地文件
			case 'local':
				return $this->downLocalFile($file);
			//FTP文件
			case 'ftp':
				return $this->downFtpFile($file);
				break;
			default:
				$this->error = '不支持的文件存储类型！';
				return false;
		}
	}
	
	/**
	 * 下载本地文件，支持断点续传
	 * @param  array $file 文件信息数组
	 * @param  callable $callback 下载回调函数，一般用于增加下载次数
	 * @param  string $args 回调函数参数
	 * @return boolean            下载失败返回false
	 */
	private function downLocalFile($file){
		$path = $file['save_path'].$file['save_name'];
		if(!is_file($path)){
			header("HTTP/1.1 505 Internal server error");
			exit;
		}
		$size  = $file['size'];
		$name  = $file['save_name'];
		$type  = $file['type'];
		$begin = 0;
		$end   = $size-1;
		//断点续传
		if(isset($_SERVER['HTTP_RANGE']) && preg_match('/\=\s*(\d+)\s*\-?\s*(\d+)?/', $_SERVER['HTTP_RANGE'], $matches)){
			header('HTTP /1.1 206 Partial Content');
			$begin = $matches[1];
			isset($matches[1]) && $end = $matches[2];
			header('Content-Range: bytes '.$begin.'-'.$end.'/'.$size);
		}else{
			header('Content-Range: bytes 0-'.$end.'/'.$size);
		}
		//响应头
		header('Content-Length:'.($end-$begin+1));
		header('Content-type: '.$type);
		header('Content-Description: File Transfer');
		header('Accenpt-Ranges: bytes');
		header('Cache-control: public');
		header('Pragma: public');
		//解决在IE中下载时中文乱码问题
		isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])
			? header('Content-Disposition: attachment; filename="'.rawurlencode($name).'"')
			: header('Content-Disposition: attachment; filename="'.$name.'"');
		//下载
		ob_end_clean();
		set_time_limit(0);
		ini_set('memory_limit', '2048M');
		$fp = fopen($path, 'rb');
		fseek($fp, $begin, SEEK_SET);
		while(!feof($fp) && $begin<=$end && (connection_status()==0)){
			print fread($fp, min(1024*16, ($end-$begin)+1));
			$begin += 1024*16;
		}
		fclose($fp);
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
		$file = $this->where(['md5' => $file['md5'], 'sha1' => $file['sha1'], 'location' => $file['location']])->find();
		return $file ? $file->toArray() : false;
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
			case 'local':
				$path = '/'.$file['save_path'].$file['save_name'];
				break;
			case 'alioss':
				$path = 'https://qiguo.oss-cn-shanghai.aliyuncs.com/'.$file['save_path'].$file['save_name'];
				break;
			default:
				$path = '';
		}
		$url[$id] = $path;
		return $url[$id];
	}
	
}
