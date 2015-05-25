<?php
	/**
	 * Locations date /event
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locations;
	
	use Exception;
	use DateTime;
	
	/**
	 * Date
	 */
	
	class Date extends Locations {
		
		/**
		 * Date ID
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Date
		 * @var object $Date
		 */
		
		public $Date;
		
		/**
		 * Date text
		 * @var string $text
		 */
		
		public $text;
		
		/**
		 * Type
		 * @var object $Type
		 */
		
		public $Type;
		
		/**
		 * Location ID
		 * @var object $Location
		 */
		
		public $Location;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			parent::__construct(); 
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$query = "SELECT * FROM location_date WHERE id = ?";
				
				if ($row = $this->db->fetchRow($query, $id)) {
					$this->id = $id;
					$this->Date = new DateTime($row['date']);
					$this->text = $row['text']; 
					$this->meta = json_decode($row['meta']); 
					$this->Type = new DateType($row['type_id']);
					$this->Location = new Location($row['location_id']); 
				}
			}
		}
		
		/**
		 * Validate changes
		 */
		
		public function validate() {
			if (!$this->Date instanceof DateTime) {
				throw new Exception("Date is not an instance of DateTime");
			}
			
			if (!$this->Type instanceof DateType) {
				throw new Exception("Date type is not an instance of Railpage\Locations\DateType");
			}
			
			if (!$this->Location instanceof Location) {
				throw new Exception("Date location is not an instance of Railpage\Locations\Location");
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this date
		 */
		
		public function commit() {
			$this->validate(); 
		
			$data = array(
				"date" => $this->Date->format("Y-m-d"),
				"text" => $this->text,
				"meta" => json_encode($this->meta),
				"location_id" => $this->Location->id,
				"type_id" => $this->Type->id
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				// Update
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("location_date", $data, $where);
			} else {
				// Insert
				$this->db->insert("location_date", $data);
				$this->id = $this->db->lastInsertId();
			}
			
			return true;
		}
	}
	