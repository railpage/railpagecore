<?php
	/**
	 * PTV GTFS class
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\GTFS\AU\PTV;
	
	use Exception;
	use DateTime;
	use Zend\Http\Client;
	use Zend\Db\Sql\Sql;
	use Zend\Db\Sql\Insert;
	use Zend\Db\Adapter\Adapter;
	use Railpage\GTFS\GTFSInterface;
	
	/**
	 * PTV provider class for GTFS
	 * @since Version 3.8.7
	 */
	
	class PTV implements GTFSInterface {
		
		/**
		 * Timetable data source
		 * @var string $provider The name of this GTFS data provider
		 */
		
		public $provider = "PTV";
		
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
			
			$url = strlen($method) > 0 ? "/v2/" . $method : "/v2";
			$Date = new DateTime;
			$data = array();
			
			$other['devid'] = $this->api_username;
			
			if ($method == "healthcheck") {
				$other['timestamp'] = $Date->format(DateTime::ISO8601);
			}
			
			/**
			 * Loop through $parameters and add to the URL
			 */
			
			foreach ($parameters as $key => $val) {
				if (is_array($val)) {
					// Figure it out later
				} else {
					$url .= "/" . urlencode($key) . "/" . urlencode($val); 
				}
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
						$query = "SELECT stop_id FROM au_ptv_stops WHERE stop_id = " . $row['result']['stop_id'] . " LIMIT 1"; 
						$result = $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE); 
						
						if ($result->count() === 0) {
							$Insert = $this->db->insert("au_ptv_stops");
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
	}
?>