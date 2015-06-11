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
			
			Debug::RecordInstance();
			$timer = Debug::GetTimer(); 
			
			/**
			 * Fetch the WOE (Where On Earth) data from Yahoo
			 */
			
			$woe = Place::getWOEData(strtoupper($code));
			
			if (isset($woe['places']['place'][0]['name'])) {
				$woe = $woe['places']['place'][0];
				
				$this->name = $woe['name'];
				
				if (isset($woe['country attrs'])) {
					$this->code = $woe['country attrs']['code'];
					$this->url = new Url("/locations/" . strtolower($this->code));
				}
				
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
				
				if (isset($woe['timezone'])) {
					$this->timezone = $woe['timezone'];
				}
			}
			
			Debug::LogEvent(__METHOD__, $timer);
		}
		
		/**
		 * Get regions within this country
		 * @return array
		 * @param string $country Kept in for backwards compatibility with parent::getRegions()
		 */
		
		public function getRegions($country = false) {
			$query = "SELECT COUNT(id) AS count, region AS name, region_slug AS slug FROM location WHERE country = ? GROUP BY region ORDER BY region ASC";
			
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
				
				/**
				 * Get WOE data for this region
				 */
				
				$woe = getWOEData($row['name'] . "," . $this->code); 
				$shortname = $row['name'];
				
				if (isset($woe['places']['place'][0]['name'])) {
					$row['name'] = $woe['places']['place'][0]['name'];
				}
				
				if (!empty($this->timezone)) {
					$row['timezone'] = $this->timezone;
				}
				
				if (isset($woe['places']['place'][0]['timezone'])) {
					$row['timezone'] = $woe['places']['place'][0]['timezone'];
				}
				
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
	