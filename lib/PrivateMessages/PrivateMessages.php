<?php
	/** 
	 * Private Messages class
	 * @package Railpage
	 * @since Version 3.3
	 * @version 3.3
	 * @author Michael Greenhill
	 */
	 
	namespace Railpage\PrivateMessages;
	use Railpage\AppCore;
	use Railpage\Module;
	use DateTime;
	use Exception;
	use Railpage\Users\User;
	 
	define("PM_INBOX", "inbox"); 
	define("PM_OUTBOX", "outbox"); 
	define("PM_SENTBOX", "sentbox"); 
	define("PM_SAVEBOX", "savebox"); 
	
	if (!defined("PRIVMSGS_READ_MAIL")) {
		// Assume that the rest are undefined as well
		define('PRIVMSGS_READ_MAIL', 0);
		define('PRIVMSGS_NEW_MAIL', 1);
		define('PRIVMSGS_SENT_MAIL', 2);
		define('PRIVMSGS_SAVED_IN_MAIL', 3);
		define('PRIVMSGS_SAVED_OUT_MAIL', 4);
		define('PRIVMSGS_UNREAD_MAIL', 5);
	}
	
	/**
	 * Private Messages - base class
	 * @since Version 3.3
	 * @version 3.3
	 * @author Michael Greenhill
	 */
	
	class PrivateMessages extends AppCore {
		
		/**
		 * DB handle
		 * @var object $db
		 * @since Version 3.3
		 */
		
		public $db;
		
		/**
		 * Constructor
		 * @since Version 3.3
		 * @param object $db
		 */
		
		public function __construct($db = false, $user = false) {
			parent::__construct(); 
			
			$this->Module = new Module("privatemessages");
			
			foreach (func_get_args() as $arg) {
				if ($arg instanceof User) {
					$this->setUser($arg);
				}
			}
		}
		
		/**
		 * Get the number of unread PMs for this user
		 * @since Version 3.3
		 * @version 3.3
		 * @return array|boolean
		 */
		
		public function getUnread() {
			if (!$this->User->id) {
				throw new Exception("Cannot fetch unread PMs - not a registered user");
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT DISTINCT p.privmsgs_id, p.privmsgs_subject, p.privmsgs_from_userid AS from_user_id, p.privmsgs_date, u.username AS from_username, pt.privmsgs_text
							FROM nuke_bbprivmsgs AS p
							LEFT JOIN nuke_bbprivmsgs_text AS pt ON p.privmsgs_id = pt.privmsgs_text_id
							LEFT JOIN nuke_users AS u ON u.user_id = p.privmsgs_from_userid
							WHERE (p.privmsgs_type = ".PRIVMSGS_UNREAD_MAIL." OR p.privmsgs_type = ".PRIVMSGS_NEW_MAIL.")
							AND p.privmsgs_to_userid = ".$this->User->id." 
							ORDER BY p.privmsgs_date DESC";
				
				if ($rs = $this->db->query($query)) {
					$return = array(); 
					while ($row = $rs->fetch_assoc()) {
						$return[$row['privmsgs_id']] = $row; 
					}
					
					return $return;
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$query = "SELECT DISTINCT p.privmsgs_id, p.privmsgs_subject, p.privmsgs_from_userid AS from_user_id, p.privmsgs_date, u.username AS from_username, pt.privmsgs_text
							FROM nuke_bbprivmsgs AS p
							LEFT JOIN nuke_bbprivmsgs_text AS pt ON p.privmsgs_id = pt.privmsgs_text_id
							LEFT JOIN nuke_users AS u ON u.user_id = p.privmsgs_from_userid
							WHERE (p.privmsgs_type = ? OR p.privmsgs_type = ?)
							AND p.privmsgs_to_userid = ? 
							ORDER BY p.privmsgs_date DESC";
				
				$return = array();
				
				foreach ($this->db->fetchAll($query, array(PRIVMSGS_UNREAD_MAIL, PRIVMSGS_NEW_MAIL, $this->User->id)) as $row) {
					$return[$row['privmsgs_id']] = $row; 
				}
				
				return $return;
			}
		}
		
		/**
		 * Delete messages with a given object ID
		 * @since Version 3.2
		 * @param string $object_id
		 */
		
		public function deleteObjects($object_id = false) {
			if (!$object_id) {
				//throw new Exception("Cannot delete objects - no object ID given"); 
				return false;
			} 
			
			if ($this->db instanceof \sql_db) {
				// Get message IDs
				$query = "SELECT privmsgs_id FROM nuke_bbprivmsgs WHERE object_id = '".$this->db->real_escape_string($object_id)."'";
				
				if ($rs = $this->db->query($query)) {
					$ids = array(); 
					while ($row = $rs->fetch_assoc()) {
						$ids[] = $row['privmsgs_id']; 
					}
					
					if (count($ids)) {
						if ($this->db->query("DELETE FROM nuke_bbprivmsgs WHERE privmsgs_id IN ('".implode("','", $ids)."')")) {
							if ($this->db->query("DELETE FROM nuke_bbprivmsgs_text WHERE privmsgs_text_id IN ('".implode("','", $ids)."')")) {
								return true;
							} else {
								throw new Exception($this->db->error."\n".$query); 
								return false;
							}
						} else {
							throw new Exception($this->db->error."\n".$query); 
							return false;
						}
					} else {
						return false;
					}
				} else {
					throw new Exception($this->db->error."\n".$query); 
					return false;
				}
			} else {
				$query = "SELECT privmsgs_id FROM nuke_bbprivmsgs WHERE object_id = ?";
				
				$ids = array();
				
				foreach ($this->db->fetchAll($query, $object_id) as $row) {
					$ids[] = $row['privmsgs_id']; 
				}
				
				if (count($ids)) {
					$this->db->delete("nuke_bbprivmsgs", "privmsgs_id IN ('" . implode("','", $ids) . "')"); 
					$this->db->delete("nuke_bbprivmsgs_text", "privmsgs_text_id IN ('" . implode("','", $ids) . "')"); 
					return true;
				}
			}
		}
		
		/**
		 * Get IDs of deleted messages for this user
		 * @since Version 3.4
		 * @param int $user_id
		 * @return array
		 */
		
		public function getDeleted($user_id = false) {
			if (!$user_id) {
				throw new Exception("Cannot fetch deleted message IDs - no user ID given"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT privmsgs_id FROM privmsgs_hidelist WHERE user_id = '".$this->db->real_escape_string($user_id)."'"; 
				
				if ($rs = $this->db->query($query)) {
					$return = array(); 
					
					while ($row = $rs->fetch_assoc()) {
						if (!in_array($row['privmsgs_id'], $return)) {
							$return[] = $row['privmsgs_id']; 
						}
					}
					
					return $return;
				} else {
					throw new Exception($this->db->error."\n\n".$query); 
				}
			} else {
				$query = "SELECT privmsgs_id FROM privmsgs_hidelist WHERE user_id = ?";
				
				$return = array();
				foreach ($this->db->fetchAll($query, $user_id) as $row) {
					if (!in_array($row['privmsgs_id'], $return)) {
						$return[] = $row['privmsgs_id']; 
					}
				}
				
				return $return;
			}
		}
		
		/**
		 * Get all messages for administrative purposes
		 * @since Version 3.8.7
		 * @param int $items_per_page
		 * @param int $page
		 * @return array
		 */
		
		public function getAllMessages($items_per_page = 25, $page = 1) {
			if (!filter_var($items_per_page, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot fetch messages - invalid items_per_page parameter provided");
			}
			
			if (!filter_var($page, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot fetch messages - invalid page number parameter provided");
			}
			
			$page = ($page - 1) * $items_per_page;
			
			$query = "SELECT SQL_CALC_FOUND_ROWS privmsgs_id AS id, privmsgs_type AS type, privmsgs_date AS date FROM nuke_bbprivmsgs ORDER BY privmsgs_date DESC LIMIT ?, ?";
			$return = array();
			
			if ($result = $this->db->fetchAll($query, array($page, $items_per_page))) {
				$return['total'] = $this->db_readonly->fetchOne("SELECT FOUND_ROWS() AS total"); 
				
				foreach ($result as $row) {
					$Date = new DateTime;
					$Date->setTimestamp($row['date']);
					$row['date'] = $Date;
					
					$return['messages'][] = $row;
				}
			}
			
			return $return;
		}
	}
	