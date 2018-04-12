<?php
/**
 * 验证码
 * @author 夏爽
 */
namespace app\common\model;

class VerifyCode extends Base{
	
	/* 自动完成 */
	//写入（包含新增、更新）时自动完成
	protected $auto = [];
	//新增时自动完成
	protected $insert = [
		'status' => 1, //状态[0禁用-1启用]
	];
	//更新时自动完成
	protected $update = [
	];
	
	//自动写入时间
	protected $autoWriteTimestamp = true;    //模型中定义autoWriteTimestamp属性，true时间戳-datetime时间格式-false关闭写入
	protected $updateTime         = false;    //更新时间字段，默认为update_time，false关闭写入
	
	//只读字段
	protected $readonly = ['mobile'];    //模型中定义readonly属性，配置指定只读字段
	
	//验证码发送间隔，秒
	protected $confine_time = 60;
	//每天验证码条数
	protected $confine_max = 20;
	//验证码有效时长，秒
	protected $valid_time = 600;
	
	/**
	 * 发送验证码
	 * @param string $mobile 手机号
	 * @param int $type 验证码类型
	 * @return bool
	 */
	public function send($mobile, $type){
		//发送限制
		$result = $this->confine($mobile);
		if(!$result){
			return false;
		}
		//生成验证码
		$code = rand(100000, 999999);
		//发送短信
		$result = service('AliSms')->send($mobile, 'verify_code', ['code' => $code]);
		if(!$result){
			$this->error = '短信发送失败';
			return false;
		}
		//入库
		$result = $this->isUpdate(false)
			->save(['mobile' => $mobile, 'code' => $code, 'type' => $type]);
		if(!$result){
			$this->error = '短信发送失败';
			return false;
		}
		return true;
	}
	
	/**
	 * 校验验证码
	 */
	/**
	 * 校验验证码
	 * @param string $mobile 手机号
	 * @param string $code 验证码
	 * @param int $type 验证码类型
	 */
	public function check($mobile, $code, $type){
		$verify_code = $this->get(['mobile' => $mobile, 'code' => $code, 'type' => $type]);
		if(!$verify_code){
			$this->error = '验证码不存在';
			return false;
		}
		if($verify_code->status!=1 || $verify_code->create_time+$this->valid_time<time()){
			$this->error = '验证码已失效';
			return false;
		}
		$result = $verify_code->isUpdate(true)->save(['status' => 2]);
		if(!$result){
			$this->error = '操作失败';
			return false;
		}
		return true;
	}
	
	/**
	 * 发送限制
	 * @param string $mobile 手机号
	 */
	protected function confine($mobile){
		$verify_code = $this->where(['mobile' => $mobile])->order('id DESC')->find();
		if($verify_code){
			//发送间隔
			if($verify_code['create_time']+$this->confine_time>time()){
				$this->error = "两次发送验证码时间至少{$this->confine_time}秒";
				return false;
			}
			//发送次数
			$time = strtotime(date('Y-m-d 00:00:00'));
			$num  = $this->where(['mobile' => $mobile, 'create_time' => ['egt', $time]])->count();
			if($num>$this->confine_max){
				$this->error = '今天发送验证码的次数已达上限';
				return false;
			}
		}
		return true;
	}
	
}
