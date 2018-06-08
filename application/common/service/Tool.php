<?php
/**
 * 工具
 * @author xs
 */
namespace app\common\service;

class Tool extends Base{
	
	/**
	 * 数据库查询-生成距离值
	 * @param string $lat 纬度
	 * @param string $lng 经度
	 * @return string
	 */
	public function strFieldJuli($lat, $lng){
		return "(2 * 6378.137* ASIN(SQRT(POW(SIN(3.1415926535898*(".$lat."-lat)/360),2)+COS(3.1415926535898*".$lat."/180)* COS(lat * 3.1415926535898/180)*POW(SIN(3.1415926535898*(".$lng."-lng)/360),2))))*1000 as juli";
	}
	
	/**
	 * 安全过滤字符串
	 * @param  string $string
	 * @return string
	 */
	public function filter($string){
		if(is_array($string)){
			$string = implode('，', $string);
			$string = htmlspecialchars(str_shuffle($string));
		}else{
			$string = htmlspecialchars($string);
		}
		$string = str_replace('%20', '', $string);
		$string = str_replace('%27', '', $string);
		$string = str_replace('%2527', '', $string);
		$string = str_replace('*', '', $string);
		$string = str_replace('"', '&quot;', $string);
		$string = str_replace("'", '', $string);
		$string = str_replace('"', '', $string);
		$string = str_replace(';', '', $string);
		$string = str_replace('<', '&lt;', $string);
		$string = str_replace('>', '&gt;', $string);
		$string = str_replace("{", '', $string);
		$string = str_replace('}', '', $string);
		return $string;
	}
	
	/**
	 * 生成随机数
	 * @param  int $length 随机数长度
	 * @param  int $type 包含的字符类型
	 * @return string
	 */
	public function random($length, $type = 0){
		$data   = [
			0 => '123456789',
			1 => 'abcdefghjkmnpqrstuxy',
			2 => 'ABCDEFGHJKMNPQRSTUXY',
			3 => '123456789abcdefghjkmnpqrstuxy',
			4 => '123456789ABCDEFGHJKMNPQRSTUXY',
			5 => 'abcdefghjkmnpqrstuxyABCDEFGHJKMNPQRSTUXY',
			6 => '123456789abcdefghjkmnpqrstuxyABCDEFGHJKMNPQRSTUXY',
			7 => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
		];
		$chars  = isset($data[$type]) ? $data[$type] : $data[7];
		$max    = strlen($chars)-1;
		$string = '';
		for($i = 0; $i<$length; $i++){
			$string .= $chars[mt_rand(0, $max)];
		}
		return $string;
	}
	
	/**
	 * 字符串截取，支持中文和其他编码
	 * @static
	 * @access public
	 * @param string $str 需要转换的字符串
	 * @param string $start 开始位置
	 * @param string $length 截取长度
	 * @param string $charset 编码格式
	 * @param string $suffix 截断显示字符
	 * @return string
	 */
	public function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true){
		if(mb_strlen($str)<=$length){
			return $str;
		}
		if(function_exists("mb_substr"))
			$slice = mb_substr($str, $start, $length, $charset);
		elseif(function_exists('iconv_substr')){
			$slice = iconv_substr($str, $start, $length, $charset);
			if(false===$slice){
				$slice = '';
			}
		}else{
			$re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
			$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
			$re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
			$re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
			preg_match_all($re[$charset], $str, $match);
			$slice = join("", array_slice($match[0], $start, $length));
		}
		return $suffix ? $slice.'...' : $slice;
	}
	
	/**
	 * 递归-数组整理
	 * @param array $data 需要整理的数据
	 * @param int $pid 从指定父ID开始整理
	 * @param string $pid_name 父ID名称
	 * @param string $list_name 自数组名称
	 * @return array 多维数组
	 */
	public function sortArrayRecursio($data, $option = []){
		$option = array_merge(array(
			'parent_id'      => 0, //指定父id开始整理
			'id_name'        => 'id', //id键名
			'parent_id_name' => 'parent_id',
			'child'          => 'child', //子集键名
		), $option);
		//递归
		$fn = function($data, $option) use (&$fn){
			$return = array();
			if(isset($data[$option['parent_id']]) && is_array($data[$option['parent_id']])){
				foreach($data[$option['parent_id']] as $k => $v){
					$new_option          = array_merge(
						$option,
						array('parent_id' => $v[$option['id_name']])
					);
					$v[$option['child']] = isset($data[$v[$option['id_name']]]) ? $fn($data, $new_option) : [];
					$return[]            = $v;
				}
			}
			return $return;
		};
		//先分组
		$group_data = array();
		foreach($data as $k => $v){
			$group_data[$v[$option['parent_id_name']]][] = $v;
		}
		
		return $fn($group_data, $option);
	}
	
	/**
	 * 生成数据签名
	 * @param  mixed $data 需要生成签名的数据
	 * @return string 签名字符串
	 */
	public function sign($data, $algo = 'md5'){
		if(is_array($data)){
			ksort($data);
		}
		$string = serialize($data); //转为字符串
		return hash($algo, $string.config('data_secret_key')); //生成签名
	}
	
	/**
	 * 获取访客IP地址
	 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
	 * @return string|int
	 */
	public function getClientIp($type = 0){
		$type = $type ? 1 : 0;
		static $client_ip = [];
		if($client_ip) return $client_ip[$type];
		$ip = '';
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			$pos = array_search('unknown', $arr);
			if(false!==$pos) unset($arr[$pos]);
			$ip = trim($arr[0]);
		}elseif(isset($_SERVER['HTTP_CLIENT_IP'])){
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}elseif(isset($_SERVER['REMOTE_ADDR'])){
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		// IP地址合法验证
		$long      = sprintf("%u", ip2long($ip));
		$client_ip = $long ? [$ip, $long] : ['0.0.0.0', 0];
		return $client_ip[$type];
	}
	
	/**
	 * 获取访客IP地址
	 * @return string
	 */
	public function getIp(){
		$onlineip = '';
		if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')){
			$onlineip = getenv('HTTP_CLIENT_IP');
		}elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')){
			$onlineip = getenv('HTTP_X_FORWARDED_FOR');
		}elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')){
			$onlineip = getenv('REMOTE_ADDR');
		}elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')){
			$onlineip = $_SERVER['REMOTE_ADDR'];
		}
		return $onlineip;
	}
	
	/**
	 * 获取星座、干支、生肖
	 * @param  string $birth 出生年月日，格式：时间戳||yyyy-mm-dd||yyyymmdd
	 * @return array|false   $array('xz'=>星座,'gz'=>干支,'sx'=>生肖)
	 */
	public function birthExt($birth){
		if(strstr($birth, '-')===false && strlen($birth)!==8){
			$birth = date("Y-m-d", $birth);
		}
		if(strlen($birth)===8){
			if(eregi('([0-9]{4})([0-9]{2})([0-9]{2})$', $birth, $bir)) $birth = "{$bir[1]}-{$bir[2]}-{$bir[3]}";
		}
		if(strlen($birth)<8){
			return false;
		}
		$tmpstr = explode('-', $birth);
		if(count($tmpstr)!==3){
			return false;
		}
		$y = (int)$tmpstr[0];    //年
		$m = (int)$tmpstr[1];    //月
		$d = (int)$tmpstr[2];    //日
		if($m<1 || $m>12 || $d<1 || $d>31) return false;
		$result = array();
		//获取星座
		$xzdict = array('摩羯', '水瓶', '双鱼', '白羊', '金牛', '双子', '巨蟹', '狮子', '处女', '天秤', '天蝎', '射手');
		$zone   = array(1222, 120, 219, 321, 420, 521, 622, 723, 823, 923, 1024, 1123, 1222);
		if((100*$m+$d)>=$zone[0] || (100*$m+$d)<$zone[1]){
			$i = 0;
		}else{
			for($i = 1; $i<12; $i++){
				if((100*$m+$d)>=$zone[$i] && (100*$m+$d)<$zone[$i+1]){
					break;
				}
			}
		}
		$result['xz'] = $xzdict[$i].'座';
		//获取干支
		$gzdict       = array(
			array('甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'),
			array('子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'),
		);
		$i            = $y-1900+36;
		$result['gz'] = $gzdict[0][($i%10)].$gzdict[1][($i%12)];
		//获取生肖
		$sxdict       = array('鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪');
		$result['sx'] = $sxdict[(($y-4)%12)];
		return $result;
	}
	
	/**
	 * 时间转为过去式
	 * @param  string $time 时间戳
	 * @return string
	 */
	public function timePast($time){
		$date   = '';
		$nowday = strtotime(date('Y-m-d'));
		//未来、非今年
		if($time>time() || $time<strtotime(date('Y').'-01-01')) return date('Y-m-d H:i', $time);
		//今年内，7天内
		if($time>=$nowday){
			$second = time()-$time;
			if($second<60){
				$date = $second.'秒前';
			}else if($second<60*60){
				$date = floor($second/60).'分钟前';
			}else{
				$date = floor($second/60/60).'小时前';
			}
		}else if($time>=$nowday-(24*60*60)){
			$date = '昨天'.date('H:i', $time);
		}else if($time>=$nowday-(7*24*60*60)){
			$date = ceil(($nowday-$time)/(24*60*60)).'天前'.date('H:i', $time);;
		}else{
			$date = date('m-d H:i', $time);
		}
		return $date;
	}
	
	/**
	 * 删除指定的标签和内容
	 * @param string $str 数据源
	 * @param array $tags 需要删除的标签数组
	 * @param string $content 是否删除标签内的内容 默认为0保留内容    1不保留内容
	 * @return string
	 */
	public function htmlTagsStrip($str, $tags, $content = 0){
		if($content){
			$html = array();
			foreach($tags as $tag){
				$html[] = '/(<'.$tag.'.*?>[\s|\S]*?<\/'.$tag.'>)/';
			}
			$data = preg_replace($html, '', $str);
		}else{
			$html = array();
			foreach($tags as $tag){
				$html[] = "/(<(?:\/".$tag."|".$tag.")[^>]*>)/i";
			}
			$data = preg_replace($html, '', $str);
		}
		return $data;
	}
	
	/**
	 * 保留指定标签
	 * @param string $str 数据源
	 * @param string $tags 需要保留的标签数组
	 * @return string
	 */
	public function htmlTagsRetain($str, $tags = '<img><a>'){
		$search = array(
			'@<script[^>]*?>.*?</script>@si',  //去除script标签
			/*          '@<[\/\!]*?[^<>]*?>@si', // Strip out HTML tags*/
			'@<style[^>]*?>.*?</style>@siU',    //去除style标签
			'@<![\s\S]*?--[ \t\n\r]*>@'         //去除注释内容
		);
		$str    = preg_replace($search, '', $str);
		$str    = strip_tags($str, $tags);//保留指定标签
		return $str;
	}
	
	/**
	 * 格式化字节大小
	 * @param  number $size 字节数
	 * @param  string $delimiter 数字和单位分隔符
	 * @return string 格式化后的带单位的大小
	 */
	public function formatBytes($size, $delimiter = ''){
		$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
		for($i = 0; $size>=1024 && $i<5; $i++){
			$size /= 1024;
		}
		return round($size, 2).$delimiter.$units[$i];
	}
	
	/**
	 * 手机号格式验证
	 */
	public function isMobile($mobile, $type = array()){
		$regular = [
			1 => '/^1(3[0-9]|5[0-35-9]|7[0-9]|8[0-9])\\d{8}$/', //手机号码 移动|联通|电信
			2 => '/^1(34[0-8]|(3[5-9]|5[017-9]|8[0-9])\\d)\\d{7}$/', //中国移动
			3 => '/^1(3[0-2]|5[256]|8[56])\\d{8}$/', //中国联通
			4 => '/^1((33|53|8[09])[0-9]|349)\\d{7}$/', //中国电信
			5 => '/^0(10|2[0-5789]|\\d{3})-\\d{7,8}$/', //大陆地区固话及小灵通
		];
		$all     = $type ? false : true;
		foreach($regular as $k => $v){
			if($all || in_array($k, $type)){
				$result = preg_match($v, $mobile);
				if($result){
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * 校验邮箱
	 * @param $email
	 * @return bool
	 */
	public function isEmail($email){
		return (!preg_match('#[a-z0-9&\-_.]+@[\w\-_]+([\w\-.]+)?\.[\w\-]+#is', $email) || !$email) ? false : true;
	}
	
	/**
	 * @brief 判断客户端是否为微信
	 * @return boolean
	 */
	public static function isWechatAccess(){
		if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
			return true;
		}
		return false;
	}
	
	/**
	 * 判断是电脑还是手机访问
	 * @return bool
	 */
	public function isMobileAccess(){
		$useragent               = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$useragent_commentsblock = preg_match('|\(.*?\)|', $useragent, $matches)>0 ? $matches[0] : '';
		$mobile_os_list          = ['Google Wireless Transcoder', 'Windows CE', 'WindowsCE', 'Symbian',
			'Android', 'armv6l', 'armv5', 'Mobile', 'CentOS', 'mowser', 'AvantGo', 'Opera Mobi', 'J2ME/MIDP',
			'Smartphone', 'Go.Web', 'Palm', 'iPAQ',
		];
		$mobile_token_list       = ['Profile/MIDP', 'Configuration/CLDC-', '160×160', '176×220', '240×240',
			'240×320', '320×240', 'UP.Browser', 'UP.Link', 'SymbianOS', 'PalmOS', 'PocketPC',
			'SonyEricsson', 'Nokia', 'BlackBerry', 'Vodafone', 'BenQ', 'Novarra-Vision', 'Iris',
			'NetFront', 'HTC_', 'Xda_', 'SAMSUNG-SGH', 'Wapaka', 'DoCoMo', 'iPhone', 'iPod',
		];
		$found_mobile            = false;
		foreach($mobile_os_list as $substr){
			if(false!==strpos($useragent_commentsblock, $substr)){
				$found_mobile = true;
				break;
			}
		}
		foreach($mobile_token_list as $substr){
			if(false!==strpos($useragent, $substr)){
				$found_mobile = true;
				break;
			}
		}
		return $found_mobile;
	}
	
	/**
	 * 字符串转为数组
	 * @param string $string 解析格式（a:名称1,b:名称2）
	 * @return array
	 */
	public function strToArray($string){
		$array = $string ? preg_split('/[,;\r\n]+/', trim($string, ",;\r\n")) : [];
		$data  = [];
		foreach($array as $v){
			if(strpos($v, ':')){
				list($key, $value) = explode(':', $v);
				$data[$key] = $value;
			}else{
				$data[] = $v;
			}
		}
		return $data;
	}
	
	/**
	 * 字符串命名风格转换
	 * @param  string $name 字符串
	 * @param  integer $type 转换类型 type 0 将 Java 风格转换为 C 的风格 1 将 C 风格转换为 Java 的风格
	 * @param  bool $ucfirst 首字母是否大写（驼峰规则）
	 * @return string
	 */
	public function convertHump($name, $type = 0, $ucfirst = true){
		if($type){
			$name = preg_replace_callback('/_([a-zA-Z])/', function($match){
				return strtoupper($match[1]);
			}, $name);
			return $ucfirst ? ucfirst($name) : lcfirst($name);
		}
		return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
	}
}
