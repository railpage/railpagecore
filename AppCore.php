<?php
	/**
	 * Railpage AppCore
	 * @since Version 3.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage; 
	
	use stdClass;
	use Railpage\Users\User;
	
	/**
	 * App Core
	 */
	
	class AppCore {
		
		/**
		 * Database object
		 * @var object $db An instance of ZendDB or sql_db representing a database connection. sql_db is being phased out and replaced with ZendDB v1
		 * @since Version 3.7
		 */
		
		protected $db;
		
		/**
		 * Memcache object
		 * @since Version 3.7.5
		 * @var object $memcache A memcache object providing get/set/update helpers. This is being phased out in favour of getMemcacheObject(), setMemcacheObject() and deleteMemcacheObject()
		 */
		
		public $memcache;
		
		/**
		 * Use cache or not
		 * @since Version 3.7.5
		 * @var boolean $useCache A simple yes/no for fetching objects from Memcache. Not really used
		 */
		 
		public $useCache = true;
		
		/**
		 * Destroy
		 * @since Version 3.7.5
		 * @var boolean $destory
		 */
		
		public $destroy = false;
		
		/**
		 * Short url / shortcut / alias to this object
		 * @since Version 3.8.6
		 * @var string $fwlink
		 */
		
		public $fwlink;
		
		/**
		 * Image object
		 * 
		 * Surpases $this->photo_id and $this->Asset
		 * @since Version 3.8.7
		 * @var \Railpage\Images\Image $Image An instance of \Railpage\Images\Image representing the cover photo or dominant image of this object
		 */
		
		public $Image;
		
		/**
		 * Namespace
		 * @since Verson 3.8.7
		 * @var string $namespace The namespace of the class/object, used to standardise memcache or Image data keys
		 */
		
		public $namespace;
		
		/**
		 * Module information
		 * @since Version 3.8.7
		 * @var object $Module
		 */
		
		public $Module;
		
		/**
		 * StatsD object
		 * @since Version 3.8.7
		 * @var \stdClass $StatsD
		 */
		
		public $StatsD;
		
		/**
		 * Constructor
		 * @since Version 3.7
		 * @global object $ZendDB
		 * @global object $ZendDB_ReadOnly
		 */
		
		public function __construct() {
			global $ZendDB, $ZendDB_ReadOnly; 
			
			if (isset($ZendDB)) {
				$this->db = $ZendDB; 
			} elseif (file_exists("db" . DS . "zend_db.php")) {
				require("db" . DS . "zend_db.php"); 
				$this->db = $ZendDB;
				$this->destroy = true;
			} elseif (file_exists(".." . DS . "db" . DS . "zend_db.php")) {
				require(".." . DS . "db" . DS . "zend_db.php"); 
				$this->db = $ZendDB;
				$this->destroy = true;
			} else {
				require("db" . DS . "connect.php"); 
				throw new \Exception(__CLASS__." needs a database object");
				$this->db = $db;
				$this->destroy = true;
			}
			
			if (isset($ZendDB_ReadOnly)) {
				$this->db_readonly = $ZendDB_ReadOnly; 
			} elseif (file_exists("db" . DS . "zend_db.php")) {
				require("db" . DS . "zend_db.php"); 
				$this->db_readonly = $ZendDB_ReadOnly;
				$this->destroy = true;
			} elseif (file_exists(".." . DS . "db" . DS . "zend_db.php")) {
				require(".." . DS . "db" . DS . "zend_db.php"); 
				$this->db_readonly = $ZendDB_ReadOnly;
				$this->destroy = true;
			}
			
			/** 
			 * Create Memcache object
			 */
			
			if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . "memcache.php")) {
				require(__DIR__ . DIRECTORY_SEPARATOR . "memcache.php"); 
				
				$this->memcache = $memcache;
			}
			
			/**
			 * Build the StatsD object
			 */
			
			$this->StatsD = new stdClass;
			$this->StatsD->target = new stdClass;
		}
		
		/**
		 * Deconstructor
		 *
		 * Close connections to the database
		 * @since Version 3.7
		 */
		
		protected function __deconstruct() {
			if ($this->destroy) {
				if ($this->db instanceof \sql_db) {
					$this->db->close(); 
				} else {
					$this->closeConnection(); 
				}
			}
		}
		
		/**
		 * Cache an object in Memcache
		 * @deprecated 3.8.7 Calls to this method should be replaced with setMemcacheObject() with the same parameter(s)
		 * @since Version 3.7.5
		 * @param string $key Memcache's unique identifier for this data
		 * @param mixed $value The data we wish to store in Memcache
		 * @param int $exp A unix timestamp representing the expiry point. Leave as 0 for never expire
		 * @return boolean
		 */
		
		protected function setCache($key = false, $value = "thisisanemptyvalue", $exp = 0) {
			if (!$this->useCache) {
				return false;
			}
			
			return setMemcacheObject($key, $value, $exp); 
		}
		
		/**
		 * Fetch an object from Memcache
		 * @deprecated 3.8.7 Calls to this method should be replaced with getMemcacheObject() with the same parameter(s)
		 * @since Version 3.7.5
		 * @param string $key
		 * @return mixed
		 */
		
		protected function getCache($key = false) {
			if (!$this->useCache) {
				return false;
			}
			
			return getMemcacheObject($key);
		}
		
		/**
		 * Remove an object from Memcache
		 * @deprecated 3.8.7 Calls to this method should be replaced with deleteMemcacheObject() with the same parameter(s)
		 * @since Version 3.7.5
		 * @param string $key
		 * @return mixed
		 */
		
		protected function removeCache($key = false) {
			if (!$this->useCache) {
				return false;
			}
			
			return removeMemcacheObject($key);
		}
		
		/**
		 * Remove an object from Memcache
		 * @deprecated 3.8.7 Calls to this method should be replaced with deleteMemcacheObject() with the same parameter(s)
		 * @since Version 3.7.5
		 * @param string $key
		 * @return mixed
		 */
		
		protected function deleteCache($key = false) {
			if (!$this->useCache) {
				return false;
			}
			
			return removeMemcacheObject($key); 
		}
		
		/**
		 * Set the user for this object
		 * @since Version 3.8.7
		 * @param \Railpage\Users\User $User
		 * @returns $this
		 */
		
		public function setUser(User $User) {
			
			$this->User = $User;
			
			return $this;
			
		}
		
		/**
		 * Set the object string for this object
		 * @since Version 3.8.7
		 * @param object $object
		 * @returns $this
		 */
		
		public function setObject($object) {
			
			if (is_object($object)) {
				$this->object = get_class($object);
			}
			
			return $this;
			
		}
	}
?>