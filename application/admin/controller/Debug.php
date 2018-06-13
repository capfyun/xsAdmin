<?php
/**
 * 调试
 * @author xs
 */
namespace app\admin\controller;



use think\Validate;
use xs\auth\Auth;
use xs\Rsa;
use xs\Upload;

class Debug extends \app\common\controller\AdminBase{
	
	public function index(){
	}
	
	/**
	 * 测试
	 */
	public function test(){
		
		
		$a = Rsa::publics('../openssl/rsa_public_key.pem')->encrypt('asdasd');
		
		halt($a);
		
		return $this->fetch();
	}
	
	
	public function produce(){

		// 设置生产相关配置，具体配置参数见 [Configuration](Configuration.md)
		$config = \Kafka\ProducerConfig::getInstance();
		$config->setMetadataRefreshIntervalMs(10000);
		$config->setMetadataBrokerList('10.13.4.159:9192');
		$config->setBrokerVersion('0.9.0.1');
		$config->setRequiredAck(1);
		$config->setIsAsyn(false);
		$config->setProduceInterval(500);
		$producer = new \Kafka\Producer(function() {
			return array(
				array(
					'topic' => 'test',
					'value' => 'test....message.',
					'key' => 'testkey',
				),
			);
		});
//		$producer->setLogger($logger);
		$producer->success(function($result) {
			var_dump($result);
		});
		$producer->error(function($errorCode, $context) {
			var_dump($errorCode);
		});
		$producer->send();
	}
	
	
	public function consumer(){
		
		$config = \Kafka\ConsumerConfig::getInstance();
		$config->setMetadataRefreshIntervalMs(10000);
		$config->setMetadataBrokerList('10.13.4.159:9192');
		$config->setGroupId('test');
		$config->setBrokerVersion('0.9.0.1');
		$config->setTopics(array('test'));
//$config->setOffsetReset('earliest');
		$consumer = new \Kafka\Consumer();
//		$consumer->setLogger($logger);
		$consumer->start(function($topic, $part, $message) {
			var_dump($message);
		});
	}
}
