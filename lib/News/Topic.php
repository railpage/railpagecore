<?php
	/**
	 * News classes
	 * @since Version 3.0.1
	 * @version 3.9
	 * @author Michael Greenhill
	 * @package Railpage
	 * @copyright Copyright (c) 2012 Michael Greenhill
	 */
	 
	namespace Railpage\News;
	
	use Exception;
	use Railpage\Url;
	use Railpage\Module;
	
	// Make sure the parent class is loaded
		
	/**
	 * News topics
	 * @since Version 3.0.1
	 * @version 3.0.1
	 * @author Michael Greenhill
	 * @package Railpage
	 * @copyright Copyright (c) 2012 Michael Greenhill
	 */
	
	class Topic extends Base {
		
		/** 
		 * Topic ID
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $topic_id
		 */
		
		public $id;
		
		/** 
		 * Topic alias
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var string $topic_alias
		 */
		
		public $alias;
		
		/** 
		 * Topic title
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $topic_title
		 */
		
		public $title;
		
		/** 
		 * Topic image
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $topic_image
		 */
		
		public $image;
		
		/**
		 * Descriptive text
		 * @since Version 3.8
		 * @var string $desc
		 */
		
		public $desc;
		
		/** 
		 * Constructor
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @param object $db
		 * @param int $topic_id
		 */
		
		public function __construct($topic_id = false) {			
			$this->id = $topic_id; 
			
			parent::__construct();
			
			if ($this->id) {
				$this->mckey = "railpage:news.topic=" . $topic_id;
				
				if ($this->db instanceof \sql_db) {
					$query = "SELECT * FROM nuke_topics WHERE topicid = '".$this->db->real_escape_string($this->id)."'";
					
					if ($rs = $this->db->query($query)) {
						if ($rs->num_rows == 1) {
							$row = $rs->fetch_assoc(); 
							
							$this->id 		= $row['topicid']; 
							$this->alias	= $row['topicname']; 
							$this->title 	= $row['topictext'];
							$this->image	= $row['topicimage']; 
							$this->desc		= $row['desc'];
						}
					} else {
						trigger_error(__CLASS__.": Could not retrieve topic ID ".$topic_id); 
						trigger_error($this->db->error); 
						trigger_error($query); 
						
						return false;
					}
				} else {
					if (!$row = getMemcacheObject($this->mckey)) {
						$query = "SELECT * FROM nuke_topics WHERE topicid = ?";
						
						$row = $this->db_readonly->fetchRow($query, $this->id);
						
						setMemcacheObject($this->mckey, $row, strtotime("+6 months"));
					}
					
					$this->id 		= $row['topicid']; 
					$this->alias	= $row['topicname']; 
					$this->title 	= $row['topictext'];
					$this->image	= $row['topicimage']; 
					$this->desc		= isset($row['desc']) ? $row['desc'] : "";
				}
			}
			
			if (!empty($this->alias)) {
				$this->url = new Url(sprintf("%s/t/%s", $this->Module->url, $this->alias));
			}
		}
		
		/**
		 * Get stories from topic
		 * @version 3.0
		 * @since Version 3.0
		 * @return mixed
		 * @param int $page
		 * @param int $limit
		 * @param boolean $total
		 */
		 
		public function stories($page = 0, $limit = 25, $total = true) {
			$return = false;
			$mckey = "railpage:topic_id=" . $this->id . ".stories.page=" . $page . ".limit=" . $limit . ".total=" . (int)$total;
			$mcexp = strtotime("+1 hour"); 
			
			if (!$return = getMemcacheObject($mckey)) {
				// Get it from Sphinx
				
				$Sphinx = $this->getSphinx();
			
				$query = $Sphinx->select("*")
						->from("idx_news_article")
						->orderBy("story_time_unix", "DESC")
						->limit($page * $limit, $limit)
						->where("topic_id", "=", $this->id);
						
				$matches = $query->execute(); 
				
				$meta = $Sphinx->query("SHOW META");
				$meta = $meta->execute();
				
				if (is_array($matches) && count($matches)) {
					$return = array(
						"total" => $meta[1]['Value'],
						"children" => array(),
						"page" => $page,
						"perpage" => $limit,
						"topic_id" => $this->id
					);
					
					foreach ($matches as $id => $row) {
						
						$row['time_relative'] = time2str($row['story_time_unix']);
						$row['time'] = $row['story_time'];
						$row['title'] = format_topictitle($row['story_title']);
						
						// Match the first sentence
						$line = explode("\n", $row['story_blurb']); 
						$row['firstline'] = preg_replace('/([^?!.]*.).*/', '\\1', strip_tags($line[0]));
							
						if (empty($row['story_slug'])) {
							$row['slug'] = $this->createSlug($row['story_id']); 
						}
						
						$row['url'] = $this->makePermaLink($row['story_slug']); 
						$row['hometext'] = $row['story_blurb'];
						$row['bodytext'] = $row['story_body'];
						$row['featured_image'] = $row['story_image'];
						$row['informant'] = $row['username'];
						
						$return['children'][$id] = $row;
					}
				}
			} 
			
			if (!isset($return) || $return === false || !is_array($return)) {
				$query = "SELECT SQL_CALC_FOUND_ROWS s.*, t.topicname, t.topicimage, t.topictext, u.user_id AS informant_id FROM nuke_stories AS s LEFT JOIN nuke_topics AS t ON s.topic = t.topicid LEFT JOIN nuke_users AS u ON s.informant = u.username WHERE s.topic = ? AND s.approved = ? ORDER BY s.time DESC LIMIT ?, ?"; 
				
				$return = array(); 
				$return['total'] 	= 0;
				$return['children'] = array(); 
				$return['page'] 	= $page; 
				$return['perpage'] 	= $limit; 
				$return['topic_id'] = $this->id;
				
				if ($result = $this->db_readonly->fetchAll($query, array($this->id, "1", $page * $limit, $limit))) {
					$return['total'] 	= $this->db_readonly->fetchOne("SELECT FOUND_ROWS() AS total"); 
					
					foreach ($result as $row) {
						if (function_exists("relative_date")) {
							$row['time_relative'] = relative_date(strtotime($row['time']));
						} else {
							$row['time_relative'] = $row['time'];
						}
						
						$row['title'] = format_topictitle($row['title']);
						
						// Match the first sentence
						$line = explode("\n", $row['hometext']); 
						$row['firstline'] 	= preg_replace('/([^?!.]*.).*/', '\\1', strip_tags($line[0]));
							
						if (empty($row['slug'])) {
							$row['slug'] = $this->createSlug($row['sid']); 
						}
						
						$row['url'] = $this->makePermaLink($row['slug']); 
						
						$return['children'][] = $row; 
					}
				}
				
				setMemcacheObject($mckey, $return, $mcexp);
			}
			
			return $return;
		}
		
		/**
		 * Alias for $this->stories()
		 * @since Version 3.8.7
		 * @return mixed
		 * @param int $page
		 * @param int $limit
		 * @param boolean $total
		 */
		
		public function getStories($page = 0, $limit = 25, $total = true) {
			return $this->stories($page, $limit, $total);
		}
		
		/**
		 * Validate the topic
		 * @since Version 3.5
		 * @return boolean
		 */
		
		public function validate() {
			if (empty($this->title)) {
				throw new \Exception("Cannot validate news topic - no title provided"); 
				return false;
			}
			
			if (empty($this->alias)) {
				$this->alias = create_slug($this->title);
				
				if ($this->id > 0) {
					$result = $this->db_readonly->fetchAll("SELECT topicname FROM nuke_topics WHERE topicname = ? AND topicid != ?", array($this->alias, $this->id)); 
				} else {
					$result = $this->db_readonly->fetchAll("SELECT topicname FROM nuke_topics WHERE topicname = ?", $this->alias); 
				}
			
				if (count($result)) {
					$this->alias .= count($result);
				}
			}
			
			if (empty($this->alias)) {
				throw new \Exception("Cannot validate news topic - no alias / permalink provided"); 
				return false;
			}
			
			return true;
		}
		
		/**
		 * Commit this news topic
		 * @since Vesion 3.5
		 * @return boolean
		 */
		
		public function commit() {
			if (!$this->validate()) {
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$dataArray = array(); 
				$dataArray['topictext'] = $this->db->real_escape_string($this->title); 
				$dataArray['topicname'] = $this->db->real_escape_string($this->alias); 
				$dataArray['desc'] = $this->db->real_escape_string($this->desc);
				
				if ($this->id) {
					$where = array(); 
					$where['topicid'] = $this->db->real_escape_string($this->id); 
					
					removeMemcacheObject($this->mckey);
					
					$query = $this->db->buildQuery($dataArray, "nuke_topics", $where); 
				} else {
					$query = $this->db->buildQuery($dataArray, "nuke_topics"); 
				}
				
				if ($rs = $this->db->query($query)) {
					return true;
				} else {
					throw new \Exception($this->db->error."\n\n".$query); 
					return false;
				}
			} else {
				$data = array(
					"topictext" => $this->title,
					"topicname" => $this->alias,
					"desc" => $this->desc
				);
				
				if ($this->id) {
					$where = array(
						"topicid = ?" => $this->id
					);
					
					removeMemcacheObject($this->mckey);
					
					$this->db->update("nuke_topics", $data, $where); 
					return true;
				} else {
					$this->db->insert("nuke_topics", $data);
					$this->id = $this->db->lastInsertId();
					return true;
				}
			}
		}
		
		/**
		 * Get most read stories this week
		 * @since Version 3.9
		 * @yield new \Railpage\News\Article
		 */
		
		public function yieldMostReadThisWeek($items_per_page = 25) {
			$query = "SELECT sid FROM nuke_stories WHERE topic = ? ORDER BY weeklycounter DESC LIMIT 0, ?";
			
			foreach ($this->db->fetchAll($query, array($this->id, $items_per_page)) as $row) {
				yield new Article($row['sid']);
			}
		}
	}
?>