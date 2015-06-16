<?php
	/**
	 * Railpage AppCore
	 * @since Version 3.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage; 
	
	use stdClass;
	use Exception;
	use Memcached as MemcachedBlahBlah;
	use Redis;
	use Railpage\Users\User;
	use Foolz\SphinxQL\SphinxQL;
	use Foolz\SphinxQL\Connection;
	use SphinxClient;
	use NilPortugues\Sphinx\SphinxClient as NilPortuguesSphinxClient;
	
	use Monolog\Logger;
	use Monolog\Handler\SwiftMailerHandler;
	use Monolog\Handler\PushoverHandler;
	use Monolog\Formatter\HtmlFormatter;
	use Swift_Message;
	use Swift_Mailer;
	use Swift_SmtpTransport;
	use Swift_Encoding;
	use Swift_Plugins_AntiFloodPlugin;
	use Swift_Plugins_DecoratorPlugin;
	
	use Doctrine\Common\Cache\MemcachedCache;
	use Doctrine\Common\Cache\RedisCache;
	
	
	if (!defined("RP_SITE_ROOT")) {
		define("RP_SITE_ROOT", "");
	}
	
	if (!defined("RP_WEB_ROOT")) {
		define("RP_WEB_ROOT", "");
	}
	
	if (!defined("RP_HOST")) {
		define("RP_HOST", "www.railpage.com.au");
	}
	
	if (!defined("DS")) {
		define("DS", DIRECTORY_SEPARATOR);
	}
	
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
		 * User object
		 * @since Version 3.9
		 * @var \Railpage\Users\User $User
		 */
		
		public $User;
		
		/**
		 * Staff object
		 * @since Version 3.9
		 * @var \Railpage\Users\User $Staff
		 */
		
		public $Staff;
		
		/**
		 * Author object
		 * @since Version 3.9
		 * @var \Railpage\Users\User $Author
		 */
		
		public $Author;
		
		/**
		 * Constructor
		 * @since Version 3.7
		 * @global object $ZendDB
		 * @global object $ZendDB_ReadOnly
		 */
		
		public function __construct() {
			global $ZendDB, $ZendDB_ReadOnly, $PHPUnitTest;
			
			/**
			 * Create the registry
			 */
			
			$Registry = Registry::getInstance();
			
			$this->log = self::getLogger();
			
			if (isset($PHPUnitTest) && $PHPUnitTest === true) {
				
				try {
					$this->db = $Registry->get("db_phpunit");
				} catch (Exception $e) {
					require("db.dist" . DS . "zend_db.php"); 
					$this->db = $ZendDB;
					$this->destroy = true;
					
					$Registry->set("db_phpunit", $this->db);
				}
				
				$ZendDB_ReadOnly = $this->db;
				
			} else {
				
				/**
				 * Load / set the database instance
				 */
				
				try {
					$this->db = $Registry->get("db");
				} catch (Exception $e) {
					
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
						
						// Attempt to resolve the DB connection to a stream path. If it can't be resolved, assume this is a CI environment and load a test DB connector
						
						if (stream_resolve_include_path("db" . DS . "connect.php")) {
							require("db" . DS . "connect.php"); 
							throw new Exception(__CLASS__." needs a database object");
							$this->db = $db;
							$this->destroy = true;
						}
					}
					
					$Registry->set("db", $this->db);
				}
				
				/**
				 * Load / set the read-only database instance
				 */
				
				try {
					$this->db_readonly = $Registry->get("db_readonly");
				} catch (Exception $e) {
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
					
					$Registry->set("db_readonly", $this->db_readonly);
				}
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
			
			try {
				$this->StatsD = $Registry->get("statsd"); 
			} catch (Exception $e) {
				$this->StatsD = new stdClass;
				$this->StatsD->target = new stdClass;
				$Registry->set("statsd", $this->StatsD);
			}
			
			/**
			 * Load the config
			 */
			
			$this->Config = self::getConfig();
			
			/**
			 * Load / set the Memcached object
			 */
			
			$this->Memcached = self::getMemcached();
			
			/**
			 * Load / set the Redis object
			 */
			
			$this->Redis = self::getRedis();
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
					#$this->closeConnection(); 
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
		 * @return $this
		 */
		
		public function setUser(User $User) {
			
			$this->User = $User;
			
			return $this;
			
		}
		
		/**
		 * Set the author for this object
		 * @since Version 3.8.7
		 * @param \Railpage\Users\User $User
		 * @return $this
		 */
		
		public function setAuthor(User $User) {
			
			$this->Author = $User;
			
			return $this;
			
		}
		
		/**
		 * Set the staff user object for this object
		 * @since Version 3.8.7
		 * @param \Railpage\Users\User $User
		 * @return $this
		 */
		
		public function setStaff(User $User) {
			
			$this->Staff = $User;
			
			return $this;
			
		}
		
		/**
		 * Set the object string for this object
		 * @since Version 3.8.7
		 * @param object $object
		 * @return $this
		 */
		
		public function setObject($object) {
			
			if (is_object($object)) {
				$this->object = get_class($object);
			}
			
			return $this;
			
		}
		
		/**
		 * Set the guest user for this object
		 * @since Version 3.8.7
		 * @param \Railpage\Users\User $Guest
		 * @return $this
		 */
		
		public function setGuest(User $Guest) {
			
			$this->Guest = $Guest;
			
			return $this;
			
		}
		
		/**
		 * Set the recipient for this object
		 * @since Version 3.8.7
		 * @param \Railpage\Users\User $Recipient
		 * @return $this
		 */
		
		public function setRecipient(User $Recipient) {
			
			$this->Recipient = $Recipient;
			
			return $this;
			
		}
		
		/**
		 * Create and connect to SphinxQL instance
		 * @since Version 3.9.0
		 * @return \Foolz\SphinxQL\SphinxQL
		 */
		
		public static function getSphinx() {
			
			$Config = self::getConfig(); 
			
			$conn = new Connection();
			$conn->setParams(array("host" => $Config->Sphinx->Host, "port" => $Config->Sphinx->Port));
			
			return SphinxQL::create($conn);
			
		}
		
		/**
		 * Create and connect to the Sphinx API
		 * @since Version 3.9.1
		 * @return \NilPortugues\Sphinx\SphinxClient
		 */
		
		public static function getSphinxAPI() {
			
			#require_once(RP_SITE_ROOT . DS . "vendor" . DS . "nilportugues" . DS . "sphinx-search" . DS . "src" . DS . "NilPortugues" . DS . "Sphinx" . DS . "SphinxClient.php"); 
			
			$Config = self::getConfig(); 
			
			if (class_exists("SphinxClient")) {
				$Sphinx = new SphinxClient;
			} else {
				$Sphinx = new NilPortuguesSphinxClient;
			}
			
			$Sphinx->setServer($Config->Sphinx->Host, 9306);
			$Sphinx->setMatchMode(SPH_MATCH_ALL);
			$Sphinx->setMaxQueryTime(300);
			
			return $Sphinx;
			
		}
		
		/**
		 * Get RP configuration
		 * @since Version 3.9.1
		 * @return \stdClass
		 */
		
		public static function getConfig() {
			$Registry = Registry::getInstance();
			
			try {
				$Config = $Registry->get("config");
			} catch (Exception $e) {
				if (function_exists("getRailpageConfig")) {
					$Config = getRailpageConfig();
				} elseif (defined("RP_SITE_ROOT") && file_exists(RP_SITE_ROOT . DS . "config" . DS . "config.railpage.json")) {
					$Config = json_decode(file_get_contents(RP_SITE_ROOT . DS . "config" . DS . "config.railpage.json")); 
				} elseif (file_exists(dirname(__DIR__) . DS . "config.railpage.json")) {
					$Config = json_decode(file_get_contents(dirname(__DIR__) . DS . "config.railpage.json"));
				} elseif (file_exists(dirname(__DIR__) . DS . "config" . DS . "dist.config.railpage.php")) {
					require_once(dirname(__DIR__) . DS . "config" . DS . "dist.config.railpage.php");
					$Config = $RailpageConfig;
				}
				
				if (!isset($Config)) {
					$Config = array(
						"Memcached" => array(
							"Host" => "127.0.0.1",
							"Port" => 11211
						)
					);
					
					$Config = json_decode(json_encode($Config));
				}
				
				$Registry->set("config", $Config);
			}
			
			return $Config;
		}
		 
		
		/**
		 * Get our Redist instance
		 * @since Version 3.9.1
		 * @return \Doctrine\Common\Cache\RedisCache
		 */
		
		public static function getRedis() {
			if (!extension_loaded("redis") || (defined("NOREDIS") && NOREDIS == true)) {
				return new NullCacheDriver;
			}
			
			$Registry = Registry::getInstance();
			
			$Config = self::getConfig();
			
			try {
				$cacheDriver = $Registry->get("redis");
			} catch (Exception $e) {
				$Redis = new Redis;
				$Redis->connect($Config->Memcached->Host, 6379);
				
				$cacheDriver = new RedisCache;
				$cacheDriver->setRedis($Redis);
				
				$Registry->set("redis", $cacheDriver);
			}
			
			return $cacheDriver;
		}
		
		/**
		 * Get our Memcached instance
		 * @since Version 3.9.1
		 * @return \Doctrine\Common\Cache\MemcachedCache
		 */
		
		public static function getMemcached() {
			if (!extension_loaded("memcached")) {
				return new NullCacheDriver;
			}
			
			$Registry = Registry::getInstance();
			
			$Config = self::getConfig();
			
			try {
				$cacheDriver = $Registry->get("memcached");
			} catch (Exception $e) {
				$Memcached = new MemcachedBlahBlah;
				$Memcached->addServer($Config->Memcached->Host, 11211);
				
				$cacheDriver = new MemcachedCache;
				$cacheDriver->setMemcached($Memcached);
				
				$Registry->set("memcached", $cacheDriver);
			}
			
			return $cacheDriver;
		}
		
		/**
		 * Create a URL slug if the create_slug() function doesn't exist
		 * @since Version 3.9.1
		 * @param string $url
		 * @return string
		 */
		
		public static function create_slug($string) {
			
			if (function_exists("create_slug")) {
				return create_slug($string); 
			}
			
			$find = array(
				"(",
				")",
				"-"
			);
			
			$replace = array(); 
			
			foreach ($find as $item) {
				$replace[] = "";
			}
			
			$string = str_replace($find, $replace, $string);
				
			$slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', trim($string)));
			return $slug;
		}
		
		/**
		 * Set the database connection for this object
		 * @since Version 3.9.1
		 * @return $this
		 */
		
		public function setDatabaseConnection($cn = false) {
			if (!is_object($cn)) {
				throw new Exception("Invalid database connection specified");
			}
			
			$this->db = $cn;
			
			return $this;
		}
		
		/**
		 * Set the read-only database connection for this object
		 * @since Version 3.9.1
		 * @return $this
		 */
		
		public function setDatabaseReadOnlyConnection($cn = false) {
			if (!is_object($cn)) {
				throw new Exception("Invalid database connection specified");
			}
			
			$this->db_readonly = $cn;
			
			return $this;
		}
		
		/**
		 * Get the logger interface
		 * @since Version 3.9.1
		 * @return \Monolog\Logger
		 */
		
		public static function getLogger() {
			
			$Registry = Registry::getInstance();
			
			$Config = self::getConfig();
			
			try {
				$Log = $Registry->get("log");
			} catch (Exception $e) {
				$Log = new Logger("railpage");
				
				/**
				 * Shit's broke, send an email via SwiftMail
				 */
				
				if (isset($Config->SMTP)) {
					$transport = Swift_SmtpTransport::newInstance($Config->SMTP->host, $Config->SMTP->port, $Config->SMTP->TLS = true ? "tls" : NULL)
						->setUsername($Config->SMTP->username)
						->setPassword($Config->SMTP->password);
						
					$mailer = Swift_Mailer::newInstance($transport);
					$mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin(100, 30));
					
					$message = Swift_Message::newInstance("[RP] Something went wrong!")
						->setFrom(array("errorz@railpage.com.au" => "Failpage service"))
						->setTo(array("michael@railpage.com.au" => "Michael Greenhill"))
						->setBody("", "text/html");
					
					$htmlFormatter = new HtmlFormatter;
					
					$mailStream = new SwiftMailerHandler($mailer, $message, Logger::ERROR);
					$mailStream->setFormatter($htmlFormatter);
					
					$Log->pushHandler($mailStream);
				}
				
				/**
				 * Pushover
				 */
				
				#$Log->pushHandler(new PushoverHandler("aVHTDYcWLH7y8ZXoDNnaHjAuH7gnY5", "uWy9phgETHeYLqTZBL5YSVjoqB93id", "Railpage API"));
				
				$Registry->set("log", $Log);
			}
			
			return $Log;
		}
		
		/**
		 * Get alert-to-email object
		 * @since Version 3.9.1
		 * @return \Monolog\Logger
		 */
		
		public static function getAlerter() {
			
			$Registry = Registry::getInstance();
			
			$Config = self::getConfig();
			
			try {
				$Monolog = $Registry->get("alerter"); 
			} catch (Exception $e) {
				$Monolog = new Logger("railpage"); 
				#$Monolog->pushHandler(new SwiftMailerHandler(
			}
			
			return $Monolog;
		}
		
		/**
		 * Get our smarty template engine
		 * @since Version 3.9.1
		 * @return \Railpage\Template
		 */
		
		public static function getSmarty() {
			
			$Registry = Registry::getInstance(); 
			
			$Config = self::getConfig(); 
			
			try {
				$Smarty = $Registry->get("smarty"); 
			} catch (Exception $e) {
				$Smarty = new Template;
				
				/**
				 * Send the RP config to Smarty
				 */
				
				$Smarty->setRailpageConfig($Config);
				
				/**
				 * Smarty options and configuration
				 */
				
				$Smarty->debugging 		= false;
				$Smarty->caching 		= false;
				$Smarty->cache_lifetime = 120;
				$Smarty->site_root 		= RP_SITE_ROOT; 
				$Smarty->use_sub_dirs	= true;
				$Smarty->assign("rp_host", RP_HOST);
				$Smarty->assign("rand", rand());
				$Smarty->assign("rp_version", defined("RP_VERSION") ? RP_VERSION : "0.0.0");
				$Smarty->assign("rp_web_root", defined("RP_WEB_ROOT") ? RP_WEB_ROOT : __DIR__);
				$Smarty->assign("site_name", $Config->SiteName);
				$Smarty->assign("rp_production", (defined("RP_ISPRODUCTION") ? "RP_ISPRODUCTION" : define("RP_ISPRODUCTION", true)) ? true : false);
				$Smarty->assign("ad_header", $Config->AdHeader);
				$Smarty->assign("rp_map_styles", json_encode($Config->Google->Maps->Styles));
				$Smarty->assign("rp_map_icons", json_encode($Config->Google->Maps->Icons));
				$Smarty->subtheme = "smooth";
				
				$Smarty->Assign("rp_ui_show_banner_head", true);
				
				/**
				 * Set the template compile directory
				 */
				
				$Smarty->setCompileDir(dirname(RP_SITE_ROOT) . DS . "smarty" . DS . "compile");
				
				$Registry->set("smarty", $Smarty);
				
			}
			
			return $Smarty;
		}
		
		/**
		 * Return the database connection
		 * @since Version 3.9.1
		 */
		
		public function getDatabaseConnection() {
			return $this->db;
		}
	}
	