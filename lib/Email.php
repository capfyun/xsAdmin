<?php
/**
 * 电子邮件
 * @author xs
 */
namespace lib;

class Email{
	//邮箱默认配置
	private static $config = [
		'host'     => 'smtp.163.com', //smtp服务器的名称
		'username' => '18668088112@163.com', //邮箱用户名
		'password' => '', //邮箱密码
		'fromname' => '七果游戏', //发件人姓名
	];
	
	/**
	 * 邮件发送
	 * @param  array $param 邮件内容 ['to'=>'','name'=> '尊敬的客户','title'=> '','content' =>'',]
	 * @return boolean
	 */
	public static function send($param = [], $config = []){
		$param  = array_merge([
			'to'      => '', //收件地址
			'name'    => '尊敬的客户', //收件人名称
			'title'   => '', //标题
			'content' => '', //内容
		], $param);
		$config = array_merge(self::$config, $config);
		//实例化
		$mail = new \lib\email\PHPMailer();
		//配置参数
		$mail->IsSMTP();            //启用SMTP
		$mail->IsHTML(true);        //是否HTML格式邮件
		$mail->AddAddress($param['to'], $param['name']);
		$mail->SMTPAuth = true; //启用smtp认证
		$mail->WordWrap = 50;    //设置每行字符长度
		$mail->CharSet  = 'utf-8'; //设置邮件编码
//		$mail->SMTPSecure = 'ssl';
//		$mail->Port = 465;
		$mail->AltBody  = '这是一封HTML电子邮件'; //邮件正文不支持HTML的备用显示
		$mail->Host     = $config['host']; //smtp服务器的名称（这里以QQ邮箱为例）
		$mail->Username = $config['username']; //你的邮箱名
		$mail->Password = $config['password']; //邮箱密码
		$mail->From     = $config['username']; //发件人地址（也就是你的邮箱地址）
		$mail->FromName = $config['fromname']; //发件人姓名
		$mail->Subject  = $param['title']; //邮件主题
		$mail->Body     = $param['content']; //邮件内容
		//发送
		return $mail->Send();
	}
	
}
