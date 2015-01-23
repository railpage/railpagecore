<?php
	/**
	 * News classes
	 * @since Version 3.0.1
	 * @version 3.0.1
	 * @author Michael Greenhill
	 * @package Railpage
	 * @copyright Copyright (c) 2012 Michael Greenhill
	 */
	 
	namespace Railpage\News;
	
	use Railpage\Module;
	use Exception;
	use DateTime;
	
	// Make sure the parent class is loaded
	#require_once(__DIR__ . DS . "class.news.base.php");
		
	/**
	 * News class
	 * @author Michael Greenhill
	 * @version 3.0.1
	 * @package Railpage
	 * @copyright Copyright (c) 2011 Michael Greenhill
	 * @since Version 3.0
	 */
	
	class News extends Base {
		
		/**
		 * Get story
		 * @version 3.0
		 * @since Version 3.0
		 * @return mixed
		 * @param int $id
		 * @param boolean $pending
		 */
		 
		public function getStory($id = false, $pending = false) {
			if (!$id || !$this->db) {
				return false;
			}
			
			$return = false;
			
			if ($pending) {
				// Get story from pending table
				$query = "SELECT u.username, p.qid as sid, p.uname as informant, p.subject as title, p.story as hometext, p.storyext as bodytext, p.topic, p.source, t.topicname, t.topicimage, t.topictext, p.timestamp as time FROM nuke_users u, nuke_queue p, nuke_topics t WHERE p.topic = t.topicid AND p.uid = u.user_id AND p.qid = '".$this->db->real_escape_string($id)."'";
			} else {
				$query = "SELECT s.*, t.topicname, t.topicimage, t.topictext, t.topicid FROM nuke_stories s, nuke_topics t WHERE s.topic = t.topicid AND s.sid = '".$this->db->real_escape_string($id)."'";
			}
			
			if ($rs = $this->db->query($query)) {
				$return = $rs->fetch_assoc(); 
				
				$whitespace_find = array("<p> </p>", "<p></p>", "<p>&nbsp;</p>");
				$whitespace_replace = array("", "", ""); 
		
				$return['hometext'] = str_replace($whitespace_find, $whitespace_replace, $return['hometext']); 
				$return['bodytext'] = str_replace($whitespace_find, $whitespace_replace, $return['bodytext']); 
			} else {
				trigger_error("News: unable to fetch story id ".$id); 
				trigger_error($this->db->error); 
			}
			
			return $return;
		}
		
		/**
		 * Get stories from topic
		 * @version 3.0
		 * @since Version 3.0
		 * @return mixed
		 * @param int $id
		 * @param int $page
		 * @param int $limit
		 * @param boolean $total
		 */
		 
		public function getStoriesFromTopic($id = false, $page = 0, $limit = 25, $total = true) {
			if (!$id || !$this->db) {
				return false;
			}
			
			$return = false;
			$query 	= "SELECT SQL_CALC_FOUND_ROWS s.*, t.topicname, t.topicimage, t.topictext, u.user_id AS informant_id FROM nuke_stories s, nuke_topics t, nuke_users u WHERE s.informant = u.username AND s.topic = t.topicid AND t.topicid = ".$this->db->real_escape_string($id)." ORDER BY s.time DESC LIMIT ".$this->db->real_escape_string($page * $limit).", ".$this->db->real_escape_string($limit); 
			
			if ($rs = $this->db->query($query)) {
				$total = $this->db->query("SELECT FOUND_ROWS() AS total"); 
				$total = $total->fetch_assoc(); 
				
				$return = array(); 
				$return['topic_id'] = $id;
				$return['total'] = $total['total']; 
				$return['page'] = $page; 
				$return['perpage'] = $limit; 
				
				require_once("includes/functions.php"); 
				
				while ($row = $rs->fetch_assoc()) {
					if (function_exists("relative_date")) {
						$row['time_relative'] = relative_date(strtotime($row['time']));
					} else {
						$row['time_relative'] = $row['time'];
					}
					
					$return['children'][] = $row; 
				}
			} else {
				trigger_error("News: unable to fetch stories from topic");
				trigger_error($this->db->error); 
				trigger_error($query); 
			}
			
			return $return;
		}
		
		/**
		 * Get JSON object for a news article
		 * @since Version 3.8.7
		 * @param int $article_id
		 * @return string
		 */
		
		public static function getArticleJSON($article_id) {
			$key = sprintf("json:railpage.news.article=%d", $article_id);
			
			#deleteMemcacheObject($key);
			
			if (!$json = getMemcacheObject($key)) {
				$Article = new Article($article_id);
				$json = $Article->makeJSON();
			}
			
			return $json;
		}
		
		/**
		 * Wild stab in the dark guess at one of our topics based on the topic sent from an RSS feed
		 * @since Version 3.9
		 * @param string|array $topic
		 * @return \Railpage\News\Topic
		 */
		
		public static function guessTopic($topic) {
			
			/**
			 * Normalise the topic name
			 */
			
			if (is_array($topic)) {
				$topic = implode(", ", $topic);
			}
			
			$topic = trim($topic);
			
			/**
			 * Attempt to find the topic in our existing list
			 */
			
			$Topic = new Topic($topic);
			
			if (filter_var($Topic->id, FILTER_VALIDATE_INT)) {
				return $Topic;
			}
			
			/**
			 * If we don't have a valid topic ID then it didn't work. Time to approximate
			 */
			
			if (preg_match("/(uk|europe|germany|france|spain|russia|c&s america|n america|s america|north america|south america|canada|usa|asia|africa|middle east|saudi arabia|india|pakistan|china)/i", $topic)) {
				return new Topic("international");
			}
			
			if (preg_match("/(passenger|business|policy|infrastructure|traction & rolling stock|freight|technology|urban|high speed)/i", $topic)) {
				return new Topic("other-rail");
			}
			
			/**
			 * Still nothing? Go with generic
			 */
			
			if (!filter_var($Topic->id, FILTER_VALIDATE_INT)) {
				return new Topic("other-rail");
			}
		}
	}
?>