<?php
/**
 * WebSocket
 * @author xs
 */
namespace xs;

class WebSocket{
	//socket主机
	public $master = null;
	//连接池
	public $sockets = [];
	//握手
	public $hand = false;
	//配置
	public $config = [
		'event'   => null, //回调函数
		'log'     => false, //是否开启日志
		'address' => '127.0.0.1', //地址
		'port'    => 2000, //端口
		'timeout' => null, //超时时间
		'max'     => 1024, //最大连接数
	];
	/* 事件 */
	const EVENT_OPEN    = 'open'; //开启连接
	const EVENT_CLOSE   = 'close'; //关闭连接
	const EVENT_HAND    = 'hand'; //牵手
	const EVENT_REQUEST = 'request'; //访问
	
	/**
	 * 创建服务端
	 * @param array $config
	 * @return $this
	 */
	public function service($config = []){
//		substr(php_sapi_name(), 0, 3)=='cli'
//		or exit("请通过命令行模式运行!");
		error_reporting(E_ALL);
		set_time_limit(0);
		ob_implicit_flush();
		//获取配置
		$this->config = array_merge($this->config, $config);
		//链接
		$this->master    = $this->create($this->config['address'], $this->config['port']);
		$this->sockets[] = $this->master;
		return $this;
	}
	
	/**
	 * 创建一个socket
	 * @param string $address 地址
	 * @param int $port 端口号
	 * @return resource
	 */
	private function create($address = '', $port = 0){
		//建立一个socket套接字
		$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)
		or exit("socket_create() failed");
		//配置参数
		socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1)
		or exit("socket_option() failed");
		//绑定
		socket_bind($server, $address, $port)
		or exit("socket_bind() failed");
		//监听
		socket_listen($server, 2)
		or exit("socket_listen() failed");
		
		$this->log('开始监听: '.$address.' : '.$port);
		return $server;
	}
	
	/**
	 * 运行
	 */
	public function run(){
		
		while(true){
			//阻塞接收客户端链接
			@socket_select($this->sockets, $write = null, $except = null, $this->config['timeout']);
			foreach($this->sockets as $k => $v){
				//连接主机的client
				if($v==$this->master){
					$client     = socket_accept($this->master);
					$this->hand = false;
					if($client<0) continue;
					//超过最大连接数
					if(count($this->sockets)>$this->config['max']) continue;
					
					$this->sockets[] = $client;
					$this->event(['type' => self::EVENT_OPEN, 'socket' => $v, 'websocket' => $this]);
				}else{
					$byte = @socket_recv($v, $buffer, 2048, 0);
					//关闭连接
					if($byte<7 || $byte>1000){
						$this->close($v, $k);
						$this->event(['type' => self::EVENT_CLOSE, 'socket' => $v, 'websocket' => $this]);
						continue;
					}
					//没有握手进行握手
					if(!$this->hand){
						$this->handshake($v, $buffer);
						$this->event(['type' => self::EVENT_HAND, 'socket' => $v, 'websocket' => $this]);
					}else{
						$buffer = $this->decode($buffer);
						$this->event(['type' => self::EVENT_REQUEST, 'socket' => $v, 'websocket' => $this, 'msg' => $buffer]);
					}
				}
			}
		}
	}
	
	/**
	 * 回调函数
	 */
	public function callback($param = []){
		switch($param['type']){
			case 'in':
				break;
			case 'out':
				break;
			case 'msg';
				$this->send($param['socket'], $param['msg']);
				break;
		}
	}
	
	
	
	/**
	 * 推送消息
	 * @param resource $socket
	 * @param string $msg 消息
	 * @return bool|int
	 */
	public function send($socket, $msg = ''){
		$msg = $this->encode($msg);
		return socket_write($socket, $msg, strlen($msg));
	}
	
	/**
	 * 与客户端握手连接
	 * @param resource $socket
	 * @param string $buffer
	 */
	private function handshake($socket, $buffer = ''){
		// 获取加密key
		$accept_key = $this->encry($buffer);
		$upgrade    = "HTTP/1.1 101 Switching Protocols\r\n".
			"Upgrade: websocket\r\n".
			"Connection: Upgrade\r\n".
			"Sec-WebSocket-Accept: ".$accept_key."\r\n"."\r\n";
		// 写入socket
		socket_write($socket, $upgrade, strlen($upgrade));
		// 标记握手已经成功，下次接受数据采用数据帧格式
		$this->hand = true;
		return true;
	}
	
	/**
	 * 事件回调
	 * @param array $param 回调参数
	 */
	private function event($param = []){
		if($this->config['event']){
			call_user_func($this->config['event'], $param);
		}
	}
	
	/**
	 * 日志记录
	 * @param string $str 内容
	 */
	private function log($str = ''){
		if($this->config['log']){
			$str = $str."\r\n";
			fwrite(STDOUT, iconv('utf-8', 'gbk//IGNORE', $str));
		}
	}
	
	/**
	 * 断开连接
	 * @param resource $socket
	 */
	private function close($socket, $key){
		//删除变量
		unset($this->sockets[$key]);
		//释放资源
		socket_close($socket);
		return true;
	}
	
	/**
	 * 加密WebSocket-Key
	 * @param string $buffer
	 * @return string
	 */
	private function encry($buffer){
		$key  = $this->getWebSocketKey($buffer);
		$mask = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
		return base64_encode(sha1($key.$mask, true));
	}
	
	/**
	 * 获取WebSocket-Key
	 * @param string $buffer
	 * @return string
	 */
	private function getWebSocketKey($buffer = ''){
		$key = '';
		if(preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $buffer, $match)){
			$key = $match[1];
		}
		return $key;
	}
	
	/**
	 * 解包
	 * @param string $buffer 解包的字符串
	 * @return string
	 */
	private function decode($buffer = ''){
		
		$len = ord($buffer[1])&127;
		if($len===126){
			$masks = substr($buffer, 4, 4);
			$data  = substr($buffer, 8);
		}else if($len===127){
			$masks = substr($buffer, 10, 4);
			$data  = substr($buffer, 14);
		}else{
			$masks = substr($buffer, 2, 4);
			$data  = substr($buffer, 6);
		}
		
		$decoded = '';
		for($index = 0; $index<strlen($data); $index++){
			$decoded .= $data[$index]^$masks[$index%4];
		}
		return $decoded;
	}
	
	/**
	 * 解包（备用）
	 * @param string $buffer 解包的字符串
	 * @return string
	 */
	private function decode1($buffer = ''){
		$mask = [];
		$data = '';
		$msg  = unpack('H*', $buffer);
		$head = substr($msg[1], 0, 2);
		if(hexdec($head{1})===8){
			$data = false;
		}else if(hexdec($head{1})===1){
			$mask[] = hexdec(substr($msg[1], 4, 2));
			$mask[] = hexdec(substr($msg[1], 6, 2));
			$mask[] = hexdec(substr($msg[1], 8, 2));
			$mask[] = hexdec(substr($msg[1], 10, 2));
			$s      = 12;
			$e      = strlen($msg[1])-2;
			$n      = 0;
			for($i = $s; $i<=$e; $i += 2){
				$data .= chr($mask[$n%4]^hexdec(substr($msg[1], $i, 2)));
				$n++;
			}
		}
		return $data;
	}
	
	/**
	 * 解包（备用）
	 * @param string $buffer 解包的字符串
	 * @return string
	 */
	private function decode2($buffer){
		$a = str_split($buffer, 125);
		if(count($a)==1){
			return "\x81".chr(strlen($a[0])).$a[0];
		}
		$ns = "";
		foreach($a as $o){
			$ns .= "\x81".chr(strlen($o)).$o;
		}
		return $ns;
	}
	
	/**
	 * 打包
	 * @param string $buffer 需要打包的字符串
	 * @return string
	 */
	private static function encode($buffer){
		$len = strlen($buffer);
		if($len<=125){
			return "\x81".chr($len).$buffer;
		}else if($len<=65535){
			return "\x81".chr(126).pack("n", $len).$buffer;
		}else{
			return "\x81".char(127).pack("xxxxN", $len).$buffer;
		}
	}
	
	/**
	 * 打包（备用）
	 * @param string $buffer 需要打包的字符串
	 * @return string
	 */
	private function encode1($msg){
		$msg      = preg_replace(array('/\r$/', '/\n$/', '/\r\n$/',), '', $msg);
		$frame    = array();
		$frame[0] = '81';
		$len      = strlen($msg);
		$frame[1] = $len<16 ? '0'.dechex($len) : dechex($len);
		$frame[2] = '';
		for($i = 0; $i<strlen($msg); $i++){
			$frame[2] .= dechex(ord($msg{$i}));
		}
		
		$data = implode('', $frame);
		return pack("H*", $data);
	}
	
}

