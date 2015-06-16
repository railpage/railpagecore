<?php
	/**
	 * A single-instance storage instance using PHP's Registry pattern for shared resources across classes
	 * See http://avedo.net/101/the-registry-pattern-and-php/
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage;
	
	use Exception;
	
	class Registry {
		
		/**
		 * Array of items stored in the registry
		 * @since Version 3.9.1
		 * @var array
		 */
		
		private $registry = array();
		
		/**
		 * An instance of this object
		 * @since Version 3.9.1
		 */
		
		private static $instance = null;
		
		/**
		 * Return an instance of the registry
		 * @since Version 3.9.1
		 * @return \Railpage\Registry
		 */
		
		public static function getInstance() {
			if (self::$instance === null) {
				self::$instance = new Registry;
			}
			
			return self::$instance;
		}
		
		/**
		 * Set an object in the registry
		 * @since Version 3.9.1
		 * @param string $key Name of the object to set
		 * @param mixed $value The object to set
		 * @return \Railpage\Registry;
		 */
		
		public function set($key, $value) {
			#if (isset($this->registry[strtolower($key)])) {
			#	throw new Exception(sprintf("There is already an entry for %s in the registry", $key));
			#}
			
			$this->registry[strtolower($key)] = $value;
			
			return $this;
		}
		
		/**
		 * Get an object from the registry
		 * @since Version 3.9.1
		 * @param string $key Name of the object to retrieve
		 * @return mixed
		 */
		
		public function get($key) {
			if (!isset($this->registry[strtolower($key)])) {
				throw new Exception(sprintf("The requested key '%s' does not exist in the registry", $key)); 
			}
			
			return $this->registry[strtolower($key)];
		}
		
		/**
		 * Remove an object from the registry
		 * @since Version 3.9.1
		 * @param string $key Name of the object to remove
		 * @return \Railpage\Registry;
		 */
		
		public function remove($key) {
			if (isset($this->registry[strtolower($key)])) {
				unset($this->registry[strtolower($key)]);
			}
			
			return $this;
		}
		
		/**
		 * Private constructor
		 * @since Version 3.9.1
		 */
		
		private function __construct() {}
		
		/**
		 * Private cloner
		 * @since Version 3.9.1
		 */
		
		private function __clone() {}
	}
	