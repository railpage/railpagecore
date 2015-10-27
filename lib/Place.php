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
	use Railpage\Locations\Factory as LocationsFactory;
	use Railpage\Debug;
	use Railpage\GTFS\GTFS;
	use Railpage\Registry;
	use Railpage\Images\Images;
	use Exception;
	use stdClass;
	use flickr_railpage;
	use DateTime;
	use DateTimeZone;
	use GuzzleHttp\Client;
	use GuzzleHttp\Exception\RequestException;
	
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
		
		public function __construct($lat = false, $lon = false, $radius = 0.1) {
			parent::__construct(); 
			
			$timer = Debug::getTimer(); 
			Debug::RecordInstance(); 
			
			$this->GuzzleClient = new Client;
			
			if (filter_var($lat, FILTER_VALIDATE_FLOAT) && filter_var($lon, FILTER_VALIDATE_FLOAT)) {
				$this->lat = $lat;
				$this->lon = $lon;
				$this->radius = $radius;
				$this->url = sprintf("/place?lat=%s&lon=%s", $this->lat, $this->lon);
				
				$this->load(); 
			}
			
			Debug::logEvent(__METHOD__, $timer); 
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
			
			$this->Country = LocationsFactory::CreateCountry($woe['places']['place'][0]['country']);
			
			/**
			 * Bit trickier - find the region, ie, the next geographical location down from a country
			 */
			
			foreach ($woe['places']['place'][0] as $key => $val) {
				if (isset($val['type']) && strtolower($val['type']) != "country") {
					$this->Region = LocationsFactory::CreateRegion($val['woeid']);
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
		 * Get photos from the Sphinx search API within or adjacent to this place
		 * @since Version 3.9.1
		 * @param int $num
		 * @return array
		 */
		
		public function getPhotosFromSphinx($num = 10) {
			
			$Sphinx = AppCore::getSphinxAPI(); 
			
			$Sphinx->SetGeoAnchor("lat", "lon", deg2rad($this->lat), deg2rad($this->lon)); 
			$Sphinx->SetFilterRange("@geodist", 0, 5000); // 1km radius
			$Sphinx->SetSortMode(SPH_SORT_EXTENDED, '@geodist ASC');
			
			$result = $Sphinx->query("", "idx_images"); 
			
			$return = array(
				"stat" => "ok"
			);
			
			if (!$result) {
				$return['stat'] = "err";
				$return['message'] = $Sphinx->getLastError();
				return $return;
			}
			
			if (empty($result['matches'])) {
				return $return;
			}
			
			foreach ($result['matches'] as $row) {
				$meta = json_decode($row['attrs']['meta'], true); 
				
				$return['photos'][] = array(
					"id" => $row['attrs']['image_id'],
					"provider" => $row['attrs']['provider'],
					"photo_id" => $row['attrs']['photo_id'],
					"url" => $row['attrs']['url'],
					"distance" => round($row['attrs']['@geodist']),
					"lat" => rad2deg($row['attrs']['lat']),
					"lon" => rad2deg($row['attrs']['lon']),
					"title" => empty(trim($row['attrs']['title'])) ? "Untitled" : $row['attrs']['title'],
					"description" => $row['attrs']['description'],
					"sizes" => Images::normaliseSizes($meta['sizes'])
				);
			}
			
			$return['photos'] = array_slice($return['photos'], 0, $num);
			
			return $return;
			
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
		 * Get an associative array of this object
		 * @since Version 3.10.0
		 * @return array
		 */
		
		public function getArray() {
			
			$array = array(
				"lat" => $this->lat,
				"lon" => $this->lon,
				"name" => $this->name,
				"address" => $this->getAddress(),
				"region" => array(
					"name" => $this->Region->name,
					"code" => $this->Country->code
				),
				"country" => array(
					"name" => $this->Country->name,
					"code" => $this->Country->code
				),
				"url" => $this->url instanceof Url ? $this->url->getURLs() : array("url" => $this->url)
			);
			
			return $array;
			
		}
		
		/**
		 * Get the street address of this place
		 * @since Version 3.8.7
		 * @return array
		 */
		
		public function getAddress() {
			
			$mckey = sprintf("railpage.place.address.lat=%s&lon=%s", $this->lat, $this->lon);
			
			if ($address = $this->Memcached->fetch($mckey)) {
				return $address; 
			}
			
			/**
			 * Try to fetch it from the local cache first
			 */
			
			$query = "SELECT address FROM woecache WHERE lat = ? AND lon = ?";
			$params = [
				round(str_pad($this->lat, 12, 0), 8), 
				round(str_pad($this->lon, 12, 0), 8)
			];
			
			if ($result = $this->db->fetchOne($query, $params)) {
				if (!is_null($result)) {
					return json_decode($result, true);
				}
			}
			
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
			
			/**
			 * Store it in Memcached
			 */
			
			$this->Memcached->save($mckey, $return);
			
			/**
			 * Store it in our database
			 */
			
			$query = "INSERT INTO woecache (
						lat, lon, response, stored, address
					) VALUES (
						%s, %s, NULL, NOW(), %s
					) ON DUPLICATE KEY UPDATE
						address = VALUES(address),
						stored = NOW()";
			
			$query = sprintf($query, $this->db->quote($this->lat), $this->db->quote($this->lon), $this->db->quote(json_encode($return))); 
			$this->db->query($query); 
			
			return $return;
			
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
			
			$mckey = md5(sprintf("railpage:lat=%s;lon=%s;weather;days=%s", $this->lat, $this->lon, $datekey));
			
			if ($weather = $this->Redis->fetch($mckey)) {
				return $weather;
			}
			
			/**
			 * Check the database before we try to fetch it from the weather API 
			 */
			
			$GeoplaceID = PlaceUtility::findGeoPlaceID($this->lat, $this->lon); 
			
			if ($days instanceof DateTime) {
				$query = "SELECT date, min, max, weather, icon FROM geoplace_forecast WHERE date = ? AND geoplace = ?";
				$params = [ $days->format("Y-m-d"), $GeoplaceID ];
			} else {
				$query = "SELECT date, min, max, weather, icon FROM geoplace_forecast WHERE date >= ? AND geoplace = ? LIMIT 0, ?";
				$params = array(
					date("Y-m-d"),
					$GeoplaceID,
					$days
				);
			}
			
			if ($result = $this->db->fetchAll($query, $params)) {
				$weather = array(); 
				
				foreach ($result as $row) {
					$weather[$row['date']]['forecast'] = array(
						"min" => $row['min'],
						"max" => $row['max'],
						"weather" => array(
							"title" => $row['weather'],
							"icon" => $row['icon']
						)
					);
				}
				
				return $weather;
			}
			
			/**
			 * Didn't find the weather cached in memory or database, so let's look it up...
			 */
			
			
			
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
			
			//try {
				$response = $this->GuzzleClient->get($url);
			//} catch (\GuzzleHTTP\RequestException $e) {
			//	return false;
			//} catch (Exception $e) {
			//	return false;
			//}
				
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
				
				$data = [
					"geoplace" => $GeoplaceID,
					"expires" => date("Y-m-d H:i:s", strtotime("+24 hours")),
					"date" => $ForecastDate->format("Y-m-d"),
					"min" => round($row['temp']['min']),
					"max" => round($row['temp']['max']),
					"weather" => $row['weather'][0]['main'],
					"icon" => $weather[$ForecastDate->format("Y-m-d")]['forecast']['weather']['icon']
				];
				
				$this->db->insert("geoplace_forecast", $data);
					
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
		 * @param array $types Yahoo Woe types to lookup
		 * @return array
		 */
		
		public static function getWOEData($lookup = false, $types = false) {
			if ($lookup === false) {
				return false;
			}
			
			$return = array();
			$expiry = strtotime("+1 year"); 
			$mckey = "railpage:woe=" . $lookup;
			
			if ($types) {
				$mckey .= ";types=" . implode(",", $types); 
			}
			
			$Cache = AppCore::getRedis();
			$Cache = AppCore::getMemcached(); 
			
			/**
			 * Try and get the WoE data from Memcached or Redis
			 */
			
			if ($return = $Cache->fetch($mckey)) {
				
				/**
				 * Convert JSON back to an array if required
				 */
				
				if (!is_array($return) && is_string($return)) {
					$return = json_decode($return, true); 
				}
				
				return $return;
				
			}
			
			/**
			 * Try and get the WoE data from the database
			 */
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$query = "SELECT response FROM cache_woe WHERE hash = ?";
			
			if ($return = $Database->fetchOne($query, md5($mckey))) {
				
				$return = json_decode($return, true);
				
				$Cache->save($mckey, $return, $expiry); 
				
				return $return;
				
			}
			
			/**
			 * Nothing found in our cache - look it up
			 */
				
			$Config = AppCore::getConfig(); 
			
			$latlng = $lookup;
			
			if (preg_match("@[a-zA-Z]+@", $lookup) || strpos($lookup, ",")) {
				$lookup = sprintf("places.q('%s')", $lookup);
			} else {
				$lookup = sprintf("place/%s", $lookup);
			}
			
			if ($types === false) {
				$url = sprintf("http://where.yahooapis.com/v1/%s?lang=en&appid=%s&format=json", $lookup, $Config->Yahoo->ApplicationID);
			} else {
				$url = sprintf("http://where.yahooapis.com/v1/places\$and(.q('%s'),.type(%s))?lang=en&appid=%s&format=json", $latlng, implode(",", $types), $Config->Yahoo->ApplicationID);
			}
					
			/**
			 * Attempt to fetch the WoE data from our local cache
			 */
			
			if (strpos($lookup, ",") !== false) {
				$tmp = str_replace("places.q('", "", str_replace("')", "", $lookup));
				$tmp = explode(",", $tmp);
				$return = PlaceUtility::LatLonWoELookup($tmp[0], $tmp[1]);
				
				$Cache->save($mckey, $return, strtotime("+1 hour")); 
				
				return $return;
			}
			
			/**
			 * Try and fetch using GuzzleHTTP from the web service
			 */
			
			try {
				$GuzzleClient = new Client;
				$response = $GuzzleClient->get($url);
			} catch (RequestException $e) {
				switch ($e->getResponse()->getStatusCode()) {
					case 503 : 
						throw new Exception("Your call to Yahoo Web Services failed and returned an HTTP status of 503. That means: Service unavailable. An internal problem prevented us from returning data to you.");
						break;
					
					case 403 : 
						throw new Exception("Your call to Yahoo Web Services failed and returned an HTTP status of 403. That means: Forbidden. You do not have permission to access this resource, or are over your rate limit.");
						break;
					
					case 400 : 
						if (!$return = PlaceUtility::getViaCurl($url)) {
							throw new Exception(sprintf("Your call to Yahoo Web Services failed and returned an HTTP status of 400. That means:  Bad request. The parameters passed to the service did not match as expected. The exact error is returned in the XML/JSON response. The URL sent was: %s\n\n%s", $url, json_decode($e->getResponse()->getBody())));
						}
						
						break;
					
					default : 
						throw new Exception("Your call to Yahoo Web Services returned an unexpected HTTP status of: " . $e->getResponse()->getStatusCode());
				}
			}
			
			if (!$return && isset($response) && $response->getStatusCode() == 200) {
				$return = json_decode($response->getBody(), true);
			}
			
			$return['url'] = $url;
			
			/**
			 * Attempt to cache this data
			 */
			
			if ($return !== false) {
				
				/**
				 * Save it in MariaDB
				 */
				
				$data = [
					"hash" => md5($mckey),
					"response" => json_encode($return),
					"expiry" => date("Y-m-d H:i:s", $expiry)
				]; 
				
				$Database->insert("cache_woe", $data); 
				
				$rs = $Cache->save($mckey, $return, $expiry); 
				
				/**
				 * Verify that it actually saved in the cache handler. It's being a turd lately
				 */
				
				if (!$rs || json_encode($return) != json_encode($Cache->fetch($mckey))) {
					$Cache->save($mckey, json_encode($return), $expiry); 
				}
				
			}
			
			return $return;
		}
		
		/**
		 * Return an instance of this object from the cache or whateverzz
		 * @since Version 3.9.1
		 * @return \Railpage\Place
		 */
		
		public static function Factory($lat = false, $lon = false) {
			
			$Memcached = AppCore::getMemcached(); 
			$Redis = AppCore::getRedis(); 
			$Registry = Registry::getInstance(); 
			
			$regkey = sprintf("railpage.place;lat=%s;lon=%s", $lat, $lon); 
			
			try {
				$Place = $Registry->get($regkey); 
			} catch (Exception $e) {
				$Place = new Place($lat, $lon); 
				
				$Registry->set($regkey, $Place); 
			} 
				
			return $Place; 
			
		}
	}