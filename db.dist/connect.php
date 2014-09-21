<?php
	/**
	 * Connect to the database
	 * @package Railpage
	 * @author Michael Greenhill
	 * @since Version 3.4
	 */
	
	// Load the database configuration
	require(__DIR__ . DIRECTORY_SEPARATOR . "config.dist.php");
	
	// Load the extended MySQLi class
	require_once(__DIR__ . DIRECTORY_SEPARATOR . "mysqli.php"); 
	
	// Connect to the database
	$db = new \sql_db($dbhost, $dbuname, $dbpass, $dbname);
?>