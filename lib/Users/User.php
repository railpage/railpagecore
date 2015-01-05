<?php
	/**
	 * Base user class
	 * @since Version 3.0.1
	 * @version 3.9
	 * @author Michael Greenhill
	 * @package Railpage
	 */
	
	namespace Railpage\Users;
	
	use stdClass;
	use Exception;
	use DateTime;
	use DateTimeZone;
	
	use Railpage\Module;
	use Railpage\Url;
	use Railpage\Forums\Thread;
	use Railpage\Forums\Forum;
	use Railpage\Forums\Forums;
	use Railpage\Forums\Index;
	
	/**
	 * User class
	 * @since Version 3.0
	 * @author Michael Greenhill
	 * @todo Declare all the user vars, populate them. Make this more object-orientated. Does not cater for the creation of new users
	 */
	
	class User extends Base {
		
		/**
		 * Array of group IDs this user is a member of
		 * @since Version 3.7
		 * @var array $groups
		 */
		
		public $groups = array(); 
		
		/**
		 * SSL option
		 * @since Version 3.2
		 * @var boolean $ssl
		 */
		
		public $ssl = false;
		
		/** 
		 * User ID
		 * @var int $id
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $id;
		
		/**
		 * Authentication provider
		 * Column: provider
		 * @since Version 3.8.7
		 * @var string $provider
		 */
		
		public $provider;
		
		/** 
		 * Guest indicator
		 * @var boolean $guest
		 * @since Version 3.0
		 * @version 3.0
		 */
		 
		public $guest = true;
		
		/** 
		 * Username
		 * @var string $username
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $username;
		
		/** 
		 * User active flag
		 * @var int active
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $active = 0;
		
		/** 
		 * Activation key
		 * @var string $act_key
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $act_key = "";
		
		/** 
		 * New password ( needs to be confirmed )
		 * @var string $password_new
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $password_new;
		
		/** 
		 * User password
		 * @var string $password
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $password;
		
		/**
		 * BCrypt password
		 * @var string $password_bcrypt
		 * @since Version 3.2
		 */
		
		public $password_bcrypt;
		
		/** 
		 * Session time
		 * @var int $session_time
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $session_time = 0;
		
		/** 
		 * Session page
		 * @var string $session_page
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $session_page = "";
		
		/** 
		 * Current session time
		 * Column: user_current_visit
		 * @var string $session_current
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $session_current = 0;
		
		/** 
		 * Last session time
		 * Column: user_last_visit
		 * @var string $session_last
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $session_last = 0;
		
		/** 
		 * Session IP
		 * Column: last_session_ip
		 * @var string $session_ip
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $session_ip = "";
		
		/** 
		 * Session CSLH
		 * Column: last_session_cslh
		 * @var string $session_cslh
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $session_cslh = "";
		
		/** 
		 * Session ignore multi user checks
		 * Column: last_session_ignore
		 * @var string $session_mu_ignore
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $session_mu_ignore = 0;
		
		/** 
		 * Website
		 * @var string $website
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $website = "";
		
		/** 
		 * Last visit
		 * @var int $lastvisit
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $lastvisit = 0;
		
		/** 
		 * Registration date
		 * @var mixed $regdate
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $regdate;
		
		/** 
		 * Authentication level
		 * @var int $level
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $level = 0;
		
		/** 
		 * Forum posts
		 * @var int $posts
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $posts = 0;
		
		/** 
		 * Style (? NFI what this is)
		 * @var int $style
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $style = 4;
		
		/** 
		 * Language
		 * @var string $lang
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $lang = "english";
		
		/** 
		 * Date format
		 * @var string $date_format
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $date_format = "D M d, Y g:i a";
		
		/** 
		 * New PMs
		 * @var int $privmsg_new
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $privmsg_new = 0;
		
		/** 
		 * Unread PMs
		 * @var int $privmsg_unread
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $privmsg_unread = 0;
		
		/** 
		 * Last PM ID
		 * @var int $privmsg_last_id
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $privmsg_last_id = 0;
		
		/** 
		 * Show email address to all users
		 * @var int $email_show
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $email_show = 1;
		
		/** 
		 * Attach user's signature to post
		 * @var int $signature_attach
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $signature_attach = 0;
		
		/** 
		 * Show all users signatures
		 * @var int $signature_showall
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $signature_showall = 0;
		
		/** 
		 * Signature
		 * @var string $signature
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $signature = "";
		
		/** 
		 * Signature BBCode UID
		 * @var string $signature_bbcode_uid
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $signature_bbcode_uid = "sausages";
		
		/** 
		 * Timezone
		 * Column: timezone
		 * @var string $timezone
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $timezone = "Australia/Melbourne";
		
		/** 
		 * Enable glossary
		 * Column: user_enableglossary
		 * @var string $enable_glossary
		 * @since Version 3.3
		 * @version 3.3
		 */
		 
		public $enable_glossary = 0;
		
		/** 
		 * Enable RTE
		 * Column: user_enablerte
		 * @var string $enable_rte
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $enable_rte = 1;
		
		/** 
		 * Enable HTML posts
		 * @var int $enable_html
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $enable_html = 1;
		
		/** 
		 * Enable BBCode
		 * @var int $enable_bbcode
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $enable_bbcode = 1;
		
		/** 
		 * Enable smilies (smiles/emoticons/etc)
		 * @var int $enable_emoticons
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $enable_emoticons = 1;
		
		/** 
		 * Enable this user's avatar
		 * @var int $enable_avatar
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $enable_avatar = 1;
		
		/** 
		 * Enable this user's PMs
		 * @var int $enable_privmsg
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $enable_privmsg = 1;
		
		/** 
		 * Enable popups for new private messages
		 * @var int $enable_privmsg_popup
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $enable_privmsg_popup = 1;
		
		/** 
		 * Enable auto login
		 * @var int $enable_autologin
		 * @since Version 3.2
		 */
		 
		public $enable_autologin = 1;
		
		/** 
		 * Hide this users online status
		 * Column: user_allow_viewonline
		 * @var int $hide
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $hide = 0;
		
		/** 
		 * Notify user of events
		 * @var int $notify
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $notify = 1;
		
		/** 
		 * Notify user of new PMs
		 * @var int $notify_privmsg
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $notify_privmsg = 1;
		
		/** 
		 * User rank ID
		 * @var int $rank_id
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $rank_id = 0;
		
		/** 
		 * User rank text
		 * @var string $rank_text
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $rank_text;
		
		/** 
		 * Avatar image URL
		 * @var string $avatar
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $avatar = "http://static.railpage.com.au/modules/Forums/images/avatars/765-default-avatar.png";
		
		/**
		 * Avatar width
		 * @var int $avatar_width
		 * @since Version 3.1
		 * @version 3.1
		 */
		
		public $avatar_width = 100;
		
		/**
		 * Avatar height
		 * @var int $avatar_height
		 * @since Version 3.1
		 * @version 3.1
		 */
		
		public $avatar_height = 100;
		
		/** 
		 * Avatar filename
		 * @var string $avatar_filename
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $avatar_filename = "";
		
		/** 
		 * Avatar type
		 * @var int $avatar_type
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $avatar_type = 0;
		
		/** 
		 * Use Gravatar if this user doesn't have an avatar
		 * @var int $avatar_gravatar
		 * @version 3.2
		 */
		 
		public $avatar_gravatar = 0;
		
		/** 
		 * Email address
		 * @var string $contact_email
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $contact_email = "";
		
		/** 
		 * ICQ username
		 * @var string $contact_icq
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $contact_icq = "";
		
		/** 
		 * AIM username
		 * @var string $contact_aim
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $contact_aim = "";
		
		/** 
		 * YIM username
		 * @var string $contact_yim
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $contact_yim = "";
		
		/** 
		 * MSN username
		 * @var string $contact_msn
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $contact_msn = "";
		
		/** 
		 * User location - where they're from
		 * @var string $location
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $location = "";
		
		/** 
		 * Occupation
		 * @var string $occupation
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $occupation = "";
		
		/** 
		 * Interests
		 * @var string $interests
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $interests = "";
		
		/** 
		 * Real name
		 * @var string $real_name
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $real_name = "";
		
		/** 
		 * Publicly viewable email address
		 * @var string $contact_email_public
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $contact_email_public = "";
		
		/** 
		 * News - stories submitted by this user
		 * @var int $news_submissions
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $news_submissions = 0;
		
		/** 
		 * Theme
		 * @var string $theme
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $theme = "jiffy_simple";
		
		/** 
		 * Warning level
		 * Column: user_warnlevel
		 * @var string $warning_level
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $warning_level = 0;
		
		/** 
		 * Warning level bar colour
		 * @var string $warning_level_colour
		 * @since Version 3.1
		 * @version 3.1
		 */
		 
		public $warning_level_colour;
		
		/** 
		 * Exempt this user from warnings
		 * Column: disallow_mod_warn
		 * @var string $warning_exempt
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $warning_exempt = 0;
		
		/** 
		 * Group CP
		 * Column: user_group_cp
		 * @var int $group_cp
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $group_cp = 0;
		
		/** 
		 * List group CP
		 * Column: user_group_list_cp
		 * @var int $group_list_cp
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $group_list_cp = 0;
		
		/** 
		 * Active CP
		 * Column: user_active_cp
		 * @var int $active_cp
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $active_cp = 0;
		
		/** 
		 * Opt out of report notifications
		 * Column: user_report_optout
		 * @var int $report_optout
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $report_optout = 0;
		
		/** 
		 * User wheat
		 * Column: uWheat
		 * @var int $wheat
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $wheat = 0;
		
		/** 
		 * User chaff
		 * Column: uChaff
		 * @var int $chaff
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $chaff = 0;
		
		/** 
		 * Items per page
		 * Column: user_forum_postsperpage
		 * @var int $items_per_page
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $items_per_page = 25;
		
		/** 
		 * API key
		 * Column: api_key
		 * @var string $api_key
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $api_key = "";
		
		/** 
		 * API Secret
		 * Column: api_secret
		 * @var string $api_secret
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $api_secret = "";
		
		/** 
		 * Flickr oauth token
		 * Column: flickr_oauth_token
		 * @var string $flickr_oauth_token
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $flickr_oauth_token = "";
		
		/** 
		 * Flickr oauth secret
		 * Column: flickr_oauth_token_secret
		 * @var string $flickr_oath_secret
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $flickr_oauth_secret = "";
		
		/** 
		 * Flickr NSID
		 * Column: flickr_nsid
		 * @var string $flickr_nsid
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $flickr_nsid = "";
		
		/** 
		 * Flickr username
		 * Column: flickr_username
		 * @var string $flickr_username
		 * @since Version 3.0.1
		 * @version 3.0.1
		 */
		 
		public $flickr_username = "";
		
		/**
		 * OAuth consumer key
		 * Column: oauth_consumer.consumer_key
		 * @var string $oauth_key
		 * @since Version 3.2
		 * @version 3.2
		 */
		
		public $oauth_key = "";
		
		/**
		 * OAuth consumer secret
		 * Column: oauth_consumer.consumer_secret
		 * @var string $oauth_secret
		 * @since Version 3.2
		 * @version 3.2
		 */
		
		public $oauth_secret = "";
		
		/**
		 * OAuth consumer ID
		 * Column: oauth_consumer_id
		 * @var string $oauth_id
		 * @since Version 3.3
		 */
		
		public $oauth_id = "";
		
		/** 
		 * Homepage sidebar type
		 * Column: sidebar_type
		 * @var int $sidebar_type
		 * @since Version 3.2
		 */
		
		public $sidebar_type = 1;
		
		/**
		 * Facebook user ID
		 * @column: facebook_user_id
		 * @var int $facebook_user_id
		 * @since Version 3.2
		 */
		
		public $facebook_user_id = "";
		
		/**
		 * Reported to StopForumSpam.com
		 * @since Version 3.5
		 * @column: reported_to_sfs
		 * @var boolean $reported_to_sfs
		 */
		
		public $reported_to_sfs = 0;
		
		/**
		 * User notes
		 * @since Version 3.6
		 * @var array $notes
		 */
		
		public $notes = array();
		
		/**
		 * User options
		 * @since Version 3.7.5
		 * @column user_opts
		 * @var object $preferences
		 */
		
		public $preferences = ""; 
		
		/**
		 * Profile URL
		 * @since Version 3.8.7
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Meta data for this user
		 * @since Version 3.8.7
		 * @var array $meta
		 */
		
		public $meta;
		
		/**
		 * Constructor
		 * @since Version 3.0
		 * @version 3.0
		 * @param object $db
		 * @param array $preferences
		 */
		 
		public function __construct() {
	 
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			if (function_exists("debug_recordInstance")) {	
				debug_recordInstance(__CLASS__);
			}
			
			parent::__construct(); 
			
			$this->guest(); 
			
			$this->Module = new Module("users");
			
			
			foreach (func_get_args() as $arg) {
				if (filter_var($arg, FILTER_VALIDATE_INT)) {
					$this->id = $arg;
					$this->load();
				} elseif (is_string($arg) && strlen($arg) > 1) {
					$query = "SELECT user_id FROM nuke_users WHERE username = ?";
					$this->id = $this->db->fetchOne($query, $arg);
					
					if (filter_var($this->id, FILTER_VALIDATE_INT)) {
						$this->load();
					}
				}
			}
			
			if (RP_DEBUG) {
				$site_debug[] = "Railpage: " . __CLASS__ . "(" . $this->id . ") instantiated in " . round(microtime(true) - $debug_timer_start, 5) . "s";
			}
		}
		
		/**
		 * Populate the user object
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @return boolean
		 * @param int $user_id
		 */
		
		public function load($user_id = false) {
			if ($user_id) {
				$this->id = $user_id; 
			}
			
			// Get out early
			if (!$this->id) {
				return false;
			}
			
			$this->createUrls();
			
			$this->mckey = "railpage:user_id=" . $this->id; 
			$cached = false;
			
			if ($data = $this->getCache($this->mckey)) {
				$cached = true;
				
			} elseif ($this->db instanceof \sql_db) {
				$query  = "SELECT u.*, COALESCE(SUM((SELECT COUNT(*) FROM nuke_bbprivmsgs WHERE privmsgs_to_userid='".$this->db->real_escape_string($this->id)."' AND (privmsgs_type='5' OR privmsgs_type='1'))), 0) AS unread_pms FROM nuke_users u WHERE u.user_id = '".$this->db->real_escape_string($this->id)."';";
				
				if (!defined("RP_PLATFORM") || RP_PLATFORM != "API") {
					$query .= "SELECT o.* FROM organisation o, organisation_member om WHERE o.organisation_id = om.organisation_id AND om.user_id = ".$this->db->real_escape_string($this->id).";";
					$query .= "SELECT oc.* FROM oauth_consumer AS oc LEFT JOIN nuke_users AS u ON u.oauth_consumer_id = oc.id WHERE u.user_id = ".$this->db->real_escape_string($this->id).";";
				}
				
				if ($this->db->multi_query($query)) {
					// Get the user data
					
					if ($rs = $this->db->store_result()) {
						if ($rs->num_rows == 1 && $data = $rs->fetch_assoc()) {
							//unset($data['user_password']); 
							$data['session_logged_in'] = true;
							$data['session_start'] = $data['user_session_time'];
							
							$rs->free(); 
						} else {
							trigger_error("User: Could not retrieve user from database");
							trigger_error($this->db->error); 
							trigger_error($query);
							
							return false;
						}
					} else {
						trigger_error("User: Could not retrieve user from database");
						trigger_error($this->db->error); 
						trigger_error($query); 
						
						return false;
					}
					
					// Get the organisation membership
					if ($this->db->more_results()) {
						$this->db->next_result();
						
						if ($rs = $this->db->store_result()) {
							$data['organisations'] = array(); 
							
							while ($row = $rs->fetch_assoc()) {
								$data['organisations'][$row['organisation_id']] = $row;
							}
						}
					}
					
					// OAuth consumer key
					if ($this->db->more_results()) {
						$this->db->next_result();
						
						if ($rs = $this->db->store_result()) {
							$row = $rs->fetch_assoc(); 
							$data['oauth_key']		= $row['consumer_key'];
							$data['oauth_secret']	= $row['consumer_secret'];
						}
					}
				} else {
					throw new \Exception($this->db->error); 
					return false;
				}
			} else {
				// Zend_Db
				
				$query = "SELECT u.*, COALESCE(SUM((SELECT COUNT(*) FROM nuke_bbprivmsgs WHERE privmsgs_to_userid= ? AND (privmsgs_type='5' OR privmsgs_type='1'))), 0) AS unread_pms FROM nuke_users u WHERE u.user_id = ?";
				
				if ($data = $this->db->fetchRow($query, array($this->id, $this->id))) {
					#unset($data['user_password']); 
					#unset($data['user_password_bcrypt']);
					
					$data['session_logged_in'] = true;
					$data['session_start'] = $data['user_session_time'];
					
					if (!defined("RP_PLATFORM") || RP_PLATFORM != "API") {
						$data['organisations'] = array(); 
						
						$query = "SELECT o.* FROM organisation o, organisation_member om WHERE o.organisation_id = om.organisation_id AND om.user_id = ?"; 
						
						if ($orgs = $this->db->fetchAll($query, $this->id)) {
							foreach ($orgs as $row) {
								$data['organisations'][$row['organisation_id']] = $row;
							}
						}
						
						$query = "SELECT oc.* FROM oauth_consumer AS oc LEFT JOIN nuke_users AS u ON u.oauth_consumer_id = oc.id WHERE u.user_id = ?";
						
						if ($row = $this->db->fetchRow($query, $this->id)) {
							$data['oauth_key']		= $row['consumer_key'];
							$data['oauth_secret']	= $row['consumer_secret'];
						}
					}
				}
			}
				
			/**
			 * Process some of the returned values
			 */
			
			// Set the full avatar path
			if (!empty($data['user_avatar'])) {
				$data['user_avatar_filename'] = $data['user_avatar']; 
				
				if (!stristr($data['user_avatar'], "http://") && !stristr($data['user_avatar'], "https://")) {
					// Assume local avatar
					$data['user_avatar'] = "http://".$_SERVER['SERVER_NAME']."/modules/Forums/images/avatars/".$data['user_avatar'];
				}
				
				if (is_null($data['user_avatar_width']) || is_null($data['user_avatar_height'])) {
					if ($size = @getimagesize($data['user_avatar'])) {
						$data['user_avatar_width'] = $size[0];
						$data['user_avatar_height'] = $size[1];
					}
				}
			}
			
			if (empty($data['user_avatar']) || substr($data['user_avatar'], -9, 5) == "blank") {
				$data['user_avatar'] = format_avatar("http://static.railpage.com.au/modules/Forums/images/avatars/gallery/blank.png", 120, 120);
				$data['user_avatar_filename'] = format_avatar("http://static.railpage.com.au/modules/Forums/images/avatars/gallery/blank.png", 120, 120);
			}
			
			// Backwards compatibility
			if ($data['timezone']) {
				$timezone = new DateTime(null, new DateTimeZone($data['timezone'])); 
				$data['user_timezone'] = str_pad(($timezone->getOffset() / 60 / 60), 5, ".00"); 
			}
			
			// Check for theme existance
			if (class_exists("\\smarty_railpage")) {
				$smarty = new \smarty_railpage();
				if (!$smarty->theme_exists($data['theme']) || $data['theme'] == "MGHTheme" || $data['theme'] == "") {
					$data['theme'] = $this->default_theme;
				}
			}
			
			// Nice time
			$data['user_lastvisit_nice'] = date($data['user_dateformat'], $data['user_lastvisit']); 
			
			/**
			 * Start setting the class vars
			 */
			
			$this->getGroups();
			
			if (!$cached) {
				$this->setCache($this->mckey, $data, strtotime("+6 hours"));
			}
			
			$this->provider 	= isset($data['provider']) ? $data['provider'] : "railpage";
			$this->preferences	= json_decode($data['user_opts']); 
			$this->guest 		= false;
			$this->ssl			= $data['user_enablessl'];
			$this->username 	= $data['username']; 
			$this->active 		= $data['user_active']; 
			$this->regdate 		= $data['user_regdate'];
			$this->level		= $data['user_level'];
			$this->posts		= $data['user_posts'];
			$this->style		= $data['user_style'];
			$this->theme		= (!is_null($data['theme'])) ? $data['theme'] : $this->default_theme;
			$this->lang			= $data['user_lang'];
			$this->date_format	= $data['user_dateformat'];
			$this->rank_id		= $data['user_rank'];
			$this->rank_text	= isset($data['rank_title']) && !empty($data['rank_title']) ? $data['rank_title'] : NULL;
			$this->location		= $data['user_from'];
			$this->occupation	= $data['user_occ'];
			$this->interests	= $data['user_interests'];
			$this->real_name	= $data['name'];
			$this->timezone		= isset($data['timezone']) && !empty($data['timezone']) ? $data['timezone'] : "Australia/Melbourne";
			$this->website		= $data['user_website'];
			$this->hide			= $data['user_allow_viewonline']; 
			
			$this->wheat		= $data['uWheat'];
			$this->chaff		= $data['uChaff'];
			
			$this->facebook_user_id	= $data['facebook_user_id'];
			
			if ($this->wheat == 0) {
				$this->reputation = '100% (+'.$this->wheat.'/'.$this->chaff.'-)';
			} else {
				$this->reputation = number_format(((($this->chaff/$this->wheat)/2)*100),1).'% (+'.$this->wheat.'/'.$this->chaff.'-)';
			}
			
			$this->api_key		= $data['api_key'];
			$this->api_secret	= $data['api_secret'];
			
			$this->report_optout	= $data['user_report_optout'];
			
			$this->warning_level	= $data['user_warnlevel'];
			$this->warning_exempt	= $data['disallow_mod_warn'];
			
			$this->group_cp			= $data['user_group_cp'];
			$this->group_list_cp	= $data['user_group_list_cp'];
			$this->active_cp		= $data['user_active_cp'];
			
			$this->items_per_page	= $data['user_forum_postsperpage'];
			
			$this->avatar 			= $data['user_avatar'];
			$this->avatar_filename	= $data['user_avatar_filename'];
			$this->avatar_type		= $data['user_avatar_type'];
			$this->avatar_width		= $data['user_avatar_width']; 
			$this->avatar_height	= $data['user_avatar_height']; 
			$this->avatar_gravatar	= $data['user_avatar_gravatar'];
			
			$this->privmsg_new		= $data['user_new_privmsg'];
			$this->privmsg_unread	= $data['user_unread_privmsg'];
			$this->privmsg_last_id	= $data['user_last_privmsg'];
			
			$this->email_show		= $data['user_viewemail']; 
			$this->news_submissions	= $data['storynum']; 
			
			$this->notify 			= $data['user_notify']; 
			$this->notify_privmsg	= $data['user_notify_pm']; 
			
			$this->contact_email 		= $data['user_email']; 
			$this->contact_icq 			= $data['user_icq']; 
			$this->contact_aim 			= $data['user_aim']; 
			$this->contact_yim 			= $data['user_yim']; 
			$this->contact_msn 			= $data['user_msnm'];
			
			if ($this->email_show) {
				$this->contact_email_public	= $this->contact_email; 
			} else {
				$this->contact_email_public = $data['femail'];
			}
			
			$this->signature			= $data['user_sig']; 
			$this->signature_attach		= $data['user_attachsig'];
			$this->signature_showall	= $data['user_showsigs'];
			$this->signature_bbcode_uid	= $data['user_sig_bbcode_uid'];
			$this->act_key 				= $data['user_actkey']; 
			
			if (isset($data['password_new'])) {
				$this->password_new = $data['password_new']; 
			}
			
			$this->password 			= $data['user_password']; 
			$this->password_bcrypt		= $data['user_password_bcrypt'];
			
			$this->lastvisit 			= $data['user_lastvisit'];
			$this->session_time 		= $data['user_session_time']; 
			$this->session_page 		= $data['user_session_page']; 
			$this->session_current 		= $data['user_current_visit']; 
			$this->session_last 		= $data['user_last_visit']; 
			$this->session_last_nice	= date($data['user_dateformat'], $data['user_lastvisit']); 
			$this->session_ip	 		= $data['last_session_ip']; 
			$this->session_cslh 		= $data['last_session_cslh']; 
			$this->session_mu_ignore 	= $data['last_session_ignore']; 
			
			$this->enable_rte 			= $data['user_enablerte']; 
			$this->enable_glossary		= $data['user_enableglossary'];
			$this->enable_html 			= $data['user_allowhtml']; 
			$this->enable_bbcode		= $data['user_allowbbcode']; 
			$this->enable_emoticons 	= $data['user_allowsmile']; 
			$this->enable_avatar 		= $data['user_allowavatar']; 
			$this->enable_privmsg 		= $data['user_allow_pm']; 
			$this->enable_privmsg_popup	= $data['user_popup_pm']; 
			$this->enable_autologin		= $data['user_enableautologin']; 
			
			$this->flickr_oauth_token	= $data['flickr_oauth_token']; 
			$this->flickr_oauth_secret	= $data['flickr_oauth_token_secret']; 
			$this->flickr_nsid			= $data['flickr_nsid']; 
			$this->flickr_username		= $data['flickr_username']; 
			
			$this->sidebar_type			= $data['sidebar_type'];
			$this->reported_to_sfs		= $data['reported_to_sfs'];
			$this->meta = isset($data['meta']) ? json_decode($data['meta'], true) : array();
			
			/**
			 * Update the user registration date if required
			 */
		 
	 		if (empty($data['user_regdate_nice'])) {
	 			$datetime = new DateTime($data['user_regdate']);
	 			
	 			$data['user_regdate_nice'] = $datetime->format("Y-m-d");
	 			$update['user_regdate_nice'] = $data['user_regdate_nice'];
	 			
	 			$this->db->update("nuke_users", $update, array("user_id = ?" => $this->id));
	 		}
			
			/**
			 * Fetch the last IP address from the login logs
			 */
			
			$lastlogin = $this->getLogins(1);
			
			if (count($lastlogin)) {
				$this->session_ip = $lastlogin[key($lastlogin)]['login_ip'];
				
				if ($this->lastvisit == 0) {
					$this->lastvisit = $lastlogin[key($lastlogin)]['login_time'];
				}
			}
			
			if ($this->warning_level == 0) {
				$this->warning_level_colour = "green";
			} elseif ($this->warning_level < 66) {
				$this->warning_level_colour = "orange";
			} else {
				$this->warning_level_colour = "red";
			}
			
			if (isset($data['oauth_key']) && isset($data['oauth_secret'])) {
				$this->oauth_key 	= $data['oauth_key'];
				$this->oauth_secret = $data['oauth_secret'];
			}
			
			$this->oauth_id = $data['oauth_consumer_id'];
			
			// Bugfix for REALLY old accounts with a NULL user_level
			if ($this->level == NULL && $this->active = 1) {
				$this->level = 1;
			}
			
			// Generate a new API key and secret
			if (empty($this->api_key) || empty($this->api_secret)) {
				require_once("includes/bcrypt.class.php"); 
				$bcrypted = new \Bcrypt(4); 
				$this->api_secret 	= $bcrypted->hash($this->username.$this->regdate.$this->id);
				$this->api_key		= crypt($this->username.$this->id, "rl");
				
				try {
					$this->commit(true); 
				} catch (Exception $e) {
					global $Error;
					$Error->save($e);
				}
			}
			
			/**
			 * Set some default values for $this->preferences
			 */
			
			if (empty($this->preferences)) {
				$this->preferences = new stdClass; 
				
				$this->preferences->home = "Home"; 
				$this->preferences->showads = true;
				$this->preferences->forums = new stdClass;
				$this->preferences->forums->hideinternational = false;
				$this->commit(true);
			}
			
			return true;
		}
		
		/**
		 * Validate this user object
		 * @since Version 3.2
		 * @version 3.9
		 * @param boolean $ignore Flag to toggle if some value checks (eg password) should be ignored
		 * @return boolean
		 */
		
		public function validate($ignore = false) {
			
			if (empty($this->username)) {
				throw new Exception("Username cannot be empty");
			}
			
			if (empty($this->contact_email)) {
				throw new Exception("User must have an email address");
			}
			
			if (empty($this->regdate)) {
				$this->regdate = date("M j, Y");
			}
			
			if (!$ignore) {
				if ($this->provider == "railpage" && (empty($this->password))) {
					throw new Exception("Password is empty");
				}
				
				if (empty($this->password)) {
					$this->password = "";
				}
				
				if (empty($this->password_bcrypt)) {
					$this->password_bcrypt = "";
				}
			}
			
			if (empty($this->level)) {
				$this->level = 1;
			}
			
			if (!filter_var($this->contact_email, FILTER_VALIDATE_EMAIL)) {
				throw new Exception(sprintf("%s is not a valid email address", $this->contact_email));
			}
			
			if (empty($this->provider)) {
				$this->provider = "railpage";
			}
			
			return true;
		}
		
		/**
		 * Commit changes to existing user or create new user
		 * @since Version 3.1
		 * @version 3.9
		 * @param boolean $force Force an update of this user even if certain values (eg a password) are empty
		 * @return boolean
		 */
		
		public function commit($force = false) {
			
			if (!$this->validate($force)) {
				// Get out early
				return false;
			}
			
			if (!empty($this->mckey) && getMemcacheObject($this->mckey)) {
				removeMemcacheObject($this->mckey);
			}
			
			$dataArray = array();
			
			$dataArray['provider'] = $this->provider;
			$dataArray['meta'] = json_encode($this->meta);
			$dataArray['user_opts'] = json_encode($this->preferences);
			$dataArray['username'] = $this->username; 
			$dataArray['user_active'] = $this->active; 
			$dataArray['user_regdate'] = $this->regdate;
			$dataArray['user_level'] = $this->level;
			$dataArray['user_posts'] = $this->posts;
			$dataArray['user_style'] = $this->style;
			$dataArray['theme'] = $this->theme;
			$dataArray['user_lang'] = $this->lang;
			$dataArray['user_dateformat'] = $this->date_format;
			$dataArray['user_rank'] = $this->rank_id;
			$dataArray['user_from'] = $this->location;
			$dataArray['user_occ'] = $this->occupation;
			$dataArray['user_interests'] = $this->interests;
			$dataArray['name'] = $this->real_name;
			$dataArray['timezone'] = $this->timezone;
			$dataArray['user_website'] = $this->website;
			$dataArray['user_allow_viewonline'] = $this->hide; 
			
			$dataArray['uWheat'] = $this->wheat;
			$dataArray['uChaff'] = $this->chaff;
			
			$dataArray['api_key'] = $this->api_key;
			$dataArray['api_secret'] = $this->api_secret;
			
			$dataArray['user_report_optout'] = $this->report_optout;
			
			$dataArray['user_warnlevel'] = $this->warning_level;
			$dataArray['disallow_mod_warn'] = $this->warning_exempt;
			
			$dataArray['user_group_cp'] = $this->group_cp;
			$dataArray['user_group_list_cp'] = $this->group_list_cp;
			$dataArray['user_active_cp'] = $this->active_cp;
			
			$dataArray['user_forum_postsperpage'] = $this->items_per_page;
			
			$dataArray['user_avatar'] = $this->avatar;
			$dataArray['user_avatar_gravatar'] = $this->avatar_gravatar;
			$dataArray['user_avatar_type'] = $this->avatar_type;
			$dataArray['user_avatar_width'] = $this->avatar_width; 
			$dataArray['user_avatar_height'] = $this->avatar_height; 
			
			$dataArray['user_new_privmsg'] = $this->privmsg_new;
			$dataArray['user_unread_privmsg'] = $this->privmsg_unread;
			$dataArray['user_last_privmsg'] = $this->privmsg_last_id;
			
			$dataArray['user_viewemail'] = $this->email_show; 
			$dataArray['storynum'] = $this->news_submissions; 
			
			$dataArray['user_notify'] = $this->notify; 
			$dataArray['user_notify_pm'] = $this->notify_privmsg; 
			
			$dataArray['user_email'] = $this->contact_email; 
			$dataArray['femail'] = $this->contact_email_public; 
			$dataArray['user_icq'] = $this->contact_icq; 
			$dataArray['user_aim'] = $this->contact_aim; 
			$dataArray['user_yim'] = $this->contact_yim; 
			$dataArray['user_msnm'] = $this->contact_msn;
			
			$dataArray['user_sig'] = $this->signature; 
			$dataArray['user_attachsig'] = $this->signature_attach;
			$dataArray['user_showsigs'] = $this->signature_showall;
			$dataArray['user_sig_bbcode_uid'] = $this->signature_bbcode_uid;
			
			$dataArray['user_password'] = $this->password; 
			$dataArray['user_password_bcrypt'] = $this->password_bcrypt;
			
			$dataArray['user_lastvisit'] = $this->lastvisit;
			$dataArray['user_session_time'] = $this->session_time; 
			$dataArray['user_session_page'] = $this->session_page; 
			$dataArray['user_current_visit'] = $this->session_current; 
			$dataArray['user_last_visit'] = $this->session_last; 
			$dataArray['last_session_ip'] = $this->session_ip; 
			$dataArray['last_session_cslh'] = $this->session_cslh; 
			$dataArray['last_session_ignore'] = $this->session_mu_ignore; 
			
			$dataArray['user_enablerte'] = $this->enable_rte; 
			$dataArray['user_enableglossary'] = $this->enable_glossary;
			$dataArray['user_allowhtml'] = $this->enable_html; 
			$dataArray['user_allowbbcode'] = $this->enable_bbcode; 
			$dataArray['user_allowsmile'] = $this->enable_emoticons; 
			$dataArray['user_allowavatar'] = $this->enable_avatar; 
			$dataArray['user_allow_pm'] = $this->enable_privmsg;
			$dataArray['user_popup_pm'] = $this->enable_privmsg_popup;
			
			$dataArray['flickr_oauth_token'] = $this->flickr_oauth_token; 
			$dataArray['flickr_oauth_token_secret'] = $this->flickr_oauth_secret; 
			$dataArray['flickr_nsid'] = $this->flickr_nsid; 
			$dataArray['flickr_username'] = $this->flickr_username;
			
			$dataArray['user_actkey'] = $this->act_key;
			
			$dataArray['oauth_consumer_id'] = $this->oauth_id;
			$dataArray['sidebar_type'] = $this->sidebar_type;
			$dataArray['user_enableautologin'] = $this->enable_autologin;
			
			$dataArray['user_enablessl'] = $this->ssl;
			
			$dataArray['facebook_user_id'] = $this->facebook_user_id;
			$dataArray['reported_to_sfs'] = $this->reported_to_sfs;
			
			if ($this->db instanceof \sql_db) {
				// Escape values for SQL
				foreach ($dataArray as $key => $val) {
					$dataArray[$key] = $this->db->real_escape_string($val); 
				}
				
				
				if ($this->id) {
					// Update existing user
					$where = array(); 
					$where['user_id'] = $this->db->real_escape_string($this->id); 
					
					$query = $this->db->buildQuery($dataArray, "nuke_users", $where);
				} else {
					// Create a new user
					$query = $this->db->buildQuery($dataArray, "nuke_users");
				}
				
				try {
					if ($this->db->query($query)) {
						if (!$this->id) {
							$this->id = $this->db->insert_id;
						} 
					
						$return = true;
					} else {
						throw new \Exception($this->db->error);
					}
				} catch (Exception $e) {
					global $Error;
					$Error->save($e);
					
					$return = false;
					
					throw new \Exception($e->getMessage());
				}
				
				return $return;
			} else {
				if ($this->id) {
					$this->db->update("nuke_users", $dataArray, array("user_id = ?" => $this->id));
				} else {
					$this->db->insert("nuke_users", $dataArray);
					$this->id = $this->db->lastInsertId();
					
					$this->createUrls();
				}
				
				return true;
			}
		}
		
		/**
		 * Populate this object with guest data
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @return boolean
		 * @todo Complete this function
		 */
		
		public function guest() {
			$this->lang = "english";
			$this->date_format = "d M Y H:i";
			$this->avatar_filename = "blank.png";
			$this->email_show = 1;
			$this->signature_attach = 0;
			$this->signature_showall = 0;
			$this->enable_rte = 1;
			$this->enable_html = 1;
			$this->enable_bbcode = 1;
			$this->enable_emoticons = 1;
			$this->enable_avatar = 1;
			$this->hide = 0;
			$this->notify = 0;
			$this->notify_privmsg = 0;
			$this->theme = $this->default_theme;
			$this->items_per_page = 25;
			$this->enable_glossary = 0;
			$this->sidebar_type = 2;
			$this->timezone = "Australia/Melbourne";
			
			/**
			 * Set some default values for $this->opts
			 */
			
			if (empty($this->preferences)) {
				$this->preferences = new stdClass; 
				
				$this->preferences->home = "Home"; 
				$this->preferences->showads = true;
				$this->preferences->forums = new stdClass;
				$this->preferences->forums->hideinternational = false;
			}
			
			return true;
		}
		
		/**
		 * Check for group membership
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @param int $group_id
		 * @return boolean
		 */
		
		public function inGroup($group_id = false) {
			if (!$this->db || !$group_id) {
				return false;
			}
			
			if ($group_id == RP_GROUP_ADMINS && $this->level >= 2) {
				return true;
			}
			
			if (is_array($this->groups) && in_array($group_id, $this->groups)) {
				return true;
			} else {
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT group_id FROM nuke_bbuser_group USE INDEX (user_id) WHERE group_id = ".$this->db->real_escape_string($group_id)." AND user_id = ".$this->db->real_escape_string($this->id)." AND user_pending = 0";
				
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows == 1) {
						return true;
					} else {
						return false;
					}
				} else {
					return false;
				}
			} else {
				$query = "SELECT group_id FROM nuke_bbuser_group USE INDEX (user_id) WHERE group_id = ? AND user_id = ? AND user_pending = 0";
				
				if ($result = $this->db->fetchOne($query, array($group_id, $this->id))) {
					if ($result == $group_id) {
						return true;
					}
				}
				
				return false;
			}
		}
		
		/**
		 * Generate userdata array for phpBB2.x backwards compatibility
		 * @since Version 3.7.5
		 * @return array
		 */
		
		public function generateUserData() {
			$return = array(); 
			$return['session_id'] 	= $_SESSION['session_id']; 
			$return['user_id'] 		= $this->id;
			$return['username'] 	= $this->username;
			$return['theme']		= $this->theme;
			
			$return['session_logged_in']	= $this->id > 0 ? true : false;
			$return['user_lastvisit'] 		= $this->lastvisit;
			$return['user_showsigs']		= $this->signature_showall;
			$return['user_level']			= $this->level;
			$return['user_attachsig']		= $this->signature_attach;
			$return['user_notify']			= $this->notify;
			$return['user_allow_pm']		= $this->enable_privmsg;
			$return['user_allowhtml']		= $this->enable_html;
			$return['user_allowbbcode']		= $this->enable_bbcode;
			$return['user_allowsmile']		= $this->enable_emoticons;
			$return['user_sig']				= $this->signature;
			$return['user_report_optout']	= $this->report_optout;
			$return['user_style']			= $this->theme;
			
			$return['user_forum_postsperpage']	= $this->items_per_page;
			
			
			/**
			 * Crap values that I don't care about anymore
			 */
			
			$return['user_new_privmsg'] 	= false;
			$return['user_unread_privmsg'] 	= false;
			
			return $return;
		}
		
		/**
		 * Get user data, return as associative array
		 * @since Version 3.0
		 * @version 3.0
		 * @param int $user_id
		 * @return mixed
		 * @deprecated Deprecated since Version 3.0.1
		 */
		 
		public function getUser($user_id = false) {
			if (!$this->db) {
				return false;
			}
			
			$trace = debug_backtrace(); 
			
			throw new \Exception("Deprecated function " . $trace[0]['class'] . "->" . $trace[0]['function'] . " called from " . $trace[0]['file'] . " on line " . $trace[0]['line']);
			return false;
		}
		
		/**
		 * Set last visit time (user login)
		 * @since Version 3.0
		 * @version 3.0
		 * @param int $user_id
		 * @param int $time
		 */
		 
		public function updateVisit($user_id = false, $time = false) {
			if ($this->db && $user_id) {
				if ($this->db instanceof \sql_db) {
					if (!$time) {
						// Time not provided, select last page visit from database
						$query = "SELECT user_session_time FROM nuke_users WHERE user_id = '".$this->db->real_escape_string($user_id)."'"; 
						if ($rs = $this->db->query($query)) {
							$time = $rs->fetch_assoc(); 
							$time = $time['user_session_time'];
						} else {
							trigger_error("User: Could not update last visit timestamp"); 
							trigger_error($this->db->error); 
							trigger_error($query); 
						}
					}
					
					$this->db->query("UPDATE nuke_users SET user_lastvisit = ".$this->db->real_escape_string($time)." WHERE user_id = '".$this->db->real_escape_string($user_id)."'"); 
				} else {
					if (!$time) {
						$time = $this->db->fetchOne("SELECT user_session_time FROM nuke_users WHERE user_id = ?", $user_id); 
					}
					
					$data = array(
						"user_lastvisit" => $time
					);
					
					$this->db->update("nuke_users", $data, array("user_id = ?" => $user_id));
				}
			}
		}
		
		/**
		 * Set last session activity
		 * @since Version 3.0
		 * @version 3.0
		 * @param int $user_id
		 */
		 
		public function updateSessionTime($user_id = false) {
			if (!$user_id) {
				$user_id = $this->id;
			}
			
			if ($user_id) {
				if (!isset($_SESSION['sessiontime_lastupdate']) || $_SESSION['sessiontime_lastupdate'] <= time() - 300) {
					if (RP_DEBUG) {
						global $site_debug;
						$debug_timer_start = microtime(true);
					}
				
					if ($this->db instanceof \sql_db) {
						if (!empty($_SERVER['REMOTE_ADDR'])) {
							$ip_sql = "last_session_ip = '".$this->db->real_escape_string($_SERVER['REMOTE_ADDR'])."', "; 
						} else {
							$ip_sql = "";
						}
						
						$query = "UPDATE nuke_users SET user_session_time = '".time()."' ".$ip_sql." WHERE user_id = '".$this->db->real_escape_string($user_id)."'"; 
						
						if (!$this->db->query($query)) {
							throw new \Exception($this->db->error); 
						}
					} else {
						$data = array(
							"user_session_time" => time()
						);
						
						if (!empty($_SERVER['REMOTE_ADDR'])) {
							$data['last_session_ip'] = $_SERVER['REMOTE_ADDR']; 
						}
						
						$rs = $this->db->update("nuke_users", $data, array("user_id = ?" => $user_id));
						
						if (RP_DEBUG) {
							if ($rs === false) {
								$site_debug[] = "Zend_DB: FAILED update user_session_time for user ID " . $user_id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
							} else {
								$site_debug[] = "Zend_DB: SUCCESS update user_session_time for user ID " . $user_id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
								$_SESSION['sessiontime_lastupdate'] = time(); 
							}
						}
					}
					
					return $rs;
				}
			}
		}
		
		/**
		 * Load warning history
		 * @since Version 3.2
		 * @version 3.2
		 * @return boolean
		 */
		 
		public function loadWarnings() {
			if ($this->db instanceof \sql_db) {
				$query = "SELECT w.warn_id AS warning_id, w.user_id, u.username, w.warned_by AS staff_user_id, s.username AS staff_username, w.warn_reason, w.mod_comments AS staff_comments, w.actiontaken AS warn_action, w.warn_date FROM phpbb_warnings AS w LEFT JOIN nuke_users AS u ON u.user_id = w.user_ID LEFT JOIN nuke_users AS s ON s.user_id = w.warned_by WHERE w.user_id = ".$this->db->real_escape_string($this->id)." ORDER BY w.warn_date";
				
				if ($rs = $this->db->query($query)) {
					while ($row = $rs->fetch_assoc()) {
						$this->warnings[] = $row;
					}
					
					return true;
				} else {
					trigger_error("User : Could not load warnings for user id ".$this->id);
					trigger_error($this->db->error);
					
					return false;
				}
			} else {
				$query = "SELECT w.warn_id AS warning_id, w.user_id, u.username, w.warned_by AS staff_user_id, s.username AS staff_username, w.warn_reason, w.mod_comments AS staff_comments, w.actiontaken AS warn_action, w.warn_date FROM phpbb_warnings AS w LEFT JOIN nuke_users AS u ON u.user_id = w.user_ID LEFT JOIN nuke_users AS s ON s.user_id = w.warned_by WHERE w.user_id = ? ORDER BY w.warn_date";
				
				if ($result = $this->db->fetchAll($query, $this->id)) {
					foreach ($result as $row) {
						$this->warnings[] = $row;
					}
				}
				
				return true;
			}
		}
		
		/**
		 * Load user notes
		 * @since Version 3.2
		 * @version 3.2
		 * @return boolean
		 */
		
		public function loadNotes() {
			if (!$this->id) {
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT un.nid AS note_id, un.datetime AS note_timestamp, un.data AS note_text, un.aid AS admin_user_id, u.username AS admin_username FROM nuke_users_notes AS un LEFT JOIN nuke_users AS u ON un.aid = u.user_id WHERE un.uid = ".$this->db->real_escape_string($this->id)."";
				
				if ($rs = $this->db->query($query)) {
					while ($row = $rs->fetch_assoc()) {
						if ($row['admin_user_id'] == "0") {
							$row['admin_username'] = "System";
						}
						
						$this->notes[] = $row;
					}
					
					return true;
				} else {
					throw new \Exception($this->db->error."\n\n".$this->db->query); 
					
					return false;
				}
			} else {
				$query = "SELECT un.nid AS note_id, un.datetime AS note_timestamp, un.data AS note_text, un.aid AS admin_user_id, u.username AS admin_username FROM nuke_users_notes AS un LEFT JOIN nuke_users AS u ON un.aid = u.user_id WHERE un.uid = ?";
				
				if ($result = $this->db->fetchAll($query, $this->id)) {
					foreach ($result as $row) {
						if ($row['admin_user_id'] == "0") {
							$row['admin_username'] = "System";
						}
						
						$this->notes[] = $row;
					}
				}
				
				return true;
			}
		}
		
		/** 
		 * Add a note
		 * @since Version 3.2
		 * @version 3.2
		 * @return boolean
		 * @param string $text
		 * @param int $admin_user_id
		 */
		
		public function addNote($text = false, $admin_user_id = 0) {
			if (!$text) {
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$dataArray['uid'] = $this->id;
				$dataArray['aid'] = $this->db->real_escape_string($admin_user_id);
				$dataArray['datetime'] = time(); 
				$dataArray['data'] = $this->db->real_escape_string($text);
				
				$query = $this->db->buildQuery($dataArray, "nuke_users_notes"); 
				
				if ($rs = $this->db->query($query)) {
					return true;
				} else {
					throw new \Exception($this->db->error);
					return false;
				}
			} else {
				$data = array(
					"uid" => !filter_var($this->id, FILTER_VALIDATE_INT) ? "0" : $this->id,
					"aid" => $admin_user_id,
					"datetime" => time(),
					"data" => $text
				);
				
				return $this->db->insert("nuke_users_notes", $data);
			}
		}
		
		/**
		 * Set auto-login token
		 * @since Version 3.2
		 * @version 3.2
		 * @return boolean
		 * @param int $cookie_expire
		 */
		
		public function setAutoLogin($cookie_expire) {
			if (empty($cookie_expire)) {
				$cookie_expire = RP_AUTOLOGIN_EXPIRE;
			}
			
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$client_addr = $_SERVER['HTTP_X_FORWARDED_FOR']; 
			} else {
				$client_addr = $_SERVER['REMOTE_ADDR'];
			}	
			
			if ($this->db instanceof \sql_db) {
				$dataArray = array(); 
				$dataArray['user_id'] 				= $this->id; 
				$dataArray['autologin_token']		= $this->db->real_escape_string(get_random_string("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=<>[]{}|~", 16)); #"#".substr(hash('haval128,5', $this->username.$this->regdate.rand()), 0, 14)."+!";
				$dataArray['autologin_expire']		= $cookie_expire;
				$dataArray['autologin_ip']			= $this->db->real_escape_string($client_addr); 
				$dataArray['autologin_hostname']	= $this->db->real_escape_string($_SERVER['REMOTE_HOST']); 
				$dataArray['autologin_last']		= time(); 
				$dataArray['autologin_time']		= time();
				
				$query = $this->db->buildQuery($dataArray, "nuke_users_autologin"); 
				
				$autologin['user_id']	= $this->id;
				$autologin['token']		= $dataArray['autologin_token'];
				
				if ($this->db->query($query)) {
					// DB insert true, set the cookie
					setcookie("rp_autologin", base64_encode(implode(":",$autologin)), $cookie_expire, RP_AUTOLOGIN_PATH, RP_AUTOLOGIN_DOMAIN, RP_SSL_ENABLED, true); 
					return true;
				} else {
					throw new \Exception($this->db->error); 
					return false;
				}
			} else {
				$data = array(
					"user_id" => $this->id,
					"autologin_token" => get_random_string("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=<>[]{}|~", 16),
					"autologin_expire" => $cookie_expire,
					"autologin_ip" => $client_addr,
					"autologin_hostname" => $_SERVER['REMOTE_HOST'],
					"autologin_last" => time(),
					"autologin_time" => time()
				);
				
				$autologin = array(
					"user_id" => $this->id,
					"token" => $data['autologin_token']
				);
				
				if ($this->db->insert("nuke_users_autologin", $data)) {
					setcookie("rp_autologin", base64_encode(implode(":",$autologin)), $cookie_expire, RP_AUTOLOGIN_PATH, RP_AUTOLOGIN_DOMAIN, RP_SSL_ENABLED, true); 
					
					$this->addNote("Autologin token set");
					return true;
				}
			}
				
			return false;
		}
		
		/**
		 * Attempt auto-login
		 * @since Version 3.2
		 * @version 3.2
		 * @return mixed
		 */
		
		public function tryAutoLogin() {
			if (empty($_COOKIE['rp_autologin'])) {
				return false;
			} else {
				$cookie = explode(":", base64_decode($_COOKIE['rp_autologin'])); 
				
				if (count($cookie) < 2) {
					return false;
				}
				
				if ($this->db instanceof \sql_db) {
					$query = "SELECT autologin_id FROM nuke_users_autologin WHERE user_id = '".$this->db->real_escape_string($cookie[0])."' AND autologin_token = '".$this->db->real_escape_string($cookie[1])."'"; 
					
					if ($rs = $this->db->query($query)) {
						if ($row = $rs->fetch_assoc()) {
							$autologin_id = $row['autologin_id'];
							
							if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
								$client_addr = $_SERVER['HTTP_X_FORWARDED_FOR']; 
							} else {
								$client_addr = $_SERVER['REMOTE_ADDR'];
							}		
							
							$query = "UPDATE nuke_users_autologin SET autologin_last = ".time().", autologin_ip = '".$this->db->real_escape_string($client_addr)."', autologin_hostname = '".$this->db->real_escape_string($_SERVER['REMOTE_HOST'])."' WHERE autologin_id = ".$autologin_id; 
							
							$this->db->query($query); 
							
							// Record the login event
							try {
								$this->id = $cookie[0]; 
								$this->recordLogin();
							} catch (Exception $e) {
								global $Error; 
								$Error->save($e); 
							}
							
							return $cookie[0];
						}
					}
				} else {
					$query = "SELECT autologin_id FROM nuke_users_autologin WHERE user_id = ? AND autologin_token = ?"; 
					
					if ($autologin_id = $this->db->fetchOne($query, array($cookie[0], $cookie[1]))) {
							
						if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
							$client_addr = $_SERVER['HTTP_X_FORWARDED_FOR']; 
						} else {
							$client_addr = $_SERVER['REMOTE_ADDR'];
						}		
						
						$data = array(
							"autologin_last" => time(),
							"autologin_ip" => $client_addr,
							"autologin_hostname" => $_SERVER['REMOTE_ADDR'],
						);
						
						$this->db->update("nuke_users_autologin", $data, array("autologin_id = ?" => $autologin_id));
						
						// Record the login event
						try {
							$this->id = $cookie[0]; 
							$this->recordLogin();
						} catch (Exception $e) {
							global $Error; 
							$Error->save($e); 
						}
						
						return $cookie[0];

					}
				}
				
				return false;
			}
		}
		
		/**
		 * Get auto logins for this user
		 * @since Version 3.2
		 * @version 3.2
		 * @return mixed
		 */
		
		public function getAutoLogin() {
			if ($this->db instanceof \sql_db) {
				$query = "SELECT autologin_id AS id, autologin_token AS token, autologin_time AS date_set, autologin_expire AS date_expire, autologin_last AS date_last, autologin_ip AS ip, autologin_hostname AS hostname FROM nuke_users_autologin WHERE user_id = ".$this->id." ORDER BY autologin_last DESC"; 
				
				if ($rs = $this->db->query($query)) {
					while ($row = $rs->fetch_assoc()) {
						$autologins[$row['id']] = $row; 
					}
					
					return $autologins;
				} else {
					throw new \Exception($this->db->error); 
					return false;
				}
			} else {
				$query = "SELECT autologin_id AS id, autologin_token AS token, autologin_time AS date_set, autologin_expire AS date_expire, autologin_last AS date_last, autologin_ip AS ip, autologin_hostname AS hostname FROM nuke_users_autologin WHERE user_id = ? ORDER BY autologin_last DESC"; 
				
				$autologins = array();
				
				if ($result = $this->db->fetchAll($query, $this->id)) {
					foreach ($result as $row) {
						$autologins[$row['id']] = $row; 
					}
				}
				
				return $autologins;
			}
			
			return false;
		}
		
		/**
		 * Delete autologin token
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $token_id
		 * @return boolean
		 */
		
		public function deleteAutoLogin($token_id = false) {
			if (!$this->id) {
				return false;
			} 
			
			if ($this->db instanceof \sql_db) {
				$query = "DELETE FROM nuke_users_autologin WHERE user_id = ".$this->id; 
				
				if ($token_id) {
					$query .= " AND autologin_id = ".$this->db->real_escape_string($token_id); 
				}
				
				if ($this->db->query($query)) {
					return true;
				} else {
					throw new \Exception($this->db->error); 
					return false;
				}
			} else {
				$clause = array(
					"user_id" => $this->id
				);
				
				if ($token_id) {
					$clause['autologin_id'] = $token_id;
				}
				
				return $this->db->delete("nuke_users_autologin", $clause);
			}
		}
		
		/**
		 * Record login event
		 * @since Version 3.2
		 * @version 3.2
		 * @return boolean
		 */
		
		public function recordLogin() {
			if (!$this->id) {
				return false;
			}
			
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$client_addr = $_SERVER['HTTP_X_FORWARDED_FOR']; 
			} else {
				$client_addr = $_SERVER['REMOTE_ADDR'];
			}
			
			if ($this->db instanceof \sql_db) {
				$dataArray = array(); 
				$dataArray['user_id'] = $this->id;
				$dataArray['login_time'] = time(); 
				$dataArray['login_ip'] = $this->db->real_escape_string($client_addr); 
				$dataArray['login_hostname'] = $this->db->real_escape_string($_SERVER['REMOTE_HOST']);
				
				$query = $this->db->buildQuery($dataArray, "log_logins"); 
				
				if ($this->db->query($query)) {
					return true; 
				} else {
					throw new \Exception($this->db->error); 
					return false;
				}
			} else {
				$data = array(
					"user_id" => $this->id,
					"login_time" => time(),
					"login_ip" => $client_addr,
					"login_hostname" => $_SERVER['REMOTE_HOST']
				);
				
				if ($data['login_ip'] == $data['login_hostname']) {
					$data['login_hostname'] = gethostbyaddr($data['login_ip']);
				}
				
				return $this->db->insert("log_logins", $data);
			}
		}
		
		/**
		 * Get login history for this user
		 * @since Version 3.2
		 * @version 3.2
		 * @return mixed
		 * @param int $items_per_page
		 * @param int $page
		 */
		
		public function getLogins($items_per_page = 25, $page = 1) {
			if (!$this->id) {
				return false;
			}
				
			$logins = array();
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT * FROM log_logins USE INDEX (login_time) WHERE user_id = ".$this->id." ORDER BY login_time DESC LIMIT 0,25"; 
				
				if ($rs = $this->db->query($query)) {
					$logins = array(); 
					
					while ($row = $rs->fetch_assoc()) {
						$logins[$row['login_id']] = $row; 
					}
				} else {
					throw new \Exception($this->db->error); 
					return false;
				}
			} else {
				#$query = "SELECT * FROM log_logins USE INDEX (login_time) WHERE user_id = ? ORDER BY login_time DESC LIMIT ?,?"; 
				$query = "SELECT * FROM log_logins WHERE user_id = ? ORDER BY login_time DESC LIMIT ?,?"; # Dropped USE_INDEX - negatively impacted query performance when zero results were found
				
				$args = array(
					$this->id,
					($page - 1) * $items_per_page,
					$items_per_page
				);
				
				if ($result = $this->db->fetchAll($query, $args)) {
					foreach ($result as $row) {
						$logins[$row['login_id']] = $row; 
					}
				}
			}
			
			return $logins;
		}
		
		/**
		 * Update this user's PC ID hash
		 * @since Version 3.2
		 * @version 3.2
		 * @return boolean
		 */
		
		public function updateHash() {
			$cookie = $_COOKIE['rp_userhash']; 
			$hash	= array(); 
			$update = false;
			
			if (is_null($cookie) || empty($cookie) || empty($this->id)) {
				// No hash
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query 	= "SELECT hash FROM nuke_users_hash WHERE user_id = ".$this->id; 
				
				// Pull hash records from the database
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows) {
						while ($row = $rs->fetch_assoc()) {
							$hash[] = $row['hash']; 
						}
						
						if (in_array($cookie, $hash)) {
							$update = true; 
						}
					}
					
					$dataArray = array(); 
					$dataArray['user_id'] 	= $this->id; 
					$dataArray['hash']		= $cookie; 
					$dataArray['date']		= time(); 
					
					if (empty($_SERVER['X_FORWARDED_FOR'])) {
						$dataArray['ip'] = $_SERVER['REMOTE_ADDR']; 
					} else {
						$dataArray['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR']; 
					}
					
					if ($update) {
						$where = array(); 
						$where['hash'] = $this->db->real_escape_string($cookie); 
						$where['user_id'] = $this->id;
					} 
					
					$query = $this->db->buildQuery($dataArray, "nuke_users_hash", $where); 
					
					if ($this->db->query($query)) {
						return true;
					} else {
						throw new \Exception($this->db->error); 
						return false;
					}
				} else {
					// Couldn't execute query...
					throw new \Exception($this->db->error); 
				}
			} else {
				$query = "SELECT hash FROM nuke_users_hash WHERE user_id = ?";
				
				if ($result = $this->db->fetchAll($query, $this->id)) {
					foreach ($result as $row) {
						$hash[] = $row['hash'];
					}
					
					if (in_array($cookie, $hash)) {
						$update = true; 
					}
					
					$data = array(
						"user_id" => $this->id,
						"hash" => $cookie,
						"date" => time()
					);
					
					if (empty($_SERVER['X_FORWARDED_FOR'])) {
						$data['ip'] = $_SERVER['REMOTE_ADDR']; 
					} else {
						$data['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR']; 
					}
					
					if ($update) {
						$this->db->update("nuke_users_hash", $data, array("user_id = ?" => $this->id));
					} else {
						$this->db->insert("nuke_users_hash", $data);
					}
				}
			}
		}
		
		/**
		 * Generate a new API key for this user
		 * @since Version 3.4
		 * @return boolean
		 */
		
		public function newAPIKey() {
			$len = 32; 
			
			if (@is_readable('/dev/urandom')) { 
				$f = fopen('/dev/urandom', 'r'); 
				$urandom = fread($f, $len); 
				fclose($f); 
			} 
		
			$return=''; 
			
			for ($i = 0; $i < $len; ++$i) { 
				if (!isset($urandom)) { 
					if ($i % 2 == 0) mt_srand(time() % 2147 * 1000000 + (double)microtime() * 1000000); 
					$rand = 48 + mt_rand() % 64; 
				} else {
					$rand = 48 + ord($urandom[$i]) % 64; 
				}
		
				if ($rand > 57) {
					$rand += 7; 
				}
				
				if ($rand > 90) { 
					$rand += 6; 
				}
		
				if ($rand == 123) {
					$rand = 45; 
				}
				
				if ($rand == 124) {
					$rand = 46; 
				}
				
				$return .= chr($rand); 
			}
			
			$this->api_key = $return; 
		}
		
		/**
		 * Increase user's chaff rating by $amount
		 * @since Version 3.4
		 * @param int $amount
		 * @return boolean
		 */
		
		public function addChaff($amount = 1) {
			if (!$this->id) {
				throw new \Exception("Cannot increment chaff rating - user ID unavailable"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "UPDATE nuke_users SET uChaff = '".$this->db->real_escape_string($this->chaff + $amount)."' WHERE user_id ='".$this->id."'";
				
				if ($this->db->query($query)) {
					return true; 
				} else {
					throw new \Exception($this->db->error."\n\n".$query); 
					return false;
				}
			} else {
				$data = array(
					"uChaff" => $this->chaff + $amount
				);
				
				return $this->db->update("nuke_users", $data, array("user_id" => $this->id));
			}
		}
		
		/**
		 * Fetch the users' timeline
		 * @since Version 3.5
		 * @param object $date_start
		 * @param object $date_end
		 * @return array
		 */
		
		public function timeline($date_start, $date_end) {
			if (filter_var($date_start, FILTER_VALIDATE_INT)) {
				$page = $date_start;
			} elseif (!is_a($date_start, "DateTime")) {
				throw new \Exception("\$date_start needs to be an instance of DateTime"); 
				return false;
			}
			
			if (filter_var($date_end, FILTER_VALIDATE_INT)) {
				$items_per_page = $date_end;
			} elseif (!is_a($date_end, "DateTime")) {
				throw new \Exception("\$date_end needs to be an instance of DateTime"); 
				return false;
			}
			
			/**
			 * Filter out forums this user doesn't have access to
			 */
			
			if (isset($this->Guest) && $this->Guest instanceof User) {
				$forum_post_filter_mckey = sprintf("forum.post.filter.user:%d", $this->Guest->id);
				
				if (!$forum_post_filter = getMemcacheObject($forum_post_filter_mckey)) {
					$Forums = new Forums;
					$Index = new Index;
					
					$acl = $Forums->setUser($this->Guest)->getACL();
					
					$allowed_forums = array(); 
					
					foreach ($Index->forums() as $row) {
						$Forum = new Forum($row['forum_id']);
						
						if ($Forum->setUser($this->Guest)->isAllowed(Forums::AUTH_READ)) {
							$allowed_forums[] = $Forum->id;
						}
					}
					
					$forum_filter = "AND p.forum_id IN (" . implode(",", $allowed_forums) . ")";
					
					$forum_post_filter = "AND id NOT IN (SELECT l.id AS log_id
						FROM log_general AS l 
						LEFT JOIN nuke_bbposts AS p ON p.post_id = l.value
						WHERE l.key = 'post_id' 
						" . $forum_filter . ")";
					
					setMemcacheObject($forum_post_filter_mckey, $forum_post_filter, strtotime("+1 week"));
				}
			} else {
				$forum_post_filter = "";
			}
			
			
			if ($page && $items_per_page) {
				$query = "SELECT SQL_CALC_FOUND_ROWS * FROM log_general WHERE user_id = ? " . $forum_post_filter . " ORDER BY timestamp DESC LIMIT ?, ?";
				$offset = ($page - 1) * $items_per_page; 
				
				$params = array(
					$this->id, 
					$offset, 
					$items_per_page
				);
			} else {
				$query = "SELECT SQL_CALC_FOUND_ROWS * FROM log_general WHERE user_id = ? " . $forum_post_filter . " AND timestamp >= ? AND timestamp <= ? ORDER BY timestamp DESC";
				
				$params = array(
					$this->id, 
					$date_start->format("Y-m-d H:i:s"), 
					$date_end->format("Y-m-d H:i:s")
				);
			}
			
			$timeline = array(
				"total" => 0
			); 
			
			if ($result = $this->db->fetchAll($query, $params)) {
				if ($page && $items_per_page) {
					$timeline['page'] = $page;
					$timeline['perpage'] = $items_per_page;
				} else {
					$timeline['start'] = $date_start->format("Y-m-d H:i:s");
					$timeline['end'] = $date_end->format("Y-m-d H:i:s");
				}
				
				$timeline['total'] = $this->db->fetchOne("SELECT FOUND_ROWS() AS total"); 
				
				foreach ($result as $row) {
					$row['args'] = json_decode($row['args'], true);
					$row['timestamp'] = new DateTime($row['timestamp']); 
					
					$timeline['timeline'][$row['id']] = $row;
				}
			}
			
			/**
			 * Process the timeline data
			 */
			
			if (isset($timeline['timeline'])) {
				foreach ($timeline['timeline'] as $key => $row) {
					// Set their timezone
					$row['timestamp']->setTimezone(new DateTimeZone($this->timezone));
					
					$relative_cutoff = new DateTime("12 hours ago", new DateTimeZone($this->timezone));
					
					$moments_ago = new DateTime("60 seconds ago", new DateTimeZone($this->timezone)); 
					$minutes_ago = new DateTime("60 minutes ago", new DateTimeZone($this->timezone));
					
					if (stristr($row['title'], "loco") && empty($row['module'])) {
						$row['module'] = "locos";
					}
					
					/**
					 * Check if the meta data array exists
					 */
					
					if (!isset($row['meta'])) {
						if (!isset($row['meta'])) {
							$row['meta'] = array(
								"id" => NULL,
								"namespace" => NULL
							); 
						}
					}
					
					/**
					 * Determine the action taken and on what kind of object
					 */
					
					$row['event']['action'] = ""; $row['event']['article'] = ""; $row['event']['object'] = ""; $row['event']['preposition'] = ""; 
					
					$row['title'] = str_ireplace(array("loco link created"), array("linked a locomotive"), $row['title']);
					
					if (preg_match("@(favourited|suggested|ignored|accepted|closed|commented|removed|re-ordered|edited|edit|added|add|sorted|sort|deleted|delete|rejected|reject|tagged|tag|changed|modified|linked|created|create)@Di", $row['title'], $matches)) {
						$row['event']['action'] = strtolower($matches[1]);
					}
					
					if (preg_match("@(idea|suggestion|correction|sighting|date|post|thread|digital asset|loco photo|loco class|loco|class|location|grouping|owners|owner|operators|operator|article|story|topic|railcam photo|photo|railcam|download|event|calendar|image)@Di", $row['title'], $matches)) {
						$row['event']['object'] = strtolower($matches[1]);
					}
					
					if ($row['title'] == "Loco link removed") {
						$row['event']['action'] = "removed";
						$row['event']['object'] = "linked locomotive";
						$row['event']['article'] = "a";
						$row['event']['preposition'] = "from";
					}
					
					/** 
					 * Preposition of this action
					 */
					
					if (preg_match("@(added|add|linked)@Di", $row['event']['action']) && preg_match("@(locos)@Di", $row['module'])) {
						$row['event']['preposition'] = "to";
					}
					
					if (preg_match("@(removed)@Di", $row['title'])) {
						$row['event']['preposition'] = "from";
					}
					
					if (preg_match("@(correction|re-ordered|sorted|sort|tagged|tag|changed|modified)@Di", $row['title'])) {
						$row['event']['preposition'] = "of";
					}
					
					if (preg_match("@(added|add|edited|edit|deleted|delete|rejected|reject|created|create)@Di", $row['title']) && preg_match("@(forums|news)@Di", $row['module'])) {
						$row['event']['preposition'] = "in";
					}
					
					if (preg_match("@(unlinked)@Di", $row['title']) && preg_match("@(locos)@Di", $row['module'])) {
						$row['event']['preposition'] = "from";
					}

					
					/**
					 * Article of this action
					 */
					
					if ($row['event']['preposition'] == "of") {
						$row['event']['article'] = "the";
					}
					
					if ($row['event']['preposition'] == "in") {
						$row['event']['article'] = "a";
					}
					
					if (preg_match("@(date)@Di", $row['event']['object'], $matches)) {
						if (preg_match("@(edited)@Di", $row['event']['action'], $matches)) {
							$row['event']['preposition'] = "for";
						}
					}
					
					if (preg_match("@(correction|date|post|thread|digital asset|loco|class|location|story|topic|railcam photo|photo|railcam|download)@Di", $row['event']['object'], $matches)) {
						if (!($matches[1] == "loco" && $row['event']['action'] == "edited")) {
							$row['event']['article'] = "a";
						}
					}
					
					if (preg_match("@(cover photo)@Di", $row['event']['object'], $matches)) {
						$row['event']['article'] = "the";
					}
					
					if (preg_match("@(operator)@Di", $row['event']['object'], $matches)) {
						$row['event']['article'] = "an";
					}
					
					if ($row['event']['action'] == "re-ordered" && preg_match("@(owners|owner|operators|operator)@Di", $row['title'], $matches)) {
						$row['event']['object'] = "owners/operators";
						$row['event']['article'] = "the";
					}
					
					/**
					 * Alter the object if needed
					 */
					
					if ($row['module'] == "locos" && $row['event']['object'] == "class") {
						$row['event']['object'] = "locomotive class";
						
						if ($row['event']['action'] == "modified") {
							unset($row['event']['preposition']);
							unset($row['event']['article']);
							unset($row['event']['object']);
						}
					}
					
					if (isset($row['event']['object']) && $row['module'] == "locos" && $row['event']['object'] == "loco photo") {
						$row['event']['object'] = "cover photo";
					}
					
					/**
					 * Set the module namespace
					 */
					
					$Module = new \Railpage\Module($row['module']);
					$row['meta']['namespace'] = $Module->namespace;
					
					/**
					 * Attempt to create a link to this object or action if none exists
					 */
					
					if (!isset($row['meta']['url'])) {
						
						switch ($row['key']) {
							
							/**
							 * Forum post
							 */
							
							case "post_id" : 
								
								$row['meta']['url'] = "/f-p" . $row['value'] . ".htm#" . $row['value'];
								
							break;
							
							/**
							 * Locomotive
							 */
							
							case "loco_id" : 
								
								$Loco = new \Railpage\Locos\Locomotive($row['value']); 
								$row['meta']['url'] = $Loco->url;
							
							break;
							
							/**
							 * Locomotive class
							 */
							
							case "class_id" : 
								
								$LocoClass = new \Railpage\Locos\LocoClass($row['value']); 
								$row['meta']['url'] = $LocoClass->url;
							
							break;
						}
						
					}
					
					/**
					 * Attempt to create a meta object title for this object or action if none exists
					 */
					
					if (!isset($row['meta']['object']['title'])) {
						
						switch ($row['key']) {
							
							/**
							 * Forum post
							 */
							
							case "post_id" : 
								
								$Post = new \Railpage\Forums\Post($row['value']);
								$row['meta']['object']['title'] = $Post->thread->title;
								
							break;
							
							/**
							 * Locomotive
							 */
							
							case "loco_id" : 
								
								$Loco = new \Railpage\Locos\Locomotive($row['value']); 
								
								$row['meta']['namespace'] = $Loco->namespace;
								$row['meta']['id'] = $Loco->id;
								
								if ($row['event']['action'] == "added" && $row['event']['object'] == "loco") {
									$row['meta']['object']['title'] = $Loco->class->name;
								} else {
									$row['meta']['object']['title'] = $Loco->number;
									$row['meta']['object']['subtitle'] = $Loco->class->name;
								}
							
							break;
							
							/**
							 * Locomotive class
							 */
							
							case "class_id" : 
								
								$LocoClass = new \Railpage\Locos\LocoClass($row['value']); 
								$row['meta']['object']['title'] = $LocoClass->name;
								
								$row['meta']['namespace'] = $LocoClass->namespace;
								$row['meta']['id'] = $LocoClass->id;
							
							break;
							
							/**
							 * Location
							 */
							
							case "id" :
								
								if ($row['module'] == "locations") {
									$Location = new \Railpage\Locations\Location($row['value']);
									$row['meta']['object']['title'] = $Location->name;
									$row['meta']['url'] = $Location->url;
									unset($row['event']['article']);
									unset($row['event']['object']);
									unset($row['event']['preposition']);
								}
								
							break;
							
							/**
							 * Photo
							 */
							
							case "photo_id" : 
								
								$row['meta']['object']['title'] = "photo";
								$row['meta']['url'] = "/flickr/" . $row['value'];
								
								if ($row['event']['action'] == "commented") {
									$row['event']['object'] = "";
									$row['event']['article'] = "on";
									$row['event']['preposition'] = "a";
								}
							
							break;
							
							/**
							 * Sighting
							 */
							
							case "sighting_id" : 
								
								if (empty($row['module']) || !isset($row['module'])) {
									$row['module'] = "sightings";
								}
								
								$row['event']['preposition'] = "of";
								$row['event']['article'] = "a";
								
								if (count($row['args']['locos']) === 1) {
									$row['meta']['object']['title'] = $row['args']['locos'][key($row['args']['locos'])]['Locomotive'];
								} elseif (count($row['args']['locos']) === 2) {
									$row['meta']['object']['title'] = $row['args']['locos'][key($row['args']['locos'])]['Locomotive'];
									next($row['args']['locos']);
									
									$row['meta']['object']['title'] .= " and " . $row['args']['locos'][key($row['args']['locos'])]['Locomotive'];
								} else {
									$locos = array();
									foreach ($row['args']['locos'] as $loco) {
										$locos[] = $loco['Locomotive'];
									}
									
									$last = array_pop($locos);
									
									$row['meta']['object']['title'] = implode(", ", $locos) . " and " . $last;
								}
							
							break;
							
							/**
							 * Idea
							 */
							
							case "idea_id" : 
							
								$Idea = new \Railpage\Ideas\Idea($row['value']);
								$row['meta']['object']['title'] = $Idea->title;
								$row['meta']['url'] = $Idea->url;
								$row['glyphicon'] = "thumbs-up";
								$row['event']['object'] = "idea:";
								$row['event']['article'] = "an";
							
							break;
						}
						
					}
					
					/**
					 * Compact it all together and create a succinct message
					 */
					
					foreach ($row['event'] as $k => $v) {
						if (empty($v)) {
							unset($row['event'][$k]);
						}
					}
					
					$row['action'] = implode(" ", $row['event']);
					
					
					if ($row['timestamp'] > $moments_ago) {
						$row['timestamp_nice'] = "moments ago"; 
					} elseif ($row['timestamp'] > $minutes_ago) {
						$diff = $row['timestamp']->diff($minutes_ago);
						$row['timestamp_nice'] = $diff->format("%s minutes ago");
					} elseif ($row['timestamp'] > $relative_cutoff) {
						$diff = $row['timestamp']->diff($relative_cutoff);
						$row['timestamp_nice'] = $diff->format("About %s hours ago");
					} else {
						$row['timestamp_nice'] = $row['timestamp']->format("d/m/Y H:i"); 
					}
					
					$row['timestamp_nice'] = relative_date($row['timestamp']->getTimestamp());
					
					/**
					 * Determine the icon
					 */
					
					if (!isset($row['glyphicon'])) {
						$row['glyphicon'] = "";
					}
					
					if (isset($row['event']['object'])) {
						switch (strtolower($row['event']['object'])) {
							case "photo" :
								$row['glyphicon'] = "picture";
								break;
								
							case "cover photo" :
								$row['glyphicon'] = "picture";
								break;
						}
					}
					
					if (!isset($row['event']['action'])) {
						printArray($row);
					}
					
					switch (strtolower($row['event']['action'])) {
						case "edited" : 
							$row['glyphicon'] = "pencil";
							break;
						
						case "modified" : 
							$row['glyphicon'] = "pencil";
							break;
						
						case "added" : 
							$row['glyphicon'] = "plus";
							break;
						
						case "created" : 
							$row['glyphicon'] = "plus";
							break;
							
						case "tagged" : 
							$row['glyphicon'] = "tag";
							break;
							
						case "linked" : 
							$row['glyphicon'] = "link";
							break;
							
						case "re-ordered" : 
							$row['glyphicon'] = "random";
							break;
							
						case "removed" : 
							$row['glyphicon'] = "minus";
							break;
							
						case "commented" : 
							$row['glyphicon'] = "comment";
							break;
						
					}
					
					if (isset($row['event']['object'])) {
						switch (strtolower($row['event']['object'])) {
							case "sighting" :
								$row['glyphicon'] = "eye-open";
								break;
						}
					}
					
					$timeline['timeline'][$key] = $row;
				}
			}
			
			return $timeline;
		}
		
		/**
		 * Get all groups this user is a member of
		 * @since Version 3.7
		 * @return array
		 */
		
		public function getGroups() {
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				return false;
			}
			
			$mckey = "railpage:usergroups.user_id=" . $this->id; 
			
			if ($this->groups = $this->getCache($mckey)) {
				return $this->groups; 
			} else {
				$query = "SELECT group_id FROM nuke_bbuser_group WHERE user_id = ? AND user_pending = 0";
				
				if ($this->db instanceof \sql_db) {
					if ($stmt = $this->db->prepare($query)) {
						$stmt->bind_param("i", $this->id);
						
						if ($stmt->execute()) {
							$stmt->bind_result($group_id); 
							
							$return = array(); 
						
							while ($stmt->fetch()) {
								if (!in_array($group_id, $this->groups)) {
									$this->groups[] = $group_id;
								}
							}
						} else {
							throw new \Exception($this->db->error."\n\n".$query);
							return false;
						}
					} else {
						throw new \Exception($this->db->error."\n\n".$query);
						return false;
					}
				} else {
					if ($result = $this->db->fetchAll($query, $this->id)) {
						foreach ($result as $row) {
							if (!is_array($this->groups) || (is_array($this->groups) && !in_array($row['group_id'], $this->groups))) {
								$this->groups[] = $row['group_id'];
							}
						}
					}
				}
				
				if (!empty($this->groups)) {
					$this->setCache($mckey, $this->groups, strtotime("+2 hours"));
				}
			
				return $this->groups;
			}
		}
		
		/**
		 * Get a list of watched threads
		 * @since Version 3.8
		 * @return array
		 * @param int $page
		 * @param int $limit
		 */
		
		public function getWatchedThreads($page = 1, $limit = false) {
			// Assume Zend_Db
			
			if (!$limit) {
				$limit = $this->items_per_page;
			}
			
			$query = "SELECT SQL_CALC_FOUND_ROWS t.topic_id, t.topic_title, t.topic_poster, t.topic_time, t.topic_views, t.topic_replies, t.topic_first_post_id, t.topic_last_post_id,
						f.forum_id, f.forum_name,
						ufirst.username AS topic_first_post_username, ufirst.user_id AS topic_first_post_user_id,
						ulast.username AS topic_last_post_username, ulast.user_id AS topic_last_post_user_id,
						pfirst.post_time AS topic_first_post_date,
						plast.post_time AS topic_last_post_date
						FROM nuke_bbtopics_watch AS tw
						LEFT JOIN nuke_bbtopics AS t ON t.topic_id = tw.topic_id
						LEFT JOIN nuke_bbforums AS f ON t.forum_id = f.forum_id
						LEFT JOIN nuke_bbposts AS pfirst ON pfirst.post_id = t.topic_last_post_id
						LEFT JOIN nuke_bbposts AS plast ON plast.post_id = t.topic_last_post_id
						LEFT JOIN nuke_users AS ufirst ON ufirst.user_id = t.topic_poster
						LEFT JOIN nuke_users AS ulast ON ulast.user_id = plast.poster_id
						WHERE tw.user_id = ? 
						ORDER BY plast.post_time DESC
						LIMIT ?, ?";
			
			if ($result = $this->db->fetchAll($query, array($this->id, ($page - 1) * $limit, $limit))) {
				$return = array(); 
				$return['page'] = $page; 
				$return['items_per_page'] = $limit;
				$return['total'] = $this->db_readonly->fetchOne("SELECT FOUND_ROWS() AS total"); 
				$return['topics'] = array(); 
				
				foreach ($result as $row) {
					$return['topics'][] = $row; 
				}
				
				return $return;
			} else {
				return false;
			}
		}
		
		/**
		 * Unlink a Flickr account
		 * @return boolean
		 */
		
		public function unlinkFlickr() {
			$this->flickr_nsid = NULL;
			$this->flickr_username = NULL;
			$this->flickr_oauth_token = NULL;
			$this->flickr_oauth_secret = NULL;
			
			return $this->commit();
		}
		
		/**
		 * Get this user's ACL role
		 * @since Version 3.8.7
		 * @param int $group_id A forums group ID
		 * @param string $role The default ACL role to return if a group ID is provided
		 * @return string
		 */
		
		public function aclRole($group_id = NULL, $role = "maintainer") {
			if ($this->inGroup(RP_GROUP_ADMINS)) {
				return "administrator";
			}
			
			if ($this->inGroup(RP_GROUP_MODERATORS)) {
				return "moderator";
			}
			
			if (filter_var($group_id, FILTER_VALIDATE_INT) && $this->inGroup($group_id)) {
				return $role;
			}
			
			if (!$this->guest) {
				return "user";
			}
			
			return "guest";
		}
		
		/**
		 * Record a good event for this user
		 * @return \Railpage\Users\User
		 * @param int $amt The amount to increase their reputation by
		 * @since Version 3.8.7
		 */
		
		public function wheat($amt = 1) {
			if (!filter_var($amt, FILTER_VALIDATE_INT)) {
				$amt = 1;
			}
			
			$this->wheat = $this->wheat + $amt;
			$this->commit();
			
			return $this;
		}
		
		/**
		 * Record a negative event for this user. Counts towards their reputation accross the site
		 * @param int $amt The amount to decrease their reputation by
		 * @since Version 3.8.7
		 * @return \Railpage\Users\User
		 */
		
		public function chaff($amt = 1) {
			if (!filter_var($amt, FILTER_VALIDATE_INT)) {
				$amt = 1;
			}
			
			$this->chaff = $this->chaff + $amt;
			$this->commit();
			
			return $this;
		}
		
		/**
		 * Create URLs
		 * @since Version 3.8.7
		 * @return \Railpage\Users\User
		 */
		
		public function createUrls() {
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				return $this;
			}
			
			$PMs = new Module("pm");
			
			$this->url = new Url(sprintf("%s/%d", $this->Module->url, $this->id));
			$this->url->view = $this->url->url;
			$this->url->account = "/account";
			$this->url->sendpm = sprintf("%s/new/to/%d", $PMs->url, $this->id);
			$this->url->newpm = sprintf("%s/new/to/%d", $PMs->url, $this->id);
			
			return $this;
		}
		
		/**
		 * Set the password for this user
		 *
		 * Updated to use PHP 5.5's password_hash(), password_verify() and password_needs_rehash() functions
		 * @since Version 3.8.7
		 * @param string $password
		 * @return \Railpage\Users\User
		 */
		
		public function setPassword($password = false) {
			if (!$password || empty($password)) {
				throw new Exception("Cannot set password - no password was provided");
			}
			
			/**
			 * Check to make sure it's not a shitty password
			 */
			
			if (!$this->safePassword($password)) {
				// MGH - 6/01/2015 commented out, people are dumb.
				//throw new Exception("Your desired password is unsafe. Please choose a different password.");
			}
			
			if (function_exists("password_hash")) {
				$this->password = password_hash($password, PASSWORD_DEFAULT);
				$this->password_bcrypt = false; // Deliberately deprecate the bcrypt password option
				
				if (filter_var($this->id, FILTER_VALIDATE_INT)) {
					$this->commit();
					$this->addNote("Password changed or hash updated using password_hash()");
				}
			} else {
				require_once("includes/bcrypt.class.php");
				
				$BCrypt = new \Bcrypt(RP_BCRYPT_ROUNDS);
				
				$password = trim($password);
				$this->password = md5($password);
				$this->password_bcrypt = $BCrypt->hash($password);
				
				if (filter_var($this->id, FILTER_VALIDATE_INT)) {
					$this->commit();
					$this->addNote("Password changed or hash updated");
				}
			}
		}
		
		/**
		 * Validate a password for this account
		 *
		 * Updated to use PHP 5.5's password_hash(), password_verify() and password_needs_rehash() functions
		 * @since Version 3.8.7
		 * @param string $password
		 * @return boolean
		 */
		
		public function validatePassword($password = false, $username = false) {
			
			/**
			 * Check for a valid password
			 */
			
			if (!$password || empty($password)) {
				throw new Exception("Cannot validate password - no password was provided");
			}
			
			/**
			 * Check for a supplied userame or if this object is populated
			 */
			
			if ((!$username || empty($username)) && (!filter_var($this->id, FILTER_VALIDATE_INT) || $this->id < 1)) {
				throw new Exception("Cannot validate password for user because we don't know which user this is");
			}
			
			/**
			 * Check if a supplied username matches the username in this populated object
			 */
			
			if ($username && !empty($username) && !empty($this->username) && $this->username != $username) {
				throw new Exception("The supplied username does not match the username given for this account. Something dodgy's going on...");
			}
			
			/**
			 * Create a temporary instance of the requested user for logging purposes
			 */
			
			$TmpUser = filter_var($this->id, FILTER_VALIDATE_INT) ? new User($this->id) : new User($username);
			
			/**
			 * Get the stored password for this username
			 */
			
			if ($username && !empty($username) && empty($this->username)) {
				
				$query = "SELECT user_id, user_password, user_password_bcrypt FROM nuke_users WHERE username = ?";
				$row = $this->db->fetchRow($query, $username);
				
				$stored_user_id = $row['user_id'];
				$stored_password = $row['user_password'];
				$stored_password_bcrypt = $row['user_password_bcrypt'];
				
			} elseif (!empty($this->password)) {
				
				$stored_user_id = $this->id;
				$stored_password = $this->password;
				$stored_password_bcrypt = $this->password_bcrypt;
				
			}
			
			/**
			 * Check if the invalid auth timeout is in effect
			 */
			
			if (isset($TmpUser->meta['InvalidAuthTimeout'])) {
				if ($TmpUser->meta['InvalidAuthTimeout'] <= time()) {
					unset($TmpUser->meta['InvalidAuthTimeout']);
					unset($TmpUser->meta['InvalidAuthCounter']);
					$TmpUser->commit();
					$this->refresh();
				} else {
					$TmpUser->addNote("Login attempt while InvalidAuthTimeout is in effect");
					throw new Exception("You've attempted to log in with the wrong password too many times. We've temporarily disabled your account to protect it against hackers. Please try again soon. <a href='/account/resetpassword'>Can't remember your password?</a>");
				}
			}
			
			/**
			 * Verify the password
			 */
			
			if (md5($password) == $stored_password || password_verify($password, $stored_password) || password_verify($password, $stored_password_bcrypt)) {
				$this->load($stored_user_id);
				
				/**
				 * Check if the password needs rehashing
				 */
				
				if (password_needs_rehash($stored_password, PASSWORD_DEFAULT) || password_needs_rehash($stored_password_bcrypt, PASSWORD_DEFAULT)) {
					$this->setPassword($password);
				}
				
				/**
				 * Reset the InvalidAuthCounter
				 */
				
				unset($this->meta['InvalidAuthCounter']);
				unset($this->meta['InvalidAuthTimeout']);
				$this->commit();
				
				return true;
			}
			
			/**
			 * Older password verification code
			 */
			
			/**
			 * Load the BCrypt class
			 */
			
			require_once("includes/bcrypt.class.php");
			
			$BCrypt = new \Bcrypt(RP_BCRYPT_ROUNDS);
			
			/**
			 * Strip excess whitespace from the password
			 */
			
			$password = trim($password);
			
			/**
			 * Try to validate the password
			 */
			
			if ((empty($stored_password_bcrypt) && $stored_password == md5($password)) || ($BCrypt->verify($password, $stored_password_bcrypt))) {
				
				/**
				 * Password validated! If we haven't populated this user object, do it now
				 */
				
				if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
					$this->load($stored_user_id);
				}
				
				/**
				 * No bcrypt password - set it
				 */
				
				if (empty($stored_password_bcrypt)) {
					$this->setPassword($password);
				}
				
				/**
				 * Reset the InvalidAuthCounter
				 */
				
				unset($this->meta['InvalidAuthCounter']);
				unset($this->meta['InvalidAuthTimeout']);
				$this->commit();
				
				return true;
			}
			
			/**
			 * Unsuccessful login attempt - bump up the invalid auth counter
			 */
			
			if (!isset($TmpUser->meta['InvalidAuthCounter'])) {
				$TmpUser->meta['InvalidAuthCounter'] = 0;
			}
			
			$TmpUser->meta['InvalidAuthCounter']++;
			$TmpUser->addNote(sprintf("Invalid login attempt %d", $TmpUser->meta['InvalidAuthCounter']));
			$TmpUser->commit();
			$this->refresh();
			
			if ($TmpUser->meta['InvalidAuthCounter'] == 3) {
				$TmpUser->meta['InvalidAuthTimeout'] = strtotime("+10 minutes");
				$TmpUser->addNote("Too many invalid login attempts - account disabled for ten minutes");
				$TmpUser->commit();
				$this->refresh();
				
				throw new Exception("You've attempted to log in with the wrong password too many times. As a result, we're disabling this account for the next ten minutes. <a href='/account/resetpassword'>Can't remember your password?</a>");
			}
			
			$this->reset();
			
			return false;
		}
		
		/**
		 * Check if this account is active
		 * @since Version 3.8.7
		 * @return boolean
		 */
		
		public function isActive() {
			return (boolean) $this->active;
		}
		
		/**
		 * Refresh user data from the database
		 * @since Version 3.8.7
		 * @return \Railpage\Users\User
		 */
		
		public function refresh() {
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$this->load();
			}
			
			return $this;
		}
		
		/**
		 * Reset this user object, for security purposes
		 * @since Version 3.8.7
		 * @return \Railpage\Users\User
		 */
		
		public function reset() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
			
			$this->guest();
			
			return $this;
		}
		
		/**
		 * Check if the supplied password is considered safe
		 * @since Version 3.8.7
		 * @param string $password
		 * @return boolean
		 */
		
		public function safePassword($password = false) {
			if (!$password) {
				throw new Exception("You gotta supply a password...");
			}
			
			if (empty($password)) {
				throw new Exception("Passwords cannot be empty");
			}
			
			/**
			 * Start validating passwords
			 */
			
			if (strlen($password) < 7) {
				return false;
			}
			
			if (strtolower($password) == strtolower($this->username)) {
				return false;
			}
			
			/**
			 * Bad passwords
			 */
			
			$bad = array(
				"password",
				"pass",
				"012345",
				"0123456",
				"01234567",
				"012345678",
				"0123456789",
				"123456",
				"1234567",
				"12345678",
				"123456789",
				"1234567890",
				"letmein",
				"changeme",
				"qwerty",
				"111111",
				"iloveyou",
				"railpage",
				"password1",
				"azerty",
				"000000",
				"trains",
				"railway"
			);
			
			if (in_array($password, $bad)) {
				return false;
			}
			
			/**
			 * Looks good
			 */
			
			return true;
		}
		
		/**
		 * Validate an email address
		 * @since Version 3.8.7
		 * @param string $email
		 * @return \Railpage\Users\User
		 */
		
		public function validateEmail($email = false) {
			if (!$email || empty($email)) {
				throw new Exception("No email address was supplied.");
			}
			
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				throw new Exception(sprintf("%s is not a valid email address", $email));
			}
			
			$query = "SELECT user_id FROM nuke_users WHERE user_email = ? AND user_id != ?";
			
			$result = $this->db->fetchAll($query, array($email, $this->id));
			
			if (count($result)) {
				throw new Exception(sprintf("The requested email address %s is already in use by a different user.", $email));
			}
			
			return $this;
		}
		
		/**
		 * Get IP address this user has posted/logged in from
		 * @since Version 3.9
		 * @return array
		 */
		
		public function getIPs() {
			$ips = array(); 
			
			/**
			 * Get posts
			 */
			
			$query = "SELECT DISTINCT poster_ip FROM nuke_bbposts WHERE poster_id = ?";
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				$ips[] = decode_ip($row['poster_ip']);
			}
			
			/**
			 * Get logins
			 */
			
			$query = "SELECT DISTINCT login_ip FROM log_logins WHERE user_id = ? AND login_ip NOT IN ('" . implode("','", $ips) . "')";
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				$ips[] = $row['login_ip'];
			}
			
			natsort($ips);
			$ips = array_values($ips);
			
			return $ips;
		}
	}
?>
