<?php
	/**
	 * Railcam types
	 * EG mainline, preserved, model railway, non-rail
	 * @since Version 3.8
	 * @package Railpage
	 * @author Michael Greenill
	 */
	
	namespace Railpage\Railcams;
	
	/**
	 * Railcam type class
	 */
	
	class Type extends Railcams {
		
		/**
		 * Type ID
		 * @since Version 3.8
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Type name
		 * @since Version 3.8
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Type URL slug
		 * @since Version 3.8
		 * @var string $slug
		 */
		
		public $slug;
		
		/**
		 * Constructor
		 * @param int|string $id_or_slug
		 * @since Version 3.8
		 */
		
		public function __construct($id_or_slug = false) {
			parent::__construct();
					
			if ($id_or_slug) {
				if (filter_var($id_or_slug, FILTER_VALIDATE_INT) && $id_or_slug > 0) {
					$this->id = $id_or_slug; 
					
					$this->fetch(); 
				} else {
					$query = "SELECT id FROM railcams_type WHERE slug = ?";
					
					if ($id = $this->db->fetchOne($query, $id_or_slug)) {
						$this->id = $id;
					
						$this->fetch();
					}
				}
			} else {
				rp_fatal_error(404, "Could not find railcam ID or slug " . $id_or_slug);
			}
		}
		
		/**
		 * Fetch railcam type
		 * @since Version 3.8
		 */
		
		public function fetch() {
			$query = "SELECT * FROM railcams_type WHERE id = ?";
			
			if ($result = $this->db->fetchRow($query, $this->id)) {
				$this->name = $result['name'];
				$this->slug = $result['slug'];
			}
		}
	}
	