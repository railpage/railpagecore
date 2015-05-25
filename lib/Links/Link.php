<?php
	/**
	 * Links module link object
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
	 * Link class
	 */
	
	class Link extends Links {
		
		/**
		 * Link ID
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Link name
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Link URL
		 * @var string $url;
		 */
		 
		public $url;
		
		/**
		 * Link description
		 * @var string $desc;
		 */
		
		public $desc;
		
		/**
		 * Broken link
		 * @var boolean $broken
		 */
		
		public $broken = false;
		
		/**
		 * Approved
		 * @var boolean $approved
		 */
		
		public $approved = false;
		
		/**
		 * Link submitter
		 * @var int $user_id
		 */
		
		public $user_id;
		
		/**
		 * Date submitted
		 * @var object $Date
		 */
		 
		public $Date;
		
		/**
		 * Link category
		 * @var object $Category
		 */
		
		public $Category;
		
		/**
		 * Constructor
		 * @since Version 3.7.5
		 * @var int $link_id
		 */
		
		public function __construct($link_id = false) {
			parent::__construct(); 
			
			if ($link_id) {
				$this->id = $link_id;
				$this->fetch(); 
			}
		}
		
		/**
		 * Fetch a link
		 * @since Version 3.7.5
		 * @return boolean
		 */
		 
		public function fetch() {
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				return false;
			}
			
			$query = "SELECT lid AS link_id, cid AS category_id, title AS link_title, image AS link_image, url AS link_url, description AS link_desc, date AS link_date, user_id, link_broken, link_approved FROM nuke_links_links WHERE lid = ?";
			
			if ($row = $this->db->fetchRow($query, $this->id)) {
				$this->Category = new Category($row['category_id']); 
				
				$this->name = $row['link_title'];
				$this->url = $row['link_url'];
				$this->desc = $row['link_desc'];
				$this->Date = new DateTime($row['link_date']); 
				$this->user_id = $row['user_id']; 
				$this->broken = (bool)$row['link_broken']; 
				$this->approved = (bool)$row['link_approved']; 
				
				return true;
			}
		}
		
		/**
		 * Validate this link
		 * @since Version 3.7.5
		 * @return boolean
		 */
		
		public function validate() {
			if (empty($this->name)) {
				throw new Exception("Link title cannot be empty"); 
				return false;
			}
			
			if (empty($this->url)) {
				throw new Exception("Link URL cannot be empty"); 
				return false;
			}
			
			if (empty($this->desc)) {
				throw new Exception("Link description cannot be empty"); 
				return false;
			}
			
			if (!$this->Category instanceof Category) {
				throw new Exception("Link category cannot be empty"); 
				return false;
			}
			
			if (filter_var($this->Date, FILTER_VALIDATE_INT)) {
				$timestamp = $this->date;
				$this->Date = new DateTime(); 
				$this->Date->setTimestamp($timestamp); 
			}
			
			if (empty($this->Date)) {
				$this->Date = new DateTime; 
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this link
		 * @since Version 3.7.5
		 * @return boolean
		 */
		
		public function commit() {
			$this->validate(); 
			
			$data = array(
				"cid" => $this->Category->id,
				"title" => $this->name,
				"url" => $this->url,
				"description" => $this->desc,
				"date" => $this->Date->format("Y-m-d H:i:s"),
				"user_id" => $this->user_id,
				"link_broken" => intval($this->broken),
				"link_approved" => intval($this->approved)
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"lid = ?" => $this->id
				);
				
				$rs = $this->db->update("nuke_links_links", $data, $where);
			} else {
				$rs = $this->db->insert("nuke_links_links", $data);
				$this->id = $this->db->lastInsertId();
			}
			
			return $rs;
		}
		
		/**
		 * Reject / delete a link
		 * @since Version 3.7.5
		 * @return boolean
		 */
		
		public function reject($id = false) {
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				return false;
			}
			
			$where = array(
				"lid = ?" => $this->id
			);
			
			$this->db->delete("nuke_links_links", $where);
			
			return true;
		}
		
		/**
		 * Approve a link
		 * @since Version 3.7.5
		 * @return $this
		 */
		
		public function approve($id = false) {
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				return false;
			}
			
			$where = array(
				"lid = ?" => $this->id
			);
			
			$data = array(
				"link_approved" => 1
			);
			
			$this->db->update("nuke_links_links", $data, $where);
			
			return $this;
		}
		
		/**
		 * Publish this link
		 * @since Version 3.9
		 * @return $this
		 */
		
		public function publish() {
			$this->approve(); 
			
			return $this;
		}
	}
	