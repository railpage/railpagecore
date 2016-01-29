<?php
    /**
     * Transport for SA AdelaideMetro GTFS interface
     * @since Version 3.8.7
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\GTFS\AU\SA\AdelaideMetro;
    
    use Exception;
    use DateTime;
    use Zend\Http\Client;
    use Zend\Db\Sql\Sql;
    use Zend\Db\Sql\Select;
    use Zend\Db\Adapter\Adapter;
    use Railpage\GTFS\GTFSInterface;
    use Railpage\GTFS\StandardProvider;
    use Railpage\Url;
    
    /**
     * AdelaideMetro class
     */
    
    class AdelaideMetro extends StandardProvider {
        
        /**
         * Timetable data source
         * @var string $provider
         */
        
        public $provider = "AdelaideMetro";
        
        /**
         * Timetable data source as a constant
         * @const string PROVIDER_NAME
         * @since Version 3.9
         */
        
        const PROVIDER_NAME = "AdelaideMetro";
        
        /**
         * Continent of origin
         * @since Version 3.9
         * @const string PROVIDER_CONTINENT
         */
        
        const PROVIDER_CONTINENT = "Oceana";
        
        /**
         * Country of origin
         * @since Version 3.9
         * @const string PROVIDER_COUNTRY
         */
        
        const PROVIDER_COUNTRY = "Australia";
        
        /**
         * Country of origin
         * @since Version 3.9
         * @const string PROVIDER_COUNTRY_SHORT
         */
        
        const PROVIDER_COUNTRY_SHORT = "AU";
        
        /**
         * State or region of origin
         * @since Version 3.9
         * @const string PROVIDER_REGION
         */
        
        const PROVIDER_REGION = "South Australia";
        
        /**
         * State or region of origin
         * @since Version 3.9
         * @const string PROVIDER_REGION_SHORT
         */
        
        const PROVIDER_REGION_SHORT = "SA";
        
        /**
         * Database table prefix
         * @since Version 3.9
         * @const string DB_PREFIX
         */
        
        const DB_PREFIX = "au_sa_adelaidemetro";
        
        /**
         * List of allowed route names
         * @since Version 3.9
         * @var array $allowed_routes
         */
        
        private $allowed_routes = array(
            "BEL", 
            "GAW",
            "GAWC",
            "GRNG",
            "OSBORN",
            "OUTHA",
            "SALIS",
            "SEAFRD",
            "TONS"
        );
        
        /**
         * Get a train object
         * @since Version 3.9
         * @return object
         */
        
        public function getRoute($id = false) {
            return new Route($id, $this);
        }
    }
    