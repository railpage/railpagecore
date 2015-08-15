<?php
	/** 
	 * Loco liveries
	 * @since Version 3.2
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos\Liveries; 
	
	use Railpage\AppCore;
	use Exception;
	
	/**
	 * Base loco liveries class
	 * @since Version 3.2
	 */
	
	class Liveries extends AppCore {
		
		/**
		 * Return a list of livery IDs
		 * @since Version 3.2
		 * @return array
		 */
		
		public function listAll() {
			$query = "SELECT livery_id FROM loco_livery ORDER BY livery";
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query) as $row) {
				$return[] = $row['livery_id']; 
			}
			
			return $return;
		}
		
		/**
		 * Return a list of livery IDs
		 * @since Version 3.7.5
		 * @return array
		 */
		
		public function listAllFull() {
			$query = "SELECT * FROM loco_livery ORDER BY livery";
			
			$return = array();
			
			foreach ($this->db->fetchAll($query) as $row) {
				$Livery = new Livery($row['livery_id']);
				$row['url'] = $Livery->url;
					
				$return[] = $row;
			}
			
			return $return;
		}
		
		/**
		 * Find liveries from Flickr tags
		 * @since Version 3.2
		 * @param array $rawtags
		 * @return array
		 */
		
		public function findFromTag($rawtags = false) {
			$tags = array();
			
			foreach ($rawtags as $tag) {
				if (preg_match("@railpage\:livery\=([0-9]+)@", $tag, $matches)) {
					$tags[] = $matches[1];
				}
			}
			
			return $tags;
		}
		
		/**
		 * Get all the countries
		 * @since Version 3.10.0
		 * @return array
		 */
		
		public function getAllCountries() {
			
			$query = "SELECT l.country, g.country_name, (SELECT COUNT(*) FROM loco_livery WHERE loco_livery.country = l.country) AS num
				FROM loco_livery AS l
					LEFT JOIN geoplace AS g ON g.country_code = l.country 
				GROUP BY l.country 
				ORDER BY g.country_name";
			
			return $this->db->fetchAll($query); 
			
		}
		
		/**
		 * Get regions within a country
		 * @since Version 3.10.0
		 * @param string $region
		 * @return array
		 */
		
		public function getRegionsInCountry($country) {
			
			$country = strtoupper($country); 
			$params = [ $country, $country, $country ];
			
			$query = "SELECT l.region, COALESCE(g.region_name, 'National') AS region_name, (SELECT COUNT(*) FROM loco_livery WHERE loco_livery.country = l.country AND loco_livery.region = l.region AND loco_livery.country = ?) AS num
				FROM loco_livery AS l
					LEFT JOIN geoplace AS g ON g.region_code = l.region AND g.country_code = ?
				WHERE l.country = ?
				GROUP BY l.region
				ORDER BY g.region_name";
			
			return $this->db->fetchAll($query, $params);
			
		}
	}
	