<?php
	/**
	 * Railpage session handler
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage;
	
	use Railpage\Memcached;
	use Railpage\AppCore;
	use Railpage\SessionHandler;
	use StatsD;
	use Memcached as PHPMemcached;
	use Exception;
	use DateTime;
	use SessionHandlerInterface;
	
	
	/**
	 * Session handler
	 */
	
	class Session implements SessionHandlerInterface {
		
		/**
		 * Session ID
		 * @since Version 3.8.7
		 * @var string $id
		 */
		
		public $id;
		
		/**
		 * Session commencement
		 * @since Version 3.8.7
		 * @var \DateTime $Start
		 */
		
		public $Start;
		
		/**
		 * Memcached object
		 * @since Version 3.8.7
		 * @var \Railpage\Memcached $Memcached
		 */
		
		private $Memcached;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @return void
		 */
		
		public function __construct() {
			
			$this->Memcached = new Memcached;
			
			if (!defined("RP_SITE_DOMAIN")) {
				define("RP_SITE_DOMAIN", "railpage.com.au");
			}
			
			/*
			if ($this->Memcached->connected()) {
				session_module_name('memcached');
				session_save_path(sprintf("%s:%d", $this->Memcached->host, $this->Memcached->port));
			}
			
			if (empty(trim(ini_get("memcached.sess_prefix")))) {
				ini_set("memcached.sess_prefix", "memc.sess.key.");
			}
			*/
			
			/**
			 * Get memcached host configuration. If it's empty for whatever reason, fall back to hardcoded host(s)
			 */
			
			$Config = AppCore::getConfig(); 
			
			$host['primary']['addr'] = (isset($Config->Memcached->host) && !empty($Config->Memcached->host)) ? $Config->Memcached->host : "203.28.180.12";
			$host['primary']['port'] = (isset($Config->Memcached->port) && !empty($Config->Memcached->port)) ? $Config->Memcached->host : "11211";
			$host['secondary']['addr'] = (isset($Config->Memcached->hosts->secondary->addr) && !empty($Config->Memcached->hosts->secondary->addr)) ? $Config->Memcached->hosts->secondary->addr : "203.28.180.19";
			$host['secondary']['port'] = (isset($Config->Memcached->hosts->secondary->port) && !empty($Config->Memcached->hosts->secondary->port)) ? $Config->Memcached->hosts->secondary->port : "11211";
			
			$handlername = isset($Config->SessionHandler) && !empty($Config->SessionHandler) ? $Config->SessionHandler : "MemcachedSessionHandler";
			$handlername = sprintf("\Railpage\SessionHandler\%s", $handlername);
			
			/**
			 * Create a new \Memcached (aka PHPMemcached) object, connect our hosts to it
			 */
			
			$Memcached = new PHPMemcached;
			$Memcached->addServer($host['primary']['addr'], $host['primary']['port']); 
			$Memcached->addServer($host['secondary']['addr'], $host['secondary']['port']); 
			$Memcached->setOption(PHPMemcached::OPT_DISTRIBUTION, PHPMemcached::DISTRIBUTION_CONSISTENT);
			$Memcached->setOption(PHPMemcached::OPT_CONNECT_TIMEOUT, 150);
			$Memcached->setOption(PHPMemcached::OPT_RETRY_TIMEOUT, 0);
			$Memcached->setOption(PHPMemcached::OPT_HASH, PHPMemcached::HASH_MD5);
			
			/**
			 * Create our MemcachedSessionHandler instance and instruct PHP to use that for session storage
			 */
			
			$options = array(
				"prefix" => "memc.sess.key.",
				"expiretime" => 1800
			);
			
			$SessionHandler = new $handlername($Memcached, $options);
			session_set_save_handler($SessionHandler, true);
			
			/**
			 * Cross-subdomain cookies n shiz
			 */
			
			session_set_cookie_params(0, "/", sprintf(".%s", RP_SITE_DOMAIN)); 
			
			/**
			 * ZOMG ACTUALLY START THE EFFING SESSION
			 */
			
			session_start();
			
			/**
			 * Force a session length - from http://stackoverflow.com/a/1270960/319922
			 */
			
			ini_set("session.gc_maxlifetime", 1800);
			
			if (empty($_POST) && isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
				// last request was more than 30 minutes ago
				session_unset();     // unset $_SESSION variable for the run-time 
				session_destroy();   // destroy session data in storage
			}
			
			$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
			
			/**
			 * Re-generate the session ID to avoid session attacks - from http://stackoverflow.com/a/1270960/319922
			 */
			
			/*
			if (!isset($_SESSION['CREATED'])) {
				$_SESSION['CREATED'] = time();
			} elseif (time() - $_SESSION['CREATED'] > 1800) {
				// session started more than 30 minutes ago
				session_regenerate_id(true);    // change session ID for the current session and invalidate old session ID
				$_SESSION['CREATED'] = time();  // update creation time
			}
			*/
			
			$this->id = $this->getSessionId();
			$_SESSION['session_id'] = $this->id;
		}
		
		/**
		 * Get the session ID
		 * @return string
		 */
		
		public function getSessionId() {
			return session_id();
		}
		
		/**
		 * Set a success message to be carried forward to the next page generation
		 * @since Version 3.8.7
		 * @var string $message
		 * @return $this
		 */
		
		public function success($message = false) {
			if (!isset($_SESSION['message'])) {
				$_SESSION['message'] = array();
			}
			
			if (!empty($message)) {
				if (!in_array($message, $_SESSION['message'])) {
					$_SESSION['message'][] = sprintf("<strong>Success!</strong> %s", $message);
				}
			}
			
			return $this;
		}
		
		/**
		 * Set an error message to be carried forward to the next page generation
		 * @since Version 3.8.7
		 * @var string $message
		 * @return $this
		 */
		
		public function error($message = false) {
			if (!isset($_SESSION['error'])) {
				$_SESSION['error'] = array();
			}
			
			if (!empty($message)) {
				if (!in_array($message, $_SESSION['error'])) {
					$_SESSION['error'][] = sprintf("<strong>Whoops!</strong> %s", $message);
				}
			}
			
			return $this;
		}
		
		/**
		 * Set an informational message to be carried forward to the next page generatio
		 * @since Version 3.8.7
		 * @var string $message
		 * @return $this
		 */
		
		public function message($message = false) {
			if (!isset($_SESSION['infomessage'])) {
				$_SESSION['infomessage'] = array();
			}
			
			if (!empty($message)) {
				if (!in_array($message, $_SESSION['infomessage'])) {
					$_SESSION['infomessage'][] = $message;
				}
			}
			
			return $this;
		}
		
		/**
		 * PHP SessionHandlerInterface::open
		 * @param string $save_path
		 * @param string $name
		 * @return boolean
		 */
		
		public function open($save_path, $name) {
			return true;
		}
		
		/**
		 * PHP SessionHandlerInterface::close
		 * @return boolean
		 */
		
		public function close() {
			return true;
		}
		
		/**
		 * PHP SessionHandlerInterface::read
		 * @param string $session_id 
		 * @return string
		 */
		
		public function read($session_id) {
			#echo (sprintf("Reading data for session ID railpage:session=%d", $session_id));
			return $this->Memcached->get(sprintf("railpage:session=%d", $session_id));
		}
		
		/**
		 * PHP SessionHandlerInterface::write
		 * @param string $session_id
		 * @param string $session_data
		 * @return boolean
		 */
		
		public function write($session_id, $session_data) {
			#echo (sprintf("Writing data for session ID railpage:session=%d", $session_id));

			return $this->Memcached->put(sprintf("railpage:session=%d", $session_id), $session_data, strtotime("+2 hours"));
		}
		
		/** 
		 * PHP SessionHandlerInterface::destroy
		 * @param string $session_id
		 * @return boolean
		 */
		
		public function destroy($session_id) {
			return $this->Memcached->delete(sprintf("railpage:session=%d", $session_id));
		}
		
		/**
		 * PHP SessionHandlerInterface::gc
		 * @param string $maxlifetime
		 * @return boolean
		 */
		
		public function gc($maxlifetime) {
			return true;
		}
	}
	