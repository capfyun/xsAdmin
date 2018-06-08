<?php
/**
 * 服务层-swoole扩展类
 * @author xs
 */
namespace app\common\service;

class Swoole extends Base{
	
	/**
	 * 创建TCP服务器
	 */
	public function tcp_server(){
		//创建Server对象，监听 127.0.0.1:9501端口
		$serv = new \swoole_server("127.0.0.1", 9501);
		
		//监听连接进入事件
		$serv->on('connect', function($serv, $fd){
			echo "Client: Connect.\n";
		});
		
		//监听数据接收事件
		$serv->on('receive', function($serv, $fd, $from_id, $data){
			$serv->send($fd, "Server: ".$data);
		});
		
		//监听连接关闭事件
		$serv->on('close', function($serv, $fd){
			echo "Client: Close.\n";
		});
		
		//启动服务器
		$serv->start();
	}
	
	/**
	 * 创建UDP服务器
	 */
	public function udp_server(){
		//创建Server对象，监听 127.0.0.1:9502端口，类型为SWOOLE_SOCK_UDP
		$serv = new \swoole_server("127.0.0.1", 9502, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);
		
		//监听数据接收事件
		$serv->on('Packet', function($serv, $data, $clientInfo){
			$serv->sendto($clientInfo['address'], $clientInfo['port'], "Server ".$data);
			var_dump($clientInfo);
		});
		
		//启动服务器
		$serv->start();
	}
	
	/**
	 * 创建Web服务器
	 */
	public function http_server(){
		$http = new \swoole_http_server("0.0.0.0", 9501);
		
		$http->on('request', function($request, $response){
			var_dump($request->get, $request->post);
			$response->header("Content-Type", "text/html; charset=utf-8");
			$response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
		});
		
		$http->start();
	}
	
	/**
	 * 创建WebSocket服务器
	 */
	public function ws_server(){
		//创建websocket服务器对象，监听0.0.0.0:9502端口
		$ws = new \swoole_websocket_server("0.0.0.0", 9501);
		
		//监听WebSocket连接打开事件
		$ws->on('open', function(\swoole_websocket_server $server, $request){
			var_dump($request->fd, $request->server);
			if(isset($request->get)){
				var_dump($request->get);
			}
			$server->push($request->fd, "hello, welcome\n");
		});
		
		//监听WebSocket消息事件
		$ws->on('message', function(\swoole_websocket_server $server, $frame){
			echo "Message: {$frame->data}\n";
			$server->push($frame->fd, "server: {$frame->data}");
		});
		
		//监听WebSocket连接关闭事件
		$ws->on('close', function($ws, $fd){
			echo "client-{$fd} is closed\n";
		});
		
		$ws->start();
	}
	
	
}
