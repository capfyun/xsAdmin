<?php

function classLoader($class){
	if(strpos($class, 'xs\\')===0){
		$class = str_replace('\\', DIRECTORY_SEPARATOR, str_replace('xs\\', '', $class));
		$file  = __DIR__.DIRECTORY_SEPARATOR.$class.'.php';
		file_exists($file) && require_once $file;
	}
}

spl_autoload_register('classLoader');