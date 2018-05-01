<?php
/**
 * 控制器-公共
 * @author 夏爽
 */
namespace app\admin\controller;

class System extends \app\common\controller\AdminBase{
	
	/**
	 * 系统信息
	 */
	public function info(){
		/* 系统信息 */
		//设置报错级别
		error_reporting(E_ALL&~E_NOTICE);
		$os     = explode(' ', php_uname());
		//服务器参数
		$server = [
			['服务器域名/IP地址', $_SERVER['SERVER_NAME'].'('.(DIRECTORY_SEPARATOR=='/' ? $_SERVER['SERVER_ADDR'] : @gethostbyname($_SERVER['SERVER_NAME'])).')',],
			['服务器标识', isset($sysInfo['win_n']) && $sysInfo['win_n']!='' ? $sysInfo['win_n'] : @php_uname(),],
			['服务器操作系统', $os[0].'  内核版本：'.(DIRECTORY_SEPARATOR=='/' ? $os[2] : $os[1]),],
			['服务器解译引擎', $_SERVER['SERVER_SOFTWARE'],],
			['服务器语言', getenv("HTTP_ACCEPT_LANGUAGE"),],
			['服务器端口', $_SERVER['SERVER_PORT'],],
			['服务器主机名', DIRECTORY_SEPARATOR=='/' ? $os[1] : $os[2],],
			['绝对路径', $_SERVER['DOCUMENT_ROOT'] ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : str_replace('\\', '/', dirname(__FILE__)),],
			['管理员邮箱', $_SERVER['SERVER_ADMIN'],],
			['剩余空间', round((@disk_free_space(".")/(1024*1024)), 2).'M',],
		];
		//PHP已编译模块检测
		$module = get_loaded_extensions();
		/* PHP相关参数 */
		//禁用的函数
		$disable_functions = get_cfg_var('disable_functions');
		if($disable_functions){
			$is_br = true;
			for($i = 1; $is_br; $i++){
				$is_br = strpos($disable_functions, ',', 60*$i);
				if($is_br){
					$disable_functions = substr_replace($disable_functions, '<br/>', $is_br, 1);
				}
			}
		}else{
			$disable_functions = '<font color="red">×</font>';
		}
		$php_config = [
			['PHP版本（php_version）', PHP_VERSION],
			['PHP运行方式', strtoupper(php_sapi_name())],
			['脚本占用最大内存（memory_limit）', $this->show('memory_limit')],
			['PHP安全模式（safe_mode）', $this->show('safe_mode')],
			['POST方法提交最大限制（post_max_size）', $this->show('post_max_size')],
			['上传文件最大限制（upload_max_filesize）', $this->show('upload_max_filesize')],
			['浮点型数据显示的有效位数（precision）', $this->show('precision')],
			['脚本超时时间（max_execution_time）', $this->show('max_execution_time')],
			['socket超时时间（default_socket_timeout）', $this->show('default_socket_timeout')],
			['PHP页面根目录（doc_root）', $this->show('doc_root')],
			['用户根目录（user_dir）', $this->show('user_dir')],
			['dl()函数（enable_dl）', $this->show('enable_dl')],
			['指定包含文件目录（include_path）', $this->show('include_path')],
			['显示错误信息（display_errors）', $this->show('display_errors')],
			['自定义全局变量（register_globals）', $this->show('register_globals')],
			['"<?...?>"短标签（short_open_tag）', $this->show('short_open_tag')],
			['"<% %>"ASP风格标记（asp_tags）', $this->show('asp_tags')],
			['忽略重复错误信息（ignore_repeated_errors）', $this->show('ignore_repeated_errors')],
			['忽略重复的错误源（ignore_repeated_source）', $this->show('ignore_repeated_source')],
			['报告内存泄漏（report_memleaks）', $this->show('report_memleaks')],
			['自动字符串转义（magic_quotes_gpc）', $this->show('magic_quotes_gpc')],
			['外部字符串自动转义（magic_quotes_runtime）', $this->show('magic_quotes_runtime')],
			['打开远程文件（allow_url_fopen）', $this->show('allow_url_fopen')],
			['声明argv和argc变量（register_argc_argv）', $this->show('register_argc_argv')],
			['Cookie 支持', isset($_COOKIE) ? '<font color="green">√</font>' : '<font color="red">×</font>'],
			['拼写检查（ASpell Library）', $this->isfun('aspell_check_raw')],
			['高精度数学运算（BCMath）', $this->isfun('bcadd')],
			['PREL相容语法（PCRE）', $this->isfun('preg_match')],
			['PDF文档支持', $this->isfun('pdf_close')],
			['SNMP网络管理协议', $this->isfun('snmpget')],
			['VMailMgr邮件处理', $this->isfun('vm_adduser')],
			['Curl支持', $this->isfun('curl_init')],
			['SMTP支持/SMTP地址', get_cfg_var('SMTP') ? '<font color="green">√</font>('.get_cfg_var('SMTP').')' : '<font color="red">×</font>'],
			['被禁用的函数（disable_functions）', $disable_functions],
		];
		/* 组件支持 */
		$subassembly = [
			['FTP支持', $this->isfun('ftp_login')],
			['XML解析支持', $this->isfun('xml_set_object')],
			['Session支持', $this->isfun('session_start')],
			['Socket支持', $this->isfun('socket_accept')],
			['Calendar支持', $this->isfun('cal_days_in_month')],
			['允许URL打开文件', $this->show('allow_url_fopen')],
			['GD库支持', function_exists('gd_info') ? @gd_info()['GD Version'] : '<font color="red">×</font>'],
			['压缩文件支持(Zlib)', $this->isfun('gzclose')],
			['IMAP电子邮件系统函数库', $this->isfun('imap_close')],
			['历法运算函数库', $this->isfun('JDToGregorian')],
			['正则表达式函数库', $this->isfun('preg_match')],
			['WDDX支持', $this->isfun('wddx_add_vars')],
			['Iconv编码转换', $this->isfun('iconv')],
			['mbstring', $this->isfun('mb_eregi')],
			['高精度数学运算', $this->isfun('bcadd')],
			['LDAP目录协议', $this->isfun('ldap_close')],
			['MCrypt加密处理', $this->isfun('mcrypt_cbc')],
			['哈稀计算', $this->isfun('mhash_count')],
		];
		
		/* 第三方组件 */
		if(substr(PHP_VERSION, 2, 1)>2){
			$zend_k = 'ZendGuardLoader[启用]';
			$zend_v = get_cfg_var('zend_loader.enable') ? '<font color=green>√</font>' : '<font color=red>×</font>';
		}else{
			$zend_k = 'Zend Optimizer';
			$zend_v = function_exists('zend_optimizer_version') ? zend_optimizer_version() : (get_cfg_var('zend_optimizer.optimization_level') || get_cfg_var('zend_extension_manager.optimizer_ts') || get_cfg_var('zend.ze1_compatibility_mode') || get_cfg_var('zend_extension_ts')) ? '<font color=green>√</font>' : '<font color=red>×</font>';
		}
		$thirdparty = [
			['Zend版本', zend_version() ? : '<font color="red">×</font>'],
			[$zend_k, $zend_v],
			['eAccelerator', phpversion('eAccelerator') ? : '<font color=red>×</font>'],
			['ioncube', extension_loaded('ionCube Loader') ? ionCube_Loader_version().'.'.(int)substr(ioncube_loader_iversion(), 3, 2) : '<font color=red>×</font>'],
			['XCache', phpversion('XCache') ? : '<font color="red">×</font>'],
			['APC', phpversion('APC') ? : '<font color="red">×</font>'],
		];
		/* 数据库支持 */
		$sqlites3 = \SQLite3::version();
		$database = [
			['MySQL 数据库', $this->isfun('mysql_close').(function_exists("mysql_get_server_info") && ($s = @mysql_get_server_info()) ? '&nbsp; mysql_server 版本：'.$s : '')],
			['ODBC 数据库', $this->isfun('odbc_close')],
			['Oracle 数据库', $this->isfun('ora_close')],
			['SQL Server 数据库', $this->isfun('mssql_close')],
			['dBASE 数据库', $this->isfun('dbase_close')],
			['SQLite 数据库', $this->isfun('dbase_close')],
			['dBASE 数据库', extension_loaded('sqlite3') ? '<font color=green>√</font>　SQLite3　Ver '.$sqlites3['versionString'] : isfun('sqlite_close').(function_exists('sqlite_close') ? ' 版本： '.@sqlite_libversion() : '')],
			['Hyperwave 数据库', $this->isfun('hw_close')],
			['Postgre SQL 数据库', $this->isfun('pg_close')],
			['Informix 数据库', $this->isfun('ifx_close')],
			['DBA 数据库', $this->isfun('dba_close')],
			['DBM 数据库', $this->isfun('dbmclose')],
			['FilePro 数据库', $this->isfun('filepro_fieldcount')],
			['SyBase 数据库', $this->isfun('sybase_close')],
		
		];
		
		/* 视图 */
		return $this->fetch('', [
			'server'      => $server,
			'module'      => $module,
			'php_config'  => $php_config,
			'subassembly' => $subassembly,
			'thirdparty'  => $thirdparty,
			'database'    => $database,
		]);
	}
	
	/**
	 * 检测PHP设置参数
	 * @param string $var 参数
	 * @return string
	 */
	private function show($var){
		$result = get_cfg_var($var);
		switch($result){
			case 0:
				return '<font color="red">×</font>';
			case 1:
				return '<font color="green">√</font>';
			default:
				return $result;
		}
	}
	
	/**
	 * 检测函数支持
	 * @param string $fun_name 函数名
	 * @return string
	 */
	private function isfun($fun_name = ''){
		if(!$fun_name || trim($fun_name)=='' || preg_match('~[^a-z0-9\_]+~i', $fun_name, $tmp)) return '错误';
		return (false!==function_exists($fun_name)) ? '<font color="green">√</font>' : '<font color="red">×</font>';
	}
	
}