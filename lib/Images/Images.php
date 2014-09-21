<?php
	/**
	 * Images master class for Railpage
	 * 
	 * Find an image by provider ID etc
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images;
	
	use Railpage\AppCore;
	use Exception;
	use DateTime;
	
	/**
	 * Images class
	 * @since Version 3.8.7
	 */
	
	class Images extends AppCore {
		
		/**
		 * Constructor
		 */
		
		public function __construct() {
			parent::__construct(); 
			
			/**
			 * Record this in the debug log
			 */
				
			debug_recordInstance(__CLASS__);
		}
		
		/**
		 * Find an image by provider and provider image ID
		 * @since Version 3.8.7
		 * @param string $provider
		 * @param int $id
		 * @throws \Exception if $provider is null
		 * @throws \Exception if $photo_id is null
		 */
		
		public function findImage($provider = NULL, $photo_id = NULL) {
			if (is_null($provider)) {
				throw new Exception("Cannot lookup image from image provider - no provider given (hint: Flickr, WestonLangford)");
			}
			
			if (!filter_var($photo_id, FILTER_VALIDATE_INT) || $photo_id === 0) {
				throw new Exception("Cannot lookup image from image provider - no provider image ID given");
			}
			
			if ($id = $this->db->fetchOne("SELECT id FROM image WHERE provider = ? AND photo_id = ?", array($provider, $photo_id))) {
				if (filter_var($id, FILTER_VALIDATE_INT)) {
					return new Image($id);
				}
			}
			
			$Image = new Image;
			$Image->provider = $provider;
			$Image->photo_id = $photo_id;
			
			$Image->populate();
			
			return $Image;
		}
		
		/**
		 * Find images of a locomotive
		 * @since Version 3.8.7
		 * @param int $loco_id
		 * @param int $livery_id
		 * @return array
		 */
		
		public function findLocoImage($loco_id = NULL, $livery_id = NULL) {
			if (is_null($loco_id)) {
				throw new Exception("Cannot find loco image - no loco ID given");
			}
			
			if (is_null($livery_id)) {
				$query = "SELECT i.id FROM image_link AS il INNER JOIN image AS i ON il.image_id = i.id WHERE il.namespace = ? AND il.namespace_key = ? AND il.ignored = 0";
				$args = array(
					"railpage.locos.loco",
					$loco_id
				);
				
				$image_id = $this->db->fetchOne($query, $args); 
			} else {
				$query = "SELECT il.image_id FROM image_link AS il WHERE il.namespace = ? AND il.namespace_key = ? AND il.image_id IN (SELECT i.id FROM image_link AS il INNER JOIN image AS i ON il.image_id = i.id WHERE il.namespace = ? AND il.namespace_key = ? AND il.ignored = 0)";
				$args = array(
					"railpage.locos.liveries.livery",
					$livery_id,
					"railpage.locos.loco",
					$loco_id
				);
				
				$image_id = $results = $this->db->fetchOne($query, $args); 
			}
			
			if (isset($image_id) && filter_var($image_id, FILTER_VALIDATE_INT)) {
				$Image = new Image($image_id);
				#$Image->populate();
				
				return $Image;
			}
			
			return false;
		}
	}
?>