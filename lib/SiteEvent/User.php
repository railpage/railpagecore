<?php
	/**
	 * Fetch timeline events for this user
	 * @since Version 3.6
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\SiteEvent;
	
	use Exception;
	use DateTime;
	
	/**
	 * User class
	 */
	
	class User extends Base {
		
		/**
		 * User ID
		 * @since Version 3.6
		 * @var int $user_id
		 */
		
		public $user_id;
		
		/**
		 * Constructor
		 * @since Version 3.6
		 */
		
		public function __construct($user_id = false) {
			try {
				parent::__construct(); 
			} catch (Exception $e) {
				throw new \Exception($e->getMessage()); 
			}
			
			if (!$user_id) {
				throw new \Exception("No user ID given"); 
				return false;
			}
			
			$this->user_id = $user_id;
		}
		
		/**
		 * Get the latest x events within y keys
		 * @since Version 3.6
		 * @param int $limit
		 * @param mixed $keys
		 * @return array
		 */
		
		public function latest($limit = 25, $keys = false, $page = 1) {
			$tidy_options = array("show-body-only" => true); 


			if (is_array($keys)) {
				$sql_keys = " WHERE `key` IN ('".implode("','", $keys)."')";
			} elseif (is_string($keys)) {
				$sql_keys = " WHERE `key` = '".$this->db->real_escape_string($keys)."'"; 
			} else {
				$sql_keys = NULL;
			}
			
			$query = "SELECT e.id, e.module, e.user_id, u.username, u.user_avatar, e.timestamp, e.title, e.args, e.key, e.value FROM log_general AS e INNER JOIN nuke_users AS u ON u.user_id = e.user_id ".$sql_keys." ORDER BY id DESC LIMIT 0, ?"; 
			
			if ($stmt = $this->db->prepare($query)) {
				$stmt->bind_param("i", $limit);
				
				$stmt->execute();
				
				$return = array(); 
				
				$stmt->bind_result($id, $module_name, $user_id, $username, $user_avatar, $timestamp, $title, $args, $key, $value);
				
				while ($stmt->fetch()) {
					$row['id'] = $id; 
					$row['module'] = $module_name;
					$row['user_id'] = $user_id; 
					$row['username'] = $username;
					$row['user_avatar'] = $user_avatar;
					$row['timestamp'] = new \DateTime($timestamp); 
					$row['title'] = $title; 
					$row['args'] = json_decode($args, true); 
					$row['key'] = $key; 
					$row['value'] = $value; 
					
					$return[$timestamp] = $row;
				}
				
				$stmt->close();
				
				/**
				 * Get forum posts
				 */
				
				$query = "SELECT p.post_id, FROM_UNIXTIME( p.post_time ) AS post_time
					FROM nuke_bbposts AS p
					INNER JOIN nuke_bbtopics_watch AS w ON p.topic_id = w.topic_id
					WHERE w.user_id = ?
					AND p.poster_id != ?
					ORDER BY p.post_time DESC
					LIMIT 0, 30";
				
				if ($stmt = $this->db->prepare($query)) {
					$stmt->bind_param("ii", $this->user_id, $this->user_id);
					
					$stmt->execute();
					
					$stmt->bind_result($post_id, $post_time);
					
					while ($stmt->fetch()) {
						$post_ids[$post_id] = $post_time;
					}
					
					$stmt->close(); 
					
					foreach (array_keys($post_ids) as $post_id) {
						try {
							$Post = new \Railpage\Forums\Post($this->db, $post_id);
						} catch (\Exception $e) {
							#printArray($e->getMessage());
							//throw new \Exception($e->getMessage()); 
						}
						
						$row['post_id'] = $Post->id; 
						$row['module'] = "forums";
						$row['user_id'] = $Post->uid; 
						$row['username'] = $Post->username;
						$row['user_avatar'] = $Post->user_avatar;
						$row['timestamp'] = new \DateTime($post_ids[$post_id]); 
						$row['title'] = "Posted in ".$Post->thread->title; 
						$row['args'] = array(
							"Thread title" => $Post->thread->title,
							"Thread ID" => $Post->thread->id,
							"Forum name" => $Post->thread->forum->name,
							"Forum ID" => $Post->thread->forum->id,
							"Post excerpt" => $Post->text,
							"Object path" => "Forums &raquo; " . $Post->thread->forum->name,
						);
						
						if (strlen($row['args']['Post excerpt']) > 256) {
							$row['args']['Post excerpt'] = format_post(tidy_parse_string(trim(substr($row['args']['Post excerpt'], 0, 256))))."..."; 
						}
						
						$row['event_title'] = "<a href='/f-p".$Post->id.".htm#".$Post->id."'>".$Post->thread->title."</a>";
						
						$row['action'] = "posted in";
						$row['key'] = "post_id";
						$row['value'] = $Post->id;
						
						$return[$post_ids[$post_id]] = $row;
					}
				}
				
				try {
					$NewsBase = new \Railpage\News\Base($this->db);
					
					$latest = $NewsBase->latest(10);
					
					foreach ($latest as $id => $data) {
						$row = array(); 
						
						$row['article_id'] = $data['sid']; 
						$row['user_id'] = $data['user_id'];
						$row['username'] = $data['username']; 
						$row['user_avatar'] = $data['user_avatar']; 
						$row['article_title'] = $data['title']; 
						$row['timestamp'] = new \DateTime($data['time']);
						$row['action'] = "published"; 
						$row['event_title'] = $data['title']; 
						$row['title'] = "Published a news story";
						
						$row['args']['First line'] = $data['firstline']; 
						$row['args']['Topic ID'] = $data['topic'];
						$row['args']['Topic title'] = $data['topictext']; 
						$row['args']['Object path'] = "News &raquo; ".$data['topictext'];
						
						$row['key'] = "article_id";
						$row['value'] = $data['sid'];
						
						$return[$data['time']] = $row;
					}
				} catch (Exception $e) {
					
				}
				
				krsort($return);
					
				return $return;
			} else {
				throw new \Exception($this->db->error."\n\n".$query); 
				return false;
			}
		}
	}
	