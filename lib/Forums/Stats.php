<?php
	
	/**
	 * Forums API
	 * @since Version 3.0.1
	 * @version 3.2
	 * @package Railpage
	 * @author James Morgan, Michael Greenhill
	 */
	 
	namespace Railpage\Forums;
	
	use DateTime;
	use Exception;

	/** 
	 * phpBB stats
	 * @since Version 3.2
	 * @version 3.2
	 * @author Michael Greenhill
	 * @package Railpage
	 */
	
	class Stats extends Forums {
		
		/**
		 * List of forums these stats can be run against
		 * @since Version 3.2
		 * @var array $forum_list
		 */
		
		public $forum_list;
		
		/**
		 * Constructor
		 * @since Version 3.2
		 * @version 3.2
		 * @param object $db
		 * @param array $forum_list
		 */
		
		public function __construct() {
			parent::__construct();
			
			foreach (func_get_args() as $arg) {
				if (filter_var($arg, FILTER_VALIDATE_INT) || is_array($arg)) {
					if (is_array($arg)) {
						$forum_list = array_keys($arg);		
									
						if ($key = array_search(37, $forum_list)) {
							unset($forum_list[$key]); 
						}
						
						$this->forum_list = $forum_list;
					}
				}
			}
		}
		
		/** 
		 * Number of posts between dates
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $start
		 * @param int $end
		 * @return int
		 */
		
		public function posts($start = false, $end) {
			if (empty($end)) {
				$end = time(); 
			}
			
			if (!$start) {
				throw new Exception(__CLASS__."::".__FUNCTION__." requires a start date"); 
				return false;
			}
			
			if ($start > $end) {
				throw new Exception("Start date (".$start.") cannot be greater than end date (".$end.") - you need to swap the function args around"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT post_id FROM nuke_bbposts WHERE post_time >= ".$this->db->real_escape_string($start)." AND post_time <= ".$this->db->real_escape_string($end) . " AND forum_id NOT IN (31,37)"; 
				
				if ($rs = $this->db->query($query)) {
					return $rs->num_rows; 
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$query = "SELECT post_id FROM nuke_bbposts WHERE post_time >= ? AND post_time <= ? AND forum_id NOT IN (31,37)";
				
				$result = $this->db->fetchAll($query, array($start, $end)); 
				
				return $result === false ? 0 : count($result); 
			}
		}
		
		/**
		 * Most active forums
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $num_forums
		 * @return array
		 */
		
		public function activeForums($num_forums = 1, $age) {
			if (empty($age)) {
				$age = strtotime("30 days ago"); 
			}
			
			if (!empty($this->forum_list)) {
				$forum_list_exclusions = "AND p.forum_id IN (".implode(",", $this->forum_list).")";
			} else {
				$forum_list_exclusions = "";
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT COUNT(*) AS num_posts, p.post_id, f.forum_id, f.forum_name 
							FROM nuke_bbposts AS p 
							LEFT JOIN nuke_bbforums AS f ON p.forum_id = f.forum_id 
							WHERE p.post_time >= ".$this->db->real_escape_string($age)." ".$forum_list_exclusions." 
							GROUP BY forum_id 
							ORDER BY num_posts 
							DESC LIMIT 0, ".$this->db->real_escape_string($num_forums); 
				
				if ($rs = $this->db->query($query)) {
					while ($row = $rs->fetch_assoc()) {
						$forums[$row['forum_id']] = $row; 
					}
					
					return $forums;
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$query = "SELECT COUNT(*) AS num_posts, p.post_id, f.forum_id, f.forum_name 
							FROM nuke_bbposts AS p 
							LEFT JOIN nuke_bbforums AS f ON p.forum_id = f.forum_id 
							WHERE p.post_time >= ? ".$forum_list_exclusions." 
							AND p.poster_id NOT IN (23967)
							GROUP BY forum_id 
							ORDER BY num_posts 
							DESC LIMIT 0, ?";
				
				$forums = array(); 
				
				foreach ($this->db->fetchAll($query, array($age, $num_forums)) as $row) {
					$forums[$row['forum_id']] = $row; 
				}
				
				return $forums;
			}
		}
		
		/**
		 * Most active threads
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $num_forums
		 * @return array
		 */
		
		public function activeThreads($num_threads = 1, $age) {
			if (empty($age)) {
				$age = strtotime("30 days ago"); 
			}
			
			if (!empty($this->forum_list)) {
				$forum_list_exclusions = "AND p.forum_id IN (".implode(",", $this->forum_list).")";
			} else {
				$forum_list_exclusions = "";
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT COUNT(*) AS num_posts, p.post_id, f.forum_id, f.forum_name, t.topic_id, t.topic_title 
							FROM nuke_bbposts AS p 
							LEFT JOIN nuke_bbforums AS f ON p.forum_id = f.forum_id 
							LEFT JOIN nuke_bbtopics AS t ON t.topic_id = p.topic_id
							WHERE p.post_time >= ".$this->db->real_escape_string($age)." ".$forum_list_exclusions." 
							GROUP BY t.topic_id 
							ORDER BY num_posts DESC 
							LIMIT 0, ".$this->db->real_escape_string($num_threads); 
				
				if ($rs = $this->db->query($query)) {
					while ($row = $rs->fetch_assoc()) {
						$threads[$row['topic_id']] = $row; 
					}
					
					return $threads;
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$query = "SELECT COUNT(*) AS num_posts, p.post_id, f.forum_id, f.forum_name, t.topic_id, t.topic_title 
							FROM nuke_bbposts AS p 
							LEFT JOIN nuke_bbforums AS f ON p.forum_id = f.forum_id 
							LEFT JOIN nuke_bbtopics AS t ON t.topic_id = p.topic_id
							WHERE p.post_time >= ? ".$forum_list_exclusions." 
							AND p.poster_id NOT IN (23967)
							GROUP BY t.topic_id 
							ORDER BY num_posts DESC 
							LIMIT 0, ?";
				
				$threads = array(); 
				
				foreach ($this->db->fetchAll($query, array($age, $num_threads)) as $row) {
					$threads[$row['topic_id']] = $row; 
				}
				
				return $threads;
			}
		}
		
		/**
		 * Best contributors
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $num_users
		 * @return array
		 */
		
		public function users($num_users = 5) {
			if ($this->db instanceof \sql_db) {
				$query = "SELECT user_id, username, uWheat, uChaff, (uWheat / uChaff * 100) AS rating FROM nuke_users WHERE user_active = 1 ORDER BY rating DESC LIMIT 0, ".$this->db->real_escape_string($num_users); 
				
				if ($rs = $this->db->query($query)) {
					$users = array(); 
					while ($row = $rs->fetch_assoc()) {
						$users[$row['user_id']] = $row; 
					}
					
					return $users; 
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$query = "SELECT user_id, username, uWheat, uChaff, (uWheat / uChaff * 100) AS rating FROM nuke_users WHERE user_active = 1 ORDER BY rating DESC LIMIT 0, ?";
				
				$users = array(); 
				
				foreach ($this->db->fetchAll($query, $num_users) as $row) {
					$users[$row['user_id']] = $row; 
				}
				
				return $users;
			}
		}
		
		/**
		 * Get post counts by hour
		 * @since Version 3.9
		 * @param \DateTime $From
		 * @param \DateTime $To
		 * @return array
		 */
		
		public function getPostsByHour(DateTime $From, DateTime $To) {
			$query = "SELECT HOUR(FROM_UNIXTIME(post_time)) AS hour, count(*) AS count
						FROM nuke_bbposts 
						WHERE post_time BETWEEN UNIX_TIMESTAMP(?) AND UNIX_TIMESTAMP(?)
						GROUP BY HOUR(FROM_UNIXTIME(post_time))";
			
			return $this->db->fetchAll($query, array($From->format("Y-m-d"), $To->format("Y-m-d")));
		}
		
		/**
		 * Get post counts by day
		 * @since Version 3.9
		 * @param \DateTime $From
		 * @param \DateTime $To
		 * @return array
		 */
		
		public function getPostsByDay(DateTime $From, DateTime $To) {
			$query = "SELECT DAY(FROM_UNIXTIME(post_time)) AS day, count(*) AS count
						FROM nuke_bbposts 
						WHERE post_time BETWEEN UNIX_TIMESTAMP(?) AND UNIX_TIMESTAMP(?)
						GROUP BY DAY(FROM_UNIXTIME(post_time))";
			
			return $this->db->fetchAll($query, array($From->format("Y-m-d"), $To->format("Y-m-d")));
		}
		
		/**
		 * Get post counts by day and hour
		 * @since Version 3.9
		 * @param \DateTime $From
		 * @param \DateTime $To
		 * @return array
		 */
		
		public function getPostsByHourAndDay(DateTime $From, DateTime $To) {
			$query = "SELECT DAY(FROM_UNIXTIME(post_time)) AS day, HOUR(FROM_UNIXTIME(post_time)) AS hour, count(*) AS count
						FROM nuke_bbposts 
						WHERE post_time BETWEEN UNIX_TIMESTAMP(?) AND UNIX_TIMESTAMP(?)
						AND poster_id NOT IN (305,23967)
						GROUP BY DAY(FROM_UNIXTIME(post_time)), HOUR(FROM_UNIXTIME(post_time))";
			
			return $this->db->fetchAll($query, array($From->format("Y-m-d"), $To->format("Y-m-d")));
		}
		
		/**
		 * Get post counts by month and year
		 * @since Version 3.9
		 * @param \DateTime $From
		 * @param \DateTime $To
		 * @return array
		 */
		
		public function getPostsByMonthAndYear(DateTime $From, DateTime $To) {
			$query = "SELECT YEAR(FROM_UNIXTIME(post_time)) AS year, MONTH(FROM_UNIXTIME(post_time)) AS month, count(*) AS count
						FROM nuke_bbposts 
						WHERE post_time BETWEEN UNIX_TIMESTAMP(?) AND UNIX_TIMESTAMP(?)
						AND poster_id NOT IN (305,23967)
						GROUP BY YEAR(FROM_UNIXTIME(post_time)), MONTH(FROM_UNIXTIME(post_time))";
			
			return $this->db->fetchAll($query, array($From->format("Y-m-d"), $To->format("Y-m-d")));
		}
	}
	