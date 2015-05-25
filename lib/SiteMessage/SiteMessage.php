<?php
	/**
	 * Messages class
	 * @author Michael Greenhill
	 * @package Railpage
	 * @copyright Copyright (c) 2011, Michael Greenhill
	 * @since Version 3.0
	 */
	 
	namespace Railpage\SiteMessage;
	
	use Railpage\AppCore;
	use Exception;
	use DateTime;
	
	class SiteMessage extends AppCore {
		
		/** 
		 * Message ID
		 * @since Version 3.0
		 * @version 3.0
		 * @var int
		 */
		 
		public $id;
		
		/**
		 *
		 * Constructor
		 * @param object $db 
		 * @param int $db
		 * @since Version 3.0
		 * @version 3.0
		 *
		 */
		 
		public function __construct() {
			parent::__construct();
			
			foreach (func_get_args() as $arg) {
				if (filter_var($arg, FILTER_VALIDATE_INT)) {
					$this->id = $arg;
				}
			}
		}
		
		
		/**
		 *
		 * Display the latest message
		 * @since Version 3.0
		 * @param int $user_id
		 * @param boolean $fullArray
		 * @return mixed
		 *
		 */
		 
		public function newest($user_id = false, $fullArray = false) {
			if ($this->db instanceof \sql_db) {
				if ($user_id) {
					// Logged in, have they dismissed this message?
					$query = "SELECT * FROM messages WHERE message_active = 1 AND message_id NOT IN (SELECT message_id FROM messages_viewed WHERE user_id = ".$this->db->real_escape_string($user_id).") ORDER BY message_id DESC LIMIT 1";
				} else {
					// Guest, show it
					$query = "SELECT * FROM messages WHERE message_active = 1 ORDER BY message_id DESC LIMIT 1";
				}
				
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows == 1) {
						$row = $rs->fetch_assoc(); 
						
						if ($fullArray) {
							return $row; 
						} else {
							return $row['message_text']; 
						}
					} else {
						return false;
					}
				} else {
					trigger_error("Messages: Unable to retrieve latest message");
					trigger_error($this->db->error); 
					trigger_error($query); 
					return false;
				}
			} else {
				if ($user_id) {
					// Logged in, have they dismissed this message?
					$query = "SELECT * FROM messages WHERE message_active = 1 AND message_id NOT IN (SELECT message_id FROM messages_viewed WHERE user_id = ?) ORDER BY message_id DESC LIMIT 1";
					
					$result = $this->db->fetchRow($query, $user_id);
					return $fullArray ? $result : $result['message_text'];
				} else {
					// Guest, show it
					$query = "SELECT * FROM messages WHERE message_active = 1 ORDER BY message_id DESC LIMIT 1";
					
					$result = $this->db->fetchRow($query);
					return $fullArray ? $result : $result['message_text'];
				}
			}
		}
		
		/**
		 * Yield the list of available messages
		 * @since Versio 3.9
		 * @yield new \Railpage\SiteMessage\Message;
		 */
		
		public function yieldMessages() {
			$query = "SELECT message_id FROM messages";
			
			foreach ($this->db->fetchAll($query) as $row) {
				yield new Message($row['message_id']);
			}
		}
	}
	