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
			$this->url = "/locations/" . strtolower($this->code);
			
			/**
			 * Record this in the debug log
			 */
				
			debug_recordInstance(__CLASS__);
			
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
			
			$woe = getWOEData(strtoupper($code));
			
			/**
			 * End the debug timer
			 */
				
			if (RP_DEBUG) {
				$site_debug[] = __CLASS__ . "::" . __FUNCTION__ . "() : fetched WOE data from Yahoo in " . round(microtime(true) - $debug_timer_start, 5) . "s";
			}
			
			if (isset($woe['places']['place'][0]['name'])) {
				$woe = $woe['places']['place'][0];
				
				$this->name = $woe['name'];
				
				if (isset($woe['country attrs'])) {
					$this->code = $woe['country attrs']['code'];
					$this->url = "/locations/" . strtolower($this->code);	
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
				
				if (isset($woe['places']['place'][0]['name'])) {
					$row['name'] = $woe['places']['place'][0]['name'];
				}
				
				if (!empty($this->timezone)) {
					$row['timezone'] = $this->timezone;
				}
				
				if (isset($woe['places']['place'][0]['timezone'])) {
					$row['timezone'] = $woe['places']['place'][0]['timezone'];
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
	}
?>