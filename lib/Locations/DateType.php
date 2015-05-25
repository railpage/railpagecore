<?php
	/**
	 * Locations date / event type
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locations;
	use DateTime;
	
	/**
	 * Date
	 */
	
	class DateType extends Locations {
		
		/**
		 * ID
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Text
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Constructor
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			parent::__construct(); 
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$query = "SELECT * FROM location_datetypes WHERE id = ?";
				
				if ($row = $this->db->fetchRow($query, $id)) {
					$this->id = $id;
					$this->name = $row['name'];
				}
			}
		}
	}
	