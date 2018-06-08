<?php
/**
 * 短信
 * @author xs
 */
namespace xs;


class Sms {
	
	/**
	 * 短息发送
	 * @param  string $moblie 手机号，多个用","分隔
	 * @param  string $content 短信内容
	 * @return boolean
	 */
	public static function send($moblie, $content){
		//获取配置
		$sn = 'SDK-MOV-010-00345'; //序列号
		$pass = ''; //密码
		
		//构造要post的字符串
		$param = http_build_query([
			'sn'      => $sn, //序列号
			'pwd'     => strtoupper(md5($sn.$pass)), //此处密码需要加密 加密方式为 md5(sn+password) 32位大写
			'mobile'  => $moblie, //手机号 多个用英文的逗号隔开 post理论没有长度限制.推荐群发一次小于等于100
			'content' => iconv("UTF-8", "gb2312", $content), //短信内容
			'ext'     => '',
			'stime'   => '',//定时时间 格式为2011-6-29 11:09:21
			'rrid'    => '',
		]);
		$length = strlen($param);
		//创建socket连接
		$fp = fsockopen("sdk.entinfo.cn", 8060, $errno, $errstr, 10) or exit($errstr."--->".$errno);
		//构造post请求的头
		$data = "POST /webservice.asmx/mt HTTP/1.1\r\n".
			"Host:sdk.entinfo.cn\r\n".
			"Content-Type: application/x-www-form-urlencoded\r\n".
			"Content-Length: ".$length."\r\n".
			"Connection: Close\r\n\r\n".
			$param."\r\n"; //添加post的字符串
		//发送post的数据
		fputs($fp, $data);
		$inheader = 1;
		while(!feof($fp)){
			$line = fgets($fp, 1024); //去除请求包的头只显示页面的返回数据
			if($inheader && ($line=="\n" || $line=="\r\n")){
				$inheader = 0;
			}
			if($inheader==0){
				// echo $line;
			}
		}
		$line   = str_replace("<string xmlns=\"http://tempuri.org/\">", "", $line);
		$line   = str_replace("</string>", "", $line);
		$result = explode("-", $line);
		return count($result)>1 ? false : true;
	}
	
}
