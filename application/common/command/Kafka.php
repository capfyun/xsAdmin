<?php
/**
 * 测试
 * @auth xs
 */
namespace app\common\command;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;

class Kafka extends Base{
	/**
	 * 命令配置
	 */
	protected function configure(){
		//命令名称
		$this->setName('kafka')
			->setDescription('Here is the remark ') //描述
			//参数
			->addArgument('email', Argument::REQUIRED) //必填
			->addArgument('mobile', Argument::OPTIONAL) //选填
			//选项
			->addOption('message', 'm', Option::VALUE_REQUIRED, 'test') //必填
			->addOption('status', 's', Option::VALUE_OPTIONAL, 'test'); //选填
		
		
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
		$output->writeln('The args value is:');
		print_r($args);
		
		//获取选项值
		$options = $input->getOptions();
		$output->writeln('The options value is:');
		print_r($options);
		
		$output->writeln('Now execute command...');
		
		$output->writeln("End..");
	}
	
}