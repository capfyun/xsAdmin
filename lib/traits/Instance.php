<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */
// +---------------------------------------------------------------------------
// | SWAN [ $_SWANBR_SLOGAN_$ ]
// +---------------------------------------------------------------------------
// | Copyright $_SWANBR_COPYRIGHT_$
// +---------------------------------------------------------------------------
// | Version  $_SWANBR_VERSION_$
// +---------------------------------------------------------------------------
// | Licensed ( $_SWANBR_LICENSED_URL_$ )
// +---------------------------------------------------------------------------
// | $_SWANBR_WEB_DOMAIN_$
// +---------------------------------------------------------------------------

namespace lib\traits;

trait Instance{
	
	protected static $instance = null;
	
	/**
	 * 获取实例
	 * @param array $options 实例配置
	 * @return static
	 */
	public static function instance($options = []){
		if(is_null(self::$instance)){
			static::$instance = new static($options);
		}
		return static::$instance;
	}
	
	
	/**
	 * 初始化
	 */
	private function __construct(){}
	
}
