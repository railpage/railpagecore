<?php
	/**
	 * Messages class
	 * @author Michael Greenhill
	 * @package Railpage
	 * @copyright Copyright (c) 2011, Michael Greenhill
	 * @since Version 3.9
	 */
	 
	namespace Railpage\SiteMessages;
	
	use Railpage\AppCore;
	use Railpage\Users\User;
	use Exception;
	use DateTime;
	
	class SiteMessages extends AppCore {
		
		/**
		 * Yield the list of available messages
		 * @since Version 3.9
		 * @yield \Railpage\SiteMessage\Message
		 * @return \Railpage\SiteMessage\Message
		 */
		
		public function yieldMessages() {
			$query = "SELECT message_id FROM messages";
			
			foreach ($this->db->fetchAll($query) as $row) {
				yield new SiteMessage($row['message_id']);
			}
		}
		
		/**
		 * Get the latest message from the database
		 * @return \Railpage\SiteMessages\SiteMessage
		 * @since Version 3.9
		 */
		
		public function getLatest() {
			$query = "SELECT message_id FROM messages WHERE message_active = 1 AND (date_start = '0000-00-00' OR (date_start <= ? AND date_end >= ?)) %s AND target_user = 0";
			
			$where = array(
				date("Y-m-d"),
				date("Y-m-d")
			);
			
			if ($this->User instanceof User) {
				$user_list = " AND message_id NOT IN (SELECT message_id FROM messages_viewed WHERE user_id = ?)";
				$where[] = $this->User->id;
				
				// Fetch any targeted messages
				$query .= " UNION SELECT message_id FROM messages WHERE target_user = ? AND message_active = 1 AND message_id NOT IN (SELECT message_id FROM messages_viewed WHERE user_id = ?)"; 
				$where[] = $this->User->id;
				$where[] = $this->User->id;
			} else {
				$user_list = "";
			}
			
			$query .= " ORDER BY message_id DESC LIMIT 1";
			
			$query = sprintf($query, $user_list);
			
			$id = $this->db->fetchOne($query, $where);
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				return new SiteMessage($id);
			} else {
				return false;
			}
		}
		
		/**
		 * Find a site message matching a given object namespace and object ID
		 * @since Version 3.9.1
		 * @param object $Object
		 * @return \Railpgae\SiteMessages\SiteMessage | boolean
		 */
		
		public function getMessageForObject($Object = false) {
			if (!is_object($Object)) {
				throw new Exception("You did not provide an object");
			} 
			
			if (!isset($Object->Module) || !isset($Object->Module->namespace)) {
				throw new Exception("Object does not have a namespace to lookup");
			}
			
			if (!isset($Object->id) || !filter_var($Object->id, FILTER_VALIDATE_INT) || $Object->id === 0) {
				throw new Exception("Object does not have a valid ID to lookup");
			}
			
			$params = array(
				$Object->Module->namespace,
				$Object->id
			);
			
			$query = "SELECT message_id FROM messages WHERE object_ns = ? AND object_id = ? LIMIT 1";
			
			if ($id = $this->db->fetchOne($query, $params)) {
				return new SiteMessage($id); 
			} else {
				return false;
			}
		}
	}
	