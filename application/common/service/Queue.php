<?php
/**
 * 服务层-消息列队
 * @author xs
 */
namespace app\common\service;

class Queue extends Base{
	/**
	 * @var \Redis redis对象
	 */
	protected static $redis = null;
	//前缀
	protected $prefix = 'queue_';
	//每次处理数据数
	protected $count = 10;
	//定时任务运行间隔
	protected $timeing = 10;
	
	/**
	 * 初始化
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 定时任务
	 */
	public function timeing(){
		set_time_limit(120); //脚本最大执行时间
		//查询需要执行的间隔
		$interval = db('queue')->where(['status' => 1])->group('`interval`')->column('interval');
		/* 计时器 */
		$timer = $this->incrTimer($this->timeing); //计时器
		//计数自动校准
		$remainder = $timer%$this->timeing;
		if($remainder!=0){
			$timer = $this->incrTimer($remainder);
		}
		
		/* 执行 */
		foreach($interval as $k => $v){
			if($timer%$v==0){
				//多线程执行
				service('Thread')->push('Queue/run', $v);
			}
		}
	}
	
	/**
	 * 增加列队任务
	 * @param string $name 列队名
	 * @param array $param ['name'=>'名称','exec'=>'','$interval'=>60,'count'=>10] 执行方法，默认service层，层/类/方法，如：service/user/getName 或 user/getName
	 * @return bool
	 */
	public function push($param = []){
		//获取参数
		if(!$param['name'] || !$param['exec'] || $param['name']=='timer'){
			$this->error = '参数错误';
			return false;
		}
		$param = array_merge([
			'name'     => '',
			'exec'     => '',
			'interval' => 60, //间隔秒
			'count'    => 10, //执行次数
		], $param);
		
		//生成名称
		$name   = $this->getName($param['name']);
		$result = $this->decodeExec($param['exec']);
		if(!$result){
			return false;
		}
		
		/* 对比数据库 */
		$info = db('queue')->where(['name' => $name])->find();
		//更新
		if(!$info){
			//新增
			$result = db('queue')->insert([
				'name'        => $name,
				'interval'    => $param['interval'],
				'exec'        => $param['exec'],
				'count'       => $param['count'],
				'status'      => 1,
				'create_time' => time(),
				'update_time' => time(),
			]);
			if(!$result){
				$this->error = '入库失败';
				return false;
			}
		}else if($info['interval']!=$param['interval'] || $info['exec']!=$param['exec'] || $info['count']!=$param['count']){
			//更新
			$result = db('queue')->where(['name' => $name])->update([
				'interval'    => $param['interval'],
				'exec'        => $param['exec'],
				'count'       => $param['count'],
				'update_time' => time(),
			]);
			if(!$result){
				$this->error = '入库失败';
				return false;
			}
		}
		
		/* 保存参数 */
		$data = func_get_args();
		array_shift($data);
		service('Redis')->rPush($name, $data);
		return true;
	}
	
	/**
	 * 执行列队任务
	 * @param int $interval 执行的时间
	 * @return bool
	 */
	public function run($interval = 0){
		if($interval<=0){
			$this->error = '参数错误';
			return false;
		}
		$list = db('queue')
			->where(['status' => 1, 'interval' => $interval])
			->limit(1000)->select();
		foreach($list as $k => $v){
			//执行方法
			$exec = $this->decodeExec($v['exec']);
			if(!$exec){
				continue;
			}
			//处理数量
			for($i = 1; $i<=$v['count']; $i++){
				//获取参数
				$param = service('Redis')->lPop($v['name']);
				//没有数据则结束
				if($param===false){
					break;
				}
				//必须为有效数据
				if(!is_array($param)){
					continue;
				}
				/* 执行 */
				$model = model($exec['class'], $exec['layer']);
				call_user_func_array([$model, $exec['action']], $param);
			}
		}
		return true;
	}
	
	/**
	 * 删除列队
	 * @param string $name 列队名
	 * @return bool
	 */
	public function del($name = ''){
		//生成名称
		$name = $this->getName($name);
		//删除任务
		$result = db('queue')->where(['name' => $name])->delete();
		if(!$result){
			$this->error = '删除失败';
			return false;
		}
		return true;
	}
	
	/**
	 * 生成名称
	 * @param string $tag 标签名
	 * @return string
	 */
	protected function getName($tag = ''){
		//生成名称
		$name = $this->prefix.$tag;
		return strtolower($name);
	}
	
	/**
	 * 解析执行方法
	 * @param string $exec
	 * @return array|bool
	 */
	protected function decodeExec($exec = ''){
		//校验
		$param = explode('/', $exec);
		switch(count($param)){
			case 2:
				$layer = 'service';
				list($class, $action) = explode('/', $exec);
				break;
			case 3:
				list($layer, $class, $action) = explode('/', $exec);
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
	 * 计时器自增
	 * @param int $number 自增数
	 * @return int
	 */
	protected function incrTimer($number = 0){
		$name = $this->getName('timer');
		return service('Redis')->inc($name, $number);
	}
	
}
