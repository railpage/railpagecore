<?php
	/**
	 * Forums utility class
	 * @since Version 3.10.0
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Forums\Utility;
	
	use Exception;
	use DateTime;
	use Railpage\AppCore;
	use Railpage\ContentUtility;
	use Railpage\Url;
	use Railpage\Debug;
	use Railpage\Forums\ForumsFactory;
	use Railpage\Forums\Forums;
	use Railpage\Forums\Forum;
	use Railpage\Forums\Thread;
	use Railpage\Forums\Post;
	use Railpage\Users\Factory as UserFactory;
	use Railpage\Users\User;
	
	
	
	class ForumsUtility {
		
		/**
		 * Update the viewed time for a forum thread for this user
		 * @since Version 3.10.0
		 * @param \Railpage\Forums\Thread $Thread
		 * @param \Railpage\Users\User $User
		 * @return void
		 */
		
		public static function updateUserThreadView(Thread $Thread, $User = false) {
			
			// Prevent browser prefetch/preview from creating a timestamp
			if (
				(isset($_SERVER["HTTP_X_PURPOSE"]) && (strtolower($_SERVER["HTTP_X_PURPOSE"]) == "preview")) || 
				(isset($_SERVER["HTTP_X_MOZ"]) && (strtolower($_SERVER["HTTP_X_MOZ"]) == "prefetch")) 
			) {
				return;
			}
			
			if (!$User instanceof User || $User->id == 0 || $User->guest) {
				return;
			}
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$query = sprintf("INSERT INTO nuke_bbtopics_view (topic_id, user_id, viewed) VALUES (%d, %d, CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE viewed = CURRENT_TIMESTAMP()",
				$Database->quote(intval($Thread->id)),
				$Database->quote(intval($User->id))
			);
			
			$Database->query($query); 
			
			return;
			
		}
		
		/**
		 * Get unread forum post alerts
		 * @since Version 3.10.0
		 * @param \Railpage\Users\User $User
		 * @return array
		 */
		
		public static function getForumNotifications(User $User) {
			
			$query = "SELECT t.topic_title, t.topic_id, CONCAT('/f-t', t.topic_id, '.htm') AS topic_url, t.topic_last_post_id, 
							CONCAT('/f-p', t.topic_last_post_id, '.htm#', t.topic_last_post_id) AS topic_last_post_url,
							CONCAT('/f-t', t.topic_id, '-newest.htm') AS topic_url_newest, 
							CONCAT('/f-t', t.topic_id, '-unwatch-s0.htm') AS topic_url_unwatch,
							pu.user_id AS topic_last_post_user_id, pu.username AS topic_last_post_username, p.post_time AS topic_last_post_time,
							f.forum_id, f.forum_name, CONCAT('/f-f', f.forum_id, '.htm') AS forum_url
						FROM nuke_bbtopics AS t
						LEFT JOIN nuke_bbposts AS p ON t.topic_last_post_id = p.post_id
						LEFT JOIN nuke_users AS pu ON pu.user_id = p.poster_id
						LEFT JOIN nuke_bbtopics_view AS v ON t.topic_id = v.topic_id
						LEFT JOIN nuke_bbforums AS f ON t.forum_id = f.forum_id
						WHERE t.topic_id IN (
							SELECT topic_id FROM nuke_bbtopics_watch WHERE user_id = ?
						)
						AND p.post_time > UNIX_TIMESTAMP(v.viewed)
						AND v.user_id = ?
						GROUP BY t.topic_id
						ORDER BY p.post_time DESC";
			
			$params = [ $User->id, $User->id ];
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			return $Database->fetchAll($query, $params); 
			
			
		}
		
	}