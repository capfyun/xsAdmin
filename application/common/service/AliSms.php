<?php
/**
 * 阿里短信服务
 * @author xs
 */
namespace app\common\service;

use Aliyun\DySDKLite\SignatureHelper;

class AliSms extends Base{
	
	//密钥
	protected $access_key_id     = '8Hg70iSdonOMf6Yt';
	protected $access_key_secret = 'fITEZVUpTCRCQ9eKTFbhb7t0h9COzu';
	//短信签名
	protected $sign_name = 'PAPA游戏';
	//短信类型
	protected $send_type = '';
	
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 初始化配置
	 * @param array $config
	 */
	public function option($config = []){
		foreach($config as $k => $v){
			!isset($this->$k) || $this->$k = $v;
		}
		return $this;
	}
	
	/**
	 * 发送短信（单条）
	 * @param string $mobile 手机号
	 * @param string $type 短信类型
	 * @param array $param 参数
	 * @return bool
	 */
	public function send($mobile, $type = '', $param = []){
		//获取短信类型
		$code = $this->getSendType($type);
		if(!$code){
			return false;
		}
		return $this->sendSms($mobile, $code, $param);
	}
	
	/**
	 * 获取短信类型
	 * @param int $type 类型
	 * @return bool|string
	 */
	protected function getSendType($type){
		switch($type){
			//验证码
			case 'verify_code':
				$code    = 'SMS_127154126';
				$content = '验证码${code}。请于10分钟内填写，泄漏有风险，如非本人操作，请忽略本短信。';
				break;
			default:
				$this->error = '短信类型不存在';
				return false;
		}
		return $code;
	}
	
	/**
	 * 发送短信
	 * @param string $mobile 手机号
	 * @param string $code 模版
	 * @param array $param 参数
	 * @return bool|\stdClass
	 */
	protected function sendSms($mobile, $code, $param = []){
		
		$params = [
			//必填: 短信接收号码
			'PhoneNumbers'    => $mobile,
			//必填: 短信签名，应严格按"签名名称"填写
			'SignName'        => $this->sign_name,
			//必填: 短信模板Code，应严格按"模板CODE"填写
			'TemplateCode'    => $code,
			//可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
			'TemplateParam'   => $param,
			//可选: 设置发送短信流水号
			'OutId'           => '12345',
			//可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
			'SmsUpExtendCode' => '1234567',
		];
		
		// *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
		if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])){
			$params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
		}
		
		// 初始化SignatureHelper实例用于设置参数，签名以及发送请求
		$helper = new SignatureHelper();
		
		// 此处可能会抛出异常，注意catch
		$content = $helper->request(
			$this->access_key_id,
			$this->access_key_secret,
			"dysmsapi.aliyuncs.com",
			array_merge($params, array(
				"RegionId" => "cn-hangzhou",
				"Action"   => "SendSms",
				"Version"  => "2017-05-25",
			))
		// fixme 选填: 启用https
		// ,true
		);
		
		return $content;
	}
	
	/**
	 * 批量发送短信
	 */
	public function sendBatchSms($mobiles, $code, $params = []){
		
		$params = [
			//必填: 待发送手机号。支持JSON格式的批量调用，批量上限为100个手机号码,批量调用相对于单条调用及时性稍有延迟,验证码类型的短信推荐使用单条调用的方式
			'PhoneNumberJson'   => $mobiles,
			//必填: 短信签名，支持不同的号码发送不同的短信签名，每个签名都应严格按"签名名称"填写
			'SignNameJson'      => $this->sign_name,
			//必填: 短信模板Code，应严格按"模板CODE"填写
			'TemplateCode'      => $code,
			//必填: 模板中的变量替换JSON串,如模板内容为"亲爱的${name},您的验证码为${code}"时,此处的值为
			'TemplateParamJson' => $params,
			//可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
			//'SmsUpExtendCodeJson' => json_encode(array("90997","90998"))
		];
		
		// *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
		$params["TemplateParamJson"] = json_encode($params["TemplateParamJson"], JSON_UNESCAPED_UNICODE);
		$params["SignNameJson"]      = json_encode($params["SignNameJson"], JSON_UNESCAPED_UNICODE);
		$params["PhoneNumberJson"]   = json_encode($params["PhoneNumberJson"], JSON_UNESCAPED_UNICODE);
		
		if(!empty($params["SmsUpExtendCodeJson"] && is_array($params["SmsUpExtendCodeJson"]))){
			$params["SmsUpExtendCodeJson"] = json_encode($params["SmsUpExtendCodeJson"], JSON_UNESCAPED_UNICODE);
		}
		
		// 初始化SignatureHelper实例用于设置参数，签名以及发送请求
		$helper = new SignatureHelper();
		
		// 此处可能会抛出异常，注意catch
		$content = $helper->request(
			$this->access_key_id,
			$this->access_key_secret,
			"dysmsapi.aliyuncs.com",
			array_merge($params, array(
				"RegionId" => "cn-hangzhou",
				"Action"   => "SendBatchSms",
				"Version"  => "2017-05-25",
			))
		// fixme 选填: 启用https
		// ,true
		);
		
		return $content;
	}
	
	
	/**
	 * 短信发送记录查询 TODO 待修改
	 */
	public function querySendDetails() {
		
		$params = array ();
		
		// *** 需用户填写部分 ***
		
		// fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
		$accessKeyId = "your access key id";
		$accessKeySecret = "your access key secret";
		
		// fixme 必填: 短信接收号码
		$params["PhoneNumber"] = "17000000000";
		
		// fixme 必填: 短信发送日期，格式Ymd，支持近30天记录查询
		$params["SendDate"] = "20170710";
		
		// fixme 必填: 分页大小
		$params["PageSize"] = 10;
		
		// fixme 必填: 当前页码
		$params["CurrentPage"] = 1;
		
		// fixme 可选: 设置发送短信流水号
		$params["BizId"] = "yourBizId";
		
		// *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
		
		// 初始化SignatureHelper实例用于设置参数，签名以及发送请求
		$helper = new SignatureHelper();
		
		// 此处可能会抛出异常，注意catch
		$content = $helper->request(
			$accessKeyId,
			$accessKeySecret,
			"dysmsapi.aliyuncs.com",
			array_merge($params, array(
				"RegionId" => "cn-hangzhou",
				"Action" => "QuerySendDetails",
				"Version" => "2017-05-25",
			))
		// fixme 选填: 启用https
		// ,true
		);
		
		return $content;
	}
	
}
