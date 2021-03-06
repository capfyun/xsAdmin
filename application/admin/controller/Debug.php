<?php
/**
 * 调试
 * @author xs
 */
namespace app\admin\controller;

use lib\Addon;
use lib\Thread;
use OSS\OssClient;
use lib\IpLocation;
use lib\Rsa;
use lib\Upload;

class Debug extends \app\common\controller\AdminBase{
	
	public function index(){
	}
	
	/**
	 * 测试
	 */
	public function test(){
		$thread = Thread::instance();
		$a = $thread->push('app\home\controller\Index=>test','213','aasdqwe','yyy');
		
		halt($a);
		
		return $this->fetch();
	}
	
	public function abc(){
		echo 'asdqwe';
		exit;
	}
	
	public function produce(){
		
		// 设置生产相关配置，具体配置参数见 [Configuration](Configuration.md)
		$config = \Kafka\ProducerConfig::getInstance();
		$config->setMetadataRefreshIntervalMs(10000);
		$config->setMetadataBrokerList('127.0.0.1:9092');
//		$config->setBrokerVersion('0.9.0.1');
		$config->setRequiredAck(1);
		$config->setIsAsyn(false);
		$config->setProduceInterval(500);
		$producer = new \Kafka\Producer(function(){
			return array(
				array(
					'topic' => 'test',
					'value' => 'test....message.',
					'key'   => 'testkey',
				),
			);
		});
//		$producer->setLogger($logger);
		$producer->success(function($result){
			var_dump([
				'success',
				$result,
			]);
		});
		$producer->error(function($errorCode){
			var_dump([
				'error',
				$errorCode,
			]);
		});
		$producer->send();
		var_dump('end');
	}
	
	public function consumer(){
		
		$config = \Kafka\ConsumerConfig::getInstance();
		$config->setMetadataRefreshIntervalMs(10000);
		$config->setMetadataBrokerList('127.0.0.1:9092');
		$config->setGroupId('test');
//		$config->setBrokerVersion('0.9.0.1');
		$config->setTopics(array('test'));
//$config->setOffsetReset('earliest');
		$consumer = new \Kafka\Consumer();
//		$consumer->setLogger($logger);
		$consumer->start(function($topic, $part, $message){
			var_dump([$topic, $part, $message]);
		});
		var_dump('end');
	}
}
