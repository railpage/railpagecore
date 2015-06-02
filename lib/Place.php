<?php
	/**
	 * Where on Earth is this place?
	 * Geolookup
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage; 
	
	use Railpage\Locations\Locations;
	use Railpage\Locations\Country;
	use Railpage\Locations\Region;
	use Railpage\Locations\Location;
	
	use Railpage\GTFS\GTFS;
	
	use Exception;
	use stdClass;
	use flickr_railpage;
	use DateTime;
	use DateTimeZone;
	use GuzzleHttp\Client;
	
	/**
	 * Place class
	 */
	
	class Place extends AppCore {
		
		/**
		 * Name
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Latitude
		 * @var float $lat
		 */
		
		public $lat;
		
		/**
		 * Longitude
		 * @var float $lon
		 */
		
		public $lon;
		
		/**
		 * Radius around this location to limit adjacent searches to
		 * @var float $radius
		 */
		
		public $radius;
		
		/**
		 * Country
		 * @var object $Country
		 */
		
		public $Country;
		
		/**
		 * Region
		 * @var object $Region
		 */
		
		public $Region;
		
		/**
		 * Bounding box
		 * @var object $boundingBox
		 */
		
		public $boundingBox;
		
		/**
		 * Constructor
		 * @param float $lat
		 * @param float $lon
		 * @param float $radius
		 */
		
		public function __construct($lat, $lon, $radius = 0.1) {
			parent::__construct(); 
			
			$this->GuzzleClient = new Client;
			
			$this->lat = $lat;
			$this->lon = $lon;
			$this->radius = $radius;
			$this->url = sprintf("/place?lat=%s&lon=%s", $this->lat, $this->lon);
			
			/**
			 * Start the debug timer
			 */
			
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			if (function_exists("debug_recordInstance")) {
				debug_recordInstance(__CLASS__);
			}
			
			$this->load(); 
			
			/**
			 * End the debug timer
			 */
				
			if (RP_DEBUG) {
				$site_debug[] = __CLASS__ . "::" . __FUNCTION__ . "() : fetched WOE data from Yahoo in " . round(microtime(true) - $debug_timer_start, 5) . "s";
			}
		}
		
		/**
		 * Populate this object
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function load() {
			
			/**
			 * Fetch the WOE (Where On Earth) data from Yahoo
			 */
			
			$woe = $this->getWOEData($this->lat . "," . $this->lon);
			
			if (!isset($woe['places']['place'][0])) {
				throw new Exception("Could not find a place matching coordinates " . $this->lat . "," . $this->lon);
			}
			
			/**
			 * Simple enough - create the country object
			 */
			
			$this->Country = new Country($woe['places']['place'][0]['country']);
			
			/**
			 * Bit trickier - find the region, ie, the next geographical location down from a country
			 */
			
			foreach ($woe['places']['place'][0] as $key => $val) {
				if (isset($val['type']) && strtolower($val['type']) != "country") {
					$this->Region = new Region($val['woeid']);
					break;
				}
			}
			
			/**
			 * Set the place name
			 */
			
			if (empty($this->name)) {
				$this->name = $woe['places']['place'][0]['locality1'];
			}
			
			/**
			 * Set the bounding box
			 */
			
			$this->boundingBox = new stdClass;
			$this->boundingBox->northEast = new stdClass;
			$this->boundingBox->northEast->lat = floatval($woe['places']['place'][0]['boundingBox']['northEast']['latitude']);
			$this->boundingBox->northEast->lon = floatval($woe['places']['place'][0]['boundingBox']['northEast']['longitude']);
			
			$this->boundingBox->southWest = new stdClass;
			$this->boundingBox->southWest->lat = floatval($woe['places']['place'][0]['boundingBox']['southWest']['latitude']);
			$this->boundingBox->southWest->lon = floatval($woe['places']['place'][0]['boundingBox']['southWest']['longitude']);
		}
		
		/**
		 * Get locations adjacent to this place
		 * @return array
		 */
		
		public function getLocations() {
			$Locations = new Locations;
			
			return $Locations->nearby($this->lat, $this->lon, $this->radius);
		}
		
		/**
		 * Get photos within or adjacent to this place
		 * @return array
		 * @param int $num
		 */
		
		public function getPhotos($num = 10) {
			$lat = $this->lat;
			$lon = $this->lon;
			
			$query = "SELECT flickr_geodata.*, 3956 * 2 * ASIN(SQRT(POWER(SIN((" . $lat . " - flickr_geodata.lat) * pi() / 180 / 2), 2) + COS(" . $lat . " * pi() / 180) * COS(" . $lat . " * pi() / 180) * POWER(SIN((" . $lon . " - flickr_geodata.lon) * pi() / 180 / 2), 2))) AS distance 
				FROM flickr_geodata 
				WHERE flickr_geodata.lon BETWEEN (
						" . $lon . " - " . $this->radius . " / abs(cos(radians(" . $lat . ")) * 69)
					) AND (
						" . $lon . " + " . $this->radius . " / abs(cos(radians(" . $lat . ")) * 69)
					)
					AND flickr_geodata.lat BETWEEN (
						" . $lat . " - (" . $this->radius . " / 69) 
					) AND (
						" . $lat . " + (" . $this->radius . " / 69) 
					)
				HAVING distance < " . $this->radius . "
				ORDER BY distance
				LIMIT ?";
				
			$params = array(
				$num
			);
			
			$return = array(); 
			$square_size = 180;
			
			foreach ($this->db->fetchAll($query, $params) as $data) {
				$key = $data['photo_id'];
				
				$return[$key]['size_sq'] = RP_PROTOCOL . "://" . $_SERVER['HTTP_HOST'] . "/image_resize.php?q=90&w=" . $square_size . "&h=" . $square_size . "&square=true&image=" . str_replace("?zz=1", "", $data['size4']);
				$return[$key]['size_sq_w'] = $square_size;
				$return[$key]['size_sq_h'] = $square_size;
				
				$data['id'] = $data['photo_id'];
				
				$data['url_sq']		= $data['size0'];
				$data['width_sq']	= $data['size0_w'];
				$data['height_sq']	= $data['size0_h'];
				
				$data['url_t']		= $data['size1'];
				$data['width_t']	= $data['size1_w'];
				$data['height_t']	= $data['size1_h'];
				
				$data['url_s']		= $data['size2'];
				$data['width_s']	= $data['size2_w'];
				$data['height_s']	= $data['size2_h'];
				
				$data['url_q']		= NULL;
				$data['width_q']	= NULL;
				$data['height_q']	= NULL;
				
				$data['url_m']		= $data['size3'];
				$data['width_m']	= $data['size3_w'];
				$data['height_m']	= $data['size3_h'];
				
				$data['url_n']		= $data['size6'];
				$data['width_n']	= $data['size6_w'];
				$data['height_n']	= $data['size6_h'];
				
				$data['url_z']		= $data['size4'];
				$data['width_z']	= $data['size4_w'];
				$data['height_z']	= $data['size4_h'];
				
				$data['url_l']		= $data['size5'];
				$data['width_l']	= $data['size5_w'];
				$data['height_l']	= $data['size5_h'];
				
				$data['url_c']		= $data['size7'];
				$data['width_c']	= $data['size7_w'];
				$data['height_c']	= $data['size7_h'];
				
				$data['url_o']		= $data['size8'];
				$data['width_c']	= $data['size8_w'];
				$data['height_c']	= $data['size8_h'];
				
				$data['nicetags'] = explode(" ", $data['tags']);
				
				$return[$key] = $data;
			}
			
			
			return $return;
		}
		
		/**
		 * Get GTFS places near this place
		 * @since Version 3.8.7
		 * @return array
		 */
		
		public function getGTFSPlaces() {
			
			$places = array();
			
			foreach ((new GTFS)->getProviders() as $Provider) {
				
				if (is_object($Provider) && method_exists($Provider, "StopsNearLocation")) {
					$places[$Provider::PROVIDER_COUNTRY_SHORT][$Provider::PROVIDER_NAME] = $Provider->StopsNearLocation($this->lat, $this->lon);
				}
			}
			
			return $places;
		}
		
		/**
		 * Get the street address of this place
		 * @since Version 3.8.7
		 * @return array
		 */
		
		public function getAddress() {
			$mckey = sprintf("railpage.place.address.lat=%s&lon=%s", $this->lat, $this->lon);
			
			if ($address = $this->Redis->fetch($mckey)) {
				return $address; 
			} else {
				$url = sprintf("https://maps.googleapis.com/maps/api/geocode/json?latlng=%s,%s&sensor=false", $this->lat, $this->lon);
				
				$response = $this->GuzzleClient->get($url);
				
				if ($response->getStatusCode() == 200) {
					$result = json_decode($response->getBody(), true);
				}
				
				$return = array();
				
				if (isset($result['results'][0]['formatted_address'])) {
					$return['address'] = $result['results'][0]['formatted_address'];
					
					foreach ($result['results'] as $row) {
						if ($row['types'][0] == "street_address") {
							$return['street_address'] = $row['formatted_address'];
						}
						
						if ($row['types'][0] == "locality") {
							$return['locality'] = $row['formatted_address'];
						}
						
						if ($row['types'][0] == "administrative_area_level_1") {
							$return['region'] = $row['formatted_address'];
						}
					}
				}
				
				$this->Redis->save($mckey, $return, strtotime("+12 hours"));
				
				return $return;
			}
		}
		
		/**
		 * Get weather forecast for this place
		 * @since Version 3.8.7
		 * @return array
		 * @param int $days
		 */
		
		public function getWeatherForecast($days = 14) {
			$weather = false;
			
			/**
			 * Check if we've been given a DateTime object (a date) or a date range (eg 14 days) to work wtih
			 */
			
			$datekey = $days instanceof DateTime ? $days->format("Y-m-d") : $days;
			
			/**
			 * Try to get the weather from Memcached first
			 */
			
			$mckey = sprintf("railpage:lat=%s;lon=%s;weather;days=%s", $this->lat, $this->lon, $datekey);
			
			if ($weather = $this->Redis->fetch($mckey)) {
				return $weather;
			}
			
			/**
			 * Restrict our maximum date range to 14 days
			 */
			
			if (is_int($days) && $days > 14) {
				$days = 14;
			}
			
			if ($days instanceof DateTime) {
				$Date = $days;
				$url = "http://api.openweathermap.org/data/2.5/forecast/daily?lat=" . $this->lat . "&lon=" . $this->lon . "&units=metric&cnt=14";
				
				$Now = new DateTime;
				$diff = $Now->diff($Date);
				
				if ($diff->format("%R") != "+" || $diff->format("%a") > 14) {
					return $weather;
				}
			} else {
				$url = "http://api.openweathermap.org/data/2.5/forecast/daily?lat=" . $this->lat . "&lon=" . $this->lon . "&units=metric&cnt=" . $days;
			}
			
			/**
			 * Try to get the weather forecast from openweathermap
			 */
			
			try {
				$response = $this->GuzzleClient->get($url);
			} catch (\GuzzleHTTP\RequestException $e) {
				return false;
			} catch (Exception $e) {
				return false;
			}
				
			if ($response->getStatusCode() == 200) {
				$forecast = json_decode($response->getBody(), true);
			}
			
			if (is_array($forecast)) {
				$weather = array(); 
			}
			
			foreach ($forecast['list'] as $row) {
				$ForecastDate = new DateTime("@" . $row['dt']);
				
				$weather[$ForecastDate->format("Y-m-d")]['forecast'] = array(
					"min" => round($row['temp']['min']),
					"max" => round($row['temp']['max']),
					"weather" => array(
						"title" => $row['weather'][0]['main'],
						"icon" => function_exists("getWeatherIcon") ? getWeatherIcon($row['weather'][0]['description']) : ""
					)
				);
			}
			
			if (isset($Date) && $Date instanceof DateTime) {
				$this->Redis->save($mckey, $weather[$Date->format("Y-m-d")], strtotime("+24 hours")); 
				return $weather[$Date->format("Y-m-d")];
			}
			
			$this->Redis->save($mckey, $weather, strtotime("+24 hours"));
			
			return $weather;
		}
	
		/**
		 * Get WOE (Where On Earth) data from Yahoo's GeoPlanet API
		 *
		 * Ported from [master]/includes/functions.php
		 * @since Version 3.8.7
		 * @param string $lookup
		 * @return array
		 */
		
		public static function getWOEData($lookup = false) {
			if ($lookup === false) {
				return false;
			}
			
			$return = array();
			
			$mckey = "railpage:woe=" . $lookup;
			
			$Redis = AppCore::getRedis(); 
			
			if (!$return = $Redis->fetch($mckey)) {
				global $RailpageConfig;
				
				if (preg_match("@[a-zA-Z]+@", $lookup) || strpos($lookup, ",")) {
					$lookup = sprintf("places.q('%s')", $lookup);
				} else {
					$lookup = sprintf("place/%s", $lookup);
				}
				
				$url = sprintf("http://where.yahooapis.com/v1/%s?lang=en&appid=%s&format=json", $lookup, $RailpageConfig->Yahoo->ApplicationID);
				
				$GuzzleClient = new Client;
				$response = $GuzzleClient->get($url);
				
				if ($response->getStatusCode() == 200) {
					$result = json_decode($response->getBody(), true);
				}
				
				switch ($response->getStatusCode()) {
					case 200 :
						$return = json_decode($response->getBody(), true);
						break;
					
					case 503 : 
						throw new Exception("Your call to Yahoo Web Services failed and returned an HTTP status of 503. That means: Service unavailable. An internal problem prevented us from returning data to you.");
						break;
					
					case 403 : 
						throw new Exception("Your call to Yahoo Web Services failed and returned an HTTP status of 403. That means: Forbidden. You do not have permission to access this resource, or are over your rate limit.");
						break;
					
					case 400 : 
						throw new Exception(sprintf("Your call to Yahoo Web Services failed and returned an HTTP status of 400. That means:  Bad request. The parameters passed to the service did not match as expected. The exact error is returned in the XML/JSON response. The URL sent was: %s", $url));
						break;
					
					default : 
						throw new Exception("Your call to Yahoo Web Services returned an unexpected HTTP status of: " . $response->getStatusCode());
						
				}
				
				$return['url'] = $url;
			}
			
			if ($return !== false) {
				$Redis->save($mckey, $return, strtotime("+2 months")); 
			}
			
			return $return;
		}

	}