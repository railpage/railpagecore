<?php
	/**
	 * News classes
	 * @since Version 3.0.1
	 * @version 3.3
	 * @author Michael Greenhill
	 * @package Railpage
	 * @copyright Copyright (c) 2012 Michael Greenhill
	 */
	 
	namespace Railpage\News;
	
	use DateTime;
	use Exception;
		
	/**
	 * Find a news article from its URL slug
	 * @since Version 3.7.5
	 */
	
	class ArticleFromSlug extends Article {
		
		/**
		 * Constructor
		 * @param string $slug
		 */
		
		public function __construct($slug) {
			global $ZendDB, $ZendDB_ReadOnly;
			
			$mckey = "railpage:news.article_slug=" . $slug; 
			
			$loaded = false;
			
			if ($story_id = getMemcacheObject($mckey)) {
				try {
					parent::__construct($story_id);
					$loaded = true;
				} catch (Exception $e) {
					
				}
			}
			
			/**
			 * Fall back to a database query if we can't load the news article from Memcached
			 */
			
			if (!$loaded) {
				$story_id = $ZendDB_ReadOnly->fetchOne("SELECT sid FROM nuke_stories WHERE slug = ?", $slug); 
				
				if (filter_var($story_id, FILTER_VALIDATE_INT)) {
					setMemcacheObject($mckey, $story_id, strtotime("+6 months"));
					
					parent::__construct($story_id); 
				} else {
					throw new Exception("Could not find a story matching URL slug " . $slug);
					return false;
				}
			}
		}
	}
?>