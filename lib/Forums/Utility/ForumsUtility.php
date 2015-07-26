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
		
	}