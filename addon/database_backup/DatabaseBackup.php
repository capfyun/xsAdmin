<?php
/**
 * 数据库备份
 * @author xs
 */
namespace addon\database_backup;

use addon\Base;
use think\Hook;
use lib\Helper;

class DatabaseBackup extends Base{
	
	/**
	 * 插件信息
	 */
	protected static $title       = '数据库管理';
	protected static $description = '数据库优化、备份、还原等操作，只限超管访问';
	protected static $author      = 'xs';
	protected static $version     = '1.0';
	
	/**
	 * 选项
	 */
	public static function option(){
		return [
			'backup_path' => [
				'type'     => 'text', //checkbox、selects的值是数组
				'name'     => '数据库备份目录',
				'validate' => ['require', 'max' => 100],
				'value'    => 'resource/backup/database',
			],
		];
	}
	
	/**
	 * 注册
	 */
	public static function register(){
		if(strtolower(request()->module())!='admin'){
			return;
		}
		//权限校验
		$user_id = model('User')->isLogin() ? : model('User')->cookieLogin();
		if(!in_array($user_id, config('administrator_id'))){
			return;
		}
		require_once __DIR__.'/DatabaseController.php';
		
		$url = strtolower(
			request()->module()
			.'/'.Helper::convertHump(request()->controller())
			.'/'.request()->action()
		);
		$id  = uniqid();
		//菜单
		$menu = [
			'id'        => $id,
			'parent_id' => 4,
			'name'      => 'database/database_list',
			'title'     => '数据库',
			'sort'      => 100,
			'type'      => 1,
			'icon'      => 'fa-th-list',
		];
		//选项
		$option = [
			'optimize'    => [
				'name'    => 'database/optimize',
				'title'   => '优化表',
				'type'    => 2,
				'icon'    => 'fa-space-shuttle',
				'request' => 1,
				'param'   => 'name:2',
			],
			'export'      => [
				'name'    => 'database/export',
				'title'   => '备份',
				'type'    => 2,
				'icon'    => 'fa-sign-out',
				'request' => 1,
				'param'   => 'name:0',
			],
			'backup_list' => [
				'name'    => 'database/backup_list',
				'title'   => '已备份文件',
				'type'    => 2,
				'icon'    => 'fa-file-text-o',
				'request' => 0,
				'param'   => '',
			],
			'import'      => [
				'name'    => 'database/import',
				'title'   => '已备份文件',
				'type'    => 2,
				'icon'    => 'fa-sign-in',
				'request' => 1,
				'param'   => 'name:1',
			],
		];
		//注册菜单
		Hook::add('create_menu_after', function(&$param) use ($menu){
			$param[] = $menu;
			array_multisort(array_column($param, 'sort'), SORT_DESC, $param);
		});
		//注册选项
		if($url=='admin/database/database_list'){
			Hook::add('create_option_after', function(&$param) use ($option){
				$param = [
					$option['optimize'],
					$option['export'],
					$option['backup_list'],
				];
			});
			Hook::add('create_checked_after', function(&$param) use ($menu, $id){
				$param = [
					$id => $menu,
					4   => db('auth_rule')->where(['id' => 4])->find(),
				];
			});
		}
		
		if($url=='admin/database/backup_list'){
			Hook::add('create_option_after', function(&$param) use ($option){
				$param = [
					$option['import'],
				];
			});
			
			Hook::add('create_checked_after', function(&$param) use ($menu, $id, $option){
				$param = [
					$option['backup_list'],
					$id => $menu,
					4   => db('auth_rule')->where(['id' => 4])->find(),
				];
			});
		}
	}
}

