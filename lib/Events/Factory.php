<?php
	/**
	 * Factory code pattern - return an instance of blah from the registry, Redis, Memcached, etc...
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Events;
	
	use Railpage\Debug;
	use Railpage\AppCore;
	use Railpage\Url;
	use Railpage\Registry;
	use Exception;
	
	class Factory {
		
		/**
		 * Do we want to use Redis to cache some of these objects?
		 * @since Version 3.9.1
		 * @const boolean USE_REDIS
		 */
		
		const USE_REDIS = false;
		
		/**
		 * Return a locomotive class
		 * @since Version 3.9.1
		 * @return \Railpage\Locos\LocoClass
		 * @param int|string $id
		 */
		
		public static function CreateEvent($id = false) {
			
			$Memcached = AppCore::getMemcached(); 
			$Redis = AppCore::getRedis(); 
			$Registry = Registry::getInstance(); 
			
			if (!filter_var($id, FILTER_VALIDATE_INT)) {
				$Database = (new AppCore)->getDatabaseConnection(); 
				$id = $Database->fetchOne("SELECT id FROM event WHERE slug = ?", $id); 
			}
			
			$regkey = sprintf(Event::REGISTRY_KEY, $id); 
			
			$use_redis = false; // cannot seralize closure bullshit
			
			try {
				$Event = $Registry->get($regkey); 
			} catch (Exception $e) {
				$cachekey = sprintf(Event::CACHE_KEY, $id); 
				
				if (!$use_redis || !$Event = $Redis->fetch($cachekey)) {
					$Event = new Event($id); 
					
					if ($use_redis) {
						$Redis->save($cachekey, $Event); 
					}
				}
				
				$Registry->set($regkey, $Event); 
			} 
				
			return $Event; 
			
		}
	}