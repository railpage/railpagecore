<?php
	/**
	 * An individual users news feed, with articles filtered by their preferences
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\News;
	
	use Exception;
	use DateTime;
	use DateTimeZone;
	use DateInterval;
	use Railpage\Url;
	use Railpage\Module;
	use Railpage\Users\User;
	use Foolz\SphinxQL\SphinxQL;
	use Foolz\SphinxQL\Connection;
	
	/**
	 * News feed
	 */
	
	class Feed extends News {
		
		/**
		 * Keywords to filter on
		 * @since Version 3.8.7
		 * @var array $filter_words
		 */
		
		public $filter_words;
		
		/**
		 * Topic IDs to filter on
		 * @since Version 3.8.7
		 * @var array $filter_topics
		 */
		
		public $filter_topics;
		
		/**
		 * Find the news articles matching this users filters
		 * @since Version 3.8.7
		 * @yield \Railpage\News\Article
		 */
		
		public function findArticles($offset = 0, $limit = false) {
			
			if (empty($this->filter_topics) || empty($this->filter_words)) {
				$this->getFilters();
			}
			
			if (!$limit) {
				$limit = $this->User->items_per_page;
			}
			
			$Sphinx = $this->getSphinx();
			
			$query = $Sphinx->select("*")
					->from("idx_news_article")
					->orderBy("story_time_unix", "DESC")
					->limit($offset, $limit)
					->where("story_active", "=", 1);
			
			if (!empty($this->filter_topics) && count($this->filter_topics)) {
				$query->where("topic_id", "IN", $this->filter_topics);
			}
			
			if (!empty($this->filter_words) && count($this->filter_words) && !(count($this->filter_words) === 1 && $this->filter_words[0] == "")) {
				$words = implode(" | ", $this->filter_words);
				
				$query->match("story_text", $words)->option("ranker", "matchany");
			}
				
			$matches = $query->execute();
			
			foreach ($matches as $id => $row) {
				$row['url'] = sprintf("/news/s/%s", $row['story_slug']);
				$matches[$id] = $row;
			}
			
			return $matches;
		}
		
		/**
		 * Get filters from the database
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		public function getFilters() {
			if (!$this->User instanceof User) {
				throw new Exception("Cannot get filters for news feed because no valid user was specified");
			}
			
			if (!$row = getMemcacheObject(sprintf("rp:news.feed=%d", $this->User->id))) {
				if ($row = $this->db->fetchRow("SELECT keywords, topics FROM news_feed WHERE user_id = ?", $this->User->id)) {
					setMemcacheObject(sprintf("rp:news.feed=%d", $this->User->id), $row);
				}
			}
			
			if (isset($row) && is_array($row)) {
				$this->filter_words = json_decode($row['keywords'], true);
				$this->filter_topics = json_decode($row['topics'], true);
			}
			
			return $this;
		}
		
		/**
		 * Set filters for this user
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		public function setFilters() {
			if (!$this->User instanceof User) {
				throw new Exception("Cannot get filters for news feed because no valid user was specified");
			}
			
			if (is_string($this->filter_words)) {
				$this->filter_words = explode(",", $this->filter_words);
			}
			
			foreach ($this->filter_words as $id => $word) {
				$this->filter_words[$id] = trim($word);
			}
			
			$data = array(
				"keywords" => json_encode($this->filter_words),
				"topics" => json_encode($this->filter_topics),
				"user_id" => $this->User->id
			);
			
			if ($id = $this->db->fetchOne("SELECT id FROM news_feed WHERE user_id = ?", $this->User->id)) {
				$this->db->update("news_feed", $data, array("id = ?" => $id));
			} else {
				$this->db->insert("news_feed", $data);
			}
			
			unset($data['user_id']);
			setMemcacheObject(sprintf("rp:news.feed=%d", $this->User->id), $data);
			
			return $this;
		}
	}
	