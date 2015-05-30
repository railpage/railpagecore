<?php
	/**
	 * User utility class
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Users\Utility;
	
	use Exception;
	use DateTime;
	use Railpage\ContentUtility;
	use Railpage\Users\User;
	use Railpage\AppCore;
	
	class UserUtility {
		
		/**
		 * Normalise a user avatar path / URL
		 * @since Version 3.9.1
		 * @param array $data The data array as returned from Redis/Database
		 * @return array
		 */
		
		public static function normaliseAvatarPath($data) {
			
			if (!is_null(filter_var($data['user_avatar'], FILTER_SANITIZE_STRING))) {
				$data['user_avatar_filename'] = $data['user_avatar']; 
				
				if (!stristr($data['user_avatar'], "http://") && !stristr($data['user_avatar'], "https://")) {
					$data['user_avatar'] = sprintf("http://%s/modules/Forums/images/avatars/%s", filter_input(INPUT_SERVER, "SERVER_NAME", FILTER_SANITIZE_STRING), $data['user_avatar']);
				}
			}
			
			/**
			 * Set the default avatar
			 */
			
			if (empty($data['user_avatar']) || substr($data['user_avatar'], -9, 5) == "blank") {
				$data['user_avatar'] = function_exists("format_avatar") ? format_avatar("http://static.railpage.com.au/modules/Forums/images/avatars/gallery/blank.png", 120, 120) : "http://static.railpage.com.au/modules/Forums/images/avatars/gallery/blank.png";
				$data['user_avatar_filename'] = function_exists("format_avatar") ? format_avatar("http://static.railpage.com.au/modules/Forums/images/avatars/gallery/blank.png", 120, 120) : "http://static.railpage.com.au/modules/Forums/images/avatars/gallery/blank.png";
				$data['user_avatar_width'] = 120;
				$data['user_avatar_height'] = 120;
			}
			
			return $data;
			
		}
		
		/**
		 * Get a mapping of database columns : object vars
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public static function getColumnMapping() {
			
			$fields = array(
				
				// General
				"api_key" => "api_key", "api_secret" => "api_secret", "user_report_optout" => "report_optout",
				"user_warnlevel" => "warning_level", "disallow_mod_warn" => "warning_exempt", "user_group_cp" => "group_cp",
				"user_group_list_cp" => "group_list_cp", "user_active_cp" => "active_cp",
				
				// Avatar
				"user_avatar" => "avatar",
				"user_avatar_type" => "avatar_type",
				"user_avatar_width" => "avatar_width", "user_avatar_height" => "avatar_height",
				"user_avatar_gravatar" => "avatar_gravatar",
				
				// Private messages
				"user_new_privmsg" => "privmsg_new", "user_unread_privmsg" => "privmsg_unread",
				"user_last_privmsg" => "privmsg_last_id",
				
				// Account
				"username" => "username", "user_active" => "active", "user_regdate" => "regdate",
				"user_level" => "level", "user_posts" => "posts", "user_style" => "style",
				"user_lang" => "lang", "user_email" => "contact_email",
				"user_icq" => "contact_icq", "user_aim" => "contact_aim", "user_yim" => "contact_yim",
				"user_msnm" => "contact_msn", "user_sig" => "signature", "user_sig_bbcode_uid" => "signature_bbcode_uid",
				"user_actkey" => "act_key", "reported_to_sfs" => "reported_to_sfs",
				"user_from" => "location", "user_occ" => "occupation", "user_interests" => "interests",
				"name" => "real_name", "facebook_user_id" => "facebook_user_id",
				"uWheat" => "wheat", "uChaff" => "chaff",
				
				// Password
				"user_password" => "password", "user_password_bcrypt" => "password_bcrypt",
				
				// Session
				"user_lastvisit" => "lastvisit", "user_session_time" => "session_time",
				"user_session_page" => "session_page", "user_current_visit" => "session_current",
				"user_last_visit" => "session_last", "last_session_ip" => "session_ip",
				"last_session_cslh" => "session_cslh", "last_session_ignore" => "session_mu_ignore",
				
				// Preferences
				"user_forum_postsperpage" => "items_per_page",
				"user_viewemail" => "email_show", "user_notify" => "notify",
				"user_notify_pm" => "notify_privmsg", "user_attachsig" => "signature_attach",
				"user_showsigs" => "signature_showall", "user_enablerte" => "enable_rte",
				"user_enableglossary" => "enable_glossary", "user_allowhtml" => "enable_html",
				"user_allowbbcode" => "enable_bbcode", "user_allowsmile" => "enable_emoticons",
				"user_allow_pm" => "enable_privmsg", "user_popup_pm" => "enable_privmsg_popup",
				"user_enableautologin" => "enable_autologin", "sidebar_type" => "sidebar_type",
				"user_enablessl" => "ssl", "user_dateformat" => "date_format",
				"user_allowavatar" => "enable_avatar",
				
				
				// Flickr
				"flickr_oauth_token" => "flickr_oauth_token", "flickr_oauth_token_secret" => "flickr_oauth_token_secret", 
				"flickr_nsid" => "flickr_nsid", "flickr_username" => "flickr_username",
				
				"meta" => "meta", "user_opts" => "preferences",
				"provider" => "provider",
				"theme" => "theme",
				"user_rank" => "rank_id",
				"timezone" => "timezone",
				"user_website" => "website",
				"user_allow_viewonline" => "hide",
				"storynum" => "news_submissions",
				"femail" => "contact_email_public",
				"oauth_consumer_id" => "oauth_id",
				
				
			);
			
			return $fields;
			
		}
		
		/**
		 * Clear the caches of this user object
		 * @since Version 3.9.1
		 * @param \Railpage\Users\User $User
		 * @return void
		 */
		
		public static function clearCache(User $User) {
			
			if (empty($User->mckey)) {
				return; 
			}
			
			$User->Memcached->delete($User->mckey);
			
			try {
				$User->Redis->delete(sprintf("railpage:users.user=%d", $User->id));
			} catch (Exception $e) {
				// throw it away
			}
			
			try {
				$User->Redis->delete($User->mckey);
			} catch (Exception $e) {
				// throw it away
			}

		}
		
		/**
		 * Get user warning level bar colour
		 * @since Version 3.9.1
		 * @param int $warningLevel
		 * @return string
		 */
		
		public static function getWarningBarColour($warningLevel) {
			
			if (!filter_var($warningLevel, FILTER_VALIDATE_INT)) {
				$warningLevel = 0;
			}
			
			if ($warningLevel === 0) {
				return "green";
			}
			
			if ($warningLevel < 66) {
				return "orange";
			}
			
			return "red";
			
		}
		
		/**
		 * Get organisations that this user belongs to
		 * @since Version 3.9.1
		 * @param array $data
		 * @return array
		 */
		
		public static function getOrganisations($data) {
			
			if (defined("RP_PLATFORM") && RP_PLATFORM == "API") {
				return $data;
			}
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$data['organisations'] = array(); 
			
			$query = "SELECT o.* FROM organisation o, organisation_member om WHERE o.organisation_id = om.organisation_id AND om.user_id = ?"; 
			
			if ($orgs = $Database->fetchAll($query, $data['user_id'])) {
				foreach ($orgs as $row) {
					$data['organisations'][$row['organisation_id']] = $row;
				}
			}
			
			return $data;
			
		}
		
		/**
		 * Get OAuth configuration for this user
		 * @since Version 3.9.1
		 * @param array $data
		 * @return array
		 */
		
		public static function getOAuth($data) {
			
			if (defined("RP_PLATFORM") && RP_PLATFORM == "API") {
				return $data;
			}
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$query = "SELECT oc.* FROM oauth_consumer AS oc LEFT JOIN nuke_users AS u ON u.oauth_consumer_id = oc.id WHERE u.user_id = ?";
						
			if ($row = $Database->fetchRow($query, $data['user_id'])) {
				$data['oauth_key']		= $row['consumer_key'];
				$data['oauth_secret']	= $row['consumer_secret'];
			}
			
			return $data;
			
		}
	}