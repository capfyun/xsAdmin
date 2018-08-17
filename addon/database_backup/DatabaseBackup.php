<?php
/**
 * 数据库备份
 * @author xs
 */
namespace addon\database_backup;

use addon\Base;
use lib\Menu;
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
		Menu::push([
			'name'    => 'database/database_list',
			'title'   => '数据库',
			'icon'    => 'fa-th-list',
			'auth'    => 26,
			'sublist' => [
				['name' => 'database/optimize', 'title' => '优化表', 'auth' => 26],
				['name' => 'database/export', 'title' => '备份', 'auth' => 26],
				['name' => 'database/backup_list', 'title' => '备份列表', 'auth' => 26],
				['name' => 'database/import', 'title' => '还原', 'auth' => 26],
			],
		]);
		require_once __DIR__.'/DatabaseController.php';
		return;
	}
}

