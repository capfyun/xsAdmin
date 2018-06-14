<?php
/**
 * 消息队列
 * @auth xs
 */
namespace app\common\command;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;

class Queue extends Base{
	/**
	 * 命令配置
	 */
	protected function configure(){
		//命令名称
		$this->setName('queue')
			->setDescription('')//描述
			//参数
			->addArgument('type', Argument::REQUIRED);
	}
	
	/**
	 * 可以这样执行命令 php think test hello 13333333333 -m"this value should set" -s"this value can be null"
	 * @param Input $input
	 * @param Output $output
	 * @return void
	 */
	protected function execute(Input $input, Output $output){
		//获取参数值
		$args = $input->getArguments();
		
		switch(strtolower($args['type'])){
			//测试
			case 'test':
				$this->test();
				break;
			//生产者测试
			case 'producer' :
				$this->producer();
				break;
			default:
		}
	}
	
	/**
	 * 生产者，该方法用于调试
	 */
	private function producer(){
		
		// 设置生产相关配置，具体配置参数见 [Configuration](Configuration.md)
		$config = \Kafka\ProducerConfig::getInstance();
		$config->setMetadataBrokerList('127.0.0.1:9092');
		
		$producer = new \Kafka\Producer(function(){
			return array(
				array(
					'topic' => 'test',
					'value' => json_encode(['asd', 'xcxc' => '123']),
					'key'   => json_encode(['xcxcas' => '123123', 'asdqwe']),
				),
			);
		});
		$producer->success(function($result){
			print_r(['producer_success', $result,]);
		});
		$producer->error(function($errorCode){
			print_r(['producer_error', $errorCode,]);
		});
		$producer->send();
		print_r('end');
	}
	
	/**
	 * 消费者
	 */
	public function test(){
		
		$config = \Kafka\ConsumerConfig::getInstance();
		$config->setMetadataBrokerList('127.0.0.1:9092');
		$config->setTopics(array('test'));
		$consumer = new \Kafka\Consumer();
		$consumer->start(function($topic, $part, $message){
			print_r(['consumer', $topic, $part, $message]);
		});
		print_r('end');
	}
}