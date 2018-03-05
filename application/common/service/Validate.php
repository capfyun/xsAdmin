<?php
/**
 * 服务层-验证
 * @author 夏爽
 */
namespace app\common\service;

class Validate extends Base{
	
	/**
	 * 手机号格式验证
	 * @param  string $mobile 手机号
	 * @param  int $type 验证类型
	 * @return boolean
	 */
	public function mobile($mobile, $type = []){
		$res  = [
			1 => preg_match('/^1([3-9][0-9])\\d{8}$/', $mobile), //手机号码 移动|联通|电信
			2 => preg_match('/^1(34[0-8]|(3[5-9]|5[017-9]|8[0-9]|7[0-9])\\d)\\d{7}$/', $mobile), //中国移动
			3 => preg_match('/^1(3[0-2]|5[256]|8[56])\\d{8}$/', $mobile), //中国联通
			4 => preg_match('/^1((33|53|8[09])[0-9]|349)\\d{7}$/', $mobile), //中国电信
			5 => preg_match('/^0(10|2[0-5789]|\\d{3})-\\d{7,8}$/', $mobile), //大陆地区固话及小灵通
		];
		$type = empty($type) ? [1, 2, 3, 4, 5] : $type;
		$flag = false;
		foreach($type as $k => $v){
			if($res[$v]) $flag = true;
			break;
		}
		return ($mobile && $flag) ? true : false;
	}
	
	/**
	 * 邮箱格式验证
	 * @param  string $email 邮箱号
	 * @return boolean
	 */
	public function email($email){
		return preg_match('#[a-z0-9&\-_.]+@[\w\-_]+([\w\-.]+)?\.[\w\-]+#is', $email) ? true : false;
	}
	
	/**
	 * 身份证号验证
	 * @param  string $idcard 身份证号
	 * @return boolean
	 */
	public function idcard($idcard){
		/*
		 * 身份证15位编码规则：dddddd yymmdd xx p
		 * dddddd：6位地区编码
		 * yymmdd: 出生年(两位年)月日，如：910215
		 * xx: 顺序编码，系统产生，无法确定
		 * p: 性别，奇数为男，偶数为女
		 *
		 * 身份证18位编码规则：dddddd yyyymmdd xxx y
		 * dddddd：6位地区编码
		 * yyyymmdd: 出生年(四位年)月日，如：19910215
		 * xxx：顺序编码，系统产生，无法确定，奇数为男，偶数为女
		 * y: 校验码，该位数值可通过前17位计算获得
		 *
		 * 前17位号码加权因子为 Wi = [ 7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2 ]
		 * 验证位 Y = [ 1, 0, 10, 9, 8, 7, 6, 5, 4, 3, 2 ]
		 * 如果验证码恰好是10，为了保证身份证是十八位，那么第十八位将用X来代替
		 * 校验位计算公式：Y_P = mod( ∑(Ai×Wi),11 )
		 * i为身份证号码1...17 位; Y_P为校验码Y所在校验码数组位置
		 */
		$reg = '/^(^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$)|(^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[Xx])$)$/';
		//如果通过该验证，说明身份证格式正确，但准确性还需计算
		if(!preg_match($reg, $idcard)){
			return false;
		}
		//15位身份证号码
		if(mb_strlen($idcard)==15) return true;
		//18位身份证号码
		$wi     = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2]; //将前17位加权因子保存在数组里
		$y      = [1, 0, 'x', 9, 8, 7, 6, 5, 4, 3, 2]; //这是除以11后，可能产生的11位余数、验证码，也保存成数组
		$wi_sum = 0; //用来保存前17位各自乖以加权因子后的总和
		for($i = 0; $i<17; $i++){
			$wi_sum += iconv_substr($idcard, $i, 1)*$wi[$i];
		}
		$mod  = $wi_sum%11; //计算出校验码所在数组的位置
		$last = iconv_substr($idcard, 17); //得到最后一位身份证号码
		//如果等于2，则说明校验码是10，身份证号码最后一位应该是X
		//用计算出的验证码与最后一位身份证号码匹配，如果一致，说明通过，否则是无效的身份证号码
		if(!isset($y[$mod]) || strtolower($last)!=$y[$mod]) return false;
		return true;
	}
	
	/**
	 * 手机串号IMEI验证
	 * @param $s
	 * @return bool
	 */
	public function imei($s){
		$pat = '/^[0-9]{15}$/';
		if(!preg_match($pat, $s)){
			return false;
		}
		$sum = 0;
		$mul = 2;
		$len = 14;
		for($i = 0; $i<$len; $i++){
			$digit = substr($s, $len-$i-1, 1);
			$tp    = intval($digit)*$mul;
			$sum += $tp>=10 ? ($tp%10)+1 : $tp;
			$mul = $mul==1 ? ++$mul : --$mul;
		}
		$chk = (10-($sum%10))%10;
		return $chk==substr($s, 14, 1) ? true : false;
	}
	
}
