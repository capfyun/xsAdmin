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
			->addArgument('type', Argument::REQUIRED)
			->addOption('close', 'c', Option::VALUE_NONE, 'close consumer');
	}
	
	/**
	 * 可以这样执行命令 php think test hello 13333333333 -m"this value should set" -s"this value can be null"
	 * @param Input $input
	 * @param Output $output
	 * @return void
	 */
	protected function execute(Input $input, Output $output){
		$this->input  = $input;
		$this->output = $output;
		
		//关闭消费者
		if($input->getOption('close')){
			print_r('close');
			print_r($input->getArgument('type'));
			$queue = new \xs\Queue();
			$queue->close($input->getArgument('type'));
			exit;
		}
		
		switch(strtolower($input->getArgument('type'))){
			//测试
			case 'test':
				$this->test();
				break;
			//生产者测试
			case 'producer' :
				$this->producer();
				break;
			default:
				print_r($input->getArguments());
				print_r($input->getOptions());
		}
	}
	
	/**
	 * 生产者，该方法用于调试
	 */
	private function producer(){
		$queue = new \xs\Queue();
		$queue->producer('test', ['xcxc', 'asqwe', 'asdq' => 123123]);
		print_r('end');
	}
	
	/**
	 * 消费者
	 */
	public function test(){
		$queue = new \xs\Queue();
		$queue->consumer('test', function($data){
			dbDebug('consumer', $data);
		});
		print_r('end');
	}
}