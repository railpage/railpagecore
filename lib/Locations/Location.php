<?php
	/**
	 * Locations module 
	 * @since Version 3.0
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locations;
	
	use Railpage\Place;
	use Exception;
	use DateTime;
	use Railpage\Images\Images;
	use Railpage\Images\Image;
	use Railpage\Images\MapImage;
	
	/**
	 * Location class
	 * @since Version 3.3
	 */

	class Location extends Locations {
		
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
		 * Constructor
		 * @since Version 3.0.1
		 * @version 3.7.5
		 * @param object $db
		 * @param int $location_id
		 */
		
		public function __construct() {
			parent::__construct(); 
			
			foreach (func_get_args() as $arg) {
				if (filter_var($arg, FILTER_VALIDATE_INT)) {
					$this->id = $arg;
					$this->load(); 
				}
			}
		}
		
		/**
		 * Load the location data
		 * @since Version 3.0.1
		 * @version 3.7.5
		 * @param int $location_id
		 * @return boolean
		 */
		
		public function load($location_id = FALSE) {
			if ($location_id) {
				$this->id = $location_id;
			}
			
			if (!$this->id) {
				throw new Exception("Location->load() : No location ID provided"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT location.*, u.username, count(locations_like.location_id) AS likes FROM location LEFT JOIN locations_like ON location.id = locations_like.location_id LEFT JOIN nuke_users AS u ON u.user_id = location.user_id WHERE location.id = '".$this->db->real_escape_string($this->id)."'";
				
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows == 1) {
						$row = $rs->fetch_assoc(); 
						
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
						$this->traffc		= $row['traffic'];
						$this->environment	= $row['environment'];
						$this->amenities	= $row['amenities'];
						$this->directions_pt	= $row['directions_pt'];
						$this->directions_driving	= $row['directions_driving'];
						$this->directions_parking	= $row['directions_parking'];
							
					} else {
						throw new Exception("Location->load() : Wrong number of locations returned"); 
						
						return false;
					}
				} else {
					throw new Exception($this->db->error."\n\n".$query); 
					
					return false;
				}
			} else {
				$query = "SELECT location.*, u.username, count(locations_like.location_id) AS likes FROM location LEFT JOIN locations_like ON location.id = locations_like.location_id LEFT JOIN nuke_users AS u ON u.user_id = location.user_id WHERE location.id = ?";
				
				if ($row = $this->db->fetchRow($query, $this->id)) {
					
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
				}
			}
			
			/**
			 * If the URL slug is empty, let's create one now
			 */
			
			if (empty($this->slug) && $this->id > 0) {
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
			
			$this->url = sprintf("%s/%s", $this->makeRegionPermalink(), $this->slug);
		}
		
		/**
		 * Create a URL slug
		 * @since Version 3.7.5
		 */
		
		private function createSlug() {
			// Assume ZendDB
			$proposal = create_slug($this->name);
			
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
		
		public function validate() {
			// TODO: Validate the object
			
			if (!filter_var($this->lat, FILTER_VALIDATE_FLOAT)) {
				throw new Exception("Cannot validate location - no latitude value"); 
			}
			
			if (!filter_var($this->lon, FILTER_VALIDATE_FLOAT)) {
				throw new Exception("Cannot validate location - no longitude value"); 
			}
				
			if (is_null(filter_var($this->country, FILTER_SANITIZE_STRING))) {
				throw new Exception("Cannot validate location - no country value"); 
			}
				
			if (is_null(filter_var($this->region, FILTER_SANITIZE_STRING))) {
				throw new Exception("Cannot validate location - no region value"); 
			}
			
			if (is_null(filter_var($this->name, FILTER_SANITIZE_STRING))) {
				throw new Exception("Cannot validate location - no name specified"); 
			}
			
			if (is_null(filter_var($this->desc, FILTER_SANITIZE_STRING))) {
				throw new Exception("Cannot validate location - no description specified"); 
			}
			
			if (is_null($this->traffic)) {
				$this->traffic = "";
			}
			
			if (is_null($this->environment)) {
				$this->environment = "";
			}
			
			if (is_null($this->amenities)) {
				$this->amenities = "";
			}
			
			if (is_null($this->directions_pt)) {
				$this->directions_pt = "";
			}
			
			if (is_null($this->directions_driving)) {
				$this->directions_driving = "";
			}
			
			if (is_null($this->directions_parking)) {
				$this->directions_parking = "";
			}
			
			if (!filter_var($this->zoom, FILTER_VALIDATE_INT)) {
				$this->zoom = 12;
			}
			
			if (!filter_var($this->active)) {
				$this->active = self::STATUS_INACTIVE; 
			}
			
			return true;
		}
		
		/**
		 * Commit changes to the database
		 * @since Version 3.0.1
		 * @version 3.7.5
		 * @return boolean
		 */
		
		public function commit() {
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
			
			if (empty($this->region) || empty($this->country)) {
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
					$url	= "http://www.geoplugin.net/extras/location.gp?lat=".$this->lat."&long=".$this->lon."&format=php";
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
			}
			
			/**
			 * Fill in the gaps with the new Place object
			 */
			
			if (empty($this->region) || empty($this->country)) {
				$Place = new Place($this->lat, $this->lon); 
				
				if (!empty($Place->Country->code)) {
					$this->country = $Place->Country->code;
				}
				
				if (!empty($Place->Region->name)) {
					$this->region = $Place->Region->name;
				}
			}
			
			if (empty($this->slug)) {
				$this->createSlug(); 
			}
			
			if (empty($this->topicid)) {
				$this->topicid = 0;
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
			
			$this->name = trim($this->name);
			
			/**
			 * Validate the data
			 */
			
			$this->validate(); 
			
			if ($this->db instanceof \sql_db) {
				try {
					$dataArray = array(); 
					
					$dataArray['lat'] 			= $this->db->real_escape_string($this->lat); 
					$dataArray['long'] 			= $this->db->real_escape_string($this->lon); 
					$dataArray['country'] 		= $this->db->real_escape_string($this->country); 
					$dataArray['region'] 		= $this->db->real_escape_string($this->region); 
					$dataArray['locality'] 		= $this->db->real_escape_string($this->locality); 
					$dataArray['name'] 			= $this->db->real_escape_string($this->name); 
					$dataArray['desc'] 			= $this->db->real_escape_string($this->desc); 
					$dataArray['topicid'] 		= $this->db->real_escape_string($this->topicid); 
					$dataArray['zoom'] 			= $this->db->real_escape_string($this->zoom); 
					$dataArray['active'] 		= $this->db->real_escape_string($this->active); 
					$dataArray['date_added'] 	= $this->db->real_escape_string($this->date_added); 
					$dataArray['date_modified'] = $this->db->real_escape_string($this->date_modified); 
					$dataArray['user_id']		= $this->db->real_escape_string($this->user_id); 
					$dataArray['slug']			= $this->db->real_escape_string($this->slug);
					$dataArray['traffic']		= $this->db->real_escape_string($this->traffic);
					$dataArray['environment']	= $this->db->real_escape_string($this->environment);
					$dataArray['amenities']		= $this->db->real_escape_string($this->amenities);
					$dataArray['directions_driving']	= $this->db->real_escape_string($this->directions_driving);
					$dataArray['directions_parking']	= $this->db->real_escape_string($this->directions_parking);
					$dataArray['directions_pt']			= $this->db->real_escape_string($this->directions_pt);
					
					if (!empty($this->id)) {
						$whereClause = array(); 
						$whereClause['id'] = $this->id;
						$query = $this->db->buildQuery($dataArray, "location", $whereClause); 
					} else {
						$query = $this->db->buildQuery($dataArray, "location"); 
					}
					
					if ($this->db->query($query)) {
						if (empty($this->id)) {
							$this->id = $this->db->insert_id;
						}
						
						return true;
					} else {
						throw new Exception($this->db->error);
						return false;
					}
				} catch (Exception $e) {
					throw new Exception($e->getMessage()); 
				}
			} else {
				
				$data = array(
					"lat" => $this->lat,
					"long" => $this->lon,
					"country" => $this->country,
					"region" => $this->region,
					"region_slug" => $this->makeRegionSlug(),
					"locality" => $this->locality,
					"name" => $this->name,
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
				} else {
					$this->db->insert("location", $data);
					$this->id = $this->db->lastInsertId();
					
					removeMemcacheObject("railpage:locations.newest");
				}
			}
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
			if (!$this->id || !$this->db) {
				return false;
			}
			
			$return = array(); 
			
			if ($this->db instanceof \sql_db) {
				$query = "CALL geophotos(".$this->db->real_escape_string($this->id).", ".$this->db->real_escape_string($this->photoRadius).", ".$this->db->real_escape_string($num).")"; 
				
				if ($this->db->multi_query($query)) {
					do {
						if ($rs = $this->db->store_result()) {
							
							while ($row = $rs->fetch_assoc()) {
								$return[$row['photo_id']] = $row; 
							}
							$rs->free(); 
							
							$square_size = 180;
							
							// Create a new image size
							foreach ($return as $key => $data) {
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
						}
					} while (@$this->db->next_result());
				} else {
					$return = false;
					trigger_error("Locations: could not retrieve photos for site ".$this->id); 
					trigger_error($this->db->error); 
					trigger_error($query);
				}
			} else {
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
			}
			
			return $return;
		}
		
		/**
		 * Check if user has "liked" this location
		 * @since Version 3.0 
		 * @param int $user_id
		 * @return boolean
		 */
		
		public function doesUserLike($user_id = false) {
			if (!$this->db) {
				return false;
			}
			
			if (!$user_id) {
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT * FROM locations_like WHERE location_id = '".$this->db->real_escape_string($this->id)."' AND user_id = '".$this->db->real_escape_string($user_id)."'";
				
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows == 0) {
						return false;
					} else { 
						return true; 
					}
				} else {
					trigger_error("Locations: Could not determine if user likes location ID ".$this->id); 
					trigger_error($this->db->error);
					trigger_error($query); 
					return false;
				} 
			} else {
				$query = "SELECT * FROM locations_like WHERE location_id = ? AND user_id = ?";
				
				if ($row = $this->db->fetchRow($query, array($this->id, $user_id))) {
					return true;
				} else {
					return false;
				}
			}
		}
		
		/**
		 * Recommend a location
		 * @since Version 3.0 
		 * @param int $user_id
		 * @return boolean
		 */
		 
		public function recommend($user_id) {
			if (!$this->id || !$user_id || !$this->db || $this->doesUserLike($user_id)) {
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "INSERT INTO locations_like (location_id, user_id) VALUES ('".$this->db->real_escape_string($this->id)."', '".$this->db->real_escape_string($user_id)."')";
				
				if (!$rs = $this->db->query($query)) {
					throw new Exception("Could not insert recommendation for location ID ".$this->id." for user ".$user_id."\n".$this->db->error."\n\n".$query); 
					return false;
				} else {
					return true;
				}
			} else {
				$data = array(
					"location_id" => $this->id,
					"user_id" => $user_id
				);
				
				$this->db->insert("locations_like", $data);
				return true;
			}
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
			
			try {
				$this->commit(); 
				return true;
			} catch (Exception $e) {
				throw new Exception($e->getMessage()); 
				return false;
			}
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
			
			if ($this->db instanceof \sql_db) {
				$query = "DELETE FROM location WHERE id = '".$this->db->real_escape_string($this->id)."'";
				
				if ($rs = $this->db->query($query)) {
					return true;
				} else {
					throw new Exception($this->db->error."\n\n".$query); 
					return false;
				}
			} else {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->delete("location", $where); 
				return true;
			}
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
	}
	