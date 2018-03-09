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
		//当前请求地址
		$this->url = strtolower(
			ltrim(service('Tool')->humpToLine($this->request->controller()), '_')
			.'/'.$this->request->action()
		);
		//当前用户ID
		$this->user_id = session('user_id');
		//用户信息
		$this->user_info = [
			'user_id'  => $this->user_id,
			'username' => session('user_auth.username'),
		];
		
		/* 权限验证 */
		$result = service('Auth')->check($this->url, $this->user_id);
		if(!$result){
			$this->redirect(url('open/login'));
		}
		/* 模板赋值 */
		$this->assign([
			'menu' => [
				'main'   => $this->getMainMenu(),
				'button' => $this->getOptionsMenu(),
			],
			'page' => ['page' => 1, 'total' => 0, 'limit' => config('list_rows')], //分页数据
		]);
	}
	
	/**
	 * 获取主菜单
	 * @return string
	 */
	private function getMainMenu(){
		
		/* 权限列表 */
		$rule_list = db('auth_rule')
			->where([
				'menu'   => 1, //menu[0隐藏-1主菜单-2按钮]
				'status' => 1,
				'id'     => ['in', service('Auth')->getAuthIds($this->user_id)],
			])
			->order('sort ASC')
			->select();
		//进行递归排序
		$data = service('Tool')->sortArraySon($rule_list);
		//转为H5代码
		return $this->menuDeepToDwz($data);
	}
	
	/**
	 * 获取按键
	 * @return array
	 */
	private function getOptionsMenu(){
		/* 获取按键列表 */
		$parent_id = db('auth_rule')
			->where(['name' => $this->url])
			->value('id');
		
		$result    = db('auth_rule')
			->where([
				'parent_id' => $parent_id,
				'menu'      => 2, //menu[0隐藏-1主菜单-2按钮]
				'status'    => 1,
				'id'        => ['in', service('Auth')->getAuthIds($this->user_id)],
			])
			->order('sort ASC')
			->select();
		
		return $result;
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
