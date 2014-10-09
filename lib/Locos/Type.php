<?php
	/**
	 * Locomotive type - steam, diesel-electric, etc
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos;
	
	use Exception;
	
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
			parent::__construct();
			
			if (!is_null($id)) {
				if (filter_var($id, FILTER_VALIDATE_INT)) {
					$row = $this->db->fetchRow("SELECT * FROM loco_type WHERE id = ?", $id);
				} elseif (is_string($id)) {
					$row = $this->db->fetchRow("SELECT * FROM loco_type WHERE slug = ?", $id);
				}
				
				if (isset($row) && count($row)) {
					$this->id = $row['id']; 
					$this->name = $row['title'];
					$this->slug = $row['slug'];
					
					if (empty($this->slug)) {
						$proposal = create_slug($this->name);
						$proposal = substr($proposal, 0, 30);
						
						$query = "SELECT id FROM loco_type WHERE slug = ?";
						$result = $this->db->fetchAll($query, $proposal);
						
						if (count($result)) {
							$proposal = $proposal . count($result);
						}
						
						$this->slug = $proposal;
						$this->commit();
					}
					
					$this->url = sprintf("%s/type/%s", $this->Module->url, $this->slug);
				}
			}
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
				$proposal = create_slug($this->name);
				$proposal = substr($proposal, 0, 30);
				
				$query = "SELECT id FROM loco_type WHERE slug = ?";
				$result = $this->db->fetchAll($query, $proposal);
				
				if (count($result)) {
					$proposal = $proposal . count($result);
				}
				
				$this->slug = $proposal;
				$this->url = sprintf("%s/type/%s", $this->Module->url, $this->slug);
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
	}
?>