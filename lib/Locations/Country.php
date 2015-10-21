<?php
	/**
	 * Lineside locations by country
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locations; 
	
	use stdClass;
	use Exception;
	use DateTime;
	use Railpage\Place;
	use Railpage\Debug;
	use Railpage\Url;
	use Railpage\ISO\ISO_3166;
	use Zend_Db_Expr;
	
	
	/**
	 * Country
	 * @since Version 3.8.7
	 */
	
	class Country extends Locations {
		
		/**
		 * Country name
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Country short code
		 * @var string $code
		 */
		
		public $code;
		
		/**
		 * URL for this country
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Centre point
		 * @var object $centre
		 */
		
		public $centre;
		
		/**
		 * Bounding box
		 * @var object $boundingBox
		 */
		
		public $boundingBox;
		
		/**
		 * Timezone
		 * @var string $timezone
		 */
		
		public $timezone;
		
		/**
		 * Constructor
		 * @param string $code
		 */
		
		public function __construct($code) {
			
			parent::__construct(); 
			
			$this->code = $code;
			$this->url = new Url("/locations/" . strtolower($this->code));
			
			$countries = ISO_3166::get_countries();
			$this->name = $countries[$code]['name'];
			
			Debug::RecordInstance();
			$timer = Debug::GetTimer(); 
			
			if (!$this->loadFromCache() || empty($this->name)) {
				$woe = Place::getWOEData(strtoupper($code));
			
				if (isset($woe['places']['place'][0]['name'])) {
					$woe = $woe['places']['place'][0];
					
					$data = [ 
						"point" => new Zend_Db_Expr(sprintf("GeomFromText('POINT(%s %s)')", $woe['centroid']['latitude'], $woe['centroid']['longitude'])),
						"bb_southwest" => new Zend_Db_Expr(sprintf("GeomFromText('POINT(%s %s)')", $woe['boundingBox']['southWest']['latitude'], $woe['boundingBox']['southWest']['longitude'])),
						"bb_northeast" => new Zend_Db_Expr(sprintf("GeomFromText('POINT(%s %s)')", $woe['boundingBox']['northEast']['latitude'], $woe['boundingBox']['northEast']['longitude'])),
						"country_code" => $woe['country attrs']['code'],
						"country_name" => $woe['name'],
						"timezone" => isset($woe['timezone']) ? $woe['timezone'] : ""
					];
					
					$this->db->insert("geoplace", $data);
					
					$this->name = $woe['name'];
					
					$this->centre = new stdClass; 
					$this->centre->lat = $woe['centroid']['latitude'];
					$this->centre->lon = $woe['centroid']['longitude'];
					
					$this->boundingBox = new stdClass;
					$this->boundingBox->northEast = new stdClass;
					$this->boundingBox->northEast->lat = $woe['boundingBox']['northEast']['latitude'];
					$this->boundingBox->northEast->lon = $woe['boundingBox']['northEast']['longitude'];
					
					$this->boundingBox->southWest = new stdClass;
					$this->boundingBox->southWest->lat = $woe['boundingBox']['southWest']['latitude'];
					$this->boundingBox->southWest->lon = $woe['boundingBox']['southWest']['longitude'];
				}

			}
			
			
			
			/**
			 * Fetch the WOE (Where On Earth) data from Yahoo
			 */
			
			
			Debug::LogEvent(__METHOD__, $timer);
		}
		
		/**
		 * Populate this country from cached data
		 * @since Version 3.10.0
		 * @return boolean
		 */
		
		private function loadFromCache() {
			
			$mckey = sprintf("railpage:locations.country=%s", strtoupper($this->code)); 
			
			if (!$row = $this->Memcached->fetch($mckey)) {
			
				$query = "SELECT *, X(point) AS centroid_lat, Y(point) AS centroid_lon,
					X(bb_southwest) AS bb_southwest_lat, Y(bb_southwest) AS bb_southwest_lon,
					X(bb_northeast) AS bb_northeast_lat, Y(bb_northeast) AS bb_northeast_lon
				 FROM geoplace 
				 WHERE country_name = ? 
					AND region_code IS NULL 
					AND neighbourhood IS NULL";
				
				$row = $this->db->fetchRow($query, strtoupper($this->code));
				
				if (is_array($row)) {
					$this->Memcached->save($mckey, $row, 0);
				}
				
			}
			
			if (!isset($row) || !is_array($row) || count($row) === 0) {
				return false;
			}
				
			#$this->name = $row['country_name'];
			$this->timezone = $row['timezone'];
			
			$this->centre = new stdClass; 
			$this->centre->lat = $row['centroid_lat'];
			$this->centre->lon = $row['centroid_lat'];
			
			$this->boundingBox = new stdClass;
			$this->boundingBox->northEast = new stdClass;
			$this->boundingBox->northEast->lat = $row['bb_northeast_lat'];
			$this->boundingBox->northEast->lon = $row['bb_northeast_lon'];
			
			$this->boundingBox->southWest = new stdClass;
			$this->boundingBox->southWest->lat = $row['bb_southwest_lat'];
			$this->boundingBox->southWest->lon = $row['bb_southwest_lat'];
			
			return true;

		}
		
		/**
		 * Get regions within this country
		 * @return array
		 * @param string $country Kept in for backwards compatibility with parent::getRegions()
		 */
		
		public function getRegions($country = false) {
			$query = "SELECT COUNT(l.id) AS count, l.region_slug AS slug,
				g.region_name AS name, g.region_code, g.timezone
				FROM location AS l 
					LEFT JOIN geoplace AS g ON l.geoplace = g.id 
				WHERE l.country = ? 
				GROUP BY l.region 
				ORDER BY l.region ASC";
			
			$regions = array(); 
			
			foreach ($this->db->fetchAll($query, $this->code) as $row) {
				if (empty($row['slug'])) {
					$data = array(
						"region_slug" => $this->makeRegionSlug($row['name'])
					);
					
					$where = array(
						"region = ?" => $row['name'],
						"country = ?" => $this->code
					);
					
					$this->db->update("location", $data, $where);
					
					$row['slug'] = $this->makeRegionSlug($row['name']);
				}
				
				$row['url'] = $this->url . "/" . $row['slug'];
				$shortname = $row['region_code'];
				
				/**
				 * Get WOE data for this region
				 */
				
				/*
				#$woe = getWOEData($row['name'] . "," . $this->code); 
				$woe = Place::getWOEData($row['name'] . "," . $this->code); 
				
				
				if (isset($woe['places']['place'][0]['name'])) {
					$row['name'] = $woe['places']['place'][0]['name'];
				}
				
				if (!empty($this->timezone)) {
					$row['timezone'] = $this->timezone;
				}
				
				if (isset($woe['places']['place'][0]['timezone'])) {
					$row['timezone'] = $woe['places']['place'][0]['timezone'];
				}
				*/
				
				$row['glyph'] = strtolower(sprintf("map-%s", $this->code));
				
				/**
				 * Assign a map glyph
				 */
				
				switch (strtolower($this->code)) {
					
					case "au" :
						$row['glyph'] = strtolower(sprintf("map-%s-%s", $this->code, str_replace(array("ACT", "NSW", "QLD", "TAS", "VIC"), array("AC", "NW", "QL", "TS", "VC"), strtoupper($shortname))));
						break;
					
					case "gb" :
						$row['glyph'] = "map-uk";
						break;
					
					case "us" :
						$find = array(
							"alaska",
							"alabama",
							"arizona",
							"arkansas",
							"california",
							"colorado",
							"Connecticut",
							"delaware",
							"district of columbia",
							"florida",
							"georgia",
							"hawaii",
							"idaho",
							"illinois",
							"indiana",
							"iowa",
							"kansas",
							"kentucky",
							"louisiana",
							"maine",
							"maryland",
							"massachusetts",
							"michigan",
							"minnesota",
							"mississippi",
							"missouri",
							"montana",
							"nebraska",
							"nevada",
							"new hampshire",
							"new jersey",
							"new mexico",
							"new york",
							"north carolina",
							"north dakota",
							"ohio",
							"oklahoma",
							"oregon",
							"pennsylvania",
							"rhode island",
							"south carolina",
							"south dakota",
							"tennessee",
							"texas",
							"utah",
							"vermont",
							"virginia",
							"washington",
							"west virginia",
							"wisconsin",
							"wyoming"
						);
						
						$replace = array(
							"ak", "al", "az", "ar", "ca", "co", "ct", "de", "dc", "fl", "ga", "hi", "id", "il", "in", "ia", "ks", "ky", 
							"la", "me", "md", "ma", "mi", "mn", "ms", "mo", "mt", "ne", "nv", "nh", "nj", "nm", "ny", "nc", "nd", "oh",
							"ok", "or", "pa", "ri", "sc", "sd", "tn", "tx", "ut", "vt", "va", "wa", "wv", "wi", "wy"
						);
						
						$row['glyph'] = strtolower(sprintf("map-%s-%s", $this->code, str_ireplace($find, $replace, $shortname)));
						break;
				}
				
				$regions[] = $row;
			}
			
			return $regions;
		}
		
		/**
		 * Get locations within this country
		 * @return array
		 * @param string $region Kept for backwards compatibility with parent::getLocations()
		 * @param string $country Kept for backwards compatibility with parent::getLocations()
		 */
		
		public function getLocations($region = false, $country = false) {
			$query = "SELECT * FROM location WHERE country = ? ORDER BY name";
			
			$locations = array(); 
			
			foreach ($this->db->fetchAll($query, array($this->code)) as $row) {
				$row['url'] = $this->makeRegionPermalink($this->code, $row['region']) . "/" . $row['slug'];
				$locations[] = $row;
			}
			
			return $locations;
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
	