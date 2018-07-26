<?php

spl_autoload_register(function($class){
	if(strpos($class, 'lib\\')===0){
		$class = str_replace('\\', DIRECTORY_SEPARATOR, str_replace('lib\\', '', $class));
		$file  = __DIR__.DIRECTORY_SEPARATOR.$class.'.php';
		file_exists($file) && require_once $file;
	}
});