<?php
/**
 * 控制器-基类-admin
 * @author 夏爽
 */
namespace app\common\controller;

class BaseAdmin extends Base{
	protected $user_id   = 0; //当前用户ID
	protected $user_info = []; //用户信息
	protected $url       = ''; //当前操作地址
	
	/* 空操作，用于输出404页面 */
// 	public function _empty(){
// 		$this->inform(300,'error：404！页面不存在！');
// 	}
	
	/**
	 * 构造函数
	 */
	public function _initialize(){
		parent::_initialize();
		/* 定义属性 */
		$controller      = ltrim(service('Tool')->humpToLine($this->request->controller()), '_');
		$this->url       = strtolower($controller.'/'.$this->request->action()); //当前请求地址
		$this->user_id   = session('user_id'); //当前用户ID
		$this->user_info = [ //用户信息
			'user_id'  => $this->user_id,
			'username' => session('user_auth.username'),
		];
		
		/* 验证权限 */
		if(!$this->checkAuth()){
			if($this->request->isAjax()){
				exit($this->inform(300, $this->error));
			}
			if(isset($_SERVER['HTTP_REFERER'])){
				exit('<script>top.location.href="'.url('open/login').'"</script>');
			}
			header("Location:".url('open/login'));
			exit();
		}
		/* 模板赋值 */
		$this->assign([
			'menu' => [
				'main'   => $this->getMainMenuList(), //菜单列表
				'button' => $this->getButtonMenuList(), //按键列表
			],
			'page' => ['page' => 1, 'total' => 0, 'limit' => config('list_rows')], //分页数据
		]);
	}
	
	/**
	 * dwz获取分页数据
	 * @param \think\db\Query $db 模型
	 * @param int $param 分页参数
	 * @param int|bool $simple 是否简洁模式或者总记录数
	 * @param bool $html 是否显示分页html
	 * @return array
	 */
	protected function dwzPaging($db, $page = 1, $limit = 0){
		//初始化参数
		$param = ['page' => $page>0 ? $page : 1];
		if($limit>0) $param['list_rows'] = $limit;
		//分页
		$list = $this->paging($db, $param);
		//模板赋值
		$this->assign('page', $this->page);
		
		return $list;
	}
	
	/**
	 * dwt格式返回JSON
	 * @param  int $statusCode 200=成功、300=错误、301=超时
	 * @param  string $msg 提示信息
	 * @param  array $data 附加参数
	 */
	protected function dwzReturn($statusCode = 500, $msg = '', $data = []){
		if(isset($data['url'])){
			$url              = url($data['url'], '', false, '');
			$data['navTabId'] = db('auth_rule')->where(['name' => trim($url, '/')])->value('id');
		}
		return $this->ajaxReturn(array_merge([
			'statusCode'   => $statusCode,
			'message'      => $msg ? : ($statusCode==200 ? '操作成功！' : '操作失败！'),
			'navTabId'     => '',
			'rel'          => '',
			'callbackType' => 'closeCurrent',  //empty($data['callbackType']) ? 'closeCurrent' : $data['callbackType'],    //closeCurrent关闭页面
			'forwardUrl'   => '',
			'confirmMsg'   => '',
		], $data));
	}
	
	/**
	 * 批量改变数据状态
	 * @param string $db 操作的表名
	 * @param string $ids 数据集,分隔
	 * @param int $status 变更的状态
	 */
	protected function status($db, $ids = '', $status = 0){
		if(!$ids) return $this->dwzReturn(300, '请选择要操作的数据!');
		$result = db($db)
			->where(['id' => ['in', $ids]])
			->update(['status' => $status]);
		if(!$result) return $this->dwzReturn(300);
		return $this->dwzReturn(200, null, ['callbackType' => '']);
	}
	
	/**
	 * 批量删除数据
	 * @param string $db 操作的表名
	 * @param string $ids 数据集,分隔
	 */
	protected function delete($db, $ids = ''){
		if(!$ids) return $this->dwzReturn(300, '请选择要操作的数据!');
		$result = db($db)
			->where(['id' => ['in', $ids]])
			->delete();
		if(!$result) return $this->dwzReturn(300);
		return $this->dwzReturn(200, null, ['callbackType' => '']);
	}
	
	/**
	 * 页面提示信息
	 * @param int $type 类型：[100信息-200成功-300错误-400警告]
	 * @param string $msg 提示内容
	 */
	protected function inform($type = 100, $msg){
		switch($type){
			//信息
			case 100 :
				$data = ['name' => 'information', 'background' => '#BFE7F1', 'border' => '#6EC0D5', 'font' => '#288BA2'];
				break;
			//成功
			case 200 :
				$data = ['name' => 'success', 'background' => '#CDEEA7', 'border' => '#87B15F', 'font' => '#56A42A'];
				break;
			//错误
			case 300 :
				$data = ['name' => 'error', 'background' => '#F9C6C5', 'border' => '#EB7D80', 'font' => '#C95254'];
				break;
			//警告
			case 400 :
			default:
				$data = ['name' => 'caution', 'background' => '#FBE3A7', 'border' => '#D9A538', 'font' => '#C88B16'];
				break;
		}
		return '<div style="position:relative; left:50%; top:10%; margin-top:10px; width:60%; padding:10px 30px; background:'.$data['background'].'; border:1px solid '.$data['border'].'; text-align:center; border-radius:10px; -webkit-transform:translateX(-50%); -moz-transform:translateX(-50%); -ms-transform:translateX(-50%); transform:translateX(-50%);">'
		.'<div style="margin-top:5px;"><img src="/static/admin/img/'.$data['name'].'.png" width="40" height="36" style="vertical-align:middle; margin-top:-8px;  margin-right:5px; height:36px; width:40px;">'
		.'<span style="color:'.$data['font'].'; font-size:16px; font-weight:bold; ">'.ucfirst($data['name']).'</span></div>'
		.(!empty($msg) ? '<div style="margin-top:8px;"><span style="color:#7B7B7B; font-size:14px;">'.$msg.'</span></div>' : '').'</div>';
	}
	
	/**
	 * 验证权限
	 * @return boolean
	 */
	private function checkAuth(){
		/* 免验证 */
		$result = $this->authExemptUser($this->user_id);
		if($result) return true;
		$result = $this->authExemptUrl($this->url);
		if($result) return true;
		
		/* 用户不存在 */
		if($this->user_id<=0){
			$this->error = '用户不存在';
			return false;
		}
		/* 开始验证 */
		$result = service('Auth')->check($this->url, $this->user_id);
		if(!$result){
			$this->error = '没有访问权限';
			return false;
		}
		return true;
	}
	
	/**
	 * 免权限校验地址
	 * @param string $url
	 * @return bool
	 */
	private function authExemptUrl($url = ''){
		if(in_array($url, config('auth_exempt_url') ? : [])) return true;
		if(in_array(strtok($url, '/'), config('auth_exempt_controller') ? : [])) return true;
		return false;
	}
	
	/**
	 * 免权限校验用户
	 * @param int $user_id
	 * @return bool
	 */
	private function authExemptUser($user_id = 0){
		if(in_array($user_id, config('auth_exempt_user_id') ? : [])) return true;
		return false;
	}
	
	/**
	 * 获取主菜单
	 * @return string
	 */
	private function getMainMenuList(){
		/* 只有主页需要获取 */
		if($this->url!='index/index') return false;
		
		/* 权限列表 */
		$where = ['menu' => 1, 'status' => 1]; //menu[0隐藏-1主菜单-2按钮]
		//不是超级管理员
		if(!$this->authExemptUser($this->user_id)){
			$where['id'] = ['in', service('Auth')->getAuthIds($this->user_id)];
		}
		$rule_list = db('auth_rule')->where($where)->order('sort ASC')->select();
		//进行递归排序
		$data = service('Tool')->sortArraySon($rule_list);
		//转为H5代码
		return $this->menuDeepToDwz($data);
	}
	
	/**
	 * 获取按键
	 * @return array
	 */
	private function getButtonMenuList(){
		/* 获取按键列表 */
		$parent_id = db('auth_rule')->where(['name' => $this->url])->value('id');
		$where     = ['parent_id' => $parent_id, 'menu' => 2, 'status' => 1]; //menu[0隐藏-1主菜单-2按钮]
		//不是超级管理员
		if(!$this->authExemptUser($this->user_id)){
			$where['id'] = ['in', service('Auth')->getAuthIds($this->user_id)];
		}
		return db('auth_rule')->where($where)->order('sort ASC')->select();
	}
	
	/**
	 * 主菜单-把菜单数据$data递归输出成DWZ框架的html
	 * @param  array $data
	 * @param  int $level
	 * @return string
	 */
	private function menuDeepToDwz($data, $level = 1){
		$ret = '';
		if($data){
			// 设置前缀与后缀
			if($level==1){
				//$pre = '<div class="accordionHeader">' . "\r\n";9
				//$suf = '</div>';
				$pre = '';
				$suf = '';
			}elseif($level==2){
				$pre = '<div class="accordionContent">'."\r\n";
				$pre .= '<ul class="tree treeFolder">'."\r\n";
				$suf = "</ul>\r\n</div>\r\n";
			}else{
				$pre = '<ul class="treeFolder">'."\r\n";
				$suf = '</ul>'."\r\n";
			}
			$nextLevel = $level+1;
			$ret .= $pre;
			foreach($data as $k => $v){
				$deepRet = '';
				if(!empty($v['list'])){
					$deepRet = $this->menuDeepToDwz($v['list'], $nextLevel);
					//var_dump($deepRet);exit();
					/* if ($deepRet) {
					 $ret .= $deepRet;
					}  */
				}
				if($level==1){
					$ret .= '<div class="accordionHeader">'."\r\n";
					$ret .= '<h2><span>Folder</span>'.$v['title'].'</h2>'."\r\n";
					$ret .= '</div>';
					$ret .= $deepRet;
				}else{
					$ret .= '<li>';
					if($v['name']){
						$ret .= '<a href="'.url($v['name']).'" rel="'.$v['id'].'" ';
						switch($v['target']){
							case 1:
								$ret .= 'target="navTab" ';
								break;
							case 2:
								$ret .= 'target="dialog" ';
								break;
							default:
						}
						if($v['height']>0) $ret .= 'height="'.$v['height'].'" ';
						if($v['width']>0) $ret .= 'width="'.$v['width'].'" ';
						$ret .= '>'.$v['title'].'</a>';
					}else{
						$ret .= '<a>'.$v['title'].'</a>';
					}
					$ret .= $deepRet.'</li>'."\r\n";
				}
			}
			$ret .= $suf;
		}
		return $ret;
	}
	
}
