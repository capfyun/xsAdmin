<?php
/**
 * 伪多线程，异步操作
 * @author xs
 */
namespace lib;

class Thread{
	/**
	 * @var array 缓存的实例
	 */
	public static $instance = [];
	/**
	 * 错误信息
	 * @var string
	 */
	private $error = '';
	/**
	 * 密钥
	 * @var string
	 */
	private $key = '6c17d28ar315uz5m';
	/**
	 * 异步入口地址
	 * @var string
	 */
	private $url = 'http://xs.local/home/index/index';
	
	/**
	 * 初始化
	 */
	private function __construct($option = []){
		isset($option['key']) && $this->key = $option['key'];
		isset($option['url']) && $this->key = $option['url'];
	}
	
	/**
	 * 连接驱动
	 * @param array $option 配置数组
	 * @return static
	 */
	public static function instance(array $option = []){
		ksort($option);
		$name = md5(serialize($option));
		if(!isset(self::$instance[$name])){
			self::$instance[$name] = new static($option);
		}
		return self::$instance[$name];
	}
	
	/**
	 * 异步线程
	 * @param string $url 地址（如控制器/方法）
	 * @param array $data 参数
	 * @param string $method 请求方式
	 * @param bool $sync 是否同步
	 * @return bool | string
	 */
	public static function async($url, $param = [], $method = 'POST', $sync = false){
		//解析地址
		$parse   = parse_url($url);
		$host    = isset($parse['host']) ? $parse['host'] : '';
		$path    = isset($parse['path']) ? $parse['path'] : '';
		$query   = isset($parse['query']) ? $parse['query'] : '';
		$content = trim(str_replace('amp;', '', http_build_query($param)));
		$method  = strtoupper($method);
		//传输方式
		switch($method){
			case 'POST':
				$length = strlen($content);
				$head   = "{$method} {$path}?{$query} HTTP/1.0\r\n"
					."Host: {$host}\r\n"
					."Referer: http://{$host}{$path}\r\n"
					."Content-type: application/x-www-form-urlencoded\r\n"
					."Content-Length: {$length}\r\n\r\n"
					.$content;
				break;
			default:
				$query = $query.'&'.$content;
				$head  = "{$method} {$path}?{$query} HTTP/1.0\r\n"
					."Host: {$host}\r\n\r\n";
		}
		$fp = fsockopen($host, 80, $errno, $errstr, 3);
		if(!$fp){
			return false;
		}
		//执行
		fwrite($fp, $head);
		$result = true;
		//同步
		if($sync){
			$result = '';
			while(!feof($fp)){
				$result .= fread($fp, 4096);
			}
		}
		//关闭
		fclose($fp);
		return $result;
	}
	
	/**
	 * 新增线程任务
	 * @param string $exec 执行方法，默认service层，层/类/方法，如：service/user/getName 或 user/getName
	 * @return bool
	 */
	public function push($exec){
		//参数
		$args = func_get_args();
		//发送
		return self::async($this->url, [
			'param' => $this->encrypt($args),
			'hash'  => $this->encrypt(time()),
		], 'POST');
	}
	
	/**
	 * 多线程入口
	 * @return bool
	 */
	public function portal(){
		//不中断脚本
		ignore_user_abort(true);
		//参数
		$param = isset($_POST['param']) ? $this->decrypt($_POST['param']) : '';
		$hash  = isset($_POST['hash']) ? $this->decrypt($_POST['hash']) : '';
		if(!$param || !$hash || !is_array($param) || abs(time()-$hash)<=120){
			return false;
		}
		//解析
		$exec = $this->parse(array_shift($param));
		if(!$exec || !method_exists($exec['class'], $exec['action'])){
			return false;
		}
		//执行
		$class = new $exec['class'];
		return @call_user_func_array([$class, $exec['action']], $param);
	}
	
	/**
	 * 获取错误信息
	 * @return string
	 */
	public function getError(){
		return $this->error;
	}
	
	/**
	 * 解析执行方法
	 * @param string $exec 格式如：\app\model\user=>getName
	 * @return array|bool
	 */
	private function parse($exec){
		$param = explode('=>', $exec);
		if(count($param)!=2 || !$param[0] || !$param[1]){
			return false;
		}
		return ['class' => $param[0], 'action' => $param[1],];
	}
	
	/**
	 * 加密
	 * @param mixed $data 加密变量
	 * @return string
	 */
	private function encrypt($data){
		return Aes::instance($this->key)->encrypt(serialize($data));
	}
	
	/**
	 * 解密
	 * @param string $str 加密字符串
	 * @return mixed
	 */
	private function decrypt($str){
		return unserialize(Aes::instance($this->key)->decrypt($str));
	}
}
