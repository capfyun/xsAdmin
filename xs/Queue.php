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
	 * 单例实例
	 * @var Queue
	 */
	protected static $instance = null;
	
	/**
	 * 默认配置
	 * @var array
	 */
	private static $config = [
		//通用配置
		'metadataBrokerList' => '127.0.0.1:9092',
	];
	
	/**
	 * 初始化
	 * @param array $options 配置
	 */
	private function __construct($options = []){
		$this->config($options);
	}
	
	/**
	 * 获取实例
	 * @param array $options 实例配置
	 * @return static
	 */
	public static function instance($options = []){
		if(is_null(self::$instance)){
			static::$instance = new static($options);
		}
		return static::$instance;
	}
	
	/**
	 * 配置
	 * @param array $options 配置
	 * return $this
	 */
	public function config($options = []){
		$options = array_merge(static::$config, $options);
		foreach($options as $k => $v){
			$method = 'set'.ucfirst($k);
			\Kafka\ConsumerConfig::getInstance()->$method($v);
			\Kafka\ProducerConfig::getInstance()->$method($v);
		}
		return $this;
	}
	
	/**
	 * 注册消费者，keep run
	 * @param string $topic 主题名称
	 * @param \Closure|null $callback 回调函数
	 */
	public function consumer($topic, \Closure $callback = null){
		//订阅主题
		$config = \Kafka\ConsumerConfig::getInstance();
		$config->setGroupId($topic);
		$config->setTopics(array($topic));
		//注册
		$consumer = new \Kafka\Consumer();
		$consumer->start(function($topic, $part, $message) use ($callback){
			$data = $message['message'];
			switch($data['key']){
				//关闭
				case 'close':
					exit;
				//运行
				default:
					if(is_callable($callback)){
						$value = json_decode($data['value'], true);
						call_user_func($callback, $value);
					}
			}
		});
		//不会运行到此
	}
	
	/**
	 * 使用生产者
	 * @param string $topic 主题
	 * @param array $data 数据
	 * @return bool
	 */
	public function producer($topic, $data = []){
		$producer = new \Kafka\Producer(function() use ($topic, $data){
			return array(
				array('topic' => $topic, 'key' => 'data', 'value' => json_encode($data),),
			);
		});
		//注册发送成功事件
		$producer->success(function($result){ });
		//注册发送失败事件
		$producer->error(function($errorCode){ });
		//发送
		$producer->send();
		return true;
	}
	
	/**
	 * 关闭消费者
	 * @param string $topic 主题
	 * @return bool
	 */
	public function close($topic){
		$producer = new \Kafka\Producer(function() use ($topic){
			return array(
				array('topic' => $topic, 'key' => 'close', 'value' => 'close',),
			);
		});
		$producer->send();
		return true;
	}
	
}
