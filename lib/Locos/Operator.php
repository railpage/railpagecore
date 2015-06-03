<?php
	/** 
	 * Loco database
	 * @since Version 3.2
	 * @version 3.8.7
	 * @author Michael Greenhill
	 * @package Railpage
	 */
	
	namespace Railpage\Locos;
	
	use DateTime;
	use Exception;
	use Railpage\Url;
		
	/** 
	 * Locomotive operators
	 * @since Version 3.2
	 * @version 3.8.7
	 * @author Michael Greenhill
	 */
	
	class Operator extends Locos {
		
		/** 
		 * Operator ID
		 * @since Version 3.2
		 * @var int $id
		 */
		
		public $id;
		
		/** 
		 * Operator name
		 * @since Version 3.2
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Organistion ID
		 * @since Version 3.2
		 * @var int $organisation_id
		 */
		
		public $organisation_id;
		
		/**
		 * Operator URL
		 * @since Version 3.8.7
		 * @var string $url_operator
		 */
		
		public $url_operator;
		
		/**
		 * Owner URL
		 * @since Version 3.8.7
		 * @var string $url_owner
		 */
		
		public $url_owner;
		
		/**
		 * Constructor
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $operator_id
		 */
		
		public function __construct($operator_id = false) {
			parent::__construct(); 
			
			if (filter_var($operator_id, FILTER_VALIDATE_INT)) {
				try {
					$this->fetch($operator_id); 
				} catch (Exception $e) {
					throw new Exception($e->getMessage()); 
				}
			}
		}
		
		/**
		 * Populate the operator object
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $operator_id
		 */
		
		public function fetch($operator_id = false) {
			if (!$operator_id) {
				throw new Exception("No operator ID provided"); 
				return false;
			}
			
			// Fetch the data
			$query = "SELECT * FROM operators WHERE operator_id = ?";
			
			$row = $this->db->fetchRow($query, $operator_id);
			$this->id 		= $operator_id; 
			$this->name		= $row['operator_name']; 
			$this->organisation_id = $row['organisation_id']; 
			$this->url_operator = "/locos/browse/operator/" . $this->id;
			$this->url_owner = "/locos/browse/owner/" . $this->id;
			
			$this->url = new Url(sprintf("/locos/browse/operator/%d", $this->id));
			$this->url->operator = sprintf("/locos/browse/operator/%d", $this->id);
			$this->url->owner = sprintf("/locos/browse/owner/%d", $this->id);
			
			return true;
		}
		
		/**
		 * Verify the changes before committing them
		 * @since Version 3.2
		 * @return boolean
		 */
		
		public function validate() {
			if (empty($this->name)) {
				throw new Exception("Cannot validate Operator: the operator name cannot be empty");
			}
			
			if (!filter_var($this->organisation_id)) {
				$this->organisation_id = 0;
			}
			
			return true;
		}
		
		/**
		 * Commit the changes
		 * @since Version 3.2
		 * @version 3.2
		 * @return boolean
		 */
		
		public function commit() {
			
			$this->validate();
			
			$data = array(
				"operator_name" => $this->name,
				"organisation_id" => $this->organisation_id
			);
			
			if (!empty($this->id)) {
				$where = array(
					"operator_id = ?" => $this->id
				);
				
				$this->db->update("operators", $data, $where);
			} else {
				$this->db->insert("operators", $data); 
				$this->id = $this->db->lastInsertId(); 
			}
			
			return true;
		}
		
		/**
		 * Return this as a string
		 * @since Version 3.9.1
		 * @return string
		 */
		
		public function __toString() {
			return $this->name;
		}
	}
	