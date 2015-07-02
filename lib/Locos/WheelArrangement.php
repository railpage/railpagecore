<?php
	/**
	 * Locomotive wheel arrangement object
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos;
	
	use Exception;
	use Railpage\Url;
	use Railpage\Debug;
	use Railpage\ContentUtility;
	
	/**
	 * Locomotive wheel arrangement object
	 * @since Version 3.8.7
	 */
	
	class WheelArrangement extends Locos {
		
		/**
		 * Wheel arrangement ID
		 * @since Version 3.8.7
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Wheel arrangement name
		 * @since Version 3.8.7
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Wheel arrangement
		 * @since Version 3.8.7
		 * @var string $arrangement
		 */
		
		public $arrangement;
		
		/**
		 * Wheel arrangement image
		 * @since Version 3.10.0
		 * @var string $image
		 */
		
		public $image;
		
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
			
			if (!is_null($id)) {
				if (filter_var($id, FILTER_VALIDATE_INT)) {
					$row = $this->db->fetchRow("SELECT * FROM wheel_arrangements WHERE id = ?", $id);
				} elseif (is_string($id)) {
					$row = $this->db->fetchRow("SELECT * FROM wheel_arrangements WHERE slug = ?", $id);
				}
				
				$this->load($row); 
			}
			
			Debug::logEvent(__METHOD__, $timer);
		}
		
		/**
		 * Populate this object with results from the database
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
			$this->arrangement = $row['arrangement'];
			$this->image = $row['image'];
			$this->slug = $row['slug'];
			
			if (empty($this->image) && !preg_match("/([a-zA-Z])/", $this->arrangement)) {
				$this->image = "https://static.railpage.com.au/i/locos/whyte/" . $this->arrangement . ".png";
			}
			
			if (empty($this->slug)) {
				$this->generateSlug(); 
				$this->commit(); 
			}
			
			$this->makeURLs(); 
		}
		
		/**
		 * Populate our URLs
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function makeURLs() {
			
			$this->url = new Url(sprintf("/locos/wheelset/%s", urlencode($this->slug)));
			
		}
		
		/**
		 * Generate a URL slug
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function generateSlug() {
			
			//$proposal = ContentUtility::generateUrlSlug(sprintf("%s-%s", $this->name, $this->arrangement), 30);
			$proposal = $this->arrangement;
			
			$query = "SELECT id FROM wheel_arrangements WHERE slug = ?";
			$result = $this->db->fetchAll($query, $proposal);
			
			if (count($result)) {
				$proposal = $proposal . count($result);
			}
			
			$this->slug = $proposal;

		}
		
		/**
		 * Validate changes to this wheelset 
		 * @since Version 3.8.7
		 * @return true
		 * @throws \Exception if $this->arrangement is empty
		 */
		
		public function validate() {
			if (empty($this->arrangement)) {
				throw new Exception("Cannot validate changes to this wheel arrangement: arrangement cannot be empty");
				return false;
			}
					
			if (empty($this->slug)) {
				$this->generateSlug(); 
			}
			
			if (is_null($this->image)) {
				$this->image = "";
			}
			
			$this->makeURLs();
			
			return true;
		}
		
		/**
		 * Save changes to this wheelset
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		public function commit() {
			
			$this->validate();
			
			$data = array(
				"title" => $this->name,
				"arrangement" => $this->arrangement,
				"image" => $this->image,
				"slug" => $this->slug
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("wheel_arrangements", $data, $where);
			} else {
				$this->db->insert("wheel_arrangements", $data);
				$this->id = $this->db->lastInsertId();
			}
			
			return $this;
		}
		
		/**
		 * Get locomotive classes built by this wheel arrangement
		 * @return array
		 */
		
		public function getClasses() {
			$query = "SELECT id, name, loco_type_id, introduced AS year_introduced, manufacturer_id, wheel_arrangement_id FROM loco_class WHERE wheel_arrangement_id = ? ORDER BY name";
			
			$return = array();
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				$LocoClass = Factory::CreateLocoClass($row['id']);
				
				$return[] = $LocoClass->getArray();
			}
			
			return $return;
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
				"arrangement" => $this->arrangement,
				"image" => $this->image,
				"url" => $this->url->getURLs()
			);
		}
	}
	