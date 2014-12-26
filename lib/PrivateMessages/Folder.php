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
		 * @param object $User
		 * @param int $page
		 * @param int $items_per_page
		 */
		
		public function getContents($User = false, $page = 1, $items_per_page = 25) {
			if (empty($this->folder)) {
				throw new \Exception("Cannot get folder contents - no folder specified"); 
			} 
			
			if (!$User || !is_object($User)) {
				throw new \Exception("Cannot get folder contents - User object not provided"); 
			}
			
			if (!$User->id) {
				throw new \Exception("No user ID available"); 
			}
			
			if (!$User->enable_privmsg) {
				throw new \Exception("Private messages not available to this user"); 
			}
			
			// Store the user object
			$this->user = $User;
			
			// Fetch message IDs that have been "deleted" by this user
			$deleted = $this->getDeleted($User->id); 
			
			if (count($deleted)) {
				$exclude_sql = " AND privmsgs_id NOT IN ('".implode("', '", $deleted)."') ";
			} else {
				$exclude_sql = "";
			}
			
			if ($this->folder == PM_INBOX) {
				$pm_folder_sql = "pm.privmsgs_to_userid = ".$this->user->id." AND (pm.privmsgs_type = ".PRIVMSGS_READ_MAIL." OR pm.privmsgs_type = ".PRIVMSGS_NEW_MAIL." OR pm.privmsgs_type = ".PRIVMSGS_UNREAD_MAIL." )";
			} elseif ($this->folder == PM_OUTBOX) {
				$pm_folder_sql = "pm.privmsgs_from_userid = ".$this->user->id." AND (pm.privmsgs_type = ".PRIVMSGS_NEW_MAIL." OR pm.privmsgs_type = ".PRIVMSGS_UNREAD_MAIL.")"; 
			} elseif ($this->folder == PM_SENTBOX) {
				$pm_folder_sql = "pm.privmsgs_from_userid = ".$this->user->id." AND (pm.privmsgs_type = ".PRIVMSGS_READ_MAIL." OR pm.privmsgs_type = ".PRIVMSGS_SENT_MAIL.")"; 
			} elseif ($this->folder == PM_SAVEBOX) {
				$pm_folder_sql = "((pm.privmsgs_to_userid = ".$this->user->id." AND pm.privmsgs_type = ".PRIVMSGS_SAVED_IN_MAIL.") OR (pm.privmsgs_from_userid = ".$this->user->id." AND pm.privmsgs_type = ".PRIVMSGS_SAVED_OUT_MAIL."))";
			}
			
			// Which "page" is this?
			if ($page == 1) {
				$start = 0; 
			} else {
				$start = $page * $items_per_page; 
			}
			
			// Done checking - get the PMs - sort by date ASC because the uasort() function will fix them up properly
			$query = "SELECT pm.*, pmt.*, ufrom.username AS username_from, ufrom.user_id AS user_id_from, ufrom.user_avatar AS user_avatar_from, uto.username AS username_to, uto.user_id AS user_id_from, uto.user_avatar AS user_avatar_to
						FROM nuke_bbprivmsgs AS pm
						LEFT JOIN nuke_bbprivmsgs_text AS pmt ON pm.privmsgs_id = pmt.privmsgs_text_id
						LEFT JOIN nuke_users AS ufrom ON ufrom.user_id = privmsgs_from_userid
						LEFT JOIN nuke_users AS uto ON uto.user_id = privmsgs_to_userid
						WHERE ".$pm_folder_sql."
						".$exclude_sql."
						ORDER BY pm.privmsgs_date ASC";
						#LIMIT ".$start.", ".$this->db->real_escape_string($items_per_page);
			
			#echo $query;
			
			if ($this->db instanceof \sql_db) {
				if ($rs = $this->db->query($query)) {
					#$total = $this->db->query("SELECT FOUND_ROWS() AS total"); 
					#$total = $total->fetch_assoc(); 
					
					$return = array(); 
					$return['stat'] = "ok";
					#$return['total'] = $total['total']; 
					$return['page'] = $page; 
					$return['perpage'] = $items_per_page; 
					$return['messages'] = array(); 
					
					while ($row = $rs->fetch_assoc()) {
						// Fix up the sodding non-UTF8 characters
						$row['privmsgs_text'] = convert_to_utf8($row['privmsgs_text']);
						$row['privmsgs_subject'] = str_replace("Re: ", "", $row['privmsgs_subject']);
						
						if ($row['privmsgs_from_userid'] == $this->user->id) {
							$pm_from = $row['privmsgs_to_userid'];
						} else {
							$pm_from = $row['privmsgs_from_userid'];
						}
						
						$id = md5($row['privmsgs_subject'].$pm_from);
						
						if (function_exists("format_avatar")) {
							$row['user_avatar_from'] = format_avatar($row['user_avatar_from'], 40, 40); 
							$row['user_avatar_to'] = format_avatar($row['user_avatar_to'], 40, 40); 
						}
						
						$return['messages'][$id] = $row;
					}
					
					// Sort by loco number
					uasort($return['messages'], function($a, $b) {
						return strnatcmp($b['privmsgs_date'], $a['privmsgs_date']); 
					});
				} else {
					throw new \Exception($this->db->error); 
					$return['stat'] = "error";
					$return['error'] = $this->db->error; 
				}
				
				$return['total']	= count($return['messages']);
				$return['messages'] = array_slice($return['messages'], $start, $items_per_page);
				
				return $return;
			} else {
				$return = array(); 
				$return['stat'] = "ok";
				$return['page'] = $page; 
				$return['perpage'] = $items_per_page; 
				$return['messages'] = array(); 
				
				foreach ($this->db->fetchAll($query) as $row) {
					$row['privmsgs_text'] = convert_to_utf8($row['privmsgs_text']);
					$row['privmsgs_subject'] = str_replace("Re: ", "", $row['privmsgs_subject']);
					
					if ($row['privmsgs_from_userid'] == $this->user->id) {
						$pm_from = $row['privmsgs_to_userid'];
					} else {
						$pm_from = $row['privmsgs_from_userid'];
					}
					
					$id = md5($row['privmsgs_subject'].$pm_from);
					
					if (function_exists("format_avatar")) {
						$row['user_avatar_from'] = @format_avatar($row['user_avatar_from'], 40, 40); 
						$row['user_avatar_to'] = @format_avatar($row['user_avatar_to'], 40, 40); 
					}
					
					$return['messages'][$id] = $row;				
				}
					
				// Sort by loco number
				uasort($return['messages'], function($a, $b) {
					return strnatcmp($b['privmsgs_date'], $a['privmsgs_date']); 
				});
				
				$return['total']	= count($return['messages']);
				$return['messages'] = array_slice($return['messages'], $start, $items_per_page);
				
				return $return;
			}
		}
	}
?>