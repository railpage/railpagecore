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
	use DateTime;
	use Railpage\Place;
	
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
		
		public function __construct($country, $region = false) {
			parent::__construct(); 
			
			/**
			 * Record this in the debug log
			 */
				
			if (function_exists("debug_recordInstance")) {
				debug_recordInstance(__CLASS__);
			}
			
			/**
			 * Start the debug timer
			 */
			
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			/**
			 * Fetch the WOE (Where On Earth) data from Yahoo
			 */
			
			if ($region == false && !preg_match("@[a-zA-Z]+@", $country)) {
				// Assume a WOE ID
				$woe = Place::getWOEData($country);
			} else {
				$woe = Place::getWOEData($region . ", " . strtoupper($country));
			}
			
			/**
			 * End the debug timer
			 */
				
			if (RP_DEBUG) {
				$site_debug[] = __CLASS__ . "::" . __FUNCTION__ . "() : fetched WOE data from Yahoo in " . round(microtime(true) - $debug_timer_start, 5) . "s";
			}
			
			if (isset($woe['places']['place'][0]['name'])) {
				$row = $woe['places']['place'][0];
				
				$this->slug = $region;
				$this->Country = new Country($country);
			} elseif (isset($woe['place'])) {
				$row = $woe['place'];
				
				$this->slug = $this->makeRegionSlug($row['name']);
			}
			
			if (isset($row)) {
				if (empty($this->Country->name) && !preg_match("@[a-zA-Z]+@", $country) && isset($row['country'])) {
					$this->Country = new Country($row['country']);
				}
				
				$this->name = $row['name'];
				$this->url = $this->Country->url . "/" . $this->slug;
				
				$this->centre = new stdClass; 
				$this->centre->lat = $row['centroid']['latitude'];
				$this->centre->lon = $row['centroid']['longitude'];
				
				$this->boundingBox = new stdClass;
				$this->boundingBox->northEast = new stdClass;
				$this->boundingBox->northEast->lat = $row['boundingBox']['northEast']['latitude'];
				$this->boundingBox->northEast->lon = $row['boundingBox']['northEast']['longitude'];
				
				$this->boundingBox->southWest = new stdClass;
				$this->boundingBox->southWest->lat = $row['boundingBox']['southWest']['latitude'];
				$this->boundingBox->southWest->lon = $row['boundingBox']['southWest']['longitude'];
				
				if (isset($row['timezone'])) {
					$this->timezone = $row['timezone'];
				}
			}
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
	}
?>