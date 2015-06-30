<?php
	/**
	 * Factory code pattern - return an instance of blah from the registry, Redis, Memcached, etc...
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Organisations;
	
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
		 * @return \Railpage\Organisations\Organisation
		 * @param int|string $id
		 */
		
		public static function CreateOrganisation($id = false) {
			
			$Memcached = AppCore::getMemcached(); 
			$Redis = AppCore::getRedis(); 
			$Registry = Registry::getInstance(); 
			
			if (!filter_var($id, FILTER_VALIDATE_INT)) {
				$slugkey = sprintf(Organisation::REGISTRY_KEY, $id); 
				
				try {
					$id = $Registry->get($slugkey);
				} catch (Exception $e) {
					$Database = (new AppCore)->getDatabaseConnection(); 
					$id = $Database->fetchOne("SELECT organisation_id FROM organisation WHERE organisation_slug = ?", $id); 
					
					$Registry->set($slugkey, $id);
				}
			}
			
			$regkey = sprintf(Organisation::REGISTRY_KEY, $id); 
			
			try {
				$Organisation = $Registry->get($regkey); 
			} catch (Exception $e) {
				$cachekey = sprintf(Organisation::CACHE_KEY, $id); 
				
				if (!self::USE_REDIS || !$Organisation = $Redis->fetch($cachekey)) {
					$Organisation = new Organisation($id); 
					
					if (self::USE_REDIS) {
						$Redis->save($cachekey, $Organisation); 
					}
				}
				
				$Registry->set($regkey, $Organisation); 
			} 
				
			return $Organisation; 
			
		}
	}