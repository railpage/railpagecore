<?php
    /**
     * Transport for WA TransPerth GTFS interface
     * @since Version 3.9
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\GTFS\AU\VIC\PTV;
    
    use Exception;
    use DateTime;
    use Zend\Http\Client;
    use Zend\Db\Sql\Sql;
    use Zend\Db\Sql\Select;
    use Zend\Db\Adapter\Adapter;
    use Railpage\Url;

    /**
     * Routes class
     */
    
    class Route extends PTV {
        
        /**
         * Constructor
         * @since Version 3.9
         * @param int $route_id
         */
        
        public function __construct($route_id = false, $Provider = false) {
            
            parent::__construct(); 
            
            $routes = $this->getRoutes(); 
            
            $route = $routes[$route_id];
            
            $this->id = $route['id'];
            $this->route_id = $route['route_id'];
            $this->short_name = $route['route_short_name'];
            $this->long_name = $route['route_long_name'];
            $this->desc = $route['route_desc'];
            $this->type = 2;
            
        }
        
        
        
        /**
         * Fetch trips for this route
         * @since Version 3.9
         * @return array
         */
        
        public function getTrips() {
            
        }
        
        /**
         * Fetch stops for this route
         * @since Version 3.9
         * @return array
         */
        
        public function getStops() {
            
        }
        
        /**
         * Get path of this route
         * @since Version 3.9
         * @return array
         */
        
        public function getPath() {
            $params = array(
                "mode" => 0,
                "line" => $this->route_id
            );
            
            $stops = $this->fetch("stops-for-line", $params);
            
            printArray($this->url); 
            
            printArray($stops);
        }
    }
    