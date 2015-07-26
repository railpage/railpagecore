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
		 * Return an event
		 * @since Version 3.9.1
		 * @return \Railpage\Events\Event
		 * @param int|string $id
		 */
		
		public static function CreateEvent($id = false) {
			
			$Memcached = AppCore::getMemcached(); 
			$Redis = AppCore::getRedis(); 
			$Registry = Registry::getInstance(); 
			
			if (!filter_var($id, FILTER_VALIDATE_INT)) {
				$slugkey = sprintf(Event::REGISTRY_KEY, $id); 
				
				try {
					$id = $Registry->get($slugkey);
				} catch (Exception $e) {
					$Database = (new AppCore)->getDatabaseConnection(); 
					$id = $Database->fetchOne("SELECT id FROM event WHERE slug = ?", $id); 
					
					$Registry->set($slugkey, $id);
				}
			}
			
			$regkey = sprintf(Event::REGISTRY_KEY, $id); 
			
			try {
				$Event = $Registry->get($regkey); 
			} catch (Exception $e) {
				$cachekey = sprintf(Event::CACHE_KEY, $id); 
				
				if (!self::USE_REDIS || !$Event = $Redis->fetch($cachekey)) {
					$Event = new Event($id);
					
					if (self::USE_REDIS) {
						$Redis->save($cachekey, $Event); 
					}
				}
				
				$Registry->set($regkey, $Event); 
			}
				
			return $Event; 
			
		}
		
		/**
		 * Return an instance of EventCategory
		 * @since Version 3.9.1
		 * @return \Railpage\Events\EventCategory
		 * @param int|string $id
		 */
		
		public static function CreateEventCategory($id = false) {
			
			$Memcached = AppCore::getMemcached(); 
			$Redis = AppCore::getRedis(); 
			$Registry = Registry::getInstance(); 
			
			$regkey = sprintf(EventCategory::REGISTRY_KEY, $id); 
			
			try {
				$EventCategory = $Registry->get($regkey); 
			} catch (Exception $e) {
				$cachekey = sprintf(EventCategory::CACHE_KEY, $id); 
				
				if (!self::USE_REDIS || !$Event = $Redis->fetch($cachekey)) {
					$EventCategory = new EventCategory($id); 
					
					if (self::USE_REDIS) {
						$Redis->save($cachekey, $EventCategory); 
					}
				}
				
				$Registry->set($regkey, $EventCategory); 
			} 
				
			return $EventCategory; 

		}
	}