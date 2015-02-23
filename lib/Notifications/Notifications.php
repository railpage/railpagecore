<?php
	/**
	 * System notifications, eg email, gitter or something else
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Notifications;
	
	use Railpage\Users\User;
	use Railpage\AppCore;
	use Exception;
	use DateTime;
	
	/**
	 * Notifications
	 * @since Version 3.9.1
	 */
	
	class Notifications extends AppCore {
		
		/**
		 * Transport: Email
		 * @since Version 3.9.1
		 * @const int TRANSPORT_EMAIL
		 */
		
		const TRANSPORT_EMAIL = 1;
		
		/**
		 * Status: Queued
		 * @since Version 3.9.1
		 * @const int STATUS_QUEUED
		 */
		
		const STATUS_QUEUED = 0; 
		
		/**
		 * Status: Sent
		 * @since Version 3.9.1
		 * @const int STATUS_SENT
		 */
		 
		const STATUS_SENT = 1;
		
		/**
		 * Status: Error
		 * @since Version 3.9.1
		 * @const int STATUS_ERROR
		 */
		
		const STATUS_ERROR = 2;
		
		/**
		 * Fetch notifications in a specified state
		 * @since Version 3.9.1
		 * @param int $status
		 * @return array
		 */
		
		public function getNotificationsWithStatus($status = self::STATUS_QUEUED) {
			$query = "SELECT * FROM notifications WHERE status = ?";
			
			return $this->db->fetchAll($query, $status);
		}
		
		/**
		 * Fetch notifications by transport type
		 * @since Version 3.9.1
		 * @param int $transport
		 * @return array
		 */
		
		public function getNotificationsByTransport($transport = self::TRANSPORT_EMAIL) {
			$query = "SELECT * FROM notifications WHERE transport = ?";
			
			return $this->db->fetchAll($query, $transport);
		}
		
		/**
		 * Fetch notifications for a specified user
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getNotificationsForUser() {
			if (!isset($this->Recipient) || !$this->Recipient instanceof User) {
				throw new Exception("\$this->Recipient is not an instance of \\Railpage\\Users\\User");
			}
			
			$query = "SELECT * FROM notifications WHERE recipient = ?";
			
			return $this->db->fetchAll($query, $this->Recipient->id);
		}
		
		/**
		 * Get all notifications
		 * @since Version 3.9.1
		 * @param int $page
		 * @param int $items_per_page
		 * @return array
		 */
		
		public function getAllNotifications($page = 1, $items_per_page = 25) {
			$query = "SELECT SQL_CALC_FOUND_ROWS * FROM notifications ORDER BY date_queued DESC LIMIT ?, ?";
			
			$where = array(
				($page - 1) * $items_per_page, 
				$items_per_page
			);
			
			$result = $this->db->fetchAll($query, $where); 
			
			$return = array(
				"page" => $page, 
				"limit" => $items_per_page,
				"total" => $this->db->fetchOne("SELECT FOUND_ROWS() AS total"),
				"notifications" => $result
			);
			
			return $return;
		}
	}
		