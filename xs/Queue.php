<?php
/**
 * 消息列队
 * @author xs
 */
namespace xs;

class Queue{
	/**
	 * Queue Class
	 * 需要安装kafka http://kafka.apache.org/downloads.html
	 * 依赖 composer nmred/kafka-php:0.2.*
	 */
	/**
	 * 配置
	 * @var array
	 */
	private static $config = [
		//通用配置
		'clientId'                  => 'kafka-php',
		'brokerVersion'             => '0.10.1.0',
		'metadataBrokerList'        => '',
		'messageMaxBytes'           => '1000000',
		'metadataRequestTimeoutMs'  => '60000',
		'metadataRefreshIntervalMs' => '300000',
		'metadataMaxAgeMs'          => -1,
		//producer配置
		'requiredAck'               => 1,
		'timeout'                   => 5000,
		'isAsyn'                    => false,
		'requestTimeout'            => 6000,
		'produceInterval'           => 100,
		//consumer配置
		'groupId'                   => '',
		'sessionTimeout'            => 30000,
		'rebalanceTimeout'          => 30000,
		'topics'                    => array(),
		'offsetReset'               => 'latest', // earliest
		'maxBytes'                  => 65536, // 64kb
		'maxWaitTime'               => 100,
	];
	
	/**
	 * 初始化
	 * Queue constructor.
	 */
	public function __construct(){
		\Kafka\ConsumerConfig::getInstance()
			->setMetadataBrokerList('127.0.0.1:9092');
		\Kafka\ProducerConfig::getInstance()
			->setMetadataBrokerList('127.0.0.1:9092');
	}
	
	/**
	 * 注册生产者，keep run
	 * @param string $topic 主题名称
	 * @param \Closure|null $callback 回调函数
	 */
	public function consumer($topic, \Closure $callback = null){
		$config = \Kafka\ConsumerConfig::getInstance();
		$config->setMetadataBrokerList('127.0.0.1:9092');
		$config->setGroupId($topic);
		$config->setTopics(array($topic));
		$consumer = new \Kafka\Consumer();
		$consumer->start(function($topic, $part, $message) use ($callback){
			isset($message['key']) && $message['key']=='close' && exit();
			if(is_callable($callback)){
				$value = json_decode($message['value'], true);
				call_user_func($callback, $value);
			}
			print_r(['consumer', $topic, $part, $message]);
		});
		//不会运行到此
	}
	
	/**
	 * 使用生产者
	 * @param $topic
	 * @param array $data
	 * @return bool
	 */
	public function producer($topic, $data = []){
		$producer = new \Kafka\Producer(function() use ($topic,$data){
			return array(
				array(
					'topic' => $topic,
					'value' => json_encode($data),
//					'key'   => '',
				),
			);
		});
		//注册发送成功事件
//		$producer->success(function($result){
//			print_r(['producer_success', $result,]);
//		});
		//注册发送失败事件
//		$producer->error(function($errorCode){
//			print_r(['producer_error', $errorCode,]);
//		});
		$producer->send();
		return true;
	}
	
	/**
	 * 关闭消费者
	 * @param $topic
	 * @return bool
	 */
	public function close($topic){
		$producer = new \Kafka\Producer(function() use ($topic){
			return array(
				array(
					'topic' => $topic,
//					'value' => '',
					'key'   => 'stop',
				),
			);
		});
		$producer->send();
		return true;
	}
	
}
