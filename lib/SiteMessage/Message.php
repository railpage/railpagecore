<?php
	/**
	 * Messages class
	 * @author Michael Greenhill
	 * @package Railpage
	 * @copyright Copyright (c) 2011, Michael Greenhill
	 * @since Version 3.0
	 */
	 
	namespace Railpage\SiteMessage;
	
	use Exception;
	use DateTime;


	/** 
	 * General message object - display, edit, etc.
	 * @version 3.0 
	 * @since Version 3.0
	 */
	
	class Message extends SiteMessage {
		
		/**
		 *
		 * Display a message
		 * @since Version 3.0
		 * @version 3.0
		 * @param id $id
		 * @return mixed
		 *
		 */
		
		public function get($id = false) {
			if (!$id) {
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT * FROM messages WHERE message_id = '".$this->db->real_escape_string($id)."' AND message_active = 1";
				
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows == 1) {
						return $rs->fetch_assoc(); 
					} else {
						return false;
					}
				} else {
					trigger_error("Messages: Unable to retrieve message ID ".$id);
					trigger_error($this->db->error); 
					trigger_error($query); 
					return false;
				}	
			} else {
				$query = "SELECT * FROM messages WHERE message_id = ? AND message_active = 1";
				
				return $this->db->fetchRow($query, $id); 
			}
		}
		
		
		/**
		 *
		 * Hide a message
		 * @since Version 3.0
		 * @version 3.0
		 * @param int $message_id
		 * @param int $user_id
		 * @return mixed
		 *
		 */
		 
		public function dismiss($message_id = false, $user_id = false) {
			if (!$message_id || !$user_id) {
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "INSERT INTO messages_viewed (message_id, user_id) VALUES (".$this->db->real_escape_string($message_id).", ".$this->db->real_escape_string($user_id).")";
				
				if ($rs = $this->db->query($query)) {
					return true;
				} else {
					trigger_error("Messages: could not dimiss message id ".$message_id." for user id ".$user_id); 
					trigger_error($this->db->error); 
					trigger_error($query); 
					return false;
				}
			} else {
				$data = array(
					"message_id" => $message_id,
					"user_id" => $user_id
				);
				
				return $this->db->insert("messages_viewed", $data); 
			}
		}
		
		
		/** 
		 * Disable a message
		 * @since Version 3.0.1
		 * @version 3.0
		 * @return mixed
		 */
		
		public function deactivate() {
			if (!$this->id) {
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "UPDATE messages SET message_active = 0 WHERE message_id = '".$this->db->real_escape_string($this->id)."'"; 
				
				if ($rs = $this->db->query($query)) {
					return true;
				} else {
					trigger_error("Messages: Could not deactivate message id ".$this->id); 
					trigger_error($this->db->error); 
					trigger_error($query); 
					return false;
				}
			} else {
				$data = array(
					"message_active" => "0"
				);
				
				$where = array(
					"message_id = ?" => $this->id
				);
				
				return $this->db->update("messages", $data, $where);
			}
		}
	}
	