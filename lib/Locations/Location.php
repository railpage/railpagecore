<?php
	/**
	 * Locations module 
	 * @since Version 3.0
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locations;
	
	use Railpage\Place;
	use Railpage\PlaceUtility;
	use Exception;
	use InvalidArgumentException;
	use DateTime;
	use Railpage\Images\Images;
	use Railpage\Images\Image;
	use Railpage\Images\MapImage;
	use Railpage\ContentUtility;
	use Railpage\Users\User;
	use Railpage\Users\Factory as UserFactory;
	use Railpage\Url;
	
	/**
	 * Location class
	 * @since Version 3.3
	 */

	class Location extends Locations {
		
		/**
		 * Registry cache key
		 * @since Version 3.9.1
		 * @const string REGISTRY_KEY
		 */
		
		const REGISTRY_KEY = "railpage.location=%d";
		
		/**
		 * Status: inactive
		 * @since Version 3.9.1
		 * @const int STATUS_INACTIVE
		 */
		
		const STATUS_INACTIVE = 0; 
		
		/**
		 * Status: active
		 * @since Version 3.9.1
		 * @const int STATUS_ACTIVE
		 */
		
		const STATUS_ACTIVE = 1;
		
		/**
		 * Location ID
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $id
		 */
		
		public $id; 
		
		/**
		 * Location latitude
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var string $lat
		 */ 
		
		public $lat;
		
		/**
		 * Location longitude
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var string $lon
		 */ 
		
		public $lon;
		
		/**
		 * Location country
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var string $country
		 */ 
		
		public $country;
		
		/**
		 * Location region
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var string $region
		 */ 
		
		public $region;
		
		/**
		 * Location locality
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var string $locality
		 */ 
		
		public $locality;
		
		/**
		 * Location name
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var string $name
		 */ 
		
		public $name;
		
		/**
		 * Location description
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var string $desc
		 */ 
		
		public $desc;
		
		/**
		 * Location forum topic ID
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int topicid
		 */ 
		
		public $topicid;
		
		/**
		 * Location map zoom
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $zoom
		 */ 
		
		public $zoom;
		
		/**
		 * Location active
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $active
		 */ 
		
		public $active;
		
		/**
		 * Location date added
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $date_added
		 */ 
		
		public $date_added;
		
		/**
		 * Location date modified
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $date_modified
		 */ 
		
		public $date_modified;
		
		/**
		 * Location author user ID
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $user_id
		 */ 
		
		public $user_id;
		
		/**
		 * Location author username
		 * @since Version 3.6
		 * @version 3.6
		 * @var string $username
		 */ 
		
		public $username;
		
		/**
		 * Location number of likes
		 * @since Version 3.5
		 * @version 3.5
		 * @var int $likes
		 */ 
		
		public $likes;
		
		/**
		 * URL Slug
		 * @since Version 3.7.5
		 * @var string $slug
		 */
		
		public $slug;
		
		/**
		 * Railway type and traffic
		 * @since Version 3.7.5
		 * @var string $traffic
		 */
		
		public $traffic;
		
		/**
		 * Environment
		 * @since Version 3.7.5
		 * @var string $environment
		 */
		
		public $environment;
		
		/**
		 * Directions - driving
		 * @since Version 3.7.5
		 * @var string $directions_driving
		 */
		
		public $directions_driving;
		
		/**
		 * Directions - car parking
		 * @since Version 3.7.5
		 * @var string $directions_parking
		 */
		
		public $directions_parking;
		
		/**
		 * Directions - public transport
		 * @since Version 3.7.5
		 * @var string $directions_pt
		 */
		
		public $directions_pt;
		
		/** 
		 * Amenities
		 * @since Version 3.7.5
		 * @var string $amenities
		 */
		
		public $amenities;
		
		/**
		 * URL
		 * @since Version 3.8
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Geographic region containing this location
		 * @since Version 3.8.7
		 * @var \Railpage\Locations\Region $Region
		 */
		
		public $Region;
		
		/**
		 * Geoplace ID
		 * @since Version 3.9.1
		 * @var int $geoplace_id
		 */
		
		private $geoplace_id;
		
		/**
		 * Constructor
		 * @since Version 3.0.1
		 * @version 3.7.5
		 * @param int $location_id
		 */
		
		public function __construct($location_id = false) {
			
			parent::__construct(); 
			
			if ($id = filter_var($location_id, FILTER_VALIDATE_INT)) {
				$this->id = $id; 
				$this->load(); 
			} elseif (is_string($location_id) && $id = filter_var($location_id, FILTER_SANITIZE_STRING)) {
				$this->id = $this->db->fetchOne("SELECT id FROM location WHERE slug = ?", $id); 
				
				if (filter_var($this->id, FILTER_VALIDATE_INT)) {
					$this->load(); 
				}
			}
		}
		
		/**
		 * Load the location data
		 * @since Version 3.0.1
		 * @version 3.7.5
		 * @return boolean
		 */
		
		private function load() {
			
			$this->mckey = sprintf("railpage:locations.location=%d", $this->id); 
			
			if (!$row = $this->Memcached->fetch($this->mckey)) {
				$query = "SELECT location.*, u.username, count(locations_like.location_id) AS likes FROM location LEFT JOIN locations_like ON location.id = locations_like.location_id LEFT JOIN nuke_users AS u ON u.user_id = location.user_id WHERE location.id = ?";
				
				$row = $this->db->fetchRow($query, $this->id);
			}
			
			if (!isset($row) || !is_array($row) || count($row) === 0 || !filter_var($row['id'], FILTER_VALIDATE_INT)) {
				throw new Exception("Unable to fetch data for location ID " . $this->id); 
			}
			
			$this->lat 			= $row['lat']; 
			$this->lon 			= $row['long']; 
			$this->country		= $row['country']; 
			$this->region		= $row['region']; 
			$this->locality		= $row['locality']; 
			$this->name			= $row['name']; 
			$this->desc			= $row['desc']; 
			$this->topicid		= $row['topicid']; 
			$this->zoom			= $row['zoom']; 
			$this->active		= $row['active']; 
			$this->date_added	= $row['date_added']; 
			$this->date_modified= $row['date_modified']; 
			$this->user_id		= $row['user_id']; 
			$this->slug			= $row['slug'];
			$this->traffic		= $row['traffic'];
			$this->environment	= $row['environment'];
			$this->amenities	= $row['amenities'];
			$this->directions_pt	= $row['directions_pt'];
			$this->directions_driving	= $row['directions_driving'];
			$this->directions_parking	= $row['directions_parking'];
			$this->geoplace_id = isset($row['geoplace_id']) ? $row['geoplace_id'] : 0;
		
			/**
			 * If the URL slug is empty, let's create one now
			 */
			
			if (empty($this->slug) && filter_var($this->id, FILTER_VALIDATE_INT)) {
				$this->createSlug(); 
				$this->commit();
			}
			
			if (isset($row)) {
				$this->Region = new Region($row['country'], $row['region_slug']);
			}
			
			if (!empty($this->lat) && !empty($this->lon)) {
				$this->Image = new MapImage($this->lat, $this->lon);
				$this->Image->title = $this->name . ", " . $this->Region->Country->name;
			}
			
			$this->url = new Url(sprintf("%s/%s", $this->makeRegionPermalink(), $this->slug));
			
			if ($this->geoplace_id == 0) {
				$this->updateGeoplace();
			}
			
		}
		
		/**
		 * Create a URL slug
		 * @since Version 3.7.5
		 * @return void
		 */
		
		private function createSlug() {
			
			$proposal = ContentUtility::generateUrlSlug($this->name);
			
			$result = $this->db->fetchAll("SELECT id FROM location WHERE slug = ?", $proposal); 
			
			if (count($result)) {
				$proposal .= count($result);
			}
			
			$this->slug = $proposal;
			
		}
		
		/** 
		 * Validate the location before committing changes
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @return boolean
		 * @todo var validation
		 */
		
		private function validate() {
			
			if (!filter_var($this->lat, FILTER_VALIDATE_FLOAT)) {
				throw new Exception("Cannot validate location - no latitude value"); 
			}
			
			if (!filter_var($this->lon, FILTER_VALIDATE_FLOAT)) {
				throw new Exception("Cannot validate location - no longitude value"); 
			}
			
			/*	
			if (empty(filter_var($this->country, FILTER_SANITIZE_STRING))) {
				throw new Exception("Cannot validate location - no country value"); 
			}
				
			if (empty(filter_var($this->region, FILTER_SANITIZE_STRING))) {
				throw new Exception("Cannot validate location - no region value"); 
			}
			*/
			
			if (empty(filter_var($this->name, FILTER_SANITIZE_STRING))) {
				throw new Exception("Cannot validate location - no name specified"); 
			}
			
			if (empty(filter_var($this->desc, FILTER_SANITIZE_STRING))) {
				throw new Exception("Cannot validate location - no description specified"); 
			}
			
			$nulls = [ "traffic", "environment", "amenities", "directions_pt", "directions_driving", "directions_parking" ];
			
			foreach ($nulls as $var) {
				if (is_null($this->$var)) {
					$this->$var = "";
				}
			}
			
			if (!filter_var($this->zoom, FILTER_VALIDATE_INT)) {
				$this->zoom = 12;
			}
			
			if (!filter_var($this->active)) {
				$this->active = self::STATUS_INACTIVE; 
			}
			
			if (empty($this->slug)) {
				$this->createSlug(); 
			}
			
			if (!filter_var($this->topicid, FILTER_VALIDATE_INT)) {
				$this->topicid = 0;
			}
			
			$this->geocode(); 
			
			return true;
		}
		
		/**
		 * Set the geocode data for this location
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function geocode() {
			
			if (!empty($this->region) && !empty($this->country) && !empty($this->locality)) {
				return;
			}
			
			$woe = PlaceUtility::LatLonWoELookup($this->lat, $this->lon);
			$woe = PlaceUtility::formatWoE($woe);
			
			$this->country = $woe['country_code'];
			$this->region = $woe['region_code'];
			$this->locality = $woe['neighbourhood'];
			$this->neighbourhood = null; // $this->neighbourhood is ignored
			
			return;
			
			/*
			
			// Fetch geodata and populate the vars
			//$url	= "http://maps.google.com/maps/geo?q=".$this->lat.",".$this->lon."&output=json&sensor=false";
			$url = "http://maps.googleapis.com/maps/api/geocode/json?latlng=" . $this->lat . "," . $this->lon . "&sensor=false";
			$ch		= curl_init();
			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			
			$data		= curl_exec($ch);
			$jsondata	= json_decode($data, true); 
			curl_close($ch);
			
			if (isset($jsondata['results'][0]['address_components'])) {
				$row = $jsondata['results'][0]['address_components']; 
			}
			
			if (isset($jsondata['Placemark']) && $area = $jsondata['Placemark'][0]['AddressDetails']['Country']) {
				$this->country 			= $area['CountryNameCode']; 
				$this->region 			= isset($area['AdministrativeArea']['AdministrativeAreaName']) ? $area['AdministrativeArea']['AdministrativeAreaName'] : NULL; 
				$this->locality 		= isset($area['AdministrativeArea']['Locality']['LocalityName']) ? $area['AdministrativeArea']['Locality']['LocalityName'] : NULL; 
				$this->neighbourhood 	= isset($area['CountryNameCode']['someotherarea']) ? $area['CountryNameCode']['someotherarea'] : NULL; 
			} elseif (isset($row)) {
				// Loop through the results and try to populate the object
				foreach ($row as $area) {
					if ($area['types'][0] == "country") {
						$this->country = $area['short_name']; 
					}
					
					if ($area['types'][0] == "administrative_area_level_2") {
						$this->region = $area['long_name']; 
					}
					
					if ($area['types'][0] == "administrative_area_level_3") {
						$this->neighbourhood = $area['long_name']; 
					}
					
					if ($area['types'][0] == "locality") {
						$this->locality = $area['long_name']; 
					}
				}
			}
			
			if (empty($this->country) || empty($this->region) || empty($this->locality)) {
				// Google doesn't give data in a consistent bloody format - go here instead
				$url	= sprintf("http://www.geoplugin.net/extras/location.gp?lat=%s&long=%s&format=php", $this->lat, $this->lon);
				$ch		= curl_init();
				
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				
				$geodata	= curl_exec($ch);
				$geodata	= unserialize($geodata); 
				curl_close($ch);
				
				$this->country			= $geodata['geoplugin_countryCode']; 
				$this->region			= $geodata['geoplugin_region']; 
				$this->locality			= $geodata['geoplugin_place']; 
				$this->neighbourhood	= NULL;
			}
			
			return;
			*/
			
		}
		
		/**
		 * Commit changes to the database
		 * @since Version 3.0.1
		 * @version 3.7.5
		 * @return boolean
		 */
		
		public function commit() {
			
			/**
			 * Fill in the gaps with the new Place object
			 */
			
			if (empty($this->region) || empty($this->country)) {
				$Place = Place::Factory($this->lat, $this->lon); 
				
				if (!empty($Place->Country->code)) {
					$this->country = $Place->Country->code;
				}
				
				if (!empty($Place->Region->name)) {
					$this->region = $Place->Region->name;
				}
			}
			
			$find = array(
				"@Victoria@i",
				"@New South Wales@i",
				"@Queensland@i",
				"@South Australia@i",
				"@Tasmania@i",
				"@Northern Territory@i",
				"@Western Australia@i",
				"@Australian Capital Territory@i"
			);
			
			$replace = array(
				"VIC", 
				"NSW",
				"QLD",
				"SA",
				"TAS",
				"NT",
				"WA", 
				"ACT"
			);
			
			$this->region = preg_replace($find, $replace, $this->region);
			
			// Defaults for new locations
			if (empty($this->id)) {
				$this->active = 0;
				$this->date_added 		= time();
				$this->date_modified	= time();
			}
			
			/**
			 * Validate the data
			 */
			
			$this->validate(); 
			
				
			$data = array(
				"lat" => $this->lat,
				"long" => $this->lon,
				"country" => $this->country,
				"region" => $this->region,
				"region_slug" => $this->makeRegionSlug(),
				"locality" => $this->locality,
				"name" => trim($this->name),
				"desc" => $this->desc,
				"topicid" => $this->topicid,
				"zoom" => $this->zoom,
				"active" => $this->active,
				"date_added" => $this->date_added,
				"date_modified" => $this->date_modified,
				"user_id" => $this->user_id,
				"slug" => $this->slug,
				"traffic" => $this->traffic,
				"environment" => $this->environment,
				"directions_driving" => $this->directions_driving,
				"directions_parking" => $this->directions_parking,
				"directions_pt" => $this->directions_pt,
				"amenities" => $this->amenities
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT) && $this->id > 0) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("location", $data, $where);
				
				$this->Memcached->delete($this->mckey); 
			} else {
				$this->db->insert("location", $data);
				$this->id = $this->db->lastInsertId();
				
				$this->Memcached->delete("railpage:locations.newest");
				$this->mckey = sprintf("railpage:locations.location=%d", $this->id); 
			}
			
			$this->updateGeoplace(); 
		}
		
		/**
		 * Update the geoplace linked to this location
		 * @since Version 3.9.1
		 * @return void
		 */
		 
		private function updateGeoplace() {
			
			$id = PlaceUtility::findGeoPlaceID($this->lat, $this->lon); 
			
			if ($id != $this->geoplace_id) {
				$data = [ "geoplace" => $id ];
				$where = [ "id = ?" => $this->id ];
				
				$this->db->update("location", $data, $where); 
				$this->Memcached->delete($this->mckey); 
			}
			
			return;
			
		}
		
		/**
		 * Get photos for a given location
		 * 
		 * This function uses mysqli::multi_query() as it was buggering up all subsequent SQL queries on the page
		 * @since Version 3.0
		 * @version 3.7.5
		 * @param int $num
		 * @param int $start
		 */
		 
		public function getPhotosForSite($num = 10, $start = 0) {
			
			$Place = new Place($this->lat, $this->lon); 
			
			return $Place->getPhotosFromSphinx($num); 
			
			if (!$this->id || !$this->db) {
				return false;
			}
			
			$cachekey = sprintf("%s;photos;num=%s;start=%s", $this->mckey, intval($num), intval($start)); 
			
			if ($result = $this->Memcached->fetch($cachekey)) {
				return $result;
			}
			
			$return = array(); 
			
			// Ditch the stored procedure. Just do it through the database connection
			
			$lat = $this->lat;
			$lon = $this->lon;
			
			$query = "SELECT flickr_geodata.*, 3956 * 2 * ASIN(SQRT(POWER(SIN((" . $lat . " - flickr_geodata.lat) * pi() / 180 / 2), 2) + COS(" . $lat . " * pi() / 180) * COS(" . $lat . " * pi() / 180) * POWER(SIN((" . $lon . " - flickr_geodata.lon) * pi() / 180 / 2), 2))) AS distance 
				FROM flickr_geodata, location 
				WHERE location.id = " . $this->id . "
					AND flickr_geodata.lon BETWEEN (
						" . $lon . " - " . $this->photoRadius . " / abs(cos(radians(" . $lat . ")) * 69)
					) AND (
						" . $lon . " + " . $this->photoRadius . " / abs(cos(radians(" . $lat . ")) * 69)
					)
					AND flickr_geodata.lat BETWEEN (
						" . $lat . " - (" . $this->photoRadius . " / 69) 
					) AND (
						" . $lat . " + (" . $this->photoRadius . " / 69) 
					)
				HAVING distance < " . $this->photoRadius . "
				ORDER BY distance
				LIMIT ?";
				
			$params = array(
				$num
			);
			
			$return = array(); 
			$square_size = 180;
			
			foreach ($this->db->fetchAll($query, $params) as $data) {
				$key = $data['photo_id'];
				
				$return[$key]['size_sq'] = RP_PROTOCOL . "://" . filter_input(INPUT_SERVER, "HTTP_HOST", FILTER_SANITIZE_STRING) . "/image_resize.php?q=90&w=" . $square_size . "&h=" . $square_size . "&square=true&image=" . str_replace("?zz=1", "", $data['size4']);
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
			
			$this->Memcached->save($cachekey, $return, strtotime("+1 day")); 
			
			return $return;
		}
		
		/**
		 * Check if user has "liked" this location
		 * @since Version 3.0 
		 * @param int $user_id
		 * @return boolean
		 */
		
		public function doesUserLike($user_id = false) {
			
			if ($user_id instanceof User) {
				$user_id = $user_id->id;
			}
			
			if (!filter_var($user_id, FILTER_VALIDATE_INT)) { 
				return false;
			}
			
			$query = "SELECT * FROM locations_like WHERE location_id = ? AND user_id = ?";
			
			if ($row = $this->db->fetchRow($query, array($this->id, $user_id))) {
				return true;
			}
			
			return false;
			
		}
		
		/**
		 * Recommend a location
		 * @since Version 3.0 
		 * @param int $user_id
		 * @return boolean
		 */
		 
		public function recommend($user_id = false) {
			
			if ($user_id instanceof User) {
				$user_id = $user_id->id;
			}
			
			if (!filter_var($user_id, FILTER_VALIDATE_INT)) { 
				throw new InvalidArgumentException("No user ID provided"); 
			}
			
			if ($this->doesUserLike($user_id)) {
				return false;
			}
			
			$data = array(
				"location_id" => $this->id,
				"user_id" => $user_id
			);
			
			$this->db->insert("locations_like", $data);
			return true;
			
		}
		
		/**
		 * Approve a location
		 * @since Version 3.6
		 * @return boolean
		 */
		
		public function approve() {
			if (empty($this->id)) {
				throw new Exception("Cannot approve location - no location created yet"); 
				return false;
			}
			
			$this->active = 1; 
			$this->date_modified = time(); 
			
			$this->commit(); 
		}
		
		/**
		 * Reject / delete a location
		 * @since Version 3.6
		 * @return boolean
		 */
		
		public function reject() {
			if (empty($this->id)) {
				throw new Exception("Cannot reject location - no location created yet"); 
				return false;
			}
			
			$where = array(
				"id = ?" => $this->id
			);
			
			$this->db->delete("location", $where); 
			return true;
		}
		
		/**
		 * Get contributors of this location
		 * @since Version 3.7.5
		 * @return array
		 */
		
		public function getContributors() {
			$query = "SELECT username FROM nuke_users WHERE user_id = ?"; 
			$return = array(); 
			
			$return[$this->user_id] = $this->db->fetchOne($query, $this->user_id); 
			
			$query = "SELECT DISTINCT l.user_id, u.username FROM log_general AS l LEFT JOIN nuke_users AS u ON u.user_id = l.user_id WHERE l.module = ? AND l.key = ? AND l.value = ? AND l.user_id != ?";
			
			foreach ($this->db->fetchAll($query, array("locations", "id", $this->id, $this->user_id)) as $row) {
				$return[$row['user_id']] = $row['username']; 
			}
			
			return $return;
		}
		
		/**
		 * Get dates for this location
		 * @since Version 3.8.7
		 * @return array
		 */
		
		public function getDates() {
			return $this->db->fetchAll("SELECT * FROM location_date WHERE location_id = ?", $this->id); 
		}
		
		/**
		 * Get an array of this data
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getArray() {
			return array(
				"id" => $this->id,
				"name" => $this->name,
				"url" => $this->url instanceof Url ? $this->url->getURLs() : array("url" => $this->url)
			);
		}
		
		/**
		 * Get this object as a string
		 * @since Version 3.9.1
		 * @return string
		 */
		
		public function __toString() {
			
			return $this->name;
			
		}
	}
	