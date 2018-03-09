<?php
/**
 * 权限验证
 * @author 夏爽
 */
namespace app\admin\service;

class Auth{
	/**
	 * 权限认证类
	 * 功能特性：
	 * 1，是对规则进行认证，不是对节点进行认证。用户可以把节点当作规则名称实现对节点进行认证。
	 *      $auth=new Auth();  $auth->check('规则名称','用户id')
	 * 2，可以同时对多条规则进行认证，并设置多条规则的关系（or或者and）
	 *      $auth=new Auth();  $auth->check('规则1,规则2','用户id','and')
	 *      第三个参数为and时表示，用户需要同时具有规则1和规则2的权限。 当第三个参数为or时，表示用户值需要具备其中一个条件即可。默认为or
	 * 3，一个用户可以属于多个用户组(think_auth_group_access表 定义了用户所属用户组)。我们需要设置每个用户组拥有哪些规则(think_auth_group 定义了用户组权限)
	 *
	 * 4，支持规则表达式。
	 *      在think_auth_rule 表中定义一条规则时，如果type为1， condition字段就可以定义规则表达式。 如定义{score}>5  and {score}<100  表示用户的分数在5-100之间时这条规则才会通过。
	 */
	
	//默认配置
	protected $config = [
		'auth_on'                => true, // 认证开关
		'auth_type'              => 1, // 认证方式，1为实时认证；2为登录认证。
		'auth_rule'              => 'auth_rule', // 权限规则表
		'auth_group'             => 'auth_group', // 用户组数据表名
		'auth_group_access'      => 'auth_group_access', // 用户-用户组关系表
		'auth_user'              => 'user', //用户表
		'auth_exempt_user_id'    => [1], //免校验用户
		'auth_exempt_url'        => [], //免校验地址
		//免校验控制器
		'auth_exempt_controller' => [
			'open',
		],
	];
	
	/**
	 * 初始化
	 */
	public function __construct(){
		$auth_config = config('auth_config');
		if($auth_config){
			//可设置配置项 auth_config, 此配置项为数组。
			$this->config = array_merge($this->config, $auth_config);
		}
	}
	
	/**
	 * 检查权限
	 * @param $name string|array 需要验证的规则列表,支持逗号分隔的权限规则或索引数组
	 * @param $user_id  int  认证用户的id
	 * @param $type int 权限类型
	 * @param string $mode 执行check的模式
	 * @param relation string 如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
	 * @return boolean 通过验证返回true;失败返回false
	 */
	public function check($name, $user_id, $type = 1, $mode = 'url', $relation = 'or'){
		//总开关
		if(!$this->config['auth_on']){
			return true;
		}
		//免校验用户
		if(in_array($user_id, $this->config['auth_exempt_user_id'])){
			return true;
		}
		//需要校验的规则
		if(is_string($name)){
			$name = strtolower($name);
			$name = strpos($name, ',')!==false ? explode(',', $name) : [$name];
		}
		//保存验证通过的规则名
		$authorize_list = [];
		//免校验控制器
		if($this->config['auth_exempt_controller']){
			foreach($name as $k => $v){
				$controller = preg_replace('/\/.*$/U', '', $v);
				if(in_array($controller,$this->config['auth_exempt_controller'])){
					$authorize_list[] = $v;
				}
			}
		}
		//获取用户需要验证的所有有效规则列表
		$auth_list = $this->getAuthList($user_id, $type);
		//免校验地址
		$auth_list = array_merge($auth_list, $this->config['auth_exempt_url']);
		foreach($auth_list as $auth){
			if($mode=='url' && $auth != $query = preg_replace('/^.+\?/U', '', $auth)){
				//url模式，且含参数
				parse_str($query, $param); //解析规则中的param
				$REQUEST   = unserialize(strtolower(serialize($_REQUEST))); //接收到的参数
				$intersect = array_intersect_assoc($REQUEST, $param); //比对参数
				$auth      = preg_replace('/\?.*$/U', '', $auth);
				//如果节点相符且url参数满足
				if(in_array($auth, $name) && $intersect==$param){
					$authorize_list[] = $auth;
				}
			}else if(in_array($auth, $name)){
				$authorize_list[] = $auth;
			}
		}
		
		//多规则验证方式
		$relation = strtolower($relation);
		if($relation=='or' and !empty($authorize_list)){
			return true;
		}
		if($relation=='and' and empty(array_diff($name, $authorize_list))){
			return true;
		}
		return false;
	}
	
	/**
	 * 获得权限列表
	 * @param integer $user_id 用户id
	 * @param integer $type
	 */
	public function getAuthList($user_id, $type = 1){
		//保存用户验证通过的权限列表
		static $_auth_list = [];
		$t = implode(',', (array)$type);
		if(isset($_auth_list[$user_id.$t])){
			return $_auth_list[$user_id.$t];
		}
		//登录认证
		if($this->config['auth_type']==2 && isset($_SESSION['_AUTH_LIST_'.$user_id.$t])){
			return $_SESSION['_AUTH_LIST_'.$user_id.$t];
		}
		
		//读取用户所有权限ID集合
		$ids = $this->getAuthIds($user_id);
		if(empty($ids)){
			$_auth_list[$user_id.$t] = [];
			return [];
		}
		
		//读取用户组所有权限规
		$rules = db($this->config['auth_rule'])
			->where(['id' => ['in', $ids], 'type' => $type, 'status' => 1,])
			->field('condition,name')
			->select();
		
		//循环规则，判断结果。
		$auth_list = [];
		foreach($rules as $rule){
			//根据condition进行验证，如：{score}>5&&{coin}<=100
			if(!empty($rule['condition'])){
				//获取用户信息,一维数组
				$user    = $this->getUserInfo($user_id);
				$command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
//				dump($command);//debug
				@(eval('$condition=('.$command.');'));
				if($condition){
					$auth_list[] = strtolower($rule['name']);
				}
			}else{
				//只要存在就记录
				$auth_list[] = strtolower($rule['name']);
			}
		}
		//去重
		$auth_list               = array_unique($auth_list);
		$_auth_list[$user_id.$t] = $auth_list;
		//规则列表结果保存到session
		if($this->config['auth_type']==2){
			$_SESSION['_AUTH_LIST_'.$user_id.$t] = $auth_list;
		}
		return $auth_list;
	}
	
	/**
	 * 获取权限ID集合
	 * @param int $user_id 用户ID
	 * @return array
	 */
	public function getAuthIds($user_id){
		static $auth_ids = [];
		if(isset($auth_ids[$user_id])){
			return $auth_ids[$user_id];
		}
		//获取权限组列表
		$groups = $this->getGroups($user_id);
		//保存用户所属用户组设置的所有权限规则id
		$ids = [];
		foreach($groups as $v){
			$ids = array_merge($ids, explode(',', trim($v['rules'], ',')));
		}
		//去重
		$auth_ids[$user_id] = array_unique($ids);
		return $auth_ids[$user_id];
	}
	
	/**
	 * 获取权限组列表（根据用户ID）
	 * @param  uid int     用户id
	 * @return array       用户所属的用户组 array(
	 *     array('uid'=>'用户id','group_id'=>'用户组id','title'=>'用户组名称','rules'=>'用户组拥有的规则id,多个,号隔开'),
	 *     ...)
	 */
	public function getGroups($user_id){
		static $groups = [];
		if(isset($groups[$user_id])){
			return $groups[$user_id];
		}
		
		$user_groups      = db($this->config['auth_group_access'])->alias('a')
			->join($this->config['auth_group'].' g', 'a.group_id=g.id')
			->where("a.user_id='$user_id' and g.status='1'")
			->field('a.user_id,a.group_id,g.title,g.rules')
			->fetchSql(false)->select();
		$groups[$user_id] = $user_groups ? : [];
		return $groups[$user_id];
	}
	
	/**
	 * 获取用户信息
	 * @param $user_id int 用户ID
	 * @return array
	 */
	public function getUserInfo($user_id){
		static $users = [];
		if(isset($users[$user_id])){
			return $users[$user_id];
		}
		
		$user_info       = db($this->config['auth_user'])
			->where(['id' => $user_id])
			->find();
		$users[$user_id] = $user_info ? : [];
		return $users[$user_id];
	}
	
	/**
	 * 权限列表转html代码-权限设置
	 * @param $data
	 * @param int $i 循环标识
	 * @param string $html
	 * @return string
	 */
	public static function getAuthRuleListToHtmlCheckbox($data, $i = 1, $html = ''){
		$num     = 1;
		$is_last = 1;
		foreach($data as $k => $v){
			if(!empty($v['list'])){
				$is_last = 0;
				break;
			}
		}
		foreach($data as $k => $v){
			if($is_last==1){ //最后行
				if($num==1){ //第一格
					$html .= '<div class="last" style="white-space:nowrap;">';
					for($m = 1; $m<$i; $m++) $html .= '　　';
					$html .= '└─';
				}else{
					$html .= ' ';
				}
				$html .= '<input name="rules[]" type="checkbox" value="'.$v['id'].'"';
				if($v['isset']==1) $html .= ' checked';
				$html .= '/>'.$v['title'];
				if($num==count($data)) $html .= '</div>';
			}else{
				$html .= '<div><div>';
				for($m = 1; $m<$i; $m++) $html .= '　　';
				$html .= $i>1 ? ($num==count($data) ? '└─' : '├─') : '　 ';
				$html .= '<input name="rules[]" type="checkbox" value="'.$v['id'].'"';
				if($v['isset']==1) $html .= ' checked';
				$html .= '/>'.$v['title'].'<br/></div>';
				if(!empty($v['list'])) $html = self::getAuthRuleListToHtmlCheckbox($v['list'], $i+1, $html);
				$html .= '</div>';
			}
			$num++;
		}
		return $html;
	}
	
	/**
	 * 权限列表转html代码-添加菜单
	 * @param $data
	 * @param int $i
	 * @param string $html
	 * @param null $pid
	 * @return string
	 */
	public static function getAuthRuleListToHtmlSelect($data, $i = 1, $html = '', $pid = null){
		$num = 1;
		foreach($data as $k => $v){
			$html .= '<option value="'.$v['id'].'"';
			if($pid==$v['id']) $html .= ' selected';
			$html .= '>';
			for($m = 1; $m<$i; $m++) $html .= '　　';
			$html .= $i>1 ? ($num==count($data) ? '└─' : '├─') : '　 ';
			$html .= $v['title'];
			if(!empty($v['list'])) $html = self::getAuthRuleListToHtmlSelect($v['list'], $i+1, $html, $pid);
			$num++;
		}
		return $html;
	}
	
}
