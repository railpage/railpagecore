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
	
	/**
	 * Check if PHPUnit is running. Flag it if it is running, so we can set the appropriate DB settings
	 */
	
	if (class_exists("PHPUnit_Framework_TestCase")) {
		$PHPUnitTest = true;
	} else {
		$PHPUnitTest = false;
	}
	
	/**
	 * Load the autoloader
	 */
	
	require_once("autoload.php");
	
?>