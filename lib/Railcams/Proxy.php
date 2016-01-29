<?php
    
    /**
     * Proxy a railcam, to reduce load on the MW links
     * @since Version 3.10.0
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Railcams;
    
    use Railpage\AppCore;
    use Railpage\Debug;
    use Railpage\Url;
    use Exception;
    use DateTime;
    use InvalidArgumentException;
    
    class Proxy {
        
        /**
         * Memcached object lifetime
         * @since Version 3.10.0
         * @const int CACHE_LIFETIME Lifetime of cached information (eg pictures), in seconds
         */
        
        const CACHE_LIFETIME = 5;
        
        /**
         * The railcam we want to use 
         * @since Version 3.10.0
         * @var \Railpage\Railcams\Camera
         */
        
        private $Railcam;
        
        /**
         * Memcached handle
         * @since Version 3.10.0
         * @var $Memcached
         */
        
        private $Memcached;
        
        /**
         * Memcached key basename
         * @since Version 3.10.0
         * @var $cachekey_base
         */
        
        private $cachekey_base;
        
        /**
         * Constructor
         * @since Version 3.10.0
         */
        
        public function __construct() {
            
            $this->Memcached = AppCore::getMemcached(); 
            
        }
        
        /**
         * Set the Railcam object
         * @since Version 3.10.0
         * @param \Railpage\Railcams\Camera $Camera
         * @return \Railpage\Railcams\Proxy
         */
        
        public function setRailcam(Camera $Camera) {
            
            $this->Railcam = $Camera;
            
            $this->cachekey_base = sprintf("railpage:railcam=%d;proxy", $Camera->id);
            
            return $this;
            
        }
        
        /**
         * Fetch data from this camera
         * @since Version 3.10.0
         * @param string $action
         * @param array $data
         */
        
        public function execute($action, $data) {
            
            
            
        }
        
    }