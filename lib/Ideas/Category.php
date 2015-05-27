<?php
	/**
	 * Suggestions for side ideas and improvements, ala Wordpress.org/ideas
	 * @since Version 3.8.7
	 * @author Michael Greenhill
	 * @package Railpage
	 */
	
	namespace Railpage\Ideas;
	
	use Railpage\AppCore;
	use Railpage\Module;
	use Exception;
	use DateTime;
	
	/**
	 * Category class
	 * @since Version 3.8.7
	 */
	
	class Category extends AppCore {
		
		/**
		 * Category ID
		 * @since Version 3.8.7
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Category name
		 * @since Version 3.8.7
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Category URL slug
		 * @since Version 3.8.7
		 * @var string $slug
		 */
		
		private $slug;
		
		/**
		 * Category URL
		 * @since Version 3.8.7
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			
			parent::__construct();
			
			$this->Module = new Module("ideas");
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
				
				$query = "SELECT * FROM idea_categories WHERE id = ?";
				
				if ($row = $this->db->fetchRow($query, $this->id)) {
					$this->name = $row['title'];
					$this->slug = $row['slug'];
					
					$this->url = sprintf("%s/%s", $this->Module->url, $this->slug);
				}
			} elseif (is_string($id) && strlen($id) > 1) {
				$this->slug = $id;
				
				$query = "SELECT * FROM idea_categories WHERE slug = ?";
				
				if ($row = $this->db->fetchRow($query, $this->slug)) {
					$this->name = $row['title'];
					$this->id = $row['id'];
					
					$this->url = sprintf("%s/%s", $this->Module->url, $this->slug);
				}
			}
			
		}
		
		/**
		 * Get a list of ideas
		 * @since Version 3.8.7
		 * @yield \Railpage\Ideas\Idea
		 */
		
		public function getIdeas() {
			
			$query = "SELECT id FROM idea_ideas WHERE category_id = ? ORDER BY title";
			
			$result = $this->db->fetchAll($query, $this->id);
			
			if (count($result)) {
				
				foreach ($result as $row) {
					yield new Idea($row['id']);
				}
				
			}
			
		}
		
		/**
		 * Validate changes to this category
		 * @since Version 3.8.7
		 * @throws \Exception if $this->name is empty
		 */
		
		private function validate() {
			
			if (empty($this->name)) {
				throw new Exception("Idea category name cannot be emtpy");
			}
			
			if (empty($this->slug)) {
				$this->createSlug();
			}
			
			return true;
			
		}
		
		/**
		 * Create a URL slug
		 * @since Version 3.8.7
		 */
		
		private function createSlug() {
			$proposal = create_slug($this->name);
			
			$result = $this->db->fetchAll("SELECT id FROM idea_categories WHERE slug = ?", $proposal); 
			
			if (count($result)) {
				$proposal .= count($result);
			}
			
			$this->slug = $proposal;
		}
		
		/**
		 * Commit changes to this idea category
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
				
				$this->db->update("idea_categories", $data, $where);
			} else {
				$this->db->insert("idea_categories", $data);
				$this->id = $this->db->lastInsertId();
			}
			
			return $this;
			
		}
	}
	