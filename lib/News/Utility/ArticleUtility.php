<?php
	/**
	 * News article (story) utility class
	 * @since Version 3.10.0
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\News\Utility;
	
	use Exception;
	use DateTime;
	use Railpage\AppCore;
	use Railpage\ContentUtility;
	use Railpage\Url;
	use Railpage\Debug;
	use Railpage\News\Article;
	use Railpage\Users\Factory as UserFactory;
	use Railpage\Users\User;
	
	class ArticleUtility {
		
		/**
		 * Update the viewed time for a news article for this user
		 * @since Version 3.10.0
		 * @param \Railpage\News\Article $Article
		 * @param \Railpage\Users\User $User
		 * @return void
		 */
		
		public static function updateUserArticleView(Article $Article, $User = false) {
			
			if (!$User instanceof User || $User->id == 0 || $User->guest) {
				return;
			}
			
			$Database = AppCore::GetDatabase(); 
			
			$query = sprintf("INSERT INTO nuke_stories_view (story_id, user_id, viewed) VALUES (%d, %d, CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE viewed = CURRENT_TIMESTAMP()",
				$Database->quote(intval($Article->id)),
				$Database->quote(intval($User->id))
			);
			
			$Database->query($query); 
			
			return;
			
		}
		
		/**
		 * Get read news articles for the given user
		 * @since Version 3.10.0
		 * @param \Railpage\Users\User $User
		 * @return array
		 */
		
		public static function getReadArticlesForUser($User) {
			
			if (!$User instanceof User || $User->id == 0 || $User->guest) {
				return array(); 
			}
			
			$Database = AppCore::GetDatabase(); 
			
			$query = "SELECT v.story_id, s.title AS story_title FROM nuke_stories_view AS v LEFT JOIN nuke_stories AS s ON s.sid = v.story_id WHERE v.user_id = ?";
			
			return $Database->fetchAll($query, intval($User->id)); 
			
		}
        
        /**
         * Get the formatted lead text for this article
         * @since Version 3.10.0
         * @param \Railpage\News\Article $Article
         * @param string $section The section of the article (lead or paragraphs) to format
         * @return string
         */
        
        public static function FormatArticleText($Article, $section = "lead") {
            
            $Memcached = AppCore::GetMemcached(); 
            
            $cachekey = $section == "lead" ? Article::CACHE_KEY_FORMAT_LEAD : Article::CACHE_KEY_FORMAT_PARAGRAPHS;
            $cachekey = sprintf($cachekey, $Article->id); 
            
            $whitespace_find 	= array("<p> </p>", "<p></p>", "<p>&nbsp;</p>");
            $whitespace_replace = array("", "", ""); 
            
            #$Memcached->delete($cachekey);
            
            if (!$text = $Memcached->Fetch($cachekey)) {
                
                $text = $section == "lead" ? $Article->getLead() : $Article->getParagraphs();
                
                if (function_exists("format_post")) {
                    
                    $text = str_replace($whitespace_find, $whitespace_replace, $text);
                    $text = format_post($text); 
                    
                    if (is_object($text)) {
                        $text = $text->__toString(); 
                    }
                    
                    $Memcached->save($cachekey, $text, 0); 
                    
                }
                
            }
            
            return $text;
            
        }
	
	}