<?php
	/**
	 * Lineside locations by region
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locations; 
	
	use stdClass;
	use Exception;
	use InvalidArgumentException;
	use DateTime;
	use Railpage\Place;
	use Railpage\Url;
	use Railpage\Debug;
	
	/**
	 * Class
	 */
	
	class Region extends Locations {
		
		/**
		 * Region name
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Region short code
		 * @var string $code
		 */
		
		public $code;
		
		/**
		 * Region URL slug
		 * @var string $slug
		 */
		
		public $slug;
		
		/**
		 * URL for this region
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
		 * Country containing this region
		 * @var \Railpage\Locations\Country $Country
		 */
		
		public $Country;
		
		/**
		 * Constructor
		 * @param string $country
		 * @param string $region
		 */
		
		public function __construct($country = null, $region = false) {
			
			Debug::RecordInstance(); 
			$timer = Debug::GetTimer(); 
			
			if (is_null($country)) {
				throw new InvalidArgumentException("No country was specified"); 
			}
			
			parent::__construct(); 
			
			$this->load($country, $region); 
			
			Debug::LogEvent(__METHOD__, $timer); 
			
		}
		
		/**
		 * Populate this object
		 * @since Version 3.9.1
		 * @param string $country
		 * @param string $region
		 * @return void
		 */
		
		private function load($country, $region) {
			
			/**
			 * Fetch the WOE (Where On Earth) data from Yahoo
			 */
			
			$woe = $this->fetchWoE($country, $region);
			
			if (empty($this->Country->name) && !preg_match("@[a-zA-Z]+@", $country) && isset($woe['country'])) {
				$this->Country = new Country($woe['country']);
			}
			
			$this->name = $woe['name'];
			$this->url = new Url(sprintf("%s/%s", $this->Country->url, $this->slug));
			
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
		
		/**
		 * Get the WoE for this place
		 * @since Version 3.9.1
		 * @param string $country
		 * @param string $region
		 * @return array
		 */
		
		private function fetchWoE($country, $region) {
			
			if ($region === false && !preg_match("@[a-zA-Z]+@", $country)) {
				// Assume a WOE ID
				$woe = Place::getWOEData($country);
			} else {
				$woe = Place::getWOEData($region . ", " . strtoupper($country));
			}
			
			if (isset($woe['places']['place'][0]['name'])) {
				$this->slug = $region;
				$this->Country = new Country($country);
				
				return $woe['places']['place'][0];
			}
			
			if (isset($woe['place'])) {
				$this->slug = $this->makeRegionSlug($woe['place']['name']);
				
				return $woe['place'];
			}
			
			return $woe;
			
		}
		
		/**
		 * Get locations within this country
		 *
		 * Parameters kept to maintain compatibility with parent::getLocations()
		 * @return array
		 * @param string $region
		 * @param string $country
		 */
		
		public function getLocations($region = false, $country = false) {
			
			$query = "SELECT * FROM location WHERE country = ? AND region_slug = ? ORDER BY name";
			
			$locations = array(); 
			
			foreach ($this->db->fetchAll($query, array($this->Country->code, $this->slug)) as $row) {
				$row['url'] = $this->url . "/" . $row['slug'];
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
			
			return sprintf("%s, %s", $this->name, $this->Country);
			
		}
	}
	