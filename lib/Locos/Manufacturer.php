<?php
	/**
	 * Locomotive manufacturer / builder object
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos;
	
	use Railpage\Organisations\Organisation;
	use Railpage\Url;
	use Railpage\ContentUtility;
	use Exception;
	use DateTime;
	
	/**
	 * Locomotive manufacturer / builder object
	 */
	
	class Manufacturer extends Locos {
		
		/**
		 * Memcached key for all manufacturers
		 * @since Version 3.9.1
		 * @const string MEMCACHED_KEY_ALL
		 */
		
		const MEMCACHED_KEY_ALL = "railpage:loco.manufacturers";
		
		/**
		 * Manufacturer ID
		 * @since Version 3.8.7
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Manufacturer name
		 * @since Version 3.8.7
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Manufacturer description
		 * @since Version 3.8.7
		 * @var string $desc
		 */
		
		public $desc;
		
		/**
		 * URL Slug
		 * @since Version 3.9.1
		 * @var string $slug
		 */
		
		public $slug;
		
		/**
		 * Organistion object linked to this manufacturer
		 * @since Version 3.8.7
		 * @var \Railpage\Organisations\Organisation $Organisation
		 */
		
		public $Organisation;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param int|string $id
		 */
		
		public function __construct($id = NULL) {
			parent::__construct();
			
			if (!is_null($id)) {
				if (filter_var($id, FILTER_VALIDATE_INT)) {
					$query = "SELECT * FROM loco_manufacturer WHERE manufacturer_id = ?";
					$row = $this->db->fetchRow($query, $id);
				} elseif (is_string($id)) {
					$query = "SELECT * FROM loco_manufacturer WHERE slug = ?";
					$row = $this->db->fetchRow($query, $id);
				}
				
				if (isset($row) && count($row)) {
					$this->id = $row['manufacturer_id']; 
					$this->name = $row['manufacturer_name'];
					$this->desc = $row['manufacturer_desc'];
					$this->slug = $row['slug'];
					
					if (empty($this->slug)) {
						$proposal = ContentUtility::generateUrlSlug($this->name, 30);
						
						$query = "SELECT manufacturer_id FROM loco_manufacturer WHERE slug = ?";
						$result = $this->db->fetchAll($query, $proposal);
						
						if (count($result)) {
							$proposal = $proposal . count($result);
						}
						
						$this->slug = $proposal;
						$this->commit();
					}
					
					$this->url = new Url(sprintf("/locos/builder/%s", $this->slug));
				}
			}
		}
		
		/**
		 * Validate changes to this manufacturer
		 * @return boolean
		 * @throws \Exception if $this->name is empty
		 */
		
		public function validate() {
			if (empty($this->name)) {
				throw new Exception("Cannot validate changes to this locomotive manufacturer: manufacturer name cannot be empty");
				return false;
			}
			
			if (empty($this->slug)) {
				$proposal = ContentUtility::generateUrlSlug($this->name, 30);
				
				$query = "SELECT manufacturer_id FROM loco_manufacturer WHERE slug = ?";
				$result = $this->db->fetchAll($query, $proposal);
				
				if (count($result)) {
					$proposal = $proposal . count($result);
				}
				
				$this->slug = $proposal;
				$this->url = new Url(sprintf("/locos/builder/%s", $this->slug));
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this manufacturer
		 * @since Version 3.8.7
		 * @returns $this
		 */
		
		public function commit() {
			$this->validate();
			
			$this->Memcached->delete(self::MEMCACHED_KEY_ALL);
			
			$data = array(
				"manufacturer_name" => $this->name,
				"manufacturer_desc" => $this->desc,
				"slug" => $this->slug
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"manufacturer_id = ?" => $this->id
				);
				
				$this->db->update("loco_manufacturer", $data, $where);
			} else {
				$this->db->insert("loco_manufacturer", $data);
				$this->id = $this->db->lastInsertId();
			}
			
			return $this;
		}
		
		/**
		 * Get locomotive classes built by this manufacturer
		 * @return array
		 */
		
		public function getClasses() {
			$query = "SELECT id, name, loco_type_id, introduced AS year_introduced, manufacturer_id, wheel_arrangement_id FROM loco_class WHERE manufacturer_id = ? OR id IN (SELECT class_id FROM loco_unit WHERE manufacturer_id = ?) GROUP BY id ORDER BY name";
			
			$return = array();
			
			foreach ($this->db->fetchAll($query, array($this->id, $this->id)) as $row) {
				$LocoClass = new LocoClass($row['id']);
				
				$return[] = $LocoClass->getArray();
			}
			
			return $return;
		}
		
		/**
		 * Return this as a string
		 * @since Version 3.9.1
		 * @return string
		 */
		
		public function __toString() {
			return $this->name;
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
				"description" => $this->desc,
				"url" => $this->url->getURLs()
			);
		}
	}
	