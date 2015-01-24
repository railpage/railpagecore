<?php
	/**
	 * Standard GTFS provider class
	 * @since Version 3.9
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\GTFS;
	
	use Exception;
	use DateTime;
	use Zend\Http\Client;
	use Zend\Db\Sql\Sql;
	use Zend\Db\Sql\Select;
	use Zend\Db\Adapter\Adapter;
	use Railpage\GTFS\GTFSInterface;
	use Railpage\Url;
	
	/**
	 * Standard GTFS provider class for GTFS
	 */
	
	class StandardProvider implements GTFSInterface {
		
		/**
		 * Route type: rail
		 * @since Version 3.9
		 * @const int ROUTE_RAIL
		 */
		
		const ROUTE_RAIL = 2;
		
		/**
		 * Timetable data source
		 * @var string $provider The name of this GTFS data provider
		 */
		
		public $provider;
		
		/**
		 * Adapter object
		 * @var \Zend\Db\Adapter\Adapter $adapter ZendFramework 2 database adapter
		 */
		
		public $adapter;
		
		/**
		 * Database object
		 * @var \Zend\Db\Sql\Sql $db ZendFramework 2 database object
		 */
		
		public $db;
		
		/**
		 * Constructor
		 * @since Version 3.9
		 */
		
		public function __construct() {
			
			if (function_exists("getRailpageConfig")) {
				$this->Config = getRailpageConfig();
			}
			
			$this->adapter = new Adapter(array(
				"driver" => "Mysqli",
				"database" => $this->Config->GTFS->PTV->db_name,
				"username" => $this->Config->GTFS->PTV->db_user,
				"password" => $this->Config->GTFS->PTV->db_pass,
				"host" => $this->Config->GTFS->PTV->db_host
			));
			
			$this->db = new Sql($this->adapter);
		}
		
		/**
		 * Fetch
		 * @param string $method
		 * @param string $parameters
		 * @param string $other
		 * @return string
		 */
		
		public function fetch($method, $parameters, $other) {
			return "Not implemented";
		}
		
		/**
		 * Health
		 * @return string
		 */
		
		public function Health() {
			return "Not implemented";
		}
		
		/**
		 * Get stops near a location
		 * @param double $latitude
		 * @param double $longitude
		 * @return array
		 */
		
		public function StopsNearLocation($latitude = false, $longitude = false) {
			if (!$latitude) {
				throw new Exception("Cannot fetch " . __METHOD__ . " - no latitude given");
			}
			
			if (!$longitude) {
				throw new Exception("Cannot fetch " . __METHOD__ . " - no longitude given");
			}
			
			$query = "SELECT
						stop_id,
						stop_name,
						stop_lat,
						stop_lon,
						wheelchair_boarding, (
							  3959 * acos (
							  cos ( radians(" . $latitude . ") )
							  * cos( radians( stop_lat ) )
							  * cos( radians( stop_lon ) - radians(" . $longitude . ") )
							  + sin ( radians(" . $latitude . ") )
							  * sin( radians( stop_lat ) )
							)
						) AS distance
						FROM %s_stops
						WHERE location_type = 1
						HAVING distance < 3
						ORDER BY distance
						LIMIT 0 , 50";
			
			$query = sprintf($query, static::DB_PREFIX);
			
			$result = $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE); 
			
			$return = array();
			
			foreach ($result as $row) {
				$row = $row->getArrayCopy();
				$row['provider'] = $this->provider;
				$row['distance'] = vincentyGreatCircleDistance($row['stop_lat'], $row['stop_lon'], $latitude, $longitude);

				
				$return[] = $row;
			}
			
			return $return;
		}
		
		/**
		 * Get the database prefix
		 * @since Version 3.9
		 * @return string
		 */
		
		public function getDbPrefix() {
			return static::DB_PREFIX;
		}
		
		/**
		 * Get the provider name
		 * @since Version 3.9
		 * @return string
		 */
		
		public function getProviderName() {
			return static::PROVIDER_NAME;
		}
		
		/**
		 * Get routes from GTFS data
		 * @since Version 3.9
		 * @return array
		 */
		
		public function GetRoutes() {
			$query = sprintf("SELECT id, route_id, route_short_name, route_long_name, route_desc, route_url FROM %s_routes WHERE route_type = %d ORDER BY route_short_name", static::DB_PREFIX, self::ROUTE_RAIL); 
			$result = $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
			
			$routes = array();  
			
			if ($result) {
				foreach ($result as $row) {
					$row = $row->getArrayCopy();
					
					$row['provider'] = array(
						"name" => static::PROVIDER_NAME,
						"class" => get_class($this)
					);
					
					$row['url'] = new Url(sprintf("%s/timetables?provider=%s&id=%d", RP_WEB_ROOT, static::PROVIDER_NAME, $row['id']));
					
					$routes[$row['id']] = $row;
				}
			}
			
			return $routes;
		}
	}
?>