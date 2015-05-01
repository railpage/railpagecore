<?php
	/**
	 * Help class
	 * @since Version 3.5
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Help; 
	
	use Exception;
	use DateTime;
	
	
	/**
	 * Category
	 */
	
	class Category extends Help {
		
		/**
		 * Category ID
		 * @since Version 3.5
		 * @var int $id
		 */
		 
		public $id;
		 
		/** 
		 * Category name
		 * @since Version 3.5
		 * @var string $name
		 */
		 
		public $name;
		 
		/** 
		 * Category URL slug
		 * @since Version 3.5
		 * @var string $url_slug
		 */
		 
		public $url_slug;
		
		/**
		 * URL relative to the site domain
		 * @since Version 3.8.6
		 * @var string $url
		 */
		
		public $url;
		 
		/** 
		 * Constructor
		 * @since Version 3.5
		 * @param int $id
		 */
		 
		public function __construct($id = false) {
			parent::__construct();
			 
			if ($id) {
				$this->id = $id; 
				 
				$this->fetch(); 
			}
		}
		
		/**
		 * Fetch category details
		 */
		
		public function fetch() {
			if (!$this->id) {
				throw new Exception("Cannot fetch category - no category ID given"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT id_cat AS category_id, categories AS category_name, url_slug AS category_url_slug FROM nuke_faqCategories WHERE id_cat = '".$this->db->real_escape_string($this->id)."'"; 
				
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows == 1) {
						$row = $rs->fetch_assoc(); 
						
						$this->name = $row['category_name']; 
						$this->url_slug = $row['category_url_slug'];
					} else {
						throw new Exception($rs->num_rows." found when fetching category ID ".$this->id." - this should not happen"); 
						return false;
					}
				} else {
					throw new Exception($this->db->error."\n\n".$query); 
					return false;
				}
			} else {
				$query = "SELECT id_cat AS category_id, categories AS category_name, url_slug AS category_url_slug FROM nuke_faqCategories WHERE id_cat = ?"; 
				
				if ($row = $this->db->fetchRow($query, $this->id)) {
					$this->name = $row['category_name']; 
					$this->url_slug = $row['category_url_slug'];
				}
			}
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				if (is_string($this->url_slug)) {
					$this->url = RP_WEB_ROOT . "/help/" . $this->url_slug;
				}
			}
		}
		
		/**
		 * Get items in this category
		 * @since Version 3.5
		 * @return array
		 */
		
		public function getItems() {
			if ($this->db instanceof \sql_db) {
				$query = "SELECT id AS item_id, question AS item_title, answer AS item_text, url_slug AS item_url_slug FROM nuke_faqAnswer WHERE id_cat = '".$this->db->real_escape_string($this->id)."' ORDER BY question"; 
				
				if ($rs = $this->db->query($query)) {
					$return = array(); 
					
					while ($row = $rs->fetch_assoc()) {
						$return[$row['item_id']] = $row; 
					}
					
					return $return;
				} else {
					throw new Exception($this->db->error."\n\n".$query); 
					return false;
				}
			} else {
				$query = "SELECT id AS item_id, question AS item_title, answer AS item_text, url_slug AS item_url_slug FROM nuke_faqAnswer WHERE id_cat = ? ORDER BY item_title";
				
				$return = array();
				
				foreach ($this->db->fetchAll($query, $this->id) as $row) {
					if (!filter_var($row['item_url_slug'], FILTER_VALIDATE_INT) && is_string($row['item_url_slug']) && !empty($row['item_url_slug'])) {
						$row['url'] = RP_WEB_ROOT . "/help/" . $this->url_slug . "/" . $row['item_url_slug'];
					} else {
						$row['url'] = RP_WEB_ROOT . "/help/" . $this->url_slug . "/" . $row['item_id'];
					}
		
					$return[$row['item_id']] = $row; 
				}
				
				return $return;
			}
		}
		
		/**
		 * Validate changes to this category
		 * @since Version 3.5
		 * @return boolean
		 */
		
		public function validate() {
			if (empty($this->name)) {
				throw new Exception("Cannot validate category - name cannot be empty"); 
				return false;
			}
			
			if (empty($this->url_slug)) {
				$this->createSlug();
			}
			
			return true;
		}
		
		/**
		 * Create a URL slug
		 * @since Version 3.9.1
		 */
		
		private function createSlug() {
			$proposal = create_slug($this->name);
			
			$result = $this->db->fetchAll("SELECT id_cat FROM nuke_faqCategories WHERE url_slug = ?", $proposal); 
			
			if (count($result)) {
				$proposal .= count($result);
			}
			
			$this->url_slug = $proposal;
		}
		
		/**
		 * Commit changes to this category
		 * @since Version 3.6
		 * @return boolean
		 */
		
		public function commit() {
			$this->validate();
			
			if ($this->db instanceof \sql_db) {
				$dataArray = array(); 
				$dataArray['categories'] = $this->db->real_escape_string($this->name); 
				$dataArray['url_slug'] = $this->db->real_escape_string($this->url_slug); 
				
				if ($this->id) {
					$query = $this->db->buildQuery($dataArray, "nuke_faqCategories", array("id_cat" => $this->db->real_escape_string($this->id))); 
				} else {
					$query = $this->db->buildQuery($dataArray, "nuke_faqCategories"); 
				}
				
				if ($this->db->query($query)) {
					if (!$this->id) {
						$this->id = $this->db->insert_id; 
					} 
					
					return true; 
				} else {
					throw new Exception($this->db->error."\n\n".$query); 
					return false;
				}
			} else {
				$data = array(
					"categories" => $this->name,
					"url_slug" => $this->url_slug
				);
				
				if ($this->id) {
					$where = array("id_cat = ?" => $this->id); 
					$this->db->update("nuke_faqCategories", $data, $where);
				} else {
					$this->db->insert("nuke_faqCategories", $data);
					$this->id = $this->db->lastInsertId(); 
				}
				
				return true;
			}
		}
	}
?>