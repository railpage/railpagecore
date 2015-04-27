<?php
	
	/**
	 * Forums API
	 * @since Version 3.0.1
	 * @version 3.2
	 * @package Railpage
	 * @author James Morgan, Michael Greenhill
	 */
	 
	namespace Railpage\Forums;
	
	use Railpage\Module;
	use Railpage\Url;
	use Railpage\Users\User;
	use Zend_Acl;
	
	use DateTime;
	use Exception;
	use stdClass;
	
	/** 
	 * phpBB Forum class
	 * @since Version 3.0.1
	 * @version 3.0.1
	 * @author James Morgan
	 */
	
	class Forum extends Forums {
		
		/**
		 * Forum ID
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Category ID
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $catid
		 */
		
		public $catid;
		
		/**
		 * Forum name
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Forum description
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var string $description
		 */
		
		public $description;
		
		/**
		 * Forum status
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $status
		 */
		
		public $status;
		
		/**
		 * Forum order
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $order
		 */
		
		public $order;
		
		/**
		 * Number of posts in this forum
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $posts
		 */
		
		public $posts;
		
		/**
		 * Number of topics in this forum
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $topics
		 */
		
		public $topics;
		
		/**
		 * Last post ID
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $last_post
		 */
		
		public $last_post;
		
		/**
		 * Category object
		 * @since Version 3.2
		 * @version 3.2
		 * @var object $category
		 */
		
		var $category;
		
		/**
		 * Constructor
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @param int $forumid
		 * @param object $database
		 */
		
		function __construct($forumid, $getParent = true) {
			parent::__construct();
			
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			$this->Module = new Module("forums");
			$this->url = new Url(sprintf("/f-f%d.htm", $forumid));
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT * FROM nuke_bbforums f LEFT JOIN (nuke_bbtopics t, nuke_bbposts p, nuke_bbposts_text pt) ON (f.forum_last_post_id = p.post_id AND p.topic_id = t.topic_id AND pt.post_id = p.post_id) WHERE f.forum_id = '".$this->db->real_escape_string($forumid)."' LIMIT 1";
				
				$result = $this->db->query($query);
				
				if ($result->num_rows == 1) {
					$row = $result->fetch_assoc();
						
					foreach ($row as $key => $val) {
						//$row[$key] = iconv('windows-1256', 'UTF-8', $val);
					}
				}
				
				$result->close();
			} else {
				$query = "SELECT * FROM nuke_bbforums f LEFT JOIN (nuke_bbtopics t, nuke_bbposts p, nuke_bbposts_text pt) ON (f.forum_last_post_id = p.post_id AND p.topic_id = t.topic_id AND pt.post_id = p.post_id) WHERE f.forum_id = ? LIMIT 1";
				
				$row = $this->db->fetchRow($query, $forumid);
			}
			
			if (isset($row) && is_array($row)) {
				$this->id 			= $forumid;
				$this->catid 		= $row["cat_id"];
				$this->name 		= function_exists("html_entity_decode_utf8") ? html_entity_decode_utf8($row["forum_name"]) : $row['forum_name'];
				$this->description 	= function_exists("html_entity_decode_utf8") ? html_entity_decode_utf8($row["forum_desc"]) : $row['forum_desc'];
				$this->status 		= $row["forum_status"];
				$this->order 		= $row["forum_order"];
				$this->posts 		= $row["forum_posts"];
				$this->topics 		= $row["forum_topics"];
				$this->last_post 	= $row["forum_last_post_id"];
				
				$this->last_post_id 		= $this->last_post;
				$this->last_post_time 		= $row['post_time'];
				$this->last_post_user_id	= $row['poster_id'];
				$this->last_post_username	= $row['post_username'];
				$this->last_post_subject	= $row['post_subject'];
				$this->last_post_text		= $row['post_text'];
				$this->last_post_bbcodeuid	= $row['bbcode_uid'];
				
				$this->last_post_topic_id 		= $row['topic_id'];
				$this->last_post_topic_title 	= $row['topic_title'];
				$this->last_post_topic_time 	= $row['topic_time'];
				
				$this->acl_resource = sprintf("railpage.forums.forum:%d", $this->id);
				
				if ($getParent) {
					$this->category = new Category($this->catid);
				}
			}
			
			if (RP_DEBUG) {
				$site_debug[] = __CLASS__ . "::" . __METHOD__ . " completed in " . round(microtime(true) - $debug_timer_start, 5) . "s";
			}
		}
		
		/**
		 * Tell the forum that there's a new post
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @param int $postID
		 * @return boolean
		 */
		
		function addPost($postID = false) {
			if (empty($postID) || !$postID) {
				throw new \Exception("No post ID specified for " . __CLASS__ . "::" . __FUNCTION__ . "()"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				if ($postID != null) {
					if ($this->db->query("UPDATE nuke_bbforums SET forum_posts=forum_posts+1, forum_last_post_id='".$this->db->real_escape_string($postID)."' WHERE forum_id = '".$this->id."'") === true) { return true; } else { return false; }
				} else {
					trigger_error("PhpBB_forum: Class has no data to add post.", E_USER_NOTICE); 
					return false;
				}
			} else {
				$data = array(
					"forum_posts" => new \Zend_Db_Expr("forum_posts + 1"),
					"forum_last_post_id" => $postID
				);
				
				$where = array(
					"forum_id = ?" => $this->id
				);
				
				return $this->db->update("nuke_bbforums", $data, $where); 
			}
		}
		
		
		/**
		 * Tell the forum that there's a new thread
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @param int $topicID
		 * @return boolean
		 */ 
		
		
		function addTopic() {
			if ($this->db instanceof \sql_db) {
				if ($this->db->query("UPDATE nuke_bbforums SET forum_topics=forum_topics+1 WHERE forum_id = '".$this->id."'") === true) { return true; } else { return false; }
			} else {
				$data = array(
					"forum_topics" => new \Zend_Db_Expr("forum_topics + 1"),
				);
				
				$where = array(
					"forum_id = ?" => $this->id
				);
				
				return $this->db->update("nuke_bbforums", $data, $where); 
			}
		}
		
		/**
		 * Reload the forum data - eg when a new post or topic has been created
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		
		function refresh() {
			if ($this->db instanceof \sql_db) {
				$result = $this->db->query("SELECT * FROM nuke_bbforums WHERE forum_id = '".$this->db->real_escape_string($this->id)."' LIMIT 1");
				if ($result->num_rows == 1) {
					$row = $result->fetch_assoc;
						
					foreach ($row as $key => $val) {
						$row[$key] = iconv('windows-1256', 'UTF-8', $val);
					}
					
					$this->id 			= $this->id;
					$this->catid 		= $row["cat_id"];
					$this->name 		= $row["forum_name"];
					$this->description 	= $row["forum_desc"];
					$this->status 		= $row["forum_status"];
					$this->order 		= $row["forum_order"];
					$this->posts 		= $row["forum_posts"];
					$this->topics 		= $row["forum_topics"];
					$this->last_post 	= $row["forum_last_post_id"];
					$result->close();
				} else {
					trigger_error("PhpBB_forum: Forum ID ".$this->id." does not exist.", E_USER_NOTICE); 	
				}
			} else {
				$query = "SELECT * FROM nuke_bbforums WHERE forum_id = ? LIMIT 1";
				
				$row = $this->db->fetchRow($query, $this->id); 
				
				$this->catid 		= $row["cat_id"];
				$this->name 		= $row["forum_name"];
				$this->description 	= $row["forum_desc"];
				$this->status 		= $row["forum_status"];
				$this->order 		= $row["forum_order"];
				$this->posts 		= $row["forum_posts"];
				$this->topics 		= $row["forum_topics"];
				$this->last_post 	= $row["forum_last_post_id"];
				
				return true;
			}
		}
		
		/**
		 * Permissions check
		 * @since Version 3.2
		 * @version 3.2
		 * @param object $permissions
		 * @return boolean
		 */
		 
		public function get_permission($permission_name = false, $permissions_object = false) {
			if ($permissions_object) {
				$this->permissions = $permissions_object;
			}
			
			if (!$permission_name || !$this->permissions || !$this->id) {
				return false;
			}
			
			$object = $this->permissions->forums[$this->id];
			
			switch ($permission_name) {
				case "view":
					if ($object['auth_view'] == 0 || $object['auth_read'] == 0) {
						return true;
						
					} elseif ($this->permissions->user->id) {
						// Logged in
						
						if ($object['auth_mod'] > 0) {
							// User is a moderator
							return true;
						}
						
						if ($this->permissions->user->level > 1) {
							return true;
						}
					}
				break;
			}
			
			return false;
		}
		
		/**
		 * Get topics from this forum
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 * @param int $items_per_page
		 * @param int $page_num
		 * @param string $sort
		 */
		
		public function topics($items_per_page = 25, $page_num = 1, $sort = "DESC") {
			if ($this->db instanceof \sql_db) {
				$query = "SELECT 
								SQL_CALC_FOUND_ROWS 
								t.*, 
								ufirst.username AS first_post_username, 
								ufirst.user_id AS first_post_user_id, 
								ulast.username AS last_post_username, 
								ulast.user_id AS last_post_user_id,
								pfirst_text.post_text AS first_post_text,
								pfirst_text.bbcode_uid AS first_post_bbcode_uid,
								pfirst.post_time AS first_post_time,
								plast_text.post_text AS last_post_text,
								plast_text.bbcode_uid AS last_post_bbcode_uid,
								plast.post_time AS last_post_time
								
							FROM nuke_bbtopics AS t
							
							LEFT JOIN nuke_bbposts AS pfirst ON pfirst.post_id = t.topic_first_post_id
							LEFT JOIN nuke_bbposts AS plast ON plast.post_id = t.topic_last_post_id
							LEFT JOIN nuke_bbposts_text AS pfirst_text ON pfirst.post_id = pfirst_text.post_id
							LEFT JOIN nuke_bbposts_text AS plast_text ON plast.post_id = plast_text.post_id
							LEFT JOIN nuke_users AS ufirst ON ufirst.user_id = pfirst.poster_id
							LEFT JOIN nuke_users AS ulast ON ulast.user_id = plast.poster_id
							
							WHERE t.forum_id = ".$this->db->real_escape_string($this->id)." 
							ORDER BY t.topic_type DESC, plast.post_time ".$this->db->real_escape_string($sort)." 
							LIMIT ".$this->db->real_escape_string(($page_num - 1) * $items_per_page).", ".$this->db->real_escape_string($items_per_page);
				
				if ($rs = $this->db->query($query)) {
					$total = $this->db->query("SELECT FOUND_ROWS() AS total"); 
					$total = $total->fetch_assoc(); 
					
					$topics = array();
					$topics['total_topics'] = $total['total'];
					$topics['total_pages'] = ceil($total['total'] / $items_per_page);
					$topics['page_num'] = $page_num;
					$topics['items_per_page'] = $items_per_page;
					
					while ($row = $rs->fetch_assoc()) {
						$topics['topics'][$row['topic_id']] = $row;
					}
					
					return $topics;
				} else {
					trigger_error("phpBB Forum : Unable to fetch topic list for forum id ".$this->id);
					trigger_error($this->db->error);
					trigger_error($query);
					
					return false;
				}
			} else {
				$query = "SELECT 
								SQL_CALC_FOUND_ROWS 
								t.*, 
								ufirst.username AS first_post_username, 
								ufirst.user_id AS first_post_user_id, 
								ulast.username AS last_post_username, 
								ulast.user_id AS last_post_user_id,
								pfirst_text.post_text AS first_post_text,
								pfirst_text.bbcode_uid AS first_post_bbcode_uid,
								pfirst.post_time AS first_post_time,
								plast_text.post_text AS last_post_text,
								plast_text.bbcode_uid AS last_post_bbcode_uid,
								plast.post_time AS last_post_time
								
							FROM nuke_bbtopics AS t
							
							LEFT JOIN nuke_bbposts AS pfirst ON pfirst.post_id = t.topic_first_post_id
							LEFT JOIN nuke_bbposts AS plast ON plast.post_id = t.topic_last_post_id
							LEFT JOIN nuke_bbposts_text AS pfirst_text ON pfirst.post_id = pfirst_text.post_id
							LEFT JOIN nuke_bbposts_text AS plast_text ON plast.post_id = plast_text.post_id
							LEFT JOIN nuke_users AS ufirst ON ufirst.user_id = pfirst.poster_id
							LEFT JOIN nuke_users AS ulast ON ulast.user_id = plast.poster_id
							
							WHERE t.forum_id = ?
							ORDER BY t.topic_type DESC, plast.post_time " . $sort . "
							LIMIT ?, ?";
				
				$params = array(
					$this->id,
					($page_num - 1) * $items_per_page,
					$items_per_page
				);
				
				$result = $this->db->fetchAll($query, $params);
				
				$topics = array();
				$topics['total_topics'] = $this->db->fetchOne("SELECT FOUND_ROWS() AS total"); 
				$topics['total_pages'] = ceil($total['total'] / $items_per_page);
				$topics['page_num'] = $page_num;
				$topics['items_per_page'] = $items_per_page;
				
				foreach ($result as $row) {
					$topics['topics'][$row['topic_id']] = $row;
				}
				
				return $topics;
			}
		}
		
		/**
		 * Get threads within this forum
		 * @since Version 3.8.7
		 * @yield \Railpage\Forums\Thread
		 * @param int $items_per_page
		 * @param int $page
		 */
		
		public function getThreads($items_per_page = 25, $page = 1) {
			$query = "SELECT topic_id FROM nuke_bbtopics WHERE forum_id = ? LIMIT ?, ?";
			
			foreach ($this->db->fetchAll($query, array($this->id, ($page - 1) * $items_per_page, $items_per_page)) as $row) {
				yield new Thread($row['topic_id']);
			}
		}
		
		/**
		 * Check various forums permissions 
		 * @since Version 3.8.7
		 * @param string $permission
		 * @return boolean
		 */
		
		public function isAllowed($permission) {
			if (!$this->User instanceof User) {
				throw new Exception("Cannot check forum ACL because no valid user has been set (hint: setUser(\$User)");
			}
			
			$this->getACL();
			
			return $this->ZendACL->isAllowed("forums_viewer", $this->acl_resource, $permission);
		}
		
		/**
		 * Refresh forum data
		 * @since Version 3.9.1
		 * @return \Railpage\Forums\Forum
		 */
		
		public function refreshForumStats() {
			$query = "SELECT 
				(SELECT COUNT(post_id) AS forum_posts FROM nuke_bbposts WHERE forum_id = ?) AS forum_posts,
				(SELECT COUNT(topic_id) AS forum_topics FROM nuke_bbtopics WHERE forum_id = ?) AS forum_topics,
				(SELECT post_id AS forum_last_post_id FROM nuke_bbposts WHERE forum_id = ? ORDER BY post_time DESC LIMIT 1) AS forum_last_post_id";
			
			$where = array(
				$this->id,
				$this->id,
				$this->id
			);
			
			$stats = $this->db->fetchAll($query, $where);
			
			if ($this->id == 63) {
				printArray($stats);
			}
			
			if (isset($stats[0])) {
				$data = $stats[0];
				
				$where = array(
					"forum_id = ?" => $this->id
				);
				
				if ($this->id == 63) {
					printArray($data);
				}
				
				$this->db->update("nuke_bbforums", $data, $where);
			}
			
			return $this;
		}
	}
?>