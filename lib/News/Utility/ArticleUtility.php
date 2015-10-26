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
	
	}