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
	
	class Base extends AppCore {
		
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
	}
	