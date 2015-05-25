<?php
	
	/**
	 * Forums API
	 * @since Version 3.0.1
	 * @version 3.8.7
	 * @package Railpage
	 * @author James Morgan, Michael Greenhill
	 */
	 
	namespace Railpage\Forums;
	
	use Railpage\Users\User;
	use Exception;
	use DateTime;

	/**
	 * phpBB category class
	 * @since Version 3.2
	 * @version 3.2
	 * @author Michael Greenhill
	 */
	
	class Category extends Forums {
		
		/**
		 * Category ID
		 * @since Version 3.2
		 * @version 3.2
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Category title
		 * @since Version 3.2
		 * @version 3.2
		 * @var string $title
		 */
		
		public $title;
		
		/**
		 * Category order
		 * @since Version 3.2
		 * @version 3.2
		 * @var int $order
		 */
		
		public $order;
		
		/**
		 * Forums in this category
		 * @since Version 3.2
		 * @version 3.2
		 * @var array $forums
		 */
		
		public $forums;
		
		/**
		 * Constructor
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @param object $db
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			parent::__construct(); 
			
			$this->id = $id;
			
			$exec_sql = true;
			
			if ($this->db instanceof \sql_db) {
				if ($this->id) {
					$query = "SELECT * FROM nuke_bbcategories WHERE cat_id = ".$this->db->real_escape_string($this->id);
					
					if ($rs = $this->db->query($query)) {
					
						$row = $rs->fetch_assoc();
						
						$this->title = $row['cat_title'];
						$this->order = $row['cat_order'];
						
						$rs->close();
						
						$query = "SELECT forum_id AS id, forum_name AS title FROM nuke_bbforums WHERE cat_id = ".$this->db->real_escape_string($this->id)." ORDER BY forum_order";
						
						if ($rs = $this->db->query($query)) {
							while ($row = $rs->fetch_assoc()) {
								$result[] = $row;
							}
							
							foreach ($result as $row) {
								$this->forums[$row['id']] = $row['title'];
							}
						}
					} else {
						throw new Exception(sprintf("Category ID %d does not exist", $this->id));
					}
				}
			} else {
				$query = "SELECT * FROM nuke_bbcategories WHERE cat_id = ?";
				
				$row = $this->db->fetchRow($query, $this->id);
				$this->title = $row['cat_title']; 
				$this->order = $row['cat_order']; 
				
				$query = "SELECT forum_id AS id, forum_name AS title FROM nuke_bbforums WHERE cat_id = ? ORDER BY forum_order";
				
				foreach ($this->db->fetchAll($query, $this->id) as $row) {
					$this->forums[$row['id']] = $row['title'];
				}
			}
		}
		
		/**
		 * Get forums within this category
		 * @since Version 3.8.7
		 * @yield new Forum
		 */
		 
		public function getForums() {
			
			if (!$this->User instanceof User) {
				throw new Exception("Cannot get the list of forums within this category because no valid user has been specified");
			}
			
			$userdata = $this->User->generateUserData();
			$Index = new Index;
			
			if (!isset($prefix)) {
				$prefix = "nuke";
			}
			
			if (!isset($user_prefix)) {
				$user_prefix = "nuke";
			}
			
			require_once("includes" . DS . "auth.php");
			require_once("includes" . DS . "constants.php");
			
			$is_auth_ary = auth(AUTH_VIEW, AUTH_LIST_ALL, $userdata, $Index->forums());
			
			$forums = array();
			
			foreach ($is_auth_ary as $forum_id => $perms) {
				if (intval($perms['auth_view']) === 1) {
					$forums[] = $forum_id;
				}
				
				#printArray($perms);die;
			}
			
			$query = "SELECT forum_id FROM nuke_bbforums WHERE cat_id = ? AND forum_id IN ('" . implode("', '", $forums) . "') ORDER BY forum_order";
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				yield new Forum($row['forum_id']);
			}
		}
	}
	