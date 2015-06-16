<?php
	/**
	 * Factory code pattern - return an instance of blah from the registry, Redis, Memcached, etc...
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos;
	
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
		
		public static function CreateLocoClass($id = false) {
			
			$Memcached = AppCore::getMemcached(); 
			$Redis = AppCore::getRedis(); 
			$Registry = Registry::getInstance(); 
			
			if (!filter_var($id, FILTER_VALIDATE_INT)) {
				$id = Utility\LocomotiveUtility::getClassId($id); 
			}
			
			if ($id = filter_var($id, FILTER_VALIDATE_INT)) {
				$regkey = sprintf(LocoClass::REGISTRY_KEY, $id); 
				
				try {
					$LocoClass = $Registry->get($regkey); 
				} catch (Exception $e) {
					$cachekey = sprintf(LocoClass::CACHE_KEY, $id); 
					
					#if (self::USE_REDIS && !$LocoClass = $Redis->fetch($cachekey)) {
						$LocoClass = new LocoClass($id); 
						
						if (self::USE_REDIS) {
							$Redis->save($cachekey, $LocoClass);
						}
					#}
					
					$Registry->set($regkey, $LocoClass); 
				} 
					
				return $LocoClass; 
			}
			
			return false;
			
		}
		
		/**
		 * Return a locomotive
		 * @since Version 3.9.1
		 * @return \Railpage\Locos\Locomotive
		 * @param int $id
		 * @param string $class
		 * @param string $number
		 */
		
		public static function CreateLocomotive($id = false, $class = false, $number = false) {
			
			$Memcached = AppCore::getMemcached(); 
			$Redis = AppCore::getRedis(); 
			$Registry = Registry::getInstance(); 
			
			if (!filter_var($id, FILTER_VALIDATE_INT)) {
				$id = Utility\LocomotiveUtility::getLocoId($class, $number); 
			}
			
			if ($id = filter_var($id, FILTER_VALIDATE_INT)) {
				$regkey = sprintf(Locomotive::REGISTRY_KEY, $id); 
				
				try {
					$Loco = $Registry->get($regkey); 
				} catch (Exception $e) {
					$cachekey = sprintf(Locomotive::CACHE_KEY, $id); 
					
					#if (!self::USE_REDIS || !$Loco = $Redis->fetch($cachekey)) {
						$Loco = new Locomotive($id); 
						
						if (self::USE_REDIS) {
							$Redis->save($cachekey, $Loco);
						}
					#}
					
					$Registry->set($regkey, $Loco); 
				} 
					
				return $Loco; 
			}
			
			return false;
			
		}
		
	}