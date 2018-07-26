<?php
/**
 * curl上传
 * @author xs
 */
namespace lib\upload;

use OSS\Core\OssException;
use OSS\OssClient;

class Alioss extends Driver{
	/**
	 * Alioss Class
	 * 阿里云OSS文档 https://promotion.aliyun.com/ntms/act/ossdoclist.html
	 * 依赖 composer require aliyuncs/oss-sdk-php
	 */
	
	/**
	 * 配置
	 * @var array
	 */
	private $config = array(
		'access_id'  => '8Hg70iSdonOMf6Yt',
		'sccess_key' => 'fITEZVUpTCRCQ9eKTFbhb7t0h9COzu',
		'endpoint'   => 'oss-cn-shanghai.aliyuncs.com',
		'bucket'     => 'qiguo',
	);
	/**
	 * 连接实例
	 * @var OssClient
	 */
	private $handler = null;
	
	/**
	 * 构造
	 * @param array $config 配置
	 */
	public function __construct(array $config = array()){
		//配置
		$this->config = array_merge($this->config, $config);
		//连接实例
		$this->handler = new OssClient(
			$this->config['access_id'],
			$this->config['sccess_key'],
			$this->config['endpoint']
		);
	}
	
	/**
	 * 保存指定文件
	 * @param array $file 保存的文件信息
	 * @param boolean $replace 同名文件是否覆盖
	 * @return boolean 保存状态，true-成功，false-失败
	 */
	public function save($file, $replace = true){
		try{
			$this->handler->uploadFile(
				$this->config['bucket'],
				$file['save_path'].$file['save_name'],
				$file['tmp_name']
			);
		} catch(OssException $e) {
			$this->error = $e->getMessage();
			return false;
		}
		return true;
	}
	
	/**
	 * 文件是否存在
	 * @param array $file 保存的文件信息
	 * @return bool
	 */
	public function exist($file){
		try{
			return $this->handler->doesObjectExist(
				$this->config['bucket'],
				$file['save_path'].$file['save_name']
			);
		} catch(OssException $e) {
			$this->error = $e->getMessage();
			return false;
		}
	}
	
}