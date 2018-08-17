<?php
/**
 * 验证码
 * @author xs
 */
namespace addon\captcha;

use addon\Base;
use lib\Menu;
use think\Hook;
use lib\Helper;
use think\Request;

class Captcha extends Base{
	
	/**
	 * 插件信息
	 */
	protected static $title       = '登录验证码';
	protected static $description = '后台登录验证码';
	protected static $author      = 'xs';
	protected static $version     = '1.0';
	
	/**
	 * 选项
	 */
	public static function option(){
		return [
			'use_zh'         => [
				'type'     => 'radio', //checkbox、selects的值是数组
				'name'     => '使用中文验证码',
				'validate' => ['number', 'between' => '0,1'],
				'value'    => ['是' => true, '否' => false],
			],
			'use_img_bg'     => [
				'type'     => 'radio', //checkbox、selects的值是数组
				'name'     => '使用背景图片',
				'validate' => ['number', 'between' => '0,1'],
				'value'    => ['是' => true, '否' => false],
			],
			'use_curve'      => [
				'type'     => 'radio', //checkbox、selects的值是数组
				'name'     => '是否画混淆曲线',
				'validate' => ['number', 'between' => '0,1'],
				'value'    => ['是' => true, '否' => false],
			],
			'use_noise'      => [
				'type'     => 'radio', //checkbox、selects的值是数组
				'name'     => '是否添加杂点',
				'validate' => ['number', 'between' => '0,1'],
				'value'    => ['是' => true, '否' => false],
			],
			'case_sensitive' => [
				'type'     => 'radio', //checkbox、selects的值是数组
				'name'     => '区分大小写',
				'validate' => ['number', 'between' => '0,1'],
				'value'    => ['是' => true, '否' => false],
			],
			'length'         => [
				'type'     => 'text', //checkbox、selects的值是数组
				'name'     => '验证码位数',
				'validate' => ['number', 'between' => '1,10'],
				'value'    => 4,
			],
		];
	}
	
	/**
	 * 注册
	 */
	public static function register(){
		
		Menu::push([
			'name' => 'captcha/image',
			'show' => false,
			'auth' => false,
		]);
		require_once __DIR__.'/CaptchaController.php';
		Hook::add('module_init', function(&$request){
			if('admin/open/login'!=strtolower($request->module().'/'.$request->controller().'/'.$request->action())){
				return true;
			}
			//验证码校验
			if($request->isPost()){
				if(!session('captcha')){
					abort(json(['code' => 1000, 'msg' => '请填写验证码']));
				}
				$config = static::config();
				if(isset($config['case_sensitive']) && $config['case_sensitive']){
					session('captcha')!=input('captcha') && abort(json(['code' => 1000, 'msg' => '验证码错误']));
				}else{
					strtolower(session('captcha'))!=strtolower(input('captcha')) && abort(json(['code' => 1000, 'msg' => '验证码错误']));
				}
			}
			Hook::add('view_filter', function(&$view){
				$image = url('captcha/image');
				$captcha = <<<HTML
<div class="form-group has-feedback">
	<div class="col-sm-12" style="margin-bottom:10px;">
		<input name="captcha" value="" type="text"  class="form-control" placeholder="填写下图所示验证码">
		<span class="glyphicon glyphicon-tag form-control-feedback"></span>
	</div>
	<div class="col-sm-7">
		<img src="{$image}" width="200" id="captcha_img" class="img-responsive img-thumbnail" alt="" onclick="$(this).prop('src','{$image}?_='+Math.random());" style="cursor: pointer;">
	</div>
	<div class="col-sm-5">
		看不清？<a href="#" onclick="$('#captcha_img').attr('src','{$image}?_='+Math.random());return false;" >换一张</a>
	</div>
</div>
HTML;
				$view = str_replace('<!--$captcha-->', $captcha, $view);
			});
		});
	}
	
}
