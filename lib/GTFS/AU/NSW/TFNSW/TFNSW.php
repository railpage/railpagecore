<?php
	/**
	 * Transport for New South Wales GTFS interface
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\GTFS\AU\NSW\TFNSW;
	
	use Exception;
	use DateTime;
	use Zend\Http\Client;
	use Zend\Db\Sql\Sql;
	use Zend\Db\Sql\Select;
	use Zend\Db\Adapter\Adapter;
	use Railpage\GTFS\GTFSInterface;
	use Railpage\GTFS\StandardProvider;
	
	/**
	 * TFNSW provider class for GTFS
	 */
	
	class TFNSW extends StandardProvider {
		
		/**
		 * Timetable data source
		 * @var string $provider The name of this GTFS data provider
		 */
		
		public $provider = "TFNSW";
		
		/**
		 * Timetable data source as a constant
		 * @const string PROVIDER_NAME
		 * @since Version 3.9
		 */
		
		const PROVIDER_NAME = "TFNSW";
		
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
		
		const PROVIDER_REGION = "New South Wales";
		
		/**
		 * State or region of origin
		 * @since Version 3.9
		 * @const string PROVIDER_REGION_SHORT
		 */
		
		const PROVIDER_REGION_SHORT = "NSW";
		
		/**
		 * Database table prefix
		 * @since Version 3.9
		 * @const string DB_PREFIX
		 */
		
		const DB_PREFIX = "au_syd";
		
		/**
		 * Get a train object
		 * @since Version 3.9
		 * @return object
		 */
		
		public function getRoute($id = false) {
			return new Route($id, $this);
		}
	}
?>