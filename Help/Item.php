<?php
	/**
	 * Help class
	 * @since Version 3.5
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Help; 
	
	use Exception;
	use Railpage\Url;
	
	/**
	 * Item
	 */
	
	class Item extends Help {
		
		/**
		 * Item ID
		 * @since Version 3.5
		 * @var int $id
		 */
		 
		public $id;
		 
		/** 
		 * Item title
		 * @since Version 3.5
		 * @var string $title
		 */
		 
		public $title;
		 
		/** 
		 * Item text
		 * @since Version 3.5
		 * @var string $text
		 */
		 
		public $text;
		
		/**
		 * Last updated
		 * @since Version 3.5
		 * @var object $timestamp
		 */
		
		public $timestamp;
		
		/**
		 * URL slug
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
		 * Category object
		 * @since Version 3.5
		 * @var object $category
		 */
		
		public $category;
		 
		/** 
		 * Constructor
		 * @since Version 3.5
		 * @param int $id
		 */
		 
		public function __construct($id = false) {
			parent::__construct(); 
			 
			if ($id) {
				if (filter_var($id, FILTER_VALIDATE_INT)) {
					$this->id = $id; 
				} elseif (is_string($id)) {
					$query = "SELECT id FROM nuke_faqAnswer WHERE url_slug = ?";
					$this->id = $this->db_readonly->fetchOne($query, $id);
				}
				 
				$this->fetch(); 
			}
		}
		
		/**
		 * Fetch item details
		 */
		
		public function fetch() {
			if (!$this->id) {
				throw new Exception("Cannot fetch item - no item ID given"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT id_cat AS category_id, id AS item_id, question AS item_title, answer AS item_text, url_slug AS item_url_slug, timestamp AS item_timestamp FROM nuke_faqAnswer WHERE id = '".$this->db->real_escape_string($this->id)."'"; 
				
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows == 1) {
						$row = $rs->fetch_assoc(); 
						
						$this->title 	= $row['item_title']; 
						$this->text 	= $row['item_text'];
						$this->url_slug	= $row['item_url_slug']; 
						$this->timestamp	= new \DateTime($row['item_timestamp']); 
						
						try {
							$this->category = new Category($row['category_id']); 
						} catch (Exception $e) {
							throw new \Error($e->getMessage()); 
						}
					} else {
						throw new Exception($rs->num_rows." found when fetching item ID ".$this->id." - this should not happen"); 
						return false;
					}
				} else {
					throw new Exception($this->db->error."\n\n".$query); 
					return false;
				}
			} else {
				$query = "SELECT id_cat AS category_id, id AS item_id, question AS item_title, answer AS item_text, url_slug AS item_url_slug, timestamp AS item_timestamp FROM nuke_faqAnswer WHERE id = ?";
				
				if ($row = $this->db->fetchRow($query, $this->id)) {
					$this->title 		= $row['item_title']; 
					$this->text 		= $row['item_text'];
					$this->url_slug		= $row['item_url_slug']; 
					$this->timestamp	= new \DateTime($row['item_timestamp']); 
					$this->category 	= new Category($row['category_id']); 
				}
			}
			
			if (filter_var($this->url_slug, FILTER_VALIDATE_INT) || empty($this->url_slug) || !is_string($this->url_slug)) {
				$this->createSlug(); 
			}
			
			$this->makeUrls();
		}
		
		/**
		 * Validate changes to a help item
		 * @since Version 3.5
		 * @return boolean
		 */
		
		public function validate() {
			if (empty($this->title)) {
				throw new Exception("Cannot validate help item - title is empty"); 
				return false;
			} 
			
			if (empty($this->text)) {
				throw new Exception("Cannot validate help item - text is empty"); 
				return false;
			}
			
			if (filter_var($this->url_slug, FILTER_VALIDATE_INT) || empty($this->url_slug) || !is_string($this->url_slug)) {
				$this->createSlug(); 
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this help item
		 * @since Version 3.5
		 * @return boolean
		 */
		
		public function commit() {
			try {
				$this->validate(); 
			} catch (Exception $e) {
				throw new Exception($e->getMessage()); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$dataArray = array(); 
				$dataArray['id_cat'] = $this->category->id;
				$dataArray['question'] = $this->db->real_escape_string($this->title); 
				$dataArray['answer'] = $this->db->real_escape_string($this->text); 
				$dataArray['url_slug'] = $this->db->real_escape_string($this->url_slug);
				
				if ($this->id) {
					$query = $this->db->buildQuery($dataArray, "nuke_faqAnswer", array("id" => $this->db->real_escape_string($this->id))); 
				} else {
					$query = $this->db->buildQuery($dataArray, "nuke_faqAnswer"); 
				}
				
				if ($rs = $this->db->query($query)) {
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
					"id_cat" => $this->category->id,
					"question" => $this->title,
					"answer" => $this->text,
					"url_slug" => $this->url_slug
				);
				
				if ($this->id) {
					$where = array("id = ?" => $this->id); 
					
					$this->db->update("nuke_faqAnswer", $data, $where); 
				} else {
					$this->db->insert("nuke_faqAnswer", $data); 
					$this->id = $this->db->lastInsertId(); 
				}
			}
			
			$this->makeUrls();
			
			return true;
		}
		
		/**
		 * Generate the URL slug for this help item
		 * @since Version 3.8.6
		 * @return string
		 */
		
		public function createSlug() {
			// Assume ZendDB
			$find = array(
				"(",
				")",
				"-",
				"?",
				"!",
				"#",
				"$",
				"%",
				"^",
				"&",
				"*",
				"+",
				"=",
				"'",
				"\""
			);
			
			$replace = array(); 
			
			foreach ($find as $item) {
				$replace[] = "";
			}
			
			$name = str_replace($find, $replace, strtolower(trim($this->title)));
			$proposal = create_slug($name);
			
			/**
			 * Trim it if the slug is too long
			 */
			
			if (strlen($proposal) >= 256) {
				$proposal = substr($poposal, 0, 200); 
			}
			
			/**
			 * Check that we haven't used this slug already
			 */
			
			$result = $this->db_readonly->fetchAll("SELECT id FROM nuke_faqAnswer WHERE url_slug = ? AND id != ?", array($proposal, $this->id)); 
			
			if (count($result)) {
				$proposal .= count($result);
			}
			
			if (isset($this->slug)) {
				$this->slug = $proposal;
			}
			
			/**
			 * Add this slug to the database
			 */
			
			$data = array(
				"url_slug" => $proposal
			);
			
			$where = array(
				"id = ?" => $this->id
			);
			
			$rs = $this->db->update("nuke_faqAnswer", $data, $where); 
			
			/**
			 * Return it
			 */
			
			return $proposal;
		}
		
		/**
		 * Get contributors of this locomotive
		 * @since Version 3.7.5
		 * @return array
		 */
		
		public function getContributors() {
			$return = array(); 
			
			$query = "SELECT DISTINCT l.user_id, u.username FROM log_general AS l LEFT JOIN nuke_users AS u ON u.user_id = l.user_id WHERE l.module = ? AND l.key = ? AND l.value = ?";
			
			foreach ($this->db->fetchAll($query, array("help", "help_id", $this->id)) as $row) {
				$return[$row['user_id']] = $row['username']; 
			}
			
			return $return;
		}
		
		/**
		 * Create URLs
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		private function makeUrls() {
			
			if (is_string($this->url_slug) && !empty($row['item_url_slug'])) {
				$this->url = RP_WEB_ROOT . "/help/" . $this->category->url_slug . "/" . $this->url_slug;
			} else {
				$this->url = RP_WEB_ROOT . "/help/" . $this->category->url_slug . "/" . $this->id;
			}
			
			$url = is_string($this->url_slug) ? sprintf("%s/%s/%s", $this->Module->url, $this->category->url_slug, $this->url_slug) : sprintf("%s/%s/%s", $this->Module->url, $this->category->url_slug, $this->id);
			$this->url = new Url($url);
		}
	}
?>