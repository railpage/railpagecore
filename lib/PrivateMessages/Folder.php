<?php
	/**
	 * Private Messages folder
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\PrivateMessages;
	
	use DateTime;
	use Exception;
	use Railpage\Users\User;
	use Railpage\Url;
	
	/** 
	 * Private Messages - index / outbox / sentbox / savebox
	 * @since Version 3.3
	 * @version 3.3
	 */
	
	class Folder extends PrivateMessages {
		
		/**
		 * Inbox type
		 * @since Version 3.3
		 * @var string $folder
		 */
		
		public $folder; 
		
		/**
		 * Folder name
		 * @since Version 3.8.7
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Constructor
		 * @since Version 3.3
		 * @version 3.3
		 * @param 
		 */
		
		public function __construct($folder = PM_INBOX) {
			parent::__construct();
			
			$this->folder = $folder;
			$this->name = ucwords($folder);
			$this->url = sprintf("/messages/%s", $folder);
		}
		
		/**
		 * List the contents of this folder
		 * @since Version 3.3
		 * @version 3.3
		 * @return array
		 * @param int $page
		 * @param int $items_per_page
		 */
		
		public function getContents($page = 1, $items_per_page = 25) {
			if (empty($this->folder)) {
				throw new Exception("Cannot get folder contents - no folder specified"); 
			} 
			
			if (!$this->User instanceof User) {
				throw new Exception("Cannot get folder contents - User object not provided"); 
			}
			
			if (!$this->User->enable_privmsg) {
				throw new Exception("Private messages not available to this user"); 
			}
	 
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start_z = microtime(true);
			}
			
			/**
			 * Which page of the folder are we viewing?
			 */
			
			$start = $page === 1 ? 0 : $page * $items_per_page; 
			
			/**
			 * Caching
			 */
						
			$mckey = sprintf("railpage:privatemessages;user_id=%d;folder=%s", $this->User->id, $this->folder);
			$cachepms = false; // For the future
			
			if (!$cachepms || !$result = $this->Redis->fetch($mckey)) {
				$query = $this->generateSQLQuery(); 
				$result = $this->db->fetchAll($query);
				
				if ($cachepms) {
					$this->Redis->save($mckey, $result, strtotime("+12 hours"));
				}
			}
			
			if (isset($result) && count($result)) {
				$return = array(); 
				$return['stat'] = "ok";
				$return['page'] = $page; 
				$return['perpage'] = $items_per_page; 
				$return['messages'] = array(); 
				
				foreach ($result as $row) {
					$row['privmsgs_subject'] = str_replace("Re: ", "", $row['privmsgs_subject']);
					
					$pm_from = $row['privmsgs_from_userid'] == $this->User->id ? $row['privmsgs_to_userid'] : $row['privmsgs_from_userid'];
					
					$id = md5($row['privmsgs_subject'] . $pm_from);
					
					$return['messages'][$id] = $row;				
				}
			}
					
			/**
			 * Sort by PM date
			 */
			
			uasort($return['messages'], function($a, $b) {
				return strnatcmp($b['privmsgs_date'], $a['privmsgs_date']); 
			});
			
			$return['total']	= count($return['messages']);
			$return['messages'] = array_slice($return['messages'], $start, $items_per_page);
				
			/**
			 * Process these after the slice otherwise we're fetching avatars and processing text for every single message sent to/from this user
			 */
			
			$return['messages'] = $this->processConversations($return['messages']);
			
			if (RP_DEBUG) {
				$site_debug[] = "Railpage: " . __CLASS__ . "(" . $this->folder . ") instantiated in " . round(microtime(true) - $debug_timer_start_z, 5) . "s";
			}
			
			return $return;
		}
		
		/**
		 * Yield the conversation threads in this folder
		 * @since Version 3.9
		 * @package Railpage
		 * @author Michael Greenhill
		 */
		
		public function yieldConversations($items_per_page, $page) {
			if (!$this->User instanceof User) {
				throw new Exception("Cannot fetch " . __METHOD__ . " beause no user object has been set");
			}
			
			
		}
		
		/**
		 * Generate the SQL query for fetching the contents of this folder
		 * @since Version 3.9.1
		 * @return string
		 */
		
		private function generateSQLQuery() {
			
			/**
			 * Fetch message IDs that have been "deleted" by this user
			 */
			
			$deleted = $this->getDeleted($this->User->id); 
			$exclude_sql = count($deleted) === 0 ? "" : " AND privmsgs_id NOT IN ('".implode("', '", $deleted)."') ";
			
			/**
			 * Different SQL base queries for different folders
			 */
			
			if ($this->folder == PM_INBOX) {
				$pm_folder_sql = "pm.privmsgs_to_userid = ".$this->User->id." AND (pm.privmsgs_type = ".PRIVMSGS_READ_MAIL." OR pm.privmsgs_type = ".PRIVMSGS_NEW_MAIL." OR pm.privmsgs_type = ".PRIVMSGS_UNREAD_MAIL." )";
			} elseif ($this->folder == PM_OUTBOX) {
				$pm_folder_sql = "pm.privmsgs_from_userid = ".$this->User->id." AND (pm.privmsgs_type = ".PRIVMSGS_NEW_MAIL." OR pm.privmsgs_type = ".PRIVMSGS_UNREAD_MAIL.")"; 
			} elseif ($this->folder == PM_SENTBOX) {
				$pm_folder_sql = "pm.privmsgs_from_userid = ".$this->User->id." AND (pm.privmsgs_type = ".PRIVMSGS_READ_MAIL." OR pm.privmsgs_type = ".PRIVMSGS_SENT_MAIL.")"; 
			} elseif ($this->folder == PM_SAVEBOX) {
				$pm_folder_sql = "((pm.privmsgs_to_userid = ".$this->User->id." AND pm.privmsgs_type = ".PRIVMSGS_SAVED_IN_MAIL.") OR (pm.privmsgs_from_userid = ".$this->User->id." AND pm.privmsgs_type = ".PRIVMSGS_SAVED_OUT_MAIL."))";
			}
			
			// Done checking - get the PMs - sort by date ASC because the uasort() function will fix them up properly
			$query = "SELECT pm.*, pmt.*, ufrom.username AS username_from, ufrom.user_id AS user_id_from, ufrom.user_avatar AS user_avatar_from, 
							uto.username AS username_to, uto.user_id AS user_id_from, uto.user_avatar AS user_avatar_to
						FROM nuke_bbprivmsgs AS pm
							INNER JOIN nuke_bbprivmsgs_text AS pmt ON pm.privmsgs_id = pmt.privmsgs_text_id
							INNER JOIN nuke_users AS ufrom ON ufrom.user_id = privmsgs_from_userid
							INNER JOIN nuke_users AS uto ON uto.user_id = privmsgs_to_userid
						WHERE ".$pm_folder_sql."
							".$exclude_sql."";
			
			return $query;
			
		}
		
		/**
		 * Process and format each conversation in this folder, in this page
		 * @since Version 3.9.1
		 * @param array $row
		 * @return array
		 */
		
		private function processConversations($conversations) {
			foreach ($conversations as $id => $row) {
				$row['privmsgs_text'] = function_exists("convert_to_utf8") ? convert_to_utf8($row['privmsgs_text']) : $row['privmsgs_text'];
				$row['user_avatar_from'] = function_exists("format_avatar") ? format_avatar($row['user_avatar_from'], 40, 40) : $row['user_avatar_from']; 
				$row['user_avatar_to'] = function_exists("format_avatar") ? format_avatar($row['user_avatar_to'], 40, 40) : $row['user_avatar_to']; 
				
				$conversations[$id] = $row;
			}
			
			return $conversations;
		}
	}
	