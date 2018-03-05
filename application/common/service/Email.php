<?php
/**
 * 服务层-电子邮件
 * @author 夏爽
 */
namespace app\common\service;


class Email extends Base{
	//邮箱默认配置
	private $config = [
		'host'     => 'smtp.163.com', //smtp服务器的名称
		'username' => '18668088112@163.com', //邮箱用户名
		'password' => '', //邮箱密码
		'fromname' => '七果游戏', //发件人姓名
	];
	
	/**
	 * 初始化
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 配置参数
	 * @param array $config 参数
	 * @return $this
	 */
	public function setConfig($config = []){
		/* 配置参数 */
		$this->config = array_merge($this->config, $config);
		return $this;
	}
	
	/**
	 * 邮件发送
	 * @param  array $param 邮件内容 ['to'=>'','name'=> '尊敬的客户','title'=> '','content' =>'',]
	 * @return boolean
	 */
	public function send($param = []){
		$param = array_merge([
			'to'      => '', //收件地址
			'name'    => '尊敬的客户', //收件人名称
			'title'   => '', //标题
			'content' => '', //内容
		], $param);
		//实例化
		$mail = new \PHPMailer\PHPMailer();
		//配置参数
		$mail->IsSMTP();            //启用SMTP
		$mail->IsHTML(true);        //是否HTML格式邮件
		$mail->AddAddress($param['to'], $param['name']);
		$mail->SMTPAuth = true; //启用smtp认证
		$mail->WordWrap = 50;    //设置每行字符长度
		$mail->CharSet  = 'utf-8'; //设置邮件编码
		$mail->AltBody  = '这是一封HTML电子邮件'; //邮件正文不支持HTML的备用显示
		$mail->Host     = $this->config['host']; //smtp服务器的名称（这里以QQ邮箱为例）
		$mail->Username = $this->config['username']; //你的邮箱名
		$mail->Password = $this->config['password']; //邮箱密码
		$mail->From     = $this->config['username']; //发件人地址（也就是你的邮箱地址）
		$mail->FromName = $this->config['fromname']; //发件人姓名
		$mail->Subject  = $param['title']; //邮件主题
		$mail->Body     = $param['content']; //邮件内容
		//发送
		return $mail->Send();
	}
	
}
