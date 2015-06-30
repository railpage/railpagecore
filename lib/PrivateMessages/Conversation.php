<?php
	/**
	 * PM conversation
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\PrivateMessages;
	
	use Exception;
	use InvalidArgumentException;
	use DateTime;
	use Railpage\Users\User;
	use Railpage\Users\Factory as UserFactory;
	use Railpage\Users\Url;

	/**
	 * Load a PM conversation
	 * @since Version 3.3
	 * @version 3.3
	 * @author Michael Greenhill
	 */
	
	class Conversation extends PrivateMessages {
		
		/**
		 * Conversation subject
		 * @since Version 3.3
		 * @var string $subject
		 */
		
		public $subject;
		
		/**
		 * Array of users
		 * @since Version 3.3
		 * @var array $users
		 */
		
		public $users;
		
		/** 
		 * Constructor
		 * @since Version 3.3
		 * @version 3.3
		 * @param object $db
		 * @param int $id
		 */
		
		public function __construct() {
			parent::__construct();
			
			foreach (func_get_args() as $arg) {
				if (filter_var($arg, FILTER_VALIDATE_INT)) {
					$this->load($arg); 
				}
				
				if ($arg instanceof User) {
					$this->setUser($User);
				}
			}
		} 
		
		/** 
		 * Load the conversation
		 * @since Version 3.7.5
		 * @return boolean
		 * @param int $id
		 */
		
		public function load($id = NULL) {
			if (!filter_var($id, FILTER_VALIDATE_INT)) {
				throw new InvalidArgumentException("No private message ID was given - cannot load PM conversation"); 
			}
			
			$this->url = sprintf("/messages/conversation/%d", $id);
			
			$query = "SELECT pm.privmsgs_subject, pm.privmsgs_from_userid, pm.privmsgs_to_userid, ufrom.username AS username_from, uto.username AS username_to
						FROM nuke_bbprivmsgs AS pm 
						LEFT JOIN nuke_users AS ufrom ON pm.privmsgs_from_userid = ufrom.user_id
						LEFT JOIN nuke_users AS uto ON pm.privmsgs_to_userid = uto.user_id
						WHERE pm.privmsgs_id = ?";
			
			$row = $this->db->fetchRow($query, $id); 
			$this->subject = str_replace("Re: ", "", $row['privmsgs_subject']); 
			$this->users[$row['privmsgs_to_userid']] = $row['username_to']; 
			$this->users[$row['privmsgs_from_userid']] = $row['username_from']; 
			
			return true;
		}
		
		/**
		 * Get message IDs in this thread
		 * @since Version 3.3
		 * @return array
		 */
		
		public function fetch() {
			$user_ids = array_keys($this->users);
			
			// If only one user account found, we must've sent ourselves a PM...
			if (count($user_ids) === 1) {
				$user_ids[1] = $user_ids[0]; 
			}
			
			// Do some error checking first...
			if (empty($this->subject) || empty($this->users) || count($user_ids) != 2) {
				throw new Exception("Cannot fetch message IDs"); 
			}
			
			// Get deleted message IDs to exclude them from the search
			$deleted = $this->getDeleted($this->User->id); 
			$exclude_sql = count($deleted) > 0 ? " AND privmsgs_id NOT IN ('".implode("', '", $deleted)."') " : "";
			
			$query = "SELECT privmsgs_id 
						FROM nuke_bbprivmsgs 
						WHERE privmsgs_subject LIKE ?
						AND ((privmsgs_from_userid = ? AND privmsgs_to_userid = ?)
						OR (privmsgs_to_userid = ? AND privmsgs_from_userid = ?))
						AND (privmsgs_type = ? OR privmsgs_type = ? OR privmsgs_type = ?)
						".$exclude_sql."
						ORDER BY privmsgs_date DESC";
			
			$params = array(
				"%" . $this->subject . "%",
				$user_ids[0],
				$user_ids[1],
				$user_ids[0],
				$user_ids[1],
				PRIVMSGS_READ_MAIL,
				PRIVMSGS_UNREAD_MAIL,
				PRIVMSGS_NEW_MAIL
			);
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query, $params) as $row) {
				$return[] = $row['privmsgs_id']; 
			}
			
			return $return;
		}
	}
	