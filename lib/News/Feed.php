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
		 * Filter: Unread articles only
		 * @since Version 3.10.0
		 * @const string FILTER_UNREAD
		 */
		
		const FILTER_UNREAD = "unread";
		
		/**
		 * Filter: Read articles only
		 * @since Version 3.10.0
		 * @const string FILTER_READ
		 */
		
		const FILTER_READ = "read";
		
		/**
		 * Additional filters to apply to this feed
		 * @since Version 3.10.0
		 * @var array $filters
		 */
		 
		private $filters;
		
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
		 * @param int $offset
		 * @param int $limit
		 * @param string $orderby
		 */
		
		public function findArticles($offset = 0, $limit = 25, $orderby = "story_time_unix") {
			
			if (empty($this->filter_topics) || empty($this->filter_words)) {
				$this->getFilters();
			}
			
			if (!$limit) {
				$limit = $this->User->items_per_page;
			}
			
			$Sphinx = $this->getSphinx();
			
			$query = $Sphinx->select("*")
					->from("idx_news_article")
					->orderBy($orderby, "DESC")
					->limit($offset, $limit)
					->where("story_active", "=", 1);
			
			if (!empty($this->filter_topics) && count($this->filter_topics)) {
				$query->where("topic_id", "IN", $this->filter_topics);
			}
			
			/**
			 * Unread items only
			 */
			
			if ($this->hasFilter(self::FILTER_UNREAD) !== false) {
				$article_ids = [];
				
				foreach (Utility\ArticleUtility::getReadArticlesForUser($this->User) as $row) {
					$article_ids[] = intval($row['story_id']); 
				}
				
				if (count($article_ids)) {
					$query->where("story_id", "NOT IN", $article_ids); 
				}
			}
			
			/**
			 * Read items only
			 */
			
			if ($this->hasFilter(self::FILTER_READ) !== false) {
				$article_ids = [];
				
				foreach (Utility\ArticleUtility::getReadArticlesForUser($this->User) as $row) {
					$article_ids[] = $row['story_id']; 
				}
				
				if (count($article_ids)) {
					$query->where("story_id", "IN", $article_ids);
				}
			}
			
			/**
			 * Matching keywords
			 */
			
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
		 * Add a filter to the search
		 * @since Version 3.10.0
		 * @param string $filter
		 * @return \Railpage\News\Feed
		 */
		 
		public function addFilter($filter) {
			
			if (!$this->hasFilter($filter)) {
				$this->filters[] = $filter; 
			}
			
			if ($filter == self::FILTER_UNREAD) {
				$key = $this->hasFilter(self::FILTER_READ);
				
				if ($key !== false) {
					unset($this->filters[$key]);
				}
			}
			
			if ($filter == self::FILTER_READ) {
				$key = $this->hasFilter(self::FILTER_UNREAD);
				
				if ($key !== false) {
					unset($this->filters[$key]);
				}
			}
			
			return $this;
			
		}
		
		/**
		 * Check if we have a particular filter set
		 * @since Version 3.10.0
		 * @param string $filter
		 * @return boolean
		 */
		
		public function hasFilter($filter) {
			
			if (!in_array($filter, $this->filters)) {
				return false;
			}
			
			return array_search($filter, $this->filters);
			
		}
		
		/**
		 * Get filters from the database
		 * @since Version 3.8.7
		 * @return \Railpage\News\Feed
		 */
		
		public function getFilters() {
			if (!$this->User instanceof User) {
				throw new Exception("Cannot get filters for news feed because no valid user was specified");
			}
			
			if (!$row = $this->Memcached->fetch(sprintf("rp:news.feed=%d", $this->User->id))) {
				if ($row = $this->db->fetchRow("SELECT keywords, topics FROM news_feed WHERE user_id = ?", $this->User->id)) {
					$this->Memcached->save(sprintf("rp:news.feed=%d", $this->User->id), $row);
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
		 * @return \Railpage\News\Feed
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
			
			$key = sprintf("rp:news.feed=%d", $this->User->id);
			
			$this->Memcached->delete($key);
			$this->Memcached->save($key, $data);
			
			return $this;
		}
	}
	