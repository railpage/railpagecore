<?php
	/**
	 * EventCategory object
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Events;
	
	use Railpage\AppCore;
	use Railpage\Module;
	use Railpage\Url;
	use Exception;
	use DateTime;
	
	/**
	 * EventCategory
	 * @since Version 3.8.7
	 */
	
	class EventCategory extends AppCore {
		
		/**
		 * Category ID
		 * @since Version 3.8.7
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Name
		 * @since Version 3.8.7
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Descriptive text
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
		 * URL
		 * @since Version 3.8.7
		 * @var string $url The URL of this event category, relative to the site root
		 */
		
		public $url;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param int $category_id
		 */
		
		public function __construct($category_id = NULL) {
			parent::__construct();
			
			$this->Module = new Module("events");
			$this->namespace = $this->Module->namespace;
			
			if (filter_var($category_id, FILTER_VALIDATE_INT)) {
				$query = "SELECT * FROM event_categories WHERE id = ?";
			} elseif (is_string($category_id) && strlen($category_id) > 1) {
				$query = "SELECT * FROM event_categories WHERE slug = ?";
			}
			
			if (isset($query)) {
				if ($row = $this->db->fetchRow($query, $category_id)) {
					$this->id = $row['id'];
					$this->name = $row['title'];
					$this->desc = $row['description'];
					$this->slug = $row['slug'];
					
					$this->createUrls();
				}
			}
		}
		
		/**
		 * Validate changes to this category
		 * @return boolean
		 * @throws \Exception if $this->name is empty
		 * @throws \Exception if $this->desc is empty
		 */
		
		private function validate() {
			if (empty($this->name)) {
				throw new Exception("Event name cannot be empty");
			}
			
			if (empty($this->desc)) {
				$this->desc = "";
				#throw new Exception("Event description cannot be empty");
			}
			
			if (empty($this->slug)) {
				$this->createSlug();
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this event category
		 * @return boolean
		 */
		
		public function commit() {
			$this->validate(); 
			
			$data = array(
				"title" => $this->name,
				"description" => $this->desc,
				"slug" => $this->slug
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("event_categories", $data, $where);
			} else {
				$this->db->insert("event_categories", $data);
				$this->id = $this->db->lastInsertId();
				
				$this->createUrls();
			}
			
			return true;
		}
		
		/**
		 * Get events within a given date boundary
		 * @param \DateTime $Start A DateTime object representing the start boundary to search
		 * @param \DateTime $End A DateTime object represengint the end boundary to search
		 * @param int $limit The number of events to return. Defaults to 15 if not provied
		 * @return array
		 */
		
		public function getEvents(DateTime $Start = NULL, DateTime $End = NULL, $limit = 15) {
			if (is_null($Start)) {
				$Start = new DateTime;
			}
			
			$query = "SELECT ed.* FROM event_dates AS ed INNER JOIN event AS e ON ed.event_id = e.id WHERE e.category_id = ? AND ed.date >= ?";
			$params = array($this->id, $Start->format("Y-m-d"));
			
			if (!is_null($End)) {
				$query .= " AND ed.date <= ?";
				$params[] = $End->format("Y-m-d");
			}
			
			$query .= " ORDER BY ed.date LIMIT ?";
			$params[] = $limit;
			
			return $this->db->fetchAll($query, $params);
		}
		
		/**
		 * Create a URL slug
		 * @since Version 3.8.7
		 */
		
		private function createSlug() {
			
			$proposal = substr(create_slug($this->name), 0, 14);
			
			$result = $this->db->fetchAll("SELECT id FROM event_categories WHERE slug = ?", $proposal); 
			
			if (count($result)) {
				$proposal .= count($result);
			}
			
			$this->slug = $proposal;
			$this->commit();
			
		}
		
		/**
		 * Create URLs
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		private function createUrls() {
			if (empty($this->slug)) {
				$this->createSlug(); 
			}
			
			$this->url = new Url(sprintf("%s/category/%s", $this->Module->url, $this->slug));
		}
	}
	