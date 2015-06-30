<?php
	
	/**
	 * Forums API
	 * @since Version 3.0.1
	 * @version 3.9
	 * @package Railpage
	 * @author James Morgan, Michael Greenhill
	 */
	 
	namespace Railpage\Forums;
	
	use Railpage\Users\User;
	use Railpage\Forums\Post;
	use Railpage\Forums\Forums;
	use Railpage\AppCore;
	use Railpage\Debug;
	use Railpage\Module;
	use Exception;
	use DateTime;
	use DateTimeZone;
	use DateInterval;
	use stdClass;
	use Zend_Acl;
	use Zend_Acl_Resource;
	use Zend_Acl_Role;
	use ReflectionClass;
	use Railpage\Registry;

	define("RP_THREAD_LOCKED", 1);
	define("RP_THREAD_UNLOCKED", 0);
	 
	/**
	 * phpBB base class
	 * @since Version 3.2
	 * @version 3.2
	 * @author Michael Greenhill
	 */
	 
	class Forums extends AppCore {
		
		/**
		 * Auth: list all
		 * @const AUTH_LIST_ALL
		 */
		
		const AUTH_LIST_ALL = 0;
		
		/**
		 * Auth: All 
		 * @const AUTH_ALL
		 */
		
		const AUTH_ALL = 0;
		
		/**
		 * Auth: Registered users
		 * @const AUTH_REG
		 */
		
		const AUTH_REG = 1;
		
		/**
		 * Auth: ACL
		 * @const AUTH_ACL
		 */
		
		const AUTH_ACL = 2;
		
		/**
		 * Auth: Moderator
		 * @const AUTH_MOD
		 */
		
		const AUTH_MOD = 3;
		
		/**
		 * Auth: Administrator
		 * @const AUTH_ADMIN
		 */
		 
		const AUTH_ADMIN = 5;
		
		/**
		 * Auth: View
		 * @const AUTH_VIEW
		 */
		
		const AUTH_VIEW = 1;
		
		/**
		 * Auth: Read
		 * @const AUTH_READ
		 */
		
		const AUTH_READ = 2;
		
		/**
		 * Auth: Create a thread
		 * @const AUTH_POST
		 */
		
		const AUTH_POST = 3;
		
		/**
		 * Auth: Reply to a thread
		 * @const AUTH_REPLY
		 */
		
		const AUTH_REPLY = 4;
		
		/**
		 * Auth: Edit a post
		 * @const AUTH_EDIT
		 */
		
		const AUTH_EDIT = 5;
		
		/**
		 * Auth: Delete a post
		 * @const AUTH_DELETE
		 */
		
		const AUTH_DELETE = 6;
		
		/**
		 * Auth: Create an announcement
		 * @const AUTH_ANNOUNCE
		 */
		
		const AUTH_ANNOUNCE = 7;
		
		/**
		 * Auth: Create a sticky
		 * @const AUTH_STICKY
		 */
		
		const AUTH_STICKY = 8;
		
		/**
		 * Auth: Create a poll
		 * @deprecated Since Version 3.5
		 * @const AUTH_POLLCREATE
		 */
		
		const AUTH_POLLCREATE = 9;
		
		/**
		 * Auth: Vote in a poll
		 * @deprecated Since Version 3.5
		 * @const AUTH_VOTE
		 */
		
		const AUTH_VOTE = 10;
		
		/**
		 * Auth: attach
		 * @const AUTH_ATTACH
		 */
		
		const AUTH_ATTACH = 11;
		
		/**
		 * Memcache object
		 * @since Version 3.2
		 * @version 3.2
		 * @var object $memcache
		 */
		
		public $memcache;
		
		/**
		 * Permissions object
		 * @since Version 3.2
		 * @version 3.2
		 * @var object $permissions
		 */
		
		public $permssions;
		
		/**
		 * Forum list
		 * @since Version 3.2
		 * @version 3.2
		 * @var array $forums
		 */
		
		public $forums;
		
		/**
		 * Post BBCode UID
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var string $bbcodeuid
		 */
		
		public $bbcodeuid = 'sausages';
		
		/**
		 * Topics this user has read
		 * @since Version 3.2
		 * @var array $read_topics
		 */
		
		public $read_topics;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 */
		
		public function __construct() {
			
			parent::__construct(); 
			
			$this->Module = new Module("forums");
			
			$this->Templates = new stdClass;
			$this->Templates->watchedthreads = "thread.watching";
		}
		
		/**
		 * Get ranks from database
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function ranks() {
			$query = "SELECT * FROM nuke_bbranks WHERE rank_special = 0";
				
			if ($this->db instanceof \sql_db) {
				if ($rs = $this->db->query($query)) {
					$return = array(); 
					
					while ($row = $rs->fetch_assoc()) {
						$return[] = $row;
					}
					
					return $return;
				} else {
					trigger_error("PhpBB Base : Unable to fetch standard ranks from database");
					trigger_error($this->db->error);
					
					return false;
				}
			} else {
				return $this->db->fetchAll($query); 
			}
		}
		
		/**
		 * Latest forum posts
		 * @since Version 3.2
		 * @version 3.2
		 * @param array $auth_ary
		 * @param int $since
		 * @return array
		 */
		
		public function latestPosts($is_auth_ary, $since, $limit = 10) {
			if (empty($is_auth_ary) || !is_array($is_auth_ary)) {
				return false;
			}
			
			// To start off, get the list of forums
			$Index 		= new Index($this->db);
			$forum_list	= $Index->forums();
			
			$exclude_forums = array();
			
			for ($i = 0, $c = count($forum_list); $i < $c; $i++) {
				if ($is_auth_ary[$forum_list[$i]['forum_id']]['auth_view']) {
					if (!in_array($forum_list[$i]['forum_id'], array("37"))) {
						$exclude_forums[] = $forum_list[$i]['forum_id'];
					}
				}
			}
			
			if ($this->db instanceof \sql_db) {
				if (!empty($since) && is_int($since) && $since > 0) {
					$since = " AND p.post_time >= ".$this->db->real_escape_string(intval($since));
				}
				
				$query = "SELECT p.post_id, pt.bbcode_uid, p.post_time, pt.post_text, p.enable_bbcode, p.enable_html, p.enable_smilies, p.enable_sig, p.post_edit_time, p.poster_id AS user_id, u.username, t.topic_title, t.topic_id, f.forum_name, f.forum_id
							FROM nuke_bbposts p
							LEFT JOIN nuke_bbposts_text AS pt ON p.post_id = pt.post_id
							LEFT JOIN nuke_users AS u ON p.poster_id = u.user_id
							LEFT JOIN nuke_bbtopics AS t ON p.topic_id = t.topic_id
							LEFT JOIN nuke_bbforums AS f ON p.forum_id = f.forum_id
							WHERE p.forum_id IN (".implode(",", $exclude_forums).") ".$since."
							ORDER BY p.post_time DESC
							LIMIT 0, ".$this->db->real_escape_string($limit)."";
							
				if ($rs = $this->db->query($query)) {
					$return = array(); 
					
					while ($row = $rs->fetch_assoc()) {
						$row['post_text'] = stripslashes($row['post_text']);
						$return[] = $row;
					}
					
					return $return;
				} else {
					trigger_error("Could not fetch new posts");
					trigger_error($this->db->error);
					return false;
				}
			} else {
				$params = array(); 
				
				if (!empty($since) && is_int($since) && $since > 0) {
					$since = " AND p.post_time >= ?";
					$params[] = intval($since); 
				}
				
				$query = "SELECT p.post_id, pt.bbcode_uid, p.post_time, pt.post_text, p.enable_bbcode, p.enable_html, p.enable_smilies, p.enable_sig, p.post_edit_time, p.poster_id AS user_id, u.username, t.topic_title, t.topic_id, f.forum_name, f.forum_id
							FROM nuke_bbposts p
							LEFT JOIN nuke_bbposts_text AS pt ON p.post_id = pt.post_id
							LEFT JOIN nuke_users AS u ON p.poster_id = u.user_id
							LEFT JOIN nuke_bbtopics AS t ON p.topic_id = t.topic_id
							LEFT JOIN nuke_bbforums AS f ON p.forum_id = f.forum_id
							WHERE p.forum_id IN (".implode(",", $exclude_forums).") ".$since."
							ORDER BY p.post_time DESC
							LIMIT 0, ?";
				
				$params[] = $limit; 
				
				$return = array();
				
				foreach ($this->db->fetchAll($query, $params) as $row) {
					$row['post_text'] = stripslashes($row['post_text']);
					$return[] = $row;
				}
				
				return $return;
			}
		}
		
		/**
		 * Load the topics this user has read (stored in a cookie)
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $user_id
		 */
		
		public function getReadTopics($user_id = false) {
			global $Error;
			
			if (!$user_id) {
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				// Get read topics per user
				$query = "SELECT vt.topic_id, vt.time, t.forum_id, t.topic_last_post_id AS last_post_id, p.post_time AS last_post_time 
							FROM viewed_threads AS vt
							LEFT JOIN nuke_bbtopics AS t ON vt.topic_id = t.topic_id
							LEFT JOIN nuke_bbposts AS p ON t.topic_last_post_id = p.post_id
							WHERE vt.user_id = '".$this->db->real_escape_string($user_id)."'";
				
				$topics = array(); 
				
				if ($rs = $this->db->query($query)) {
					while ($row = $rs->fetch_assoc()) {
						$topics[$row['forum_id']]['topics'][$row['topic_id']] = $row;
					}
				
					// Set the last viewed forum time
					foreach ($topics as $forum_id => $data) {
						foreach ($data['topics'] as $topic_id => $array) {
							if ($array['last_post_time'] > $topics[$forum_id]['last_viewed']) {
								$topics[$forum_id]['last_viewed'] = $array['last_post_time'];
							}
						}
					}
					
					return true;
				} else {
					return false;
				}
			} else {
				$query = "SELECT vt.topic_id, vt.time, t.forum_id, t.topic_last_post_id AS last_post_id, p.post_time AS last_post_time 
							FROM viewed_threads AS vt
							LEFT JOIN nuke_bbtopics AS t ON vt.topic_id = t.topic_id
							LEFT JOIN nuke_bbposts AS p ON t.topic_last_post_id = p.post_id
							WHERE vt.user_id = ?";
				
				$topics = array(); 
				
				foreach ($this->db->fetchAll($query, $user_id) as $row) {
					$topics[$row['forum_id']]['topics'][$row['topic_id']] = $row;
				}
				
				// Set the last viewed forum time
				foreach ($topics as $forum_id => $data) {
					foreach ($data['topics'] as $topic_id => $array) {
						if (!isset($topics[$forum_id]['last_viewed']) || $array['last_post_time'] > $topics[$forum_id]['last_viewed']) {
							$topics[$forum_id]['last_viewed'] = $array['last_post_time'];
						}
					}
				}
				
				return $topics;
			}
		}
		
		/**
		 * New forum posts
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 * @param int $from_time
		 */
		
		public function newPosts($forums = false, $from_time = false, $items_per_page = 25, $start = 0) {
			if (!$forums || !is_array($forums)) {
				throw new \Exception("You must provide a list of forums this user has permission to view");
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT SQL_CALC_FOUND_ROWS p.post_time, pt.bbcode_uid, pt.post_subject, pt.post_text, u.username, u.user_id, t.topic_title, t.topic_id, t.topic_views, t.topic_replies, f.forum_name, f.forum_id, ufirst.user_id AS first_user_id, ufirst.username AS first_username, pfirst.post_id AS first_post_id, pfirst.post_time AS first_post_time
							FROM nuke_bbposts AS p
							LEFT JOIN nuke_bbposts_text AS pt ON p.post_id = pt.post_id
							LEFT JOIN nuke_users AS u ON u.user_id = p.poster_id
							LEFT JOIN nuke_bbtopics AS t ON p.topic_id = t.topic_id
							LEFT JOIN nuke_bbforums AS f ON f.forum_id = p.forum_id
							LEFT JOIN nuke_bbposts AS pfirst ON pfirst.post_id = t.topic_first_post_id
							LEFT JOIN nuke_users AS ufirst ON ufirst.user_id = pfirst.poster_id
							WHERE p.forum_id IN (".implode(",", $forums).")";
				
				if ($from_time) { 
					$query .= " AND p.post_time >= ".$from_time; 
				} 
				
				// Group by
				$query .= " GROUP BY t.topic_id ORDER BY p.post_time DESC";
				
				$query .= " LIMIT ".$start.", ".$items_per_page."";
				
				if ($rs = $this->db->query($query)) {
					$total = $this->db->query("SELECT FOUND_ROWS() AS total"); 
					$total = $total->fetch_assoc(); 
					
					$return['total'] = $total['total']; 
					
					$posts = array(); 
					while ($row = $rs->fetch_assoc()) {
						$return['topics'][$row['topic_id']][] = $row; 
					}
				} else {
					throw new \Exception($this->db->error); 
					return false;
				} 
				
				return $return; 
			} else {
				$query = "SELECT SQL_CALC_FOUND_ROWS p.post_time, pt.bbcode_uid, pt.post_subject, pt.post_text, u.username, u.user_id, t.topic_title, t.topic_id, t.topic_views, t.topic_replies, f.forum_name, f.forum_id, ufirst.user_id AS first_user_id, ufirst.username AS first_username, pfirst.post_id AS first_post_id, pfirst.post_time AS first_post_time
							FROM nuke_bbposts AS p
							LEFT JOIN nuke_bbposts_text AS pt ON p.post_id = pt.post_id
							LEFT JOIN nuke_users AS u ON u.user_id = p.poster_id
							LEFT JOIN nuke_bbtopics AS t ON p.topic_id = t.topic_id
							LEFT JOIN nuke_bbforums AS f ON f.forum_id = p.forum_id
							LEFT JOIN nuke_bbposts AS pfirst ON pfirst.post_id = t.topic_first_post_id
							LEFT JOIN nuke_users AS ufirst ON ufirst.user_id = pfirst.poster_id
							WHERE p.forum_id IN (".implode(",", $forums).")";
				
				$params = array(); 
				
				if ($from_time) { 
					$query .= " AND p.post_time >= ?"; 
					$params[] = $from_time;
				} 
				
				// Group by
				$query .= " GROUP BY t.topic_id ORDER BY p.post_time DESC LIMIT ?, ?";
				$params[] = $start; 
				$params[] = $items_per_page; 
				
				$result = $this->db->fetchAll($query, $params); 
				$return = array(); 
				$return['total'] = $this->db->fetchOne("SELECT FOUND_ROWS() AS total"); 
				
				foreach ($result as $row) {
					$return['topics'][$row['topic_id']][] = $row; 
				}
				
				return $return;
			}
		}
		
		/**
		 * Get forum categories
		 * @since Version 3.8.7
		 * @yield new Category
		 */
		 
		public function getCategories() {
			$query = "SELECT cat_id FROM nuke_bbcategories ORDER BY cat_order";
			
			foreach ($this->db->fetchAll($query) as $row) {
				yield new Category($row['cat_id']);
			}
		}
		
		/**
		 * Build the Forums ACL
		 * @since Version 3.8.7
		 * @param boolean $force Force an update of the ACL
		 * @todo Finish this shit
		 */
		
		public function buildACL($force = false) {
			
			$Registry = Registry::getInstance(); 
			
			try {
				$ForumsACL = $Registry->get("forumsacl"); 
				$this->ZendACL = $ForumsACL;
				return;
				
			} catch (Exception $e) {
				// Fook it
			}
			
			Debug::RecordInstance(__METHOD__); 
			$timer = Debug::getTimer(); 
			
			$acl = $Registry->get("acl"); 
			
			if (!$this->User instanceof User) {
				throw new Exception("A valid user must be set before the ACL can be built");
			}
			
			$mckey = "railpage.forums.list";
			
			if ($force || !$forums = $this->Memcached->fetch($mckey)) {
				$query = "SELECT forum_id FROM nuke_bbforums";
				
				$forums = $this->db->fetchAll($query);
				
				$this->Memcached->save($mckey, $forums);
			}
			
			$acl_forums = array();
			
			/**
			 * Add all the forums to the ACL
			 */
			
			foreach ($forums as $row) {
				$acl_forum_name = sprintf("railpage.forums.forum:%d", $row['forum_id']);
				$acl_forums[$row['forum_id']] = $acl_forum_name;
				
				try {
					$acl->get($acl_forum_name);
				} catch (Exception $e) {
					$acl->addResource(new Zend_Acl_Resource($acl_forum_name));
				}
			}
			
			/**
			 * Get the forum permissions from the database
			 */
			
			$a_sql = array("auth_view", "auth_read", "auth_post", "auth_reply", "auth_edit", "auth_delete", "auth_sticky", "auth_announce", "auth_vote", "auth_pollcreate");
			$auth_fields = array('auth_view', 'auth_read', 'auth_post', 'auth_reply', 'auth_edit', 'auth_delete', 'auth_sticky', 'auth_announce', 'auth_vote', 'auth_pollcreate');
			
			$query = "SELECT forum_id, " . implode(", ", $a_sql) . ", " . self::AUTH_ACL . " AS auth_mod FROM nuke_bbforums";
			
			$db_acl = array(); 
			
			foreach ($this->db->fetchAll($query) as $row) {
				$db_acl[$row['forum_id']] = $row;
			}
			
			/**
			 * Get the group permissions for this user
			 */
			
			$query = "SELECT a.* FROM nuke_bbauth_access AS a WHERE a.group_id IN (SELECT group_id FROM nuke_bbuser_group WHERE user_id = ? AND user_pending = 0)";
			$gperms = array(); 
			
			foreach ($this->db->fetchAll($query, $this->User->id) as $perm) {
				$forum_id = $perm['forum_id'];
				$group_id = $perm['group_id'];
				
				unset($perm['forum_id']);
				unset($perm['group_id']);
				
				$gperms[$forum_id][$group_id] = $perm;
			}
			
			/**
			 * Guest details
			 */
			
			$guestfucknamingthis = [
				self::AUTH_MOD => $this->User->inGroup(RP_GROUP_MODERATORS),
				self::AUTH_ADMIN => $this->User->inGroup(RP_GROUP_ADMINS)
			];
			
			/**
			 * Add the forum permissions to Zend_ACL
			 */
			
			foreach ($db_acl as $forum_id => $permissions) {
				$allowed = array(); 
				$denied = array();
				
				unset($permissions['forum_id']);
				
				$allowed = array_merge($allowed, array_keys($permissions, self::AUTH_ALL)); 
				
				if (!$this->User->guest) {
					$allowed = array_merge($allowed, array_keys($permissions, self::AUTH_REG)); 
				}
				
				if ($guestfucknamingthis[self::AUTH_MOD]) {
					$allowed = array_merge($allowed, array_keys($permissions, self::AUTH_MOD)); 
				}
				
				if ($guestfucknamingthis[self::AUTH_ADMIN]) {
					$allowed = array_merge($allowed, array_keys($permissions, self::AUTH_ADMIN)); 
				}
				
				$perms_acl = array_keys($permissions, self::AUTH_ACL);
				
				if (count($perms_acl)) {
					if (isset($gperms[$forum_id])) {
						foreach ($gperms[$forum_id] as $group) {
							foreach ($group as $gitem => $gval) {
								$allowed = array_merge($allowed, array_keys($permissions, self::AUTH_REG)); 
								
								if ($guestfucknamingthis[self::AUTH_MOD]) {
									$allowed = array_merge($allowed, array_keys($permissions, self::AUTH_MOD)); 
								}
								
								if ($guestfucknamingthis[self::AUTH_ADMIN]) {
									$allowed = array_merge($allowed, array_keys($permissions, self::AUTH_ADMIN)); 
								}
							}
						}
					}
				}
				
				$allowed = array_unique($allowed);
				
				#continue;
				
				/*
				foreach ($permissions as $item => $value) {
					switch ($value) {
						
						case self::AUTH_ACL . "zzz" :
							if (isset($gperms[$forum_id])) {
								foreach ($gperms[$forum_id] as $group) {
									foreach ($group as $gitem => $gval) {
										switch ($gval) {
											case self::AUTH_REG :
												$allowed[] = $item;
												break;
											
											case self::AUTH_ACL :
												// Inception
												break;
											
											case self::AUTH_MOD :
												if ($this->User->inGroup(RP_GROUP_MODERATORS)) {
													$allowed[] = $gitem;
												}
												break;
											
											case self::AUTH_ADMIN :
												if ($this->User->inGroup(RP_GROUP_ADMINS)) {
													$allowed[] = $gitem;
												}
												
												break;
										}
									}
								}
							}
							break;
						
						case self::AUTH_MOD  . "zzz": 
							if ($this->User->inGroup(RP_GROUP_MODERATORS)) {
								$allowed[] = $item;
							}
							break;
						
						case self::AUTH_ADMIN . "zzz" :
							if ($this->User->inGroup(RP_GROUP_ADMINS)) {
								$allowed[] = $item;
							}
							break;
					}
				}
				*/
				
				foreach ($permissions as $item => $value) {
					if (!in_array($item, $allowed)) {
						$denied[] = $item;
					}
				}
				
				#$allowed = array_unique($allowed);
				#$denied = array_unique($denied);
				
				$acl->allow("forums_viewer", sprintf("railpage.forums.forum:%d", $forum_id), $allowed);
				$acl->deny("forums_viewer", sprintf("railpage.forums.forum:%d", $forum_id), $denied);
			}
			
			$Registry->set("acl", $acl); 
			$Registry->set("forumsacl", $acl); 
			$this->ZendACL = $acl;
			
			Debug::LogEvent(__METHOD__, $timer); 
			
			return;
			
		}
		
		/**
		 * Return the updated ACL
		 * @since Version 3.8.7
		 * @return \Zend_Acl
		 */
		
		public function getACL() {
			if (!isset($this->ZendACL) || !$this->ZendACL instanceof Zend_Acl) {
				$this->buildACL(); 
			}
			
			return $this->ZendACL;
		}
		
		/**
		 * Get all forums
		 * @since Version 3.9
		 * @return array
		 */
		
		public function getAllForums() {
			$query = "SELECT f.*, c.*, lp.topic_id AS lastpost_topic_id, lp.poster_id AS lastpost_user_id, u.username AS lastpost_username, lp.post_time AS lastpost_time 
						FROM nuke_bbforums AS f
						LEFT JOIN nuke_bbposts AS lp ON lp.post_id = forum_last_post_id
						LEFT JOIN nuke_bbcategories AS c ON c.cat_id = f.cat_id
						LEFT JOIN nuke_users AS u ON u.user_id = lp.poster_id
						ORDER BY c.cat_order, f.forum_order";
			
			$return = array(
				"categories" => array()
			); 
			
			foreach ($this->db->fetchAll($query) as $row) {
				if (!isset($return['categories'][$row['cat_id']])) {
					$return['categories'][$row['cat_id']] = array(
						"id" => $row['cat_id'],
						"url" => sprintf("/f-c%d.htm", $row['cat_id']),
						"name" => $row['cat_title'],
						"order" => $row['cat_order'],
						"forums" => array()
					);
				}
				
				$Date = new DateTime(sprintf("@%d", $row['lastpost_time']));
				
				$return['categories'][$row['cat_id']]['forums'][$row['forum_id']] = array(
					"id" => $row['forum_id'],
					"url" => sprintf("/f-f%d.htm", $row['forum_id']),
					"name" => $row['forum_name'],
					"desc" => $row['forum_desc'],
					"status" => $row['forum_status'],
					"order" => $row['forum_order'],
					"posts" => $row['forum_posts'],
					"topics" => $row['forum_topics'],
					"lastpost" => array(
						"date" => array(
							"timestamp" => $Date->getTimestamp(),
							"absolute" => $Date->format("Y-m-d H:i:s"),
							"relative" => time2str($Date->getTimestamp())
						),
						"id" => $row['forum_last_post_id'],
						"url" => sprintf("/f-p%d.htm#%d", $row['forum_last_post_id'], $row['forum_last_post_id']),
						"thread" => array(
							"id" => $row['lastpost_topic_id'],
							"url" => sprintf("/f-t%d.htm", $row['lastpost_topic_id'])
						),
						"author" => array(
							"id" => $row['lastpost_user_id'],
							"username" => $row['lastpost_username'], 
							"url" => sprintf("/user/%d", $row['lastpost_user_id'])
						)
					),
					"prune" => array(
						"enabled" => (bool) $row['prune_enable'],
						"next" => $row['prune_next']
					),
					"permissions" => array(
						"view" => $row['auth_view'],
						"read" => $row['auth_read'],
						"post" => $row['auth_post'],
						"reply" => $row['auth_reply'],
						"edit" => $row['auth_edit'],
						"delete" => $row['auth_delete'],
						"sticky" => $row['auth_sticky'],
						"announce" => $row['auth_announce'],
						"vote" => $row['auth_vote'],
						"pollcreate" => $row['auth_pollcreate'],
						"attachments" => $row['auth_attachments'],
					),
					"parent" => array(
						"id" => $row['forum_parent']
					)
				);
			}
			
			return $return;
		}
		
		/**
		 * Get forum reputation names/values
		 * @since Version 3.9
		 * @return array
		 */
		
		public static function getReputationTypes() {
			$reps = array(
				1 => array(
					"id" => 1,
					"name" => "Like",
					"icon" => "<span class='glyphicon glyphicon-thumbs-up'></span>",
					"count" => 0
				),
				2 => array(
					"id" => 2,
					"name" => "Informative",
					"icon" => "<span class='glyphicon glyphicon-info-sign'></span>",
					"count" => 0
				),
				3 => array(
					"id" => 3,
					"name" => "Helpful",
					"icon" => "<span class='glyphicon glyphicon-flash'></span>",
					"count" => 0
				),
				4 => array(
					"id" => 4,
					"name" => "Funny",
					"icon" => "<span class='glyphicon glyphicon-heart-empty'></span>",
					"count" => 0
				),
				5 => array(
					"id" => 5,
					"name" => "Agree",
					"icon" => "<span class='glyphicon glyphicon-ok'></span>",
					"count" => 0
				),
				6 => array(
					"id" => 6,
					"name" => "Disagree",
					"icon" => "<span class='glyphicon glyphicon-remove'></span>",
					"count" => 0
				),
			);
			
			return $reps;
		}
		
		/**
		 * Get latest post ratings/reputation rankings
		 * @since Version 3.9.1
		 * @return array
		 * @param int $limit Number of results to return
		 */
		
		public function getLatestPostRatings($limit = 10) {
			$query = "SELECT r.*, u.username, t.topic_title, f.forum_name, p.poster_id
						FROM nuke_bbposts_reputation AS r 
							LEFT JOIN nuke_users AS u ON r.user_id = u.user_id 
							LEFT JOIN nuke_bbposts AS p ON r.post_id = p.post_id
							LEFT JOIN nuke_bbtopics AS t ON p.topic_id = t.topic_id
							LEFT JOIN nuke_bbforums AS f ON p.forum_id = f.forum_id
						ORDER BY r.date DESC
						LIMIT 0, ?";
			
			$ratings = array();
			
			$reputationtypes = $this->getReputationTypes(); 
			
			foreach ($this->db->fetchAll($query, $limit) as $row) {
				$Date = new DateTime($row['date'], new DateTimeZone($this->User->timezone));
				
				$row['date'] = array(
					"absolute" => $Date->format($this->User->date_format),
					"relative" => time2str($Date->getTimestamp())
				);
				
				$row['type'] = $reputationtypes[$row['type']];
				
				$ThisUser = new User($row['user_id']);
				$row['user'] = array(
					"id" => $ThisUser->id,
					"username" => $ThisUser->username,
					"url" => $ThisUser->url->getURLs()
				);
				
				$ThisUser = new User($row['poster_id']);
				$row['author'] = array(
					"id" => $ThisUser->id,
					"username" => $ThisUser->username,
					"url" => $ThisUser->url->getURLs()
				);
				
				$Post = new Post($row['post_id']);
				
				$row['post'] = array(
					"id" => $Post->id,
					"url" => $Post->url->getURLs()
				);
				
				$row['thread'] = array(
					"id" => $Post->thread->id,
					"title" => $Post->thread->title,
					"url" => $Post->thread->url->getURLs()
				);
				
				$row['forum'] = array(
					"id" => $Post->thread->forum->id,
					"name" => $Post->thread->forum->name,
					"url" => $Post->thread->forum->url->getURLs()
				);
				
				$ratings[] = $row;
			}
			
			return $ratings;
		}
		
		/**
		 * Get number of ratings per user in the last six months
		 * @since Version 3.9.1
		 * @var int $limit Maximum number of results to return
		 * @return array
		 */
		
		public function getMostUserRatings($limit = 10) {
			$query = "SELECT r.*, COUNT(r.user_id) AS count, u.username FROM nuke_bbposts_reputation AS r LEFT JOIN nuke_users AS u ON r.user_id = u.user_id WHERE r.date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY r.user_id ORDER BY count DESC LIMIT 0, ?";
			
			return $this->db->fetchAll($query, $limit);
		}
		
		/**
		 * Get forums that this user is allowed to x
		 * @since Version 3.9.1
		 * @return array
		 * @param \Railpage\Users\User $User
		 */
		
		public function getAllowedForumsIDsForUser(User $User, $permission = self::AUTH_ALL) {
			$acl = $this->setUser($User)->getACL(); 
			
			$ids = array(); 
			$allforums = $this->getAllForums();
			
			foreach ($allforums['categories'] as $category) {
				foreach ($category['forums'] as $forum) {
					if ($acl->isAllowed("forums_viewer", sprintf("railpage.forums.forum:%d", $forum['id']), $permission)) {
						$ids[] = $forum['id'];
					}
				}
			}
			
			return $ids;
		}
		
		/**
		 * Get unread forum posts for the given user
		 * @since Version 3.9.1
		 * @param \Railpage\Users\User $User
		 * @return array
		 */
		
		public function getUnreadForumThreadsForUser(User $User, $offset = 0, $items_per_page = false) {
			$forums = $this->getAllowedForumsIDsForUser($User, "auth_read");
			
			if ($items_per_page === false) {
				$items_per_page = $User->items_per_page;
			}
			
			/**
			 * Get read forums/threads from Memcached
			 */
			
			try {
				$tracking_topics = self::getReadItemsForUser($User);
				$tracking_forums = self::getReadItemsForUser($User, "f");
				
				#printArray($tracking_topics);
				#printArray($tracking_forums);
			} catch (Exception $e) {
				// Throw it away
			}
			
			$query = "
				SELECT 
					SQL_CALC_FOUND_ROWS
					plast.post_id, plast.post_time, plast.poster_id AS user_id, u.username, 
					t.topic_id, t.topic_title, t.url_slug, t.forum_id, f.forum_name
				FROM 
					nuke_bbposts AS plast
					LEFT JOIN nuke_bbtopics AS t ON plast.post_id = t.topic_last_post_id
					LEFT JOIN nuke_bbforums AS f ON t.forum_id = f.forum_id
					LEFT JOIN nuke_users AS u ON u.user_id = plast.poster_id
				WHERE 
					plast.post_time >= ?
					AND t.forum_id IN (" . implode(', ', $forums) . ")
				ORDER BY plast.post_time DESC
				LIMIT ?, ?";
			
			$params = array($User->lastvisit, $offset, $items_per_page); 
			
			$posts = $this->db->fetchAll($query, $params); 
			return array(
				"offset" => $offset,
				"items_per_page" => $items_per_page,
				"total" => $this->db->fetchOne("SELECT FOUND_ROWS() AS total"),
				"threads" => $posts
			);
			
			#printArray($posts);die;
			
			return $posts;
			
		}
	
		/** 
		 * Unserialise an array. Extracted from functions.php, phpBB 2.0 stuff
		 * @since Version 3.9.1
		 * @param string $str
		 * @return array
		 */
		
		static public function unserializeArray($str) {
			$array = array();
			$list = explode('|', $str);
			
			for ($i = 0, $c = count($list); $i < $c; $i++) {
				$row = explode('=', $list[$i], 2);
				
				if (count($row) == 2) {
					$array[$row[0]] = $row[1];
				}
			}
			
			return $array;
		}
		
		/** 
		 * Serialise an array. Extracted from functions.php, phpBB 2.0 stuff
		 * @since Version 3.9.1
		 * @param array $array
		 * @return string
		 */
		
		static public function serialize_array($array) {
			if (!is_array($array)) {
				return '';
			}
			
			$str = '';
			
			foreach($array as $var => $value) {
				if ($str) {
					$str .= '|';
				}
				$str .= $var . '=' . str_replace('|', '', $value);
			}
			return $str;
		}
		
		/**
		 * Get read threads/forums for a given user
		 * @since Version 3.9.1
		 * @param \Railpage\Users\User $User
		 * @return array
		 */
		
		static public function getReadItemsForUser(User $User, $type = "t") {
			
			/**
			 * Not logged in - no threads/forums
			 */
			
			if ($User->id == 0) {
				return array(); 
			}
			
			/**
			 * Find the base name of this cookie/memcached object
			 */
			
			$cookiename = sprintf("%s_%s", "phpbb2mysqlrp2", $type);
			
			/**
			 * Try and get it from Memcached
			 */
			
			try {
				$key = sprintf("%s:%d", $cookiename, $User->id);
				$Memcached = AppCore::getMemcached(); 
				
				if ($result = $Memcached->fetch($key)) {
					if (!is_array($result)) {
						$result = self::unserializeArray($result);
					}
					
					if (!is_array($result)) {
						return array(); 
					}
					
					return $result;
				}
			} catch (Exception $e) {
				// throw it away
			}
			
			/**
			 * Fall back to cookies
			 */
			
			if (!is_null(filter_input(INPUT_COOKIE, $cookiename))) { #isset($_COOKIE[$cookiename])) {
				$cookiedata = filter_input(INPUT_COOKIE, $cookiename); #$_COOKIE[$cookiename];
				$data = self::unserializeArray($cookiedata);
				
				if (count($data)) {
					self::saveReadItemsForUser($User, $data, $type);
						
					return $data;
				}
			}
			
			/**
			 * Couldn't find shit
			 */
			
			return array(); 
		}
		
		/**
		 * Save read threads/forums for a given user
		 * @since Version 3.9.1
		 * @param \Railpage\Users\User $User
		 * @param array $items
		 * @param string $type
		 */
		
		static public function saveReadItemsForUser(User $User, $items, $type = "t") {
			
			$cookiename = sprintf("%s_%s", "phpbb2mysqlrp2", $type);
			
			/**
			 * Try and get it from Memcached
			 */
			
			try {
				$key = sprintf("%s:%d", $cookiename, $User->id);
				$Memcached = AppCore::getMemcached(); 
				$Memcached->save($key, $items, strtotime("+1 year"));
			} catch (Exception $e) {
				// Throw it away
			}
			
			/**
			 * Save it in a cookie just for good luck
			 */
			
			setcookie($cookiename, self::serialize_array($items), strtotime("+1 year"), RP_AUTOLOGIN_PATH, RP_AUTOLOGIN_DOMAIN, RP_SSL_ENABLED, true); 
		}
	}