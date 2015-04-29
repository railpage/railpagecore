<?php
	/**
	 * Memcached controller for Railpage
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage;
	
	use StatsD;
	use Memcached as PHPMemcached;
	use Exception;
	use DateTime;
	
	/**
	 * Memcached controller
	 */
	
	class Memcached {
		
		/**
		 * Memcached host
		 * @since Version 3.8.7
		 * @var string $host
		 */
		
		public $host;
		
		/**
		 * Memcached port
		 * @since Version 3.8.7
		 * @var string $port
		 */
		
		public $port;
		
		/**
		 * Memcached connection
		 * @since Version 3.8.7
		 * @var object $cn
		 */
		
		private $cn;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @return void
		 */
		
		public function __construct() {
			if (!defined("DS")) {
				define("DS", DIRECTORY_SEPARATOR);
			}
			
			if (defined("RP_SITE_ROOT")) {
				$path = sprintf("%s%sconfig%sconfig.railpage.json", RP_SITE_ROOT, DS, DS);
			} else {
				$path = dirname(dirname(dirname(__DIR__))) . DS . "config" . DS . "config.railpage.json";
			}
			
			if (file_exists($path)) {
				$Config = json_decode(file_get_contents($path));
				
				$this->host = $Config->Memcached->Host;
				$this->port = $Config->Memcached->Port;
				
				$this->cn = new PHPMemcached;
				$this->cn->addServer($this->host, $this->port);
			}
		}
		
		/**
		 * Check if Memcached is connected to a server
		 * @return boolean
		 */
		
		public function connected() {
			if ($this->cn instanceof PHPMemcached) {
				return true;
			} 
			
			return false;
		}
	
		/**
		 * Cache an object in Memcache
		 * @since Version 3.7.5
		 * @param string $key
		 * @param mixed $value
		 * @param int $exp
		 * @return boolean
		 */
		
		function set($key = false, $value = "thisisanemptyvalue", $exp = 0) {
			if (!$key) {
				throw new Exception("Cannot set object in memcache - cache \$key cannot be empty"); 
				return false;
			}
			
			if ($value == "thisisanemptyvalue") {
				throw new Exception("Cannot set object in memcache - no \$value given for \$key"); 
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
			
			$rs = $this->cn->replace($key, $value, $exp); 
			$verb = "Update";
			
			if ($rs === false) {
				$rs = $this->cn->set($key, $value, $exp); 
				$verb = "Set";
			}
			
			if (RP_DEBUG) {
				if ($rs === false) {
					$site_debug[] = "Memcache: FAILED " . $verb . " " . $key . " (" . strlen(serialize($value)) . "b object, expires " . $exp . ") in " . number_format(microtime(true) - $debug_timer_start, 12) . "s";
				} else {
					$site_debug[] = "Memcache: SUCCEEDED " . $verb . " " . $key . " (" . strlen(serialize($value)) . "b object, expires " . $exp . ") in " . number_format(microtime(true) - $debug_timer_start, 12) . "s";
				}
			}
			
			StatsD::increment("rp.memcached.set");
		}
		
		/**
		 * Put something into Memcached
		 * @since Version 3.8.7
		 * @return boolean
		 */
		
		public function put($key, $value, $exp) {
			return $this->set($key, $value, $exp);
		}
		
		/**
		 * Fetch an object from Memcache
		 * @since Version 3.7.5
		 * @param string $key
		 * @return mixed
		 */
		
		function get($key = false) {
			if (!$key) {
				throw new Exception("Cannot fetch object from memcache - \$key was not specified"); 
				return false;
			}
			
			if (RP_DEBUG) {
				global $site_debug;
				
				$debug_timer_start = microtime(true);
			}
		
			$rs = $this->cn->get($key); 
			
			if (RP_DEBUG) {
				if ($rs === false) {
					$site_debug[] = "Memcache: NOT FOUND " . $key . " in " . number_format(microtime(true) - $debug_timer_start, 10) . "s";
				} else {
					$site_debug[] = "Memcache: FOUND " . $key . " in " . number_format(microtime(true) - $debug_timer_start, 10) . "s";
				}
			}
			
			StatsD::increment("rp.memcached.get");
			
			return $rs;
		}
		
		/**
		 * Remove an object from Memcache
		 * @since Version 3.7.5
		 * @param string $key
		 * @return mixed
		 */
		
		function remove($key = false) {
			if (!$key) {
				throw new Exception("Cannot remove object from memcache - \$key was not specified"); 
				return false;
			}
			
			if (RP_DEBUG) {
				global $site_debug;
				
				$debug_timer_start = microtime(true);
			}
			
			$rs = $this->cn->delete($key); 
			
			if (RP_DEBUG) {
				if ($rs === false) {
					$site_debug[] = "Memcache: FAILED Delete for " . $key . " in " . number_format(microtime(true) - $debug_timer_start, 10) . "s";
				} else {
					$site_debug[] = "Memcache: SUCCEEDED Delete for " . $key . " in " . number_format(microtime(true) - $debug_timer_start, 10) . "s";
				}
			}
			
			StatsD::increment("rp.memcached.delete");
			
			return $rs;
		}
		
		/**
		 * Remove an object from Memcache
		 * @since Version 3.7.5
		 * @param string $key
		 * @return mixed
		 */
		
		function delete($key = false) {
			return $this->remove($key); 
		}
	}
?>