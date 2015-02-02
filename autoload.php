<?php

function vendorAutoLoader($class, $logger = false)
{
	$filename = dirname(__FILE__) . DS . str_replace('\\', DS, $class) . '.php';
	if (file_exists($filename)) {
		require_once($filename);
	} else {
		if (is_callable($logger)) {
			call_user_func($logger, array("class $class could not be found. $filename does not exist", E_USER_NOTICE));
		} else {
			error_log("class $class could not be found. $filename does not exist");
		}
	}
	// on to the next autoloader :-)
	return;
}
spl_autoload_register('vendorAutoLoader');
