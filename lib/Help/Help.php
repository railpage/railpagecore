<?php
	/**
	 * FAQ base class
	 * @since Version 3.5
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Help; 
	
	use Railpage\AppCore;
	use Railpage\Module;
	use Exception;
	
	/** 
	 * Base class
	 */
	
	class Help extends AppCore {
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 */
		
		public function __construct() {
			
			parent::__construct(); 
			
			$this->Module = new Module("help");
			
		}
		
		/** 
		 * List all categories
		 * @since Version 3.5
		 * @return array
		 */
		
		public function getCategories() {
			$query = "SELECT id_cat AS category_id, categories AS category_name, url_slug AS category_url_slug FROM nuke_faqCategories ORDER BY categories";
			
			if ($this->db instanceof \sql_db) {
				if ($rs = $this->db->query($query)) {
					$return = array(); 
					
					while ($row = $rs->fetch_assoc()) {
						$row['url'] = RP_WEB_ROOT . "/help/" . $row['category_url_slug'];
						$return[$row['category_id']] = $row; 
					}
					
					return $return;
				} else {
					throw new Exception($this->db->error."\n\n".$query); 
					return false;
				}
			} else {
				$return = array();
				
				foreach ($this->db->fetchAll($query) as $row) {
					$row['url'] = RP_WEB_ROOT . "/help/" . $row['category_url_slug'];
					
					$return[$row['category_id']] = $row; 
				}
				
				return $return;
			}
		}
		
		/**
		 * Get category ID from URL slug
		 * @since Version 3.5
		 * @return int
		 * @param string $url_slug
		 */
		
		public function getCategoryIDFromSlug($url_slug = false) {
			if (!$url_slug) {
				throw new Exception("Cannot fetch category ID - no URL slug given"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT id_cat AS category_id FROM nuke_faqCategories where url_slug = '".$this->db->real_escape_string($url_slug)."'"; 
				
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows == 1) {
						$row = $rs->fetch_assoc(); 
						
						return $row['category_id']; 
					} elseif ($rs->num_rows > 1) {
						throw new Exception("More than one category ID found for URL slug ".$url_slug." - this should never happen"); 
						return false;
					} else {
						throw new Exception("No category ID found for URL slug ".$url_slug); 
						return false;
					}
				} else {
					throw new Exception($this->db->error."\n\n".$query); 
					return false;
				}
			} else {
				$query = "SELECT id_cat AS category_id FROM nuke_faqCategories where url_slug = ?";
				
				return $this->db->fetchOne($query, $url_slug); 
			}
		}
		
		/**
		 * Delete a help item
		 * @since Version 3.5
		 * @param int $help_id
		 * @return boolean
		 */
		
		public function deleteItem($help_id = false) {
			if (!$help_id) {
				throw new Exception("Cannot delete help item - no ID given"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "DELETE FROM nuke_faqAnswer WHERE id = '".$this->db->real_escape_string($help_id)."'"; 
				
				if ($rs = $this->db->query($query)) {
					return true; 
				} else {
					throw new Exception($this->db->error."\n\n".$query); 
					return false;
				}
			} else {
				$where = array(
					"id = ?" => $help_id
				);
				
				$this->db->delete("nuke_faqAnswer", $where); 
				return true;
			}
		}
		
		/**
		 * Delete a help category
		 * @since Version 3.5
		 * @param int $category_id
		 * @return boolan
		 */
		
		public function deleteCategory($category_id = false) {
			if (!$category_id) {
				throw new Exception("Cannot delete category - no ID given"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "DELETE FROM nuke_faqAnswer where id_cat = '".$this->db->real_escape_string($category_id)."'"; 
				
				if ($this->db->query($query)) {
					$query = "DELETE FROM nuke_faqCategories WHERE id_cat = '".$this->db->real_escape_string($category_id)."'"; 
					
					if ($this->db->query($query)) {
						return true;
					} else {
						throw new Exception($this->db->error."\n\n".$query); 
						return false;
					}
				} else {
					throw new Exception($this->db->error."\n\n".$query); 
					return false;
				}
			} else {
				$where = array(
					"id_cat = ?" => $help_id
				);
				
				$this->db->delete("nuke_faqAnswer", $where); 
				
				$where = array(
					"id_cat = ?" => $help_id
				);
				
				$this->db->delete("nuke_faqCategories", $where); 
				
				return true;
			}
		}
	}
?>