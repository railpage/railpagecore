<?php
	/**
	 * FWlink class
	 * @since Version 3.8.6
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage;
	
	use Railpage\AppCore;
	use Exception;
	
	/**
	 * FWlink class
	 */
	
	class fwlink extends AppCore {
		
		/**
		 * Link ID
		 * @since Version 3.8.6
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Original URL
		 * @since Version 3.8.6
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Canonical URL
		 * @since Version 3.8.6
		 * @var string $url_canonical
		 */
		
		public $url_canonical;
		
		/**
		 * Short URL
		 * @since Version 3.8.6
		 * @var string $url_short
		 */
		
		public $url_short;
		
		/**
		 * Title
		 * @since Version 3.7.6
		 * @var string $title
		 */
		
		public $title;
		
		/**
		 * Constructor
		 * @since Version 3.8.6
		 * @param int|string $id
		 */
		
		public function __construct($id = false) {
			parent::__construct();
			
			if ($id) {
				
				$mckey = "railpage:fwlink=" . md5($id);
				
				if ($row = getMemcacheObject($mckey)) {
					$this->id = $row['id']; 
					$this->url = $row['url']; 
					$this->title = $row['title'];
				} else {
					if (filter_var($id, FILTER_VALIDATE_INT)) {
						$query = "SELECT * FROM fwlink WHERE id = ?";
					} else {
						$query = "SELECT * FROM fwlink WHERE url = ?";
					}
					
					if ($row = $this->db->fetchRow($query, $id)) {
						$this->id = $row['id']; 
						$this->url = $row['url'];
						$this->title = $row['title'];
						
						setMemcacheObject($mckey, $row, strtotime("+1 month"));
					}
				}
				
				if (!empty($this->url)) {
					$this->url_canonical = sprintf("http://%s%s", RP_HOST, $this->url);
					$this->url_short = sprintf("http://%s/fwlink?id=%d", RP_HOST, $this->id);
				}
			}
		}
		
		/**
		 * Return the short URL
		 * @since Version 3.8.6
		 * @return string
		 */
		
		public function __toString() {
			return !empty($this->url_short) ? $this->url_short : "";
		}
		
		/**
		 * Validate changes to this URL
		 * @since Version 3.8.6
		 * @return boolean
		 */
		
		public function validate() {
			if (is_object($this->url)) {
				$this->url = strval($this->url);
			}
			
			if (empty($this->url) || !is_string($this->url)) {
				throw new Exception("Cannot validate new link - \$url is empty or not a string"); 
				return false;
			}
			
			if (empty($this->title) || !is_string($this->title)) {
				throw new Exception("Cannot validate new link - \$title is empty or not a string"); 
				return false;
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this URL
		 * @since Version 3.8.6
		 * @return boolean
		 */
		 
		public function commit() {
			if ($this->validate()) {
				$data = array(
					"url" => $this->url,
					"title" => $this->title
				);
				
				if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
					$this->db->insert("fwlink", $data); 
					$this->id = $this->db->lastInsertId(); 
				} else {
					$where = array(
						"id = ?" => $this->id
					);
					
					$this->db->update("fwlink", $data, $where); 
				}
				
				return true;
			} else {
				return false;
			}
		}
	}
	