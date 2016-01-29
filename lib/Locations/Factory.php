<?php
    /**
     * Create an instance of location
     * @since Version 3.10.0
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Locations;
    
    use Railpage\Debug;
    use Railpage\AppCore;
    use Railpage\Url;
    use Railpage\Registry;
    use Exception;
    
    class Factory {
        
        /**
         * Create a location
         * @since Version 3.10.0
         * @param string|int $id
         * @return \Railpage\Locations\Location
         */
        
        public function CreateLocation($id) {
            
            $Memcached = AppCore::getMemcached(); 
            $Redis = AppCore::getRedis(); 
            $Registry = Registry::getInstance(); 
            
            /**
             * Get the ID
             */
            
            if (!filter_var($id, FILTER_VALIDATE_INT)) {
                $id = Utility\LocationUtility::getLocationId($id); 
            }
            
            /**
             * Load or create the object instance
             */
            
            if ($id = filter_var($id, FILTER_VALIDATE_INT)) {
                $regkey = sprintf(Location::REGISTRY_KEY, $id); 
                
                try {
                    $Location = $Registry->get($regkey); 
                } catch (Exception $e) {
                    $cachekey = sprintf(Location::CACHE_KEY, $id); 
                    
                    if (!self::USE_REDIS || !$Location = $Redis->fetch($cachekey)) {
                        $Location = new Location($id); 
                        
                        if (self::USE_REDIS) {
                            $Redis->save($cachekey, $Location);
                        }
                    }
                    
                    $Registry->set($regkey, $Location); 
                } 
                
                if (filter_var($Location->id, FILTER_VALIDATE_INT)) {
                    return $Location; 
                }
                
                throw new Exception(sprintf("Location id %s could not be found", $id));
            }
            
            throw new Exception("An invalid location ID was supplied");
            
        }
        
        /**
         * Create a region
         * @since Version 3.10.0
         * @param string $country
         * @param string $region
         * @return \Railpage\Locations\Region
         */
        
        public static function CreateRegion($country = NULL, $region = false) {
            
            $Memcached = AppCore::getMemcached(); 
            $Redis = AppCore::getRedis(); 
            $Registry = Registry::getInstance();
            
            $key = sprintf("railpage:country=%s;region=%s", $country, $region); 
            
            try {
                $Region = $Registry->get($key); 
            } catch (Exception $e) {
                
                if (!$Region = $Redis->fetch($key)) {
                    $Region = new Region($country, $region); 
                    $Redis->save($key, $Region, 0);
                }
                
                $Registry->set($key, $Region); 
            }
            
            return $Region;
            
        }
        
        /**
         * Create a country
         * @since Version 3.10.0
         * @param string $country
         * @param string $region
         * @return \Railpage\Locations\Region
         */
        
        public static function CreateCountry($code) {
            
            $Memcached = AppCore::getMemcached(); 
            $Redis = AppCore::getRedis(); 
            $Registry = Registry::getInstance();
            
            $key = sprintf(Country::CACHE_KEY, strtolower($code)); 
            
            try {
                
                $Country = $Registry->get($key); 
                
            } catch (Exception $e) {
                
                if ($Country = $Redis->fetch($key)) {
                    $Registry->set($key, $Country); 
                    return $Country;
                }
                
                $Country = new Country($code); 
                $Registry->set($key, $Country); 
                $Redis->save($key, $Country, 0);
                
            }
            
            return $Country;
            
        }
        
    }
    
    