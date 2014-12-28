<?php
	/** 
	 * Downloads 
	 * @since Version 3.0
	 * @version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Downloads;
	
	use Exception;
	use Railpage\Url;
	
	/**
	 * Download category class
	 * @since Version 3.2
	 * @version 3.8.7
	 */
	
	class Category extends Base {
		
		/**
		 * Category ID
		 * @since Version 3.2
		 * @var int $id The ID of the category
		 */
		
		public $id; 
		
		/** 
		 * Title
		 * @since Version 3.2
		 * @var string $title The title of the category
		 */
		
		public $title;
		
		/**
		 * Description
		 * @since Version 3.2
		 * @var string $desc Text describing the category
		 */
		
		public $desc;
		
		/**
		 * URL to this category
		 * @since Version 3.8.7
		 * @var string $url The URL to the category relative to the site root
		 */
		
		public $url;
		
		/**
		 * Parent category
		 * @since Version 3.2
		 * @var \Railpage\Downloads\Category $Parent The parent category, if applicable
		 */
		
		public $Parent;
		
		/**
		 * Constructor
		 * @since Version 3.2
		 */
		
		public function __construct() {
			foreach (func_get_args() as $arg) {
				if (filter_var($arg, FILTER_VALIDATE_INT)) {
					$this->id = $arg;
				}
			}
			
			parent::__construct();
				
			if (!empty($this->id)) {
				try {
					$this->fetch(); 
				} catch (Exception $e) {
					throw new \Exception($e->getMessage()); 
				}
			}
		}
		
		/**
		 * Populate this object with an existing download category
		 * @since Version 3.2
		 * @return boolean
		 */
		
		public function fetch() {
			if (empty($this->id)) {
				throw new \Exception("Cannot fetch category - no category ID provided"); 
				return false;
			}
			
			$query = "SELECT * FROM download_categories WHERE category_id = ?";
			
			$row = $this->db->fetchRow($query, $this->id);
			
			$this->title = $row['category_title']; 
			$this->desc = $row['category_description'];
			$this->url = new Url(sprintf("/downloads?mode=category&id=%d", $this->id));
			
			if ($row['parentid'] > 0) {
				$this->Parent = new Category($row['parentid']); 
			}
		}
		
		/**
		 * List the downloads in this category
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function downloads() {
			$query = "SELECT d.id AS download_id, d.title AS download_title, d.description AS download_desc, UNIX_TIMESTAMP(d.date) AS download_date, c.category_id, c.category_title
						FROM download_items AS d
						LEFT JOIN download_categories AS c ON d.category_id = c.category_id 
						WHERE d.category_id = ?
						AND d.approved = 1
						AND d.active = 1
						ORDER BY d.date DESC";
			
			$return = array(
				"stat" => "ok",
				"downloads" => array()
			);
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				$return['downloads'][$row['download_id']] = $row; 
			}
			
			return $return;
		}
		
		/**
		 * Validate changes to this category
		 * @since Version 3.9
		 * @throws \Exception if $this->title is empty
		 * @return boolean
		 */
		
		private function validate() {
			if (empty($this->title)) {
				throw new Exception("Title cannot be empty");
			}
			
			if (empty($this->desc)) {
				$this->desc = "";
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this category
		 * @since Version 3.9
		 * @return $this
		 */
		
		public function commit() {
			$this->validate(); 
			
			$data = array(
				"category_title" => $this->title,
				"category_description" => $this->desc,
				"parentid" => ($this->Parent instanceof Category) ? $this->Parent->id : 0
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"category_id = ?" => $this->id
				);
				
				$this->db->update("download_categories", $data, $where); 
			} else {
				$this->db->insert("download_categories", $data);
				$this->id = $this->db->lastInsertId();
			}
			
			$this->url = new Url(sprintf("/downloads?mode=category&id=%d", $this->id));
			
			return $this;
		}
	}
?>