<?php
	/**
	 * Connect to the database using the Zend_Db framework
	 * @since Version 3.7.5
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	/**
	 * If we want to establish a connection immediately, change this to true
	 * This may needlessly slow down page generation times
	 */
	 
	$zend_getconnection = false;
	
	/**
	 * If we're going to use a read-only host as well as a read/write host, define it here.
	 * Set to false if we don't want to use a read-only host.
	 */
	
	$dbhost_readonly = false; #"203.29.53.59"; // nanak.omni.com.au 
	
	/**
	 * Present Zend DB fatal errors in a user-friendly manner
	 * @param string $message
	 */
	
	if (!function_exists("RP_ZendDb_Error")) {
		function RP_ZendDb_Error($message) {
			$file = dirname(__DIR__) . DIRECTORY_SEPARATOR . "content" . DIRECTORY_SEPARATOR . "msg-zenderror.htm";
			
			if (file_exists($file)) {
				$file = file_get_contents($file); 
				$message = str_replace("{\$error_message}", $message, $file);
			}
			
			ob_end_clean(); 
			echo $message; 
			die;
		}
	}
	 
	/**
	 * Before we do anything, load the database config
	 */
	
	require(__DIR__ . DIRECTORY_SEPARATOR . "config.php");
	
	/**
	 * Check if Zend Framework is already in the include path; if it's not, add it
	 */
	
	if (strpos(get_include_path(), "libzend-framework") === false) {
		set_include_path(get_include_path() . PATH_SEPARATOR . "/usr/share/php/libzend-framework-php");
	}
	
	/**
	 * Load some required Zend components
	 */
	
	require_once("Zend/Config.php");
	require_once("Zend/Db.php");
	
	/**
	 * Normalise the DB adapter name for Zend_Db
	 */
	
	if (preg_match("@mysqli@i", $dbadapter)) {
		$dbadapter = "Mysqli";
	} elseif (preg_match("@mysql@i", $dbadapter)) {
		$dbadapter = "Pdo_Mysql";
	}
	
	/**
	 * Set the Zend_Db config
	 */
	
	$ZendDB_Config = new Zend_Config(
		array(
			'database' => array(
				'adapter' => $dbadapter,
				'params'  => array(
					'host'     => $dbhost,
					'dbname'   => $dbname,
					'username' => $dbuname,
					'password' => $dbpass,
					'profiler' => RP_DEBUG ? true : false,
				)
			)
		)
	);
	
	$ZendDB_ReadOnly_Config = new Zend_Config(
		array(
			'database' => array(
				'adapter' => $dbadapter,
				'params'  => array(
					'host'     => $dbhost_readonly, // nanak.omni.com.au
					'dbname'   => $dbname,
					'username' => $dbuname,
					'password' => $dbpass,
					'profiler' => RP_DEBUG ? true : false,
				)
			)
		)
	);
	
	/**
	 * Try and connect; catch any errors
	 */
	
	try {
		global $ZendDB;
		
		if (isset($ZendDB) && is_object($ZendDB)) {
			if (RP_DEBUG) {
				$site_debug[] = "Zend_DB: Re-using global \$ZendDB";
			}
		} else {
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
				
			$ZendDB = Zend_Db::factory($ZendDB_Config->database);
						
			if (RP_DEBUG) {
				$site_debug[] = "Zend_DB: Created Zend_DB::factory object in " . round(microtime(true) - $debug_timer_start, 5) . "s";
			}
			
			if ($zend_getconnection) {
				if (RP_DEBUG) {
					global $site_debug;
					$debug_timer_start = microtime(true);
				}
						
				$ZendDB->getConnection(); 
						
				if (RP_DEBUG) {
					$site_debug[] = "Zend_DB: SUCCESS Established connection to " . $dbhost . "::" . $dbname . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
				}
			}
		}
	} catch (Zend_Db_Adapter_Exception $e) {
		RP_ZendDB_Error($e->getMessage());
	} catch (Zend_Exception $e) {
		RP_ZendDB_Error($e->getMessage());
	}
	
	/**
	 * Try and connect to the read-only server. If $dbhost_readonly === false, alias $ZendDB instead
	 */
	
	if ($dbhost_readonly) {
		try {
			global $ZendDB_ReadOnly;
			
			if (isset($ZendDB_ReadOnly) && is_object($ZendDB_ReadOnly)) {
				if (RP_DEBUG) {
					$site_debug[] = "Zend_DB_ReadOnly: Re-using global \$ZendDB_ReadOnly";
				}
			} else {
				if (RP_DEBUG) {
					global $site_debug;
					$debug_timer_start = microtime(true);
				}
					
				$ZendDB_ReadOnly = Zend_Db::factory($ZendDB_ReadOnly_Config->database);
							
				if (RP_DEBUG) {
					$site_debug[] = "Zend_DB_ReadOnly: Created Zend_DB_ReadOnly::factory object in " . round(microtime(true) - $debug_timer_start, 5) . "s";
				}
				
				if ($zend_getconnection) {
					if (RP_DEBUG) {
						global $site_debug;
						$debug_timer_start = microtime(true);
					}
							
					$ZendDB_ReadOnly->getConnection(); 
							
					if (RP_DEBUG) {
						$site_debug[] = "Zend_DB_ReadOnly: SUCCESS Established connection to " . $dbhost . "::" . $dbname . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
					}
				}
			}
		} catch (Zend_Db_Adapter_Exception $e) {
			RP_ZendDB_Error($e->getMessage());
		} catch (Zend_Exception $e) {
			RP_ZendDB_Error($e->getMessage());
		}
	} else {
		$ZendDB_ReadOnly =& $ZendDB;
	}
?>