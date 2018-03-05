<?php
/**
 * 命令行-服务器
 * @auth 夏爽
 */
namespace app\admin\command;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;

class Server extends \app\common\command\BaseAdmin{
	
	/**
	 * 命令配置
	 */
	protected function configure(){
		//名称描述
		$this->setName('server')->setDescription('create server '); //描述
		
		//设置参数
		$this->addArgument('type', Argument::OPTIONAL); //类型
		
		//选项定义
		$this->addOption('message', 'm', Option::VALUE_OPTIONAL);
		$this->addOption('status', 's', Option::VALUE_OPTIONAL);
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
		//分发
		switch($args['type']){
			case 'tcp':
				service('Swoole')->tcp_server();
				break;
			case 'udp':
				service('Swoole')->udp_server();
				break;
			case 'http':
				service('Swoole')->http_server();
				break;
			case 'ws':
				$this->ws();
				break;
			default:
				$output->writeln('type error:'.$args['type']);
				return;
		}
		
		$output->writeln('end');
	}
	
	/**
	 * 创建websocket服务器
	 */
	public function ws(){
		//初始化聊天室表
		$sql = 'TRUNCATE TABLE `kd_chatroom`';
		db()->execute($sql);
		
		//创建websocket服务器对象，监听0.0.0.0:9502端口
		$ws = new \swoole_websocket_server("0.0.0.0", 9501);
		
		//监听WebSocket连接打开事件
		$ws->on('open', function(\swoole_websocket_server $server, $request){
			db('chatroom')->insert([
				'user_id'     => $request->fd,
				'create_time' => time(),
				'update_time' => time(),
				'status'      => 1,
			]);
			echo $request->fd."连接\n";
		});
		
		//监听WebSocket消息事件
		$ws->on('message', function(\swoole_websocket_server $server, $frame){
			echo $frame->fd.":message: {$frame->data}\n";
			
			//msg type  1 初始化  2 通知  3 一般聊天  4 断开链接  5 获取在线用户 6 通知下线
			$data = json_decode($frame->data, true);
			/* 消息类型 */
			switch($data['type']){
				//上线
				case 1:
					//登录
					if(!$data['username']){
						$server->push($frame->fd, $this->ajaxReturn(['code' => 1000, 'msg' => '请输入用户名']));
						return;
					}
					db('chatroom')->where(['user_id' => $frame->fd])->update([
						'username' => $data['username'],
					]);
					
					$msg = [
						'type'     => 1,
						'username' => $data['username'],
					];
					break;
				//聊天
				case 2:
					$info = db('chatroom')->where(['user_id' => $frame->fd])->find();
					if(!$info['username']){
						$server->push($frame->fd, $this->ajaxReturn(['code' => 1000, 'msg' => '请先登录']));
						return;
					}
					
					$msg = [
						'type'     => 2,
						'username' => $info['username'],
						'content'  => $data['content'],
					];
					break;
				//离线
				case 3:
					$info = db('chatroom')->where(['user_id' => $frame->fd])->find();
					$msg  = [
						'type'     => 3,
						'username' => $info['username'],
					];
					break;
				default:
					$msg = '';
					break;
			}
			
			var_dump($msg);
			
			$user = db('chatroom')->where(['status' => 1])->select();
			
			foreach($user as $k => $v){
				$server->push($v['user_id'], $this->ajaxReturn(['code'=>0,'msg'=>'ok','data'=>$msg]));
			}
		});
		
		//监听WebSocket连接关闭事件
		$ws->on('close', function($ws, $fd){
			db('chatroom')->where(['user_id' => $fd])->update([
				'status'      => 0,
				'update_time' => time(),
			]);
			echo $fd."退出\n";
		});
		
		$ws->start();
	}
	
	
}