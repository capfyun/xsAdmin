<?php
/**
 * 自定义命令行-测试
 * @auth 夏爽
 */
namespace app\admin\command;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;

class Test extends \app\common\command\Base{
	
	//当前模块
	protected $module = 'admin';
	
	/**
	 * 命令配置
	 */
	protected function configure(){
		
		//设置参数
		$this->addArgument('email', Argument::OPTIONAL); //必传参数
		$this->addArgument('mobile', Argument::OPTIONAL);//可选参数
		
		//选项定义
		$this->addOption('message', 'm', Option::VALUE_OPTIONAL, 'test'); //选项值必填
		$this->addOption('status', 's', Option::VALUE_OPTIONAL, 'test'); //选项值选填
		
		$this->setName('test')->setDescription('Here is the remark ');
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