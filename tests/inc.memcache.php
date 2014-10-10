<?php
	/**
	 * Memcache initiator
	 * @since Version 3.2
	 * @package Railpage
	 */
	
	if (!defined("RP_MEMCACHE_HOST")) {
		define("RP_MEMCACHE_HOST", "127.0.0.1");
	}
	
	if (!defined("RP_MEMCACHE_PORT")) {
		define("RP_MEMCACHE_PORT", 11211);
	}
	
	if (class_exists("Memcached")) {
		$memcache = new Memcached();
		$memcache->addServer(RP_MEMCACHE_HOST, RP_MEMCACHE_PORT);
		
		$memcache->setOption(Memcached::OPT_NO_BLOCK, true);
		$memcache->setOption(Memcached::OPT_TCP_NODELAY, true);
	} else {
		trigger_error("Could not start Memcached - Memcached class does not exist!");
		$memcache = false;
	}
	
	/**
	 * Cache an object in Memcache
	 * @since Version 3.7.5
	 * @param string $key
	 * @param mixed $value
	 * @param int $exp
	 * @return boolean
	 */
	
	if (!function_exists("setMemcacheObject")) {
		function setMemcacheObject($key = false, $value = "thisisanemptyvalue", $exp = 0) {
			global $memcache; 
			
			if ($memcache) {
				if (!$key) {
					throw new \Exception("Cannot set object in memcache - cache \$key cannot be empty"); 
					return false;
				}
				
				if ($value == "thisisanemptyvalue") {
					throw new \Exception("Cannot set object in memcache - no \$value given for \$key"); 
					return false;
				}
				
				if (empty($value)) {
					trigger_error("Tried to set empty value for key " . $key . " in memcache. Are you sure it should be empty?", E_USER_WARNING); 
				}
				
				if (RP_DEBUG) {
					global $site_debug;
					$debug_timer_start = microtime(true);
					
					/*
					$site_debug[] = array(
						"key" => $key,
						"exp" => $exp,
						"value" => $value
					);
					*/
				}
				
				$rs = $memcache->replace($key, $value, $exp); 
				$verb = "Update";
				
				if ($rs === false) {
					$rs = $memcache->set($key, $value, $exp); 
					$verb = "Set";
				}
				
				if (RP_DEBUG) {
					if ($rs === false) {
						$site_debug[] = "Memcache: FAILED " . $verb . " " . $key . " (" . strlen(serialize($value)) . "b object, expires " . $exp . ") in " . number_format(microtime(true) - $debug_timer_start, 12) . "s";
					} else {
						$site_debug[] = "Memcache: SUCCEEDED " . $verb . " " . $key . " (" . strlen(serialize($value)) . "b object, expires " . $exp . ") in " . number_format(microtime(true) - $debug_timer_start, 12) . "s";
					}
				}
				
				#StatsD::increment("rp.memcached.set");
				
				return $rs;
			} else {
				return false;
			}
		}
	}
	
	/**
	 * Fetch an object from Memcache
	 * @since Version 3.7.5
	 * @param string $key
	 * @return mixed
	 */
	
	if (!function_exists("getMemcacheObject")) {
		function getMemcacheObject($key = false) {
			global $memcache; 
			
			if ($memcache) {
				if (!$key) {
					throw new \Exception("Cannot fetch object from memcache - \$key was not specified"); 
					return false;
				}
				
				if (RP_DEBUG) {
					global $site_debug;
					
					$debug_timer_start = microtime(true);
				}
			
				$rs = $memcache->get($key); 
				
				if (RP_DEBUG) {
					if ($rs === false) {
						$site_debug[] = "Memcache: NOT FOUND " . $key . " in " . number_format(microtime(true) - $debug_timer_start, 10) . "s";
					} else {
						$site_debug[] = "Memcache: FOUND " . $key . " in " . number_format(microtime(true) - $debug_timer_start, 10) . "s";
					}
				}
				
				#StatsD::increment("rp.memcached.get");
				
				return $rs;
			} else {
				return false;
			}
		}
	}
	
	/**
	 * Remove an object from Memcache
	 * @since Version 3.7.5
	 * @param string $key
	 * @return mixed
	 */
	
	if (!function_exists("removeMemcacheObject")) {
		function removeMemcacheObject($key = false) {
			global $memcache; 
			
			if ($memcache) {
				if (!$key) {
					throw new \Exception("Cannot remove object from memcache - \$key was not specified"); 
					return false;
				}
				
				if (RP_DEBUG) {
					global $site_debug;
					
					$debug_timer_start = microtime(true);
				}
				
				$rs = $memcache->delete($key); 
				
				if (RP_DEBUG) {
					if ($rs === false) {
						$site_debug[] = "Memcache: FAILED Delete for " . $key . " in " . number_format(microtime(true) - $debug_timer_start, 10) . "s";
					} else {
						$site_debug[] = "Memcache: SUCCEEDED Delete for " . $key . " in " . number_format(microtime(true) - $debug_timer_start, 10) . "s";
					}
				}
				
				#StatsD::increment("rp.memcached.delete");
				
				return $rs;
			} else {
				return false;
			}
		}
	}
	
	/**
	 * Remove an object from Memcache
	 * @since Version 3.7.5
	 * @param string $key
	 * @return mixed
	 */
	
	if (!function_exists("deleteMemcacheObject")) {
		function deleteMemcacheObject($key = false) {
			return removeMemcacheObject($key); 
		}
	}
?>