<?php
	/**
	 * Links category
	 * @since Version 3.7.5
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Links;
	
	use DateTime;
	use Exception;
	use Railpage\AppCore;
	use Railpage\Url;
	use Railpage\Module;
	
	/** 
	 * Category
	 */
	
	class Category extends Links {
		
		/**
		 * Category ID
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Category name
		 * @var string $Name
		 */
		
		public $name;
		
		/**
		 * Category description
		 * @var string $desc
		 */
		
		public $desc;
		
		/**
		 * Category URL slug
		 * @var string $slug
		 */
		
		public $slug; 
		
		/**
		 * Category access URL
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Links
		 * @var array $links
		 */
		 
		public $links = array();
		
		/**
		 * Parent category ID
		 * @var int $parent_id
		 */
		
		public $parent_id;
		
		/**
		 * Category parent
		 * @var object $parent
		 */
		
		public $parent;
		
		/**
		 * Constructor
		 * @praam int $category_id
		 */
		
		public function __construct($category_id = false, $recurse = true) {
			parent::__construct(); 
			
			if (filter_var($category_id, FILTER_VALIDATE_INT)) {
				$this->id = $category_id;
			} elseif (is_string($category_id)) {
				$this->id = $this->db->fetchOne("SELECT cid FROM nuke_links_categories WHERE slug = ?", $category_id);
			}
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$this->mckey = sprintf("railpage.links.category=%d", $this->id);
				
				if (!$row = getMemcacheObject($this->mckey)) {
					$row = $this->db->fetchRow("SELECT * FROM nuke_links_categories WHERE cid = ?", $this->id); 
					
					if (!empty($row)) {
						setMemcacheObject($this->mckey, $row);
					}
				}
					
				$this->name = $row['title']; 
				$this->desc = $row['cdescription']; 
				$this->parent_id = $row['parentid']; 
				$this->slug = $row['slug']; 
				$this->url = $this->makePermalink($this->id); 
				
				if ($this->parent_id > 0 && $recurse) {
					$this->parent = new Category($this->parent_id); 
				}
			}
		}
		
		/**
		 * Get links from this category
		 * @since Version 3.7.5
		 * @return array
		 */
		 
		public function getLinks($category_id = false, $sort = false, $direction = false) {
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot get links from a link category - invalid category ID given"); 
				return false;
			}
			
			$query = "SELECT lid AS link_id, title AS link_title, url AS link_url, description AS link_desc, date AS link_date FROM nuke_links_links WHERE cid = ? ORDER BY title ASC";
			
			$this->links = $this->db->fetchAll($query, $this->id);
			
			return $this->links;
		}
	}
	