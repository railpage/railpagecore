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
			
			$timer = Debug::GetTimer(); 
			
			$rs = $this->cn->replace($key, $value, $exp); 
			$verb = "Update";
			
			if ($rs === false) {
				$rs = $this->cn->set($key, $value, $exp); 
				$verb = "Set";
			}
			
			Debug::LogEvent(($rs === false ? "Failed" : "Succeeded") . " " . $verb . " " . $key . " (" . strlen(serialize($value)) . "b object, expires " . $exp . ")", $timer); 
			
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
			
			$timer = Debug::GetTimer(); 
		
			$rs = $this->cn->get($key); 
			
			Debug::LogEvent(($rs === false ? "NOT FOUND" : "FOUND") . " " . $key, $timer); 
			
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
			
			$timer = Debug::GetTimer(); 
			
			$rs = $this->cn->delete($key); 
			
			Debug::LogEvent(($rs === false ? "FAILED" : "SUCCEEDED") . " delete " . $key, $timer); 
			
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
	