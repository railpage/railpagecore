<?php
	/**
	 * Chronicle entry type
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Chronicle;
	
	use Railpage\Url;
	use Exception;
	use DateTime;
	
	/**
	 * Entry
	 */
	
	class EntryType extends Chronicle {
		
		/**
		 * ID
		 * @since Version 3.8.7
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Text
		 * @since Version 3.8.7
		 * @var string $text
		 */
		
		public $text;
		
		/**
		 * Chronicle group
		 * @since Version 3.8.7
		 * @var string $group
		 */
		
		public $group;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			
			parent::__construct(); 
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
			}
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$query = "SELECT * FROM chronicle_type WHERE id = ?";
				
				if ($row = $this->db->fetchRow($query, $this->id)) {
					$this->text = $row['text'];
					$this->group = $row['grouping'];
				}
			}
		}
	}
