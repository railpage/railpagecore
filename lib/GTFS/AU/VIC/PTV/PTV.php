<?php
	/**
	 * PTV GTFS class
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\GTFS\AU\VIC\PTV;
	
	use Exception;
	use DateTime;
	use Zend\Http\Client;
	use Zend\Db\Sql\Sql;
	use Zend\Db\Sql\Insert;
	use Zend\Db\Adapter\Adapter;
	use Railpage\GTFS\GTFSInterface;
	use Railpage\GTFS\StandardProvider;
	use Railpage\Url;
	
	/**
	 * PTV provider class for GTFS
	 * @since Version 3.8.7
	 */
	
	class PTV extends StandardProvider {
		
		/**
		 * Timetable data source
		 * @var string $provider The name of this GTFS data provider
		 */
		
		public $provider = "PTV";
		
		/**
		 * Timetable data source as a constant
		 * @const string PROVIDER_NAME
		 * @since Version 3.9
		 */
		
		const PROVIDER_NAME = "PTV";
		
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
		
		const PROVIDER_REGION = "Victoria";
		
		/**
		 * State or region of origin
		 * @since Version 3.9
		 * @const string PROVIDER_REGION_SHORT
		 */
		
		const PROVIDER_REGION_SHORT = "VIC";
		
		/**
		 * Database table prefix
		 * @since Version 3.9
		 * @const string DB_PREFIX
		 */
		
		const DB_PREFIX = "au_ptv";
		
		/**
		 * API endpoint
		 * @var string $endpoint The API endpoint, as PTV's data is not in GTFS format so we need to do some translation
		 */
		
		public $endpoint = "http://timetableapi.ptv.vic.gov.au";
		
		/**
		 * Request URL
		 * @var string $url The request URL to fetch data from the API with
		 */
		
		public $url;
		
		/**
		 * API username
		 * @var string $api_username
		 */
		
		public $api_username;
		
		/**
		 * API key
		 * @var string $api_key
		 */
		 
		public $api_key;
		
		/**
		 * API password
		 * @var string $api_password
		 */
		
		public $api_password;
		
		/**
		 * Routes to ignore
		 * @var array $ignore_routes
		 */
		
		public $ignore_routes = array(
			"Yarram - Melbourne via Koo Wee Rup & Dandenong",
			"Warrnambool - Melbourne via Colac & Geelong",
			"Warrnambool - Melbourne via Apollo Bay & Geelong",
			"Canberra - Melbourne via Bairnsdale",
			"Paynesville - Melbourne via Bairnsdale",
			"Mount Gambier - Melbourne via Warrnambool & Geelong",
			"Halls Gap - Melbourne via Stawell & Ballarat",
			"Maryborough - Melbourne via Ballarat",
			"Geelong - Bendigo via Ballarat",
			"Adelaide - Melbourne via Horsham & Ballarat & Geelong",
			"Griffith - Melbourne via Shepparton"
		);
		
		/**
		 * Stops / destinations to ignore
		 * @var array @ignore_stops
		 */
		
		public $ignore_stops = array(
			"Canberra Railway Station/Wentworth Ave",
			"Southern Cross Railway Station/Spencer St #122",
			"Dennis Railway Station/Victoria Rd",
			"Fairfield Railway Station/Station St",
			"Fairfield Railway Station/Wingrove St",
			"Flinders Street Railway Station/Elizabeth St #1",
			"Flinders Street Railway Station/Flinders St",
			"West Footscray Railway Station/Sunshine Rd",
			"Richmond Railway Station/Punt Rd",
			"Middle Footscray Railway Station/Buckley St",
			"Toorak Railway Station/Clendon Rd",
			"Armadale Railway Station/Kooyong Rd",
			"Middle Footscray Railway Station/Buckley St",
			"West Footscray Railway Station/Geelong Rd",
			"North Williamstown Railway Station/Ferguson St",
			"Macaulay Railway Station/Macaulay Rd",
			"Kensington Railway Station/Macaulay Rd",
			"Richmond Railway Station/Punt Rd",
			"West Richmond Railway Station/Hoddle St",
			"Southern Cross Coach Terminal/Spencer St",
			"Coach Terminal/24 Roberts Ave"
		);
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 */
		
		public function __construct() {
			
			parent::__construct(); 
			
			if (function_exists("getRailpageConfig")) {
				$this->Config = getRailpageConfig();
			}
			
			$this->api_key = $this->Config->GTFS->PTV->api_key;
			$this->api_username = $this->Config->GTFS->PTV->api_username;
			$this->api_password = $this->Config->GTFS->PTV->api_password;
			
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
		 * Fetch data
		 * @param string $method The API method to call
		 * @param array $parameters An array of data to pass to the API
		 * @param array $other Any other data we need to create the URL
		 */
		
		public function fetch($method = false, $parameters = array(), $other = array()) {
			if (!$method && empty($parameters)) {
				throw new Exception("Cannot fetch API query - no API method defined");
			}
			
			if ($method == "stops-for-line") {
				$url = "/v2";
			} else {
				$url = strlen($method) > 0 ? "/v2/" . $method : "/v2";
			}
			
			$Date = new DateTime;
			$data = array();
			
			$other['devid'] = $this->api_username;
			
			if ($method == "healthcheck") {
				$other['timestamp'] = $Date->format(DateTime::ISO8601);
			}
			
			/**
			 * Loop through $parameters and add to the URL
			 */
			
			if ($method == "search") {
				$url .= "/" . urlencode($parameters[0]);
			} else {
				foreach ($parameters as $key => $val) {
					if (is_array($val)) {
						// Figure it out later
					} else {
						$url .= "/" . urlencode($key) . "/" . urlencode($val); 
					}
				}
			}
			
			if ($method == "stops-for-line") {
				$url .= "/" . urlencode($method);
			}
			
			/**
			 * Loop through $other and add to the URL
			 */
			
			foreach ($other as $key => $val) {
				if (is_array($val)) {
					// Figure it out later
				} else {
					$data[] = urlencode($key) . "=" . urlencode($val); 
				}
			}
			
			if (count($data)) {
				$url .= "?" . implode("&", $data); 
			}
			
			/**
			 * Generate the signature
			 */
			
			$signature = strtoupper(hash_hmac("sha1", $url, $this->api_key, false));
			
			/**
			 * Final URL
			 */
			
			$this->url = $this->endpoint . $url . "&signature=" . $signature;
			
			/**
			 * Get the data
			 */
			
			$config = array(
				'adapter' => 'Zend\Http\Client\Adapter\Curl',
				'curloptions' => array(CURLOPT_FOLLOWLOCATION => true),
			);
			
			$client = new Client($this->url, $config);
			$response = $client->send();
			
			$content = $response->getContent();
			return json_decode($content, true);
		}
		
		/**
		 * API Health check
		 * @return array
		 */
		
		public function Health() {
			return $this->fetch("healthcheck");
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
			
			$parameters = array(
				"latitude" => $latitude,
				"longitude" => $longitude
			);
			
			$return = array();
			
			foreach ($this->fetch("nearme", $parameters) as $row) {
				$row['result']['location_name'] = trim($row['result']['location_name']);
				
				if ($row['result']['transport_type'] == "train" || preg_match("@Railway@i", $row['result']['location_name'])) {
					$placeData = array(
						"stop_id" => $row['result']['stop_id'],
						"stop_name" => $row['result']['location_name'],
						"stop_lat" => $row['result']['lat'],
						"stop_lon" => $row['result']['lon'],
						"wheelchair_boarding" => 0,
						"location_type" => 1
					);
					
					/**
					 * Check if this is stored in the database, and add it if it's missing
					 */
					
					if (!in_array($row['result']['location_name'], $this->ignore_stops) && 
						$row['result']['stop_id'] < 10000 &&
						!preg_match("@#([0-9]{0,3})@", $row['result']['location_name'])
					) {
						$query = sprintf("SELECT stop_id FROM %s_stops WHERE stop_id = %d LIMIT 1", self::DB_PREFIX, $row['result']['stop_id']); 
						$result = $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE); 
						
						if ($result->count() === 0) {
							$Insert = $this->db->insert(sprintf("%s_stops", self::DB_PREFIX));
							$Insert->values($placeData);
							$selectString = $this->db->getSqlStringForSqlObject($Insert);
							$results = $this->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
						}
					
						$placeData['distance'] = vincentyGreatCircleDistance($row['result']['lat'], $row['result']['lon'], $latitude, $longitude);
						$placeData['provider'] = $this->provider;
						
						if ($placeData['distance'] <= 10000) { // Limit results to 10km from provided lat/lon
							$return[] = $placeData;
						}
					}
				}
			}
			
			return $return;
		}
		
		/**
		 * Get a train object
		 * @since Version 3.9
		 * @return object
		 */
		
		public function getRoute($id = false) {
			return new Route($id, $this);
		}
		
		/**
		 * Get routes serviced by this provider
		 * @since Version 3.9
		 * @return array
		 */
		
		public function getRoutes() {
			
			$params = array("line");
			
			$results = $this->fetch("search", $params);
			
			$routes = array(); 
			
			foreach ($results as $row) {
				if ($row['result']['transport_type'] == "train") {
					$routes[$row['result']['line_id']] = array(
						"id" => $row['result']['line_id'],
						"route_id" => $row['result']['line_id'],
						"agency_id" => 1,
						"route_short_name" => $row['result']['line_number'],
						"route_long_name" => $row['result']['line_name'],
						"route_desc" => "",
						"route_url" => "",
						"route_color" => "",
						"route_text_color" => "",
						"url" => new Url(sprintf("%s/timetables?provider=%s&id=%d", RP_WEB_ROOT, static::PROVIDER_NAME, $row['result']['line_id']))
					);
				}
			}
			
			return $routes;
			
			/*
			$routes = array(
				array(
					"id" => 1,
					"route_id" => 1,
					"agency_id" => 1,
					"route_short_name" => "Alamein Line",
					"route_long_name" => "Alamein Line",
					"route_desc" => "",
					"route_url" => "",
					"route_color" => "",
					"route_text_color" => ""
				),
				array(
					"id" => 2,
					"route_id" => 2,
					"agency_id" => 1,
					"route_short_name" => "Belgrave Line",
					"route_long_name" => "Belgrave Line",
					"route_desc" => "",
					"route_url" => "",
					"route_color" => "",
					"route_text_color" => ""
				),
				array(
					"id" => 3,
					"route_id" => 3,
					"agency_id" => 1,
					"route_short_name" => "Craigieburn Line",
					"route_long_name" => "Craigieburn Line",
					"route_desc" => "",
					"route_url" => "",
					"route_color" => "",
					"route_text_color" => ""
				),
				array(
					"id" => 4,
					"route_id" => 4,
					"agency_id" => 1,
					"route_short_name" => "Cranbourne Line",
					"route_long_name" => "Cranbourne Line",
					"route_desc" => "",
					"route_url" => "",
					"route_color" => "",
					"route_text_color" => ""
				),
				array(
					"id" => 6,
					"route_id" => 6,
					"agency_id" => 1,
					"route_short_name" => "Frankston Line",
					"route_long_name" => "Frankston Line",
					"route_desc" => "",
					"route_url" => "",
					"route_color" => "",
					"route_text_color" => ""
				),
				array(
					"id" => 7,
					"route_id" => 7,
					"agency_id" => 1,
					"route_short_name" => "Glen Waverley Line",
					"route_long_name" => "Glen Waverley Line",
					"route_desc" => "",
					"route_url" => "",
					"route_color" => "",
					"route_text_color" => ""
				),
				array(
					"id" => 8,
					"route_id" => 8,
					"agency_id" => 1,
					"route_short_name" => "Hurstbridge Line",
					"route_long_name" => "Hurstbridge Line",
					"route_desc" => "",
					"route_url" => "",
					"route_color" => "",
					"route_text_color" => ""
				),
				array(
					"id" => 9,
					"route_id" => 9,
					"agency_id" => 1,
					"route_short_name" => "Lilydale Line",
					"route_long_name" => "Lilydale Line",
					"route_desc" => "",
					"route_url" => "",
					"route_color" => "",
					"route_text_color" => ""
				),
				array(
					"id" => 11,
					"route_id" => 11,
					"agency_id" => 1,
					"route_short_name" => "Pakenham Line",
					"route_long_name" => "Pakenham Line",
					"route_desc" => "",
					"route_url" => "",
					"route_color" => "",
					"route_text_color" => ""
				)
			);
			*/
			
			printArray($routes);
		}
	}
	