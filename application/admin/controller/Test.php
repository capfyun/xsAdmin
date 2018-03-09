<?php
/**
 * 测试控制器
 * @author 夏爽
 */
namespace app\admin\controller;

use \MongoDB\Driver\Manager;

class Test extends \think\Controller{
	public function index(){
	}
	
	/**
	 * 测试
	 */
	public function test(){
		$this->result('asdasd','123','123','json');
		echo 'aaaaa';exit();
		
		
		$auth = 'config/config_set';
		echo ( $auth == $query = preg_replace('/^.+\?/U', '', $auth)  ) ? 'abc' : '123';
		
		
		halt($query);
		
		$a = service('Auth')->check('index/index,config/config_set',2);
		halt($a);
		
		return $this->fetch();
	}
	
	public function aaaa($c,...$a) {
		dump($c);
		halt($a);
		
	}
	
	public function aaa(){
		$a = 2;
		for($i=1; $i<=10000; $i++){
			$a+=4;
			if($a%60 ==0){
				echo '正常,$a='.$a;
			}
		}
		echo '没有';
	}
	
	public function debug(){
	}
	
	public function socket(){
		service('WebSocket')->service([
			'event'   => [service('ChatRoom'), 'callback'], //回调函数
			'log'     => false, //是否开启日志
			'address' => '127.0.0.1', //地址
			'port'    => 8090, //端口
			'timeout' => null, //超时时间
			'max'     => 1024, //最大连接数
		])->run();
	}
	
}
