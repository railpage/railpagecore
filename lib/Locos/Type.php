<?php
	/**
	 * Locomotive type - steam, diesel-electric, etc
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos;
	
	use Railpage\Url;
	use Railpage\ContentUtility;
	use Exception;
	use InvalidArgumentException;
	use Railpage\Debug;
	
	/**
	 * Locomotive type - steam, diesel-electric, etc
	 * @since Version 3.8.7
	 */
	
	class Type extends Locos {
		
		/**
		 * Locomotive type ID
		 * @since Version 3.8.7
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Locomotive type name
		 * @since Version 3.8.7
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * URL Slug
		 * @since Version 3.8.7
		 * @var string $slug
		 */
		
		public $slug;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @var int|string $id
		 */
		
		public function __construct($id = NULL) {
			
			$timer = Debug::getTimer(); 
			
			parent::__construct();
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$row = $this->db->fetchRow("SELECT * FROM loco_type WHERE id = ?", $id);
				$this->load($row); 
			}
			
			$id = filter_var($id, FILTER_SANITIZE_STRING); 
			
			if (!is_null($id)) {
				$row = $this->db->fetchRow("SELECT * FROM loco_type WHERE slug = ?", $id);
				$this->load($row);
			}
			
			Debug::logEvent(__METHOD__, $timer);
		}
		
		/**
		 * Populate this object
		 * @since Version 3.9.1
		 * @param array $row
		 * @return void
		 */
		
		private function load($row) {
			if (!is_array($row) || count($row) === 0) {
				return false;
			}
			
			$this->id = $row['id']; 
			$this->name = $row['title'];
			$this->slug = $row['slug'];
			
			if (empty($this->slug)) {
				$proposal = ContentUtility::generateUrlSlug($this->name, 30);
				
				$query = "SELECT id FROM loco_type WHERE slug = ?";
				$result = $this->db->fetchAll($query, $proposal);
				
				if (count($result)) {
					$proposal = $proposal . count($result);
				}
				
				$this->slug = $proposal;
				$this->commit();
			}
			
			$this->url = new Url(sprintf("%s/type/%s", $this->Module->url, $this->slug));
		}
		
		/**
		 * Validate changes to this locotype 
		 * @since Version 3.8.7
		 * @return true
		 * @throws \Exception if $this->arrangement is empty
		 */
		
		public function validate() {
			if (empty($this->name)) {
				throw new Exception("Cannot validate changes to this loco type: name cannot be empty");
				return false;
			}
					
			if (empty($this->slug)) {
				$proposal = ContentUtility::generateUrlSlug($this->name, 30);
				
				$query = "SELECT id FROM loco_type WHERE slug = ?";
				$result = $this->db->fetchAll($query, $proposal);
				
				if (count($result)) {
					$proposal = $proposal . count($result);
				}
				
				$this->slug = $proposal;
				$this->url = new Url(sprintf("%s/type/%s", $this->Module->url, $this->slug));
			}
			
			return true;
		}
		
		/**
		 * Save changes to this loco type
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		public function commit() {
			$this->validate();
			
			$data = array(
				"title" => $this->name,
				"slug" => $this->slug
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("loco_type", $data, $where);
			} else {
				$this->db->insert("loco_type", $data);
				$this->id = $this->db->lastInsertId();
			}
			
			return $this;
		}
		
		/**
		 * Get an associative array of this data
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getArray() {
			return array(
				"id" => $this->id,
				"name" => $this->name,
				"url" => $this->url->getURLs()
			);
		}
		
		/**
		 * Get locomotive classes by this type
		 * @return array
		 */
		
		public function getClasses() {
			$query = "SELECT id FROM loco_class WHERE loco_type_id = ? ORDER BY name";
			
			$return = array();
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				$LocoClass = new LocoClass($row['id']);
				
				$return[] = $LocoClass->getArray();
			}
			
			return $return;
		}
	}
	