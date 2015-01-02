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
		 * @since Versio 3.9
		 * @yield new \Railpage\SiteMessage\Message;
		 */
		
		public function yieldMessages() {
			$query = "SELECT message_id FROM messages";
			
			foreach ($this->db->fetchAll($query) as $row) {
				yield new SiteMessage($row['message_id']);
			}
		}
		
		/**
		 * Get the latest message from the database
		 */
		
		public function getLatest() {
			$query = "SELECT message_id FROM messages WHERE message_active = 1 AND (date_start = '0000-00-00' OR (date_start <= ? AND date_end >= ?)) %s ORDER BY message_id DESC LIMIT 1";
			
			$where = array(
				date("Y-m-d"),
				date("Y-m-d")
			);
			
			if ($this->User instanceof User) {
				$user_list = " AND message_id NOT IN (SELECT message_id FROM messages_viewed WHERE user_id = ?)";
				$where[] = $this->User->id;
			} else {
				$user_list = "";
			}
			
			$query = sprintf($query, $user_list);
			
			#echo $query;die;
			
			$id = $this->db->fetchOne($query, $where);
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				return new SiteMessage($id);
			} else {
				return false;
			}
		}
	}
?>