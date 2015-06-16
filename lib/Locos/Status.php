<?php
	/**
	 * Locomotive status
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos;
	
	use Railpage\Debug;
	use Railpage\AppCore;
	use Railpage\Url;
	use Railpage\Glossary\Entry;
	use Exception;
	use InvalidArgumentException;
	
	class Status extends AppCore {
		
		/**
		 * Status ID
		 * @since Version 3.9.1
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Status name
		 * @since Version 3.9.1
		 * @var string $text
		 */
		
		public $name;
		
		/**
		 * Constructor
		 * @since Version 3.9.1
		 * @param int $status_id
		 * @return void
		 */
		
		public function __construct($id = false) {
			
			parent::__construct(); 
			
			if ($id = filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
				$this->mckey = sprintf("railpage:locos.status=%d", $this->id); 
				$this->populate(); 
			}
			
			return;
		}
		
		/**
		 * Populate this object
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function populate() {
			
			if (!$row = $this->Memcached->fetch($this->mckey)) {
				$query = "SELECT id, name FROM loco_status WHERE id = ?";
				$row = $this->db->fetchRow($query, $this->id); 
				$this->Memcached->save($this->mckey, $row, strtotime("+1 year")); 
			}
			
			$this->name = $row['name']; 
			
			return;
		}
		
		/**
		 * Validate changes to this object
		 * @since Version 3.9.1
		 * @return void
		 */
		 
		private function validate() {
			if (empty($this->name)) {
				throw new Exception("Name cannot be empty"); 
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this status
		 * @since Version 3.9.1
		 * @return \Railpage\Locos\Status
		 */
		
		public function commit() {
			
			$this->validate();
			
			$data = [
				"name" => $this->name
			];
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = [ "id = ?" => $this->id ];
				$this->db->update("loco_status", $data, $where); 
				$this->Memcached->delete($this->mckey); 
			} else {
				$this->db->insert("loco_status", $data); 
				$this->id = $this->db->lastInsertId(); 
			}
			
			return $this;
		}
		
		/**
		 * Return the string value of this object
		 * @since Version 3.9.1
		 * @return string
		 */
		
		public function __toString() {
			
			return $this->name; 
			
		}
	}