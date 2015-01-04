<?php
	/**
	 * Railpage session handler
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage;
	
	use Railpage\Memcached;
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
			
			if ($this->Memcached->connected()) {
				session_module_name('memcached');
				session_save_path(sprintf("%s:%d", $this->Memcached->host, $this->Memcached->port));
			}
			
			if (!defined("RP_SITE_DOMAIN")) {
				define("RP_SITE_DOMAIN", "railpage.com.au");
			}
			
			// Cross-subdomain cookies n shiz
			session_set_cookie_params(0, "/", sprintf(".%s", RP_SITE_DOMAIN)); 
			
			session_start();
			
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
?>