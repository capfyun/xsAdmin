<?php
/**
 * 伪多线程，异步操作
 * @author xs
 */
namespace lib;

class Thread {
	//密钥
	protected $secret_key = 'k+_b}yC2Hx~:uZ/O=a9g-0{6^B|LhfwFlG@I?1MY';
	//入口地址
	protected $portal_url = 'http://crm.7guoyouxi.com/open/thread';
	
	/**
	 * 异步线程
	 * @param string $url 地址（如控制器/方法）
	 * @param array $data 参数
	 * @param string $method 请求方式
	 * @return bool
	 */
	public function async($url, $param = [], $method = 'POST'){
		//配置
		$info = array_merge([
			'host'  => '',
			'path'  => '',
			'query' => '',
		], parse_url($url));
		//参数
		$query = trim(http_build_query($param)); //参数
		
		//传输方式
		switch(strtoupper($method)){
			case 'POST':
				$head = 'POST '.$info['path'].'?'.$info['query'].' HTTP/1.0'."\r\n";
				$head .= 'Host: '.$info['host']."\r\n";
				$head .= 'Referer: http://'.$info['host'].$info['path']."\r\n";
				$head .= 'Content-type: application/x-www-form-urlencoded'."\r\n";
				$head .= 'Content-Length: '.strlen($query)."\r\n";
				$head .= "\r\n";
				$head .= $query;
				break;
			default:
				$head = strtoupper($method).' '.$info['path'].'?'.$query.' HTTP/1.0'."\r\n";
				$head .= 'Host: '.$info['host']."\r\n";
				$head .= "\r\n";
		}
		$fp = fsockopen($info['host'], 80, $errno, $errstr, 3);
		if(!$fp){
			$this->error = $errstr.'('.$errno.')';
			return false;
		}
		//执行
		fwrite($fp, $head);
		//忽略执行结果
//		while(!feof($fp)){
//			echo fread($fp, 4096);
//		}
		//关闭
		fclose($fp);
		return true;
	}
	
	/**
	 * 新增线程任务
	 * @param string $exec 执行方法，默认service层，层/类/方法，如：service/user/getName 或 user/getName
	 * @return bool
	 */
	public function push($exec = ''){
		//校验格式
		$result = $this->decodeExec($exec);
		if(!$result) return false;
		//参数
		$param = func_get_args();
		array_shift($param);
		//参数加密
		$time = time();
		$data = [
			'_time'  => $time,
			'_hash'  => $this->encode($exec, $time),
			'_exec'  => $exec,
			'_param' => $param,
		];
		//执行
		return $this->async($this->portal_url, $data, 'POST');
	}
	
	/**
	 * 多线程入口
	 * @return bool
	 */
	public function portal(){
		//不中断脚本
		ignore_user_abort(true);
		//获取参数
		$_param = request()->post('_param/a');
		$_exec  = request()->post('_exec');
		$_hash  = request()->post('_hash');
		$_time  = request()->post('_time');
		//校验参数
		$hash = $this->encode($_exec, $_time);
		if($_hash!=$hash){
			$this->error = '参数校验失败';
			return false;
		}
		//解析执行方法
		$exec = $this->decodeExec($_exec);
		if(!$exec){
			return false;
		}
		//执行
		$model = model($exec['class'], $exec['layer']);
		call_user_func_array([$model, $exec['action']], $_param);
		return true;
	}
	
	
	/**
	 * 解析执行方法
	 * @param string $action
	 * @return array|bool
	 */
	protected function decodeExec($exec = ''){
		//校验
		$param = explode('/', $exec);
		switch(count($param)){
			case 2:
				$layer = 'service';
				list($class, $action) = $param;
				break;
			case 3:
				list($layer, $class, $action) = $param;
				break;
			default:
				$this->error = '格式不正确';
				return false;
		}
		return [
			'layer'  => $layer,
			'class'  => $class,
			'action' => $action,
		];
	}
	
	/**
	 * 加密参数
	 * @param string $str 加密字符串
	 * @param int $time 时间戳
	 * @return string
	 */
	protected function encode($str = '', $time = 0){
		//规则
		$hash = $this->secret_key.$time.$str;
		return md5($hash);
	}
}
