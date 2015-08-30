<?php
	/**
	 * Location utility class
	 * @since Version 3.10.0
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locations\Utility;
	
	use Railpage\Debug;
	use Railpage\AppCore;
	use Railpage\Url;
	use Railpage\Registry;
	use Exception;
	
	class LocationUtility {
		
		/**
		 * Get a location ID from a URL slug
		 * @since Version 3.10.0
		 * @param string $slug
		 * @return int
		 */
		
		public static function getLocationId($slug) {
			
			$Redis = AppCore::GetRedis();
			$Memcached = AppCore::GetMemcached(); 
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$key = sprintf("railpage:locations.slug=%s", $slug); 
			
			if (!$id = $Memcached->fetch($key)) {
				$id = $Database->fetchOne("SELECT id FROM location WHERE slug = ?", $slug); 
				$Memcached->save($key, $id, 0); 
			}
			
			return $id;
			
		}
		
	}