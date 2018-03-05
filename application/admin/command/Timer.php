<?php
/**
 * 命令行-定时器
 * @auth 夏爽
 */
namespace app\admin\command;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;

class Timer extends \app\common\command\BaseAdmin{
	
	/**
	 * 命令配置
	 */
	protected function configure(){
		//名称描述
		$this->setName('timer')->setDescription('timer manage'); //描述
		
		//设置参数
		$this->addArgument('type', Argument::OPTIONAL, '类型', 'on'); //类型
	}
	
	/**
	 * 命令执行
	 * @param Input $input
	 * @param Output $output
	 */
	protected function execute(Input $input, Output $output){
		//获取参数值
		$args = $input->getArguments();
		//获取选项值
		$options = $input->getOptions();
		
		/* 类型 */
		switch($args['type']){
			/* 开启 */
			case 'on':
				if($this->getTimerStatus()){
					echo "已有定时器在运行了，请勿重复开启！\n";
					return;
				}
				//开启定时器
				$this->setTimerStatus(true);
				$timer_id = swoole_timer_tick(10000, function($timer_id){
					//定时器状态
					if(!$this->getTimerStatus()){
						//关闭定时器
						$result = swoole_timer_clear($timer_id);
						return;
					}
					//更新操作时间
					$this->updateLastTime();
					//执行
					$this->timerRun();
				});
				break;
			/* 关闭 */
			case 'off':
				$this->setTimerStatus(false);
				echo "已关闭定时器！\n";
				break;
			/* 状态 */
			case 'status':
				$last_time = service('Redis')->get('timer_last_time');
				echo '定时器已'.($this->getTimerStatus() ? '开启' : '关闭')."，最后执行时间：{$last_time}\n";
				break;
			default:
				echo "类型错误！\n";
		}
	}
	
	/**
	 * 定时器执行任务
	 * @param int $count
	 * @param $timer_id
	 */
	public function timerRun(){
		service('Queue')->timeing();
	}
	
	/**
	 * 定时器开关
	 * @param bool $status 状态[true开启-false关闭]
	 * @param int $count 计数器数值
	 */
	private function setTimerStatus($status){
		$status = $status ? 'on' : 'off';
		service('Redis')->set('timer_status', $status);
		$this->updateLastTime();
	}
	
	/**
	 * 定时器状态
	 * @param int $count 计数器数值
	 * $return bool true开启false关闭
	 */
	private function getTimerStatus(){
		$timer_status = service('Redis')->get('timer_status');
		return $timer_status=='on' ? true : false;
	}
	
	/**
	 * 更新操作时间
	 */
	private function updateLastTime(){
		service('Redis')->set('timer_last_time', date('Y-m-d H:i:s'));
	}
	
}