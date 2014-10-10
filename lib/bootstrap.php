<?php
	/**
	 * RailpageCore bootstrapper
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	if (php_sapi_name() == "cli") {
		$_SERVER['SERVER_NAME'] = gethostname(); 
	}
	
	if (!defined("RP_DEBUG")) {
		define("RP_DEBUG", false);
	}
	
	if (!defined("DS")) {
		define("DS", DIRECTORY_SEPARATOR);
	}
	
	if (!defined("RP_SITE_ROOT")) {
		define("RP_SITE_ROOT", __DIR__);
	}
	
	/**
	 * Check if PHPUnit is running. Flag it if it is running, so we can set the appropriate DB settings
	 */
	
	$PHPUnitTest = false;
	
	if (class_exists("PHPUnit_Framework_TestCase")) {
		$PHPUnitTest = true;
		
		require_once(dirname(__DIR__) . DS . "tests" . DS . "inc.functions.php");
		require_once(dirname(__DIR__) . DS . "tests" . DS . "inc.memcache.php");
		require_once(dirname(__DIR__) . DS . "tests" . DS . "inc.config.railpage.php");
		
		/**
		 * Load the composer autoloader
		 */
		
		if (file_exists(__DIR__ . DS . "vendor" . DS . "autoload.php")) {
			require(__DIR__ . DS . "vendor" . DS . "autoload.php");
		}
	}
	
	/**
	 * Load the autoloader
	 */
	
	require_once("autoload.php");
	
?>