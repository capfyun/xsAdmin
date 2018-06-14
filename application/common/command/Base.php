<?php
/**
 * 命令行
 * @auth xs
 */
namespace app\common\command;

use think\console\Input;
use think\console\Output;

abstract class Base extends \think\console\Command{
	/**
	 * 参数
	 * @var Input
	 */
	protected $input = null;
	/**
	 * 选项
	 * @var Output
	 */
	protected $output = null;
	
}