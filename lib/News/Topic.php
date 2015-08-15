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
	use InvalidArgumentException;
	use DateTime;
	use DateTimeZone;
	use Railpage\Url;
	use Railpage\Module;
	use Railpage\Debug;
	use Railpage\ContentUtility;
	
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
		 * @param int|string $topic_id
		 */
		
		public function __construct($topic_id = false) {
			parent::__construct();
			
			if (filter_var($topic_id, FILTER_VALIDATE_INT)) {
				$this->id = $topic_id;
				$this->load(); 
				
			}
			
			if (is_string($topic_id)) {
				if (!$id = $this->Memcached->fetch(sprintf("railpage:news.topic.name=%s", $topic_id))) {
					$id = $this->db->fetchOne("SELECT topicid FROM nuke_topics WHERE topicname = ?", $topic_id);
					
					$this->Memcached->save(sprintf("railpage:news.topic.name=%s", $topic_id), $id);
				}
			}
				
			if (isset($id) && filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
				
				$this->load(); 
			}
		}
		
		/**
		 * Fetch this news topic
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function load() {
			
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				return;
			}
			
			$this->mckey = sprintf("railpage:news.topic=%d", $this->id);
			
			if (!$row = $this->Memcached->fetch($this->mckey)) {
				$query = "SELECT * FROM nuke_topics WHERE topicid = ?";
				
				$row = $this->db_readonly->fetchRow($query, $this->id);
				
				$this->Memcached->save($this->mckey, $row, strtotime("+6 months"));
			}
			
			$this->populate($row); 
			$this->makeURLs(); 
			
		}
		
		/**
		 * Make our URLs
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function makeURLs() {
			
			$this->url = new Url(sprintf("%s/t/%s", $this->Module->url, $this->alias));
			
		}
		
		/**
		 * Populate this object
		 * @since Version 3.9.1
		 * @return void
		 * @param array $row
		 */
		
		private function populate($row) {
			
			$this->id 		= $row['topicid']; 
			$this->alias	= $row['topicname']; 
			$this->title 	= $row['topictext'];
			$this->image	= $row['topicimage']; 
			$this->desc		= isset($row['desc']) ? $row['desc'] : "";
			
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
			
			if (!$return = $this->Memcached->fetch($mckey)) {
				// Get it from Sphinx
				
				if ($return = $this->fetchStoriesFromSphinx($page, $limit, $total)) {
					return $return;
				}
			} 
			
			/**
			 * Fetch from the database
			 */
			
			$return = $this->fetchStoriesFromDatabase($page, $limit, $total); 
			
			$this->Memcached->save($mckey, $return, $mcexp);
			
			return $return;
		}
		
		/**
		 * Fetch stories from the database
		 * @since Version 3.9.1
		 * @return array
		 * @param int $page
		 * @param int $limit
		 * @param boolean $total
		 */
		
		private function fetchStoriesFromDatabase($page = 0, $limit = 25, $total = true) {
			
			$query = "SELECT SQL_CALC_FOUND_ROWS s.*, t.topicname, t.topicimage, t.topictext, u.user_id AS informant_id FROM nuke_stories AS s LEFT JOIN nuke_topics AS t ON s.topic = t.topicid LEFT JOIN nuke_users AS u ON s.informant = u.username WHERE s.topic = ? AND s.approved = ? ORDER BY s.time DESC LIMIT ?, ?"; 
			
			$return = array(
				"total" => 0,
				"children" => array(),
				"page" => $page,
				"limit" => $limit,
				"topic_id" => $this->id
			);
			
			$params = array($this->id, 1, $page * $limit, $limit);
			
			if (!$result = $this->db_readonly->fetchAll($query, $params)) {
				return $return;
			}
			
			$return['total'] = $this->db->fetchOne("SELECT FOUND_ROWS() AS total"); 
			
			foreach ($result as $row) {
				$row['time_relative'] = ContentUtility::relativeTime(strtotime($row['time']));
				$row['title'] = ContentUtility::FormatTitle($row['title']);
				
				// Match the first sentence
				$line = explode("\n", $row['hometext']); 
				$row['firstline'] 	= preg_replace('/([^?!.]*.).*/', '\\1', strip_tags($line[0]));
					
				if (empty($row['slug'])) {
					$row['slug'] = $this->createSlug($row['sid']); 
				}
				
				$row['url'] = $this->makePermaLink($row['slug']); 
				$row['story_id'] = $row['sid'];
				
				$return['children'][] = $row; 
			}
			
			return $return;
			
		}
		
		/**
		 * Fetch stories from Sphinx
		 * @since Version 3.9.1
		 * @return array
		 * @param int $page
		 * @param int $limit
		 * @param boolean $total
		 */
		
		private function fetchStoriesFromSphinx($page = 0, $limit = 25, $total = true) {
			
			$Sphinx = $this->getSphinx();
		
			$query = $Sphinx->select("*")
					->from("idx_news_article")
					->orderBy("story_time_unix", "DESC")
					->limit($page * $limit, $limit)
					->where("topic_id", "=", $this->id)
					->where("story_active", "=", 1);
					
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
					
					$row['time_relative'] = ContentUtility::relativeTime($row['story_time_unix']);
					$row['time'] = $row['story_time'];
					$row['title'] = ContentUtility::FormatTitle($row['story_title']);
					
					// Match the first sentence
					$line = explode("\n", !empty($row['story_lead']) ? $row['story_lead'] : $row['story_blurb']); 
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
				
				return $return;
			}
			
			return false;
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
				throw new Exception("Cannot validate news topic - no title provided"); 
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
				throw new Exception("Cannot validate news topic - no alias / permalink provided"); 
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
			
			$data = array(
				"topictext" => $this->title,
				"topicname" => $this->alias,
				"desc" => $this->desc
			);
			
			if (filter_var($this->id)) {
				// Update
				
				$where = array(
					"topicid = ?" => $this->id
				);
				
				$this->Memcached->delete($this->mckey);
				
				$this->db->update("nuke_topics", $data, $where); 
				return true;
			}
			
			// Insert
			$this->db->insert("nuke_topics", $data);
			$this->id = $this->db->lastInsertId();
			return true;
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
	