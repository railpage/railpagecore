<?php
	/**
	 * News classes
	 * @since Version 3.0.1
	 * @version 3.8.7
	 * @author Michael Greenhill
	 * @package Railpage
	 * @copyright Copyright (c) 2012 Michael Greenhill
	 */
	 
	namespace Railpage\News;
	
	use DateTime;
	use Exception;
	use Railpage\Users\User;
	use Railpage\Url;
		
	/**
	 * News article display and management
	 * @author Michael Greenhill
	 * @version 3.0.1
	 * @since Version 3.8.7
	 * @package Railpage
	 * @copyright Copyright (c) 2012 Michael Greenhill
	 */
	
	class Article extends Base {
		
		/**
		 * Story ID
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $id
		 */
		
		public $id; 
		
		/**
		 * Story title
		 * @since Version 3.3
		 * @var string $title
		 */
		
		public $title;
		
		/**
		 * Story blurb
		 * @since Version 3.3
		 * @var string $blurb
		 */
		
		public $blurb;
		
		/**
		 * Story blurb - stripped of BBCode
		 * @since Version 3.8
		 * @var string $blurb_clean
		 */
		
		public $blurb_clean;
		
		/**
		 * Story body
		 * @since Version 3.3
		 * @var string $body
		 */
		
		public $body;
		
		/**
		 * Story body - stripped of BBCode
		 * @since Version 3.8
		 * @var string $body_clean
		 */
		
		public $body_clean;
		
		/**
		 * Hits this week
		 * @since Version 3.3
		 * @var int $hits
		 */
		
		public $hits;
		
		/**
		 * Author user ID
		 * @since Version 3.3
		 * @var int $user_id
		 */
		
		public $user_id;
		
		/**
		 * Author username
		 * @since Version 3.3
		 * @var string $username
		 */
		
		public $username;
		
		/**
		 * Staff member user ID
		 * @since Version 3.3
		 * @var int $staff_user_id
		 */
		
		public $staff_user_id;
		
		/**
		 * Staff member username
		 * @since Version 3.3
		 * @var string $staff_username
		 */
		
		public $staff_username;
		
		/**
		 * Forum thread ID
		 * @since Version 3.3
		 * @var int $topic_id
		 */
		
		public $topic_id;
		
		/**
		 * Source URL
		 * @since Version 3.3
		 * @var string $source
		 */
		
		public $source;
		
		/**
		 * Date added
		 * @since Version 3.3
		 * @var object $date
		 */
		
		public $date;
		
		/** 
		 * Approved
		 * @since Version 3.3
		 * @var boolean $approved
		 */
		
		public $approved;
		
		/**
		 * Topic object
		 * @since Version 3.3
		 * @var object $Topic
		 */
		
		public $Topic;
		
		/**
		 * If $pending set to true, we need to retrieve the story from the pending table
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var boolean $pending
		 */
		
		public $pending = false; 
		
		/** 
		 * Latitude of point of interest
		 * @since Version 3.5
		 * @var float $lat
		 */
		
		public $lat;
		
		/** 
		 * Longitude of point of interest
		 * @since Version 3.5
		 * @var float $lon
		 */
		
		public $lon;
		
		/**
		 * Flag - has this article been sent to Facebook
		 * @since Version 3.6
		 * @var boolean $sent_to_fb
		 */
		
		public $sent_to_fb;
		
		/**
		 * URL Slug
		 * @since Version 3.7.5
		 * @var string $slug
		 */
		
		public $slug;
		
		/**
		 * URL relative to site root
		 * @since Version 3.7.5
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Featured image URL
		 * @since Version 3.8
		 * @var string $featured_image
		 */
		
		public $featured_image = false;
		
		/**
		 * Fwlink (shortcut) object
		 * @since Version 3.8.6
		 * @var object $fwlink
		 */
		
		public $fwlink;
		
		/**
		 * Constructor
		 * @since Version 3.0.1
		 * @version 3.3
		 * @param int $id
		 * @param boolean $pending
		 */
		
		public function __construct($id = false, $pending = false) {
			
			$this->pending = $pending;
			
			// Set the parent constructor
			parent::__construct(); 
			
			if ($id) {
				$this->id = intval($id); 
				
				$this->fetch(); 
			}
		}
		
		/**
		 * Return news article text
		 * @since Version 3.0.1
		 * @version 3.4
		 * @param int $id
		 * @return mixed
		 */
		
		public function fetch($id = false) {
			if ($id) {
				$this->id = $id; 
			}
			
			if (!$this->id) {
				throw new Exception("Cannot fetch news article - no ID given"); 
				return false;
			}
			
			$return = false;
			
			$mckey = __METHOD__ . "-" . $this->id; 
			$mcexp = strtotime("+1 hour"); 
			
			/*
			if ($this->pending === false && $this->memcache && $return = $this->memcache->get($mckey)) {
				// Do nothing
				printArray($return);die;
			} else {
			*/
			if ($this->db instanceof \sql_db) {
				if ($this->pending == true) {
					// Get story from pending table
					$query = "SELECT u.username, 0 AS sent_to_fb, p.geo_lat, p.geo_lon, p.qid as sid, p.uname as informant, p.subject as title, p.story as hometext, p.storyext as bodytext, p.topic, p.source, t.topicname, t.topicimage, t.topictext, p.timestamp as time FROM nuke_users u, nuke_queue p, nuke_topics t WHERE p.topic = t.topicid AND p.uid = u.user_id AND p.qid = '".$this->db->real_escape_string($this->id)."'";
					$table = "pending";
				} else {
					$query = "SELECT s.*, t.topicname, t.topicimage, t.topictext, t.topicid FROM nuke_stories s, nuke_topics t WHERE s.topic = t.topicid AND s.sid = '".$this->db->real_escape_string($this->id)."'";
					$table = "published";
				}
				
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows == 0) {
						throw new Exception("Cannot fetch ".$table." article ID ".$this->id." - no story found"); 
						return false;
					}
					
					$return = $rs->fetch_assoc(); 
					
					if ($this->pending === false && $this->memcache) {
						$this->memcache->set($mckey, $return, $mcexp);
					}
				}
			} else {
				if ($this->pending == true) {
					$query = "SELECT q.qid AS sid, q.uid AS user_id, u.username, q.subject AS title, q.story AS hometext, q.storyext AS bodytext, q.timestamp AS time, q.source, t.topicname, t.topictext, q.topic, 'oldqueue' AS queue
								FROM nuke_queue AS q
								LEFT JOIN nuke_topics AS t ON q.topic = t.topicid
								LEFT JOIN nuke_users AS u ON q.uid = u.user_id
								WHERE q.qid = ?";
				} else {
					$query = "SELECT s.*, t.topicname, t.topicimage, t.topictext, t.topicid 
								FROM nuke_stories AS s 
								LEFT JOIN nuke_topics AS t ON s.topic = t.topicid
								WHERE s.sid = ?";
				}
				
				$return = $this->db_readonly->fetchRow($query, $this->id); 
			}
			/*
			}
			*/
			
			if (isset($return) && is_array($return) && !empty($return)) {
				
				$this->title			= $return['title']; 
				$this->blurb			= $return['hometext'];
				$this->body				= $return['bodytext'];
				$this->date				= new DateTime($return['time']); 
				$this->hits				= isset($return['weeklycounter']) ? intval($return['weeklycounter']) : 0; 
				$this->Topic			= new Topic($return['topic']);
				$this->user_id			= isset($return['user_id']) ? intval($return['user_id']) : 0; 
				$this->staff_user_id	= isset($return['staff_id']) ? intval($return['staff_id']) : 0; 
				$this->topic_id			= isset($return['ForumThreadID']) ? intval($return['ForumThreadID']) : 0;
				$this->source			= $return['source']; 
				$this->approved			= isset($return['approved']) ? (bool)$return['approved'] : false;
				$this->sent_to_fb		= isset($return['sent_to_fb']) ? (bool)$return['sent_to_fb'] : false;
				$this->featured_image	= isset($return['featured_image']) ? $return['featured_image'] : false;
				
				if (isset($return['geo_lat']) && !empty($return['geo_lat']) && isset($return['geo_lon']) && !empty($return['geo_lon'])) {
					$this->lat = $return['geo_lat']; 
					$this->lon = $return['geo_lon']; 
				}
				
				if (empty($return['slug'])) {
					$return['slug'] = $this->createSlug(); 
				}
				
				$this->slug = $return['slug'];
				$this->url = new Url($this->makePermaLink($this->slug)); 
				
				$this->blurb_clean	= format_post($this->blurb, false, false, true, true, true);
				$this->body_clean	= format_post($this->body, false, false, true, true, true);
				
				$ThisUser = new User($this->user_id); 
				$this->username = $ThisUser->username;
				
				$ThisUser = new User($this->staff_user_id);
				$this->staff_username = $ThisUser->username;
				
				unset($ThisUser);
				
				// Rest of this shit is for backwards compatibility
				$whitespace_find = array("<p> </p>", "<p></p>", "<p>&nbsp;</p>");
				$whitespace_replace = array("", "", ""); 
		
				$return['hometext'] = format_post(str_replace($whitespace_find, $whitespace_replace, $return['hometext'])); 
				$return['bodytext'] = format_post(str_replace($whitespace_find, $whitespace_replace, $return['bodytext'])); 
				
				$return['hometext'] = convert_to_utf8($return['hometext']);
				$return['bodytext'] = convert_to_utf8($return['bodytext']);
				
				$return['hometext'] = wpautop($return['hometext']);
				$return['bodytext'] = process_multimedia(wpautop($return['bodytext']));
				
				try {
					$this->fwlink = new \Railpage\fwlink($this->url);
					
					if (empty($this->fwlink->url)) {
						$this->fwlink->url = $this->url;
						$this->fwlink->title = $this->title;
						$this->fwlink->commit();
					}
				} catch (Exception $e) {
					global $Error; 
					$Error->save($e); 
				}
			} else {
				throw new Exception($this->db->error."\n\n".$query);
				return false;
			}
			
			return $return;
		}
		
		/**
		 * Edit a story
		 * @version 3.0
		 * @since Version 3.0
		 * @param int $id
		 * @param array $args
		 * @return mixed
		 */
		
		public function edit($args = false) {
			if (!is_array($args)) {
				return false;
			}
			
			throw new Exception("Deprecated! Use " . __CLASS__ . "::commit() instead"); 
			return false;
			
			$where	= array("sid" => $this->db->real_escape_string($this->id)); 
			$query	= $this->db->buildQuery($args, "nuke_stories", $where); 
			
			if ($rs = $this->db->query($query)) {
				return true;
			} else {
				trigger_error("News: unable to edit story id ".$id); 
				trigger_error($this->db->error); 
				trigger_error($query); 
				
				return false;
			}
		}
		
		/**
		 * Someone has viewed an article, update the view count
		 * @version 3.0
		 * @since Version 3.0
		 * @return mixed
		 * @param int $id
		 */
		 
		public function viewed() {
			if ($this->db instanceof \sql_db) {
				if ($rs = $this->db->query("UPDATE nuke_stories SET weeklycounter = weeklycounter+1 WHERE sid = ".$this->db->real_escape_string($this->id))) {
					return true;
				} else {
					trigger_error("News: Unable to update view count for story ID ".$this->id); 
					trigger_error($this->db->error); 
					return false;
				}
			} else {
				$data = array(
					"weeklycounter" => new \Zend_Db_Expr('weeklycounter + 1')
				);
				
				$where = array(
					"sid = ?" => $this->id
				);
				
				$this->db->update("nuke_stories", $data, $where); 
				return true;
			}
		}
		
		/**
		 * Reject pending news item
		 * @version 3.0
		 * @since Version 3.0
		 * @return mixed
		 * @param int $id
		 * @param boolean $pending
		 */
		 
		public function reject() {
			if ($this->db instanceof \sql_db) {
				if ($this->pending) {
					$query = "DELETE FROM nuke_queue WHERE qid = '".$this->db->real_escape_string($this->id)."'";
				} else {
					$query = "DELETE FROM nuke_stories WHERE sid = '".$this->db->real_escape_string($this->id)."'";
				}
				
				if ($this->db->query($query)) {
					return true; 
				} else {
					throw new Exception($this->db->error."\n\n".$query); 
					return false;
				}
			} else {
				if ($this->pending) {
					$where = array("qid = ?" => $this->id); 
					
					$this->db->delete("nuke_queue", $where); 
				} else {
					$where = array("sid = ?" => $this->id); 
					
					$this->db->delete("nuke_stories", $where); 
				}
				
				return true;
			}
		}
		
		/**
		 * Approve a story
		 * @version 3.0
		 * @since Version 3.0
		 * @param int $user_id
		 * @return mixed
		 */
		 
		public function approve($user_id = false) {
			if (!$this->id || !$this->db) {
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query	= "SELECT u.username as informant, p.geo_lat, p.geo_lon, p.subject as title, p.story as hometext, p.storyext as bodytext, p.topic, p.source FROM nuke_queue p, nuke_users u WHERE u.user_id = p.uid AND p.qid = ".$this->db->real_escape_string($this->id);
				
				if ($rs = $this->db->query($query)) {
					$dataArray = $rs->fetch_assoc(); 
					$dataArray['time'] 		 = "NOW()";
					$dataArray['comments']	 = "1";
					
					foreach($dataArray as $key => $val) {
						$dataArray[$key] = $this->db->real_escape_string($val); 
					}
					
					$query = $this->db->buildQuery($dataArray, "nuke_stories"); 
					
					if ($approve = $this->db->query($query)) {
						$return = $this->db->insert_id; 
						// Delete the pending post
						$this->db->query("DELETE FROM nuke_queue WHERE qid = ".$this->db->real_escape_string($this->id)); 
						
						$this->id = $return;
						
						return true;
					} else {
						throw new Exception($this->db->error."\n\n".$query);
						return false;
					}
				} else {
					throw new Exception($this->db->error."\n\n".$query);
					return false;
				}
			} else {
				if ($this->pending) {
					$query	= "SELECT u.username as informant, p.geo_lat, p.geo_lon, p.subject as title, p.story as hometext, p.storyext as bodytext, p.topic, p.source FROM nuke_queue p, nuke_users u WHERE u.user_id = p.uid AND p.qid = ?";
					
					if ($data = $this->db->fetchRow($query, $this->id)) {
						$data['approved'] = 1;
						$data['aid'] = $user_id;
						$data['time'] = new \Zend_Db_Expr('NOW()');
						
						$this->db->insert("nuke_stories", $data);
						
						$id = $this->db->lastInsertId(); 
						
						$this->db->delete("nuke_queue", array("qid = ?" => $this->id));
						
						$this->id = $id;
						return true;
					}
				} else {
					$this->approved = 1; 
					$this->staff_user_id = $user_id;
					$this->date = new DateTime;
					
					return $this->commit(); 
				}
			}
		}
		
		/**
		 * Commit changes to a story
		 * @since Version 3.4
		 * @return boolean
		 */
		
		public function commit() {
			try {
				$this->validate(); 
			} catch (Exception $e) {
				throw new Exception($e->getMessage()); 
			} 
				
			// Format the article blurb
			try {
				$this->blurb = prepare_submit($this->blurb); 
			} catch (Exception $e) {
				global $Error; 
				$Error->save($e); 
			} 
			
			// Format the article body
			try {
				$this->body = prepare_submit($this->body); 
			} catch (Exception $e) {
				global $Error; 
				$Error->save($e); 
			}
			
			if ($this->Topic instanceof \Railpage\Forums\Thread) {
				$this->topic_id = $this->Topic->id;
			}
			
			$dataArray = array(); 
			
			$dataArray['approved'] 	= $this->approved; 
			$dataArray['title']		= $this->title;
			$dataArray['hometext']	= $this->blurb->__toString(); 
			$dataArray['bodytext']	= $this->body->__toString(); 
			$dataArray['ForumThreadID']		= $this->topic_id; 
			$dataArray['source']	= $this->source; 
			$dataArray['user_id']	= $this->user_id; 
			$dataArray['staff_id']	= empty($this->staff_user_id) ? 0 : $this->staff_user_id;
			$dataArray['geo_lat']	 	= empty($this->lat) ? 0 : $this->lat; 
			$dataArray['geo_lon']		= empty($this->lon) ? 0 : $this->lon;  
			$dataArray['sent_to_fb']	= (bool)$this->sent_to_fb;
			$dataArray['time'] = $this->date->format("Y-m-d H:i:s");
			$dataArray['slug'] = $this->createSlug();
			$dataArray['topic'] = $this->Topic->id;
			
			if ($this->featured_image !== false) {
				$dataArray['featured_image'] = $this->featured_image;
			}
			
			if (!empty($this->username)) {
				$dataArray['informant'] = $this->username;
			}
			
			if ($this->db instanceof \sql_db) {
				foreach ($dataArray as $key => $val) {
					$dataArray[$key] = $this->db->real_escape_string($val); 
				}
				
				if ($this->id > 0) {
					$where = array("sid" => $this->db->real_escape_string($this->id)); 
					$query = $this->db->buildQuery($dataArray, "nuke_stories", $where); 
				} else {
					$query = $this->db->buildQuery($dataArray, "nuke_stories"); 
				}
				
				if ($rs = $this->db->query($query)) {
					if ($this->id < 1) {
						$this->id = $this->db->insert_id; 
					}
					
					return true; 
				} else {
					throw new Exception("Could not commit changes to article\n\n".$this->db->error."\n\n".$query); 
					return false;
				}
			} else {
				if (!empty($this->id) && $this->id > 0) {
					$where = array(
						"sid = ?" => $this->id
					);
					
					$result = $this->db->update("nuke_stories", $dataArray, $where); 
					
					if ($result === 0) {
						return true;
					}
					
					return $result;
				} else {
					if ($this->db->insert("nuke_stories", $dataArray)) {
						$this->id = $this->db->lastInsertId();
						
						return true;
					} else {
						return false;
					}
				}
			}
		}
		
		/**
		 * Validate changes to an article before committing changes
		 * @since Version 3.4
		 * @return boolean 
		 */
		
		public function validate() {
			if (empty($this->title)) {
				throw new Exception("Validation failed: title is empty"); 
				return false;
			}
			
			if (empty($this->blurb)) {
				throw new Exception("Validation failed: blurb is empty"); 
				return false;
			}
			
			if (empty($this->body)) {
				throw new Exception("Validation failed: body is empty"); 
				return false;
			}
			
			/**
			 * Try to get the featured image from OpenGraph tags
			 */
			
			if ($this->source && !$this->featured_image) {
				require_once("vendor" . DS . "scottmac" . DS . "opengraph" . DS . "OpenGraph.php");
				$graph = \OpenGraph::fetch($this->source);
				
				#printArray($graph->keys()); 
				#printArray($graph->schema);
				
				foreach ($graph as $key => $value) {
					if ($key == "image" && strlen($value) > 0) {
						$this->featured_image = $value;
					}
				}
			}
			
			return true;
		}
	}
?>