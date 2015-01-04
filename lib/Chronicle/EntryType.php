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
		 * Chronicle grouping: Locos
		 * @since Version 3.9
		 * @const GROUPING_LOCOS
		 */
		
		const GROUPING_LOCOS = "Locos";
		
		/**
		 * Chronicle grouping: Locations
		 * @since Version 3.9
		 * @const GROUPING_LOCATIONS
		 */
		
		const GROUPING_LOCATIONS = "Locations";
		
		/**
		 * Chronicle grouping: all others
		 * @since Version 3.9
		 * @const GROUPING_OTHER
		 */
		
		const GROUPING_OTHER = "Other";
		
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
		
		/**
		 * Validate changes to an entry type
		 * @since Version 3.9
		 * @return \Railpage\Chronicle\EntryType
		 * @throws \Exception if $this->text is empty
		 */
		
		private function validate() {
			if (empty($this->text)) {
				throw new Exception("Chronicle entry types must have text entered");
			}
			
			if (empty($this->group)) {
				$this->group = self::GROUPING_OTHER;
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this chronicle type
		 * @since Version 3.9
		 * @return \Railpage\Chronicle\EntryType
		 */
		
		public function commit() {
			$this->validate(); 
			
			$data = array(
				"grouping" => $this->group,
				"text" => $this->text
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("chronicle_type", $data, $where);
			} else {
				$this->db->insert("chronicle_type", $data);
				$this->id = $this->db->lastInsertId();
			}
			
			return $this;
		}
	}
