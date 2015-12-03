<?php
	/**
	 * System notification object
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Notifications;
	
	use Railpage\AppCore;
	use Railpage\Users\User;
	use Exception;
	use DateTime;
	
	/**
	 * Notification
	 */
	
	class Notification extends AppCore {
		
		/**
		 * Notification ID
		 * @since Version 3.9.1
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Transport type ID
		 * @since Version 3.9.1
		 * @var int $transport
		 */
		
		public $transport;
		
		/**
		 * Status ID
		 * @since Version 3.9.1
		 * @var int $status
		 */
		
		public $status;
		
		/**
		 * Notification subject
		 * @since Version 3.9.1
		 * @var string $subject
		 */
		
		public $subject;
		
		/**
		 * Notification body
		 * @since Version 3.9.1
		 * @var string $body
		 */
		
		public $body;
		
		/**
		 * Meta data for this notification
		 * @since Version 3.9.1
		 * @var array $meta
		 */
		
		public $meta;
		
		/**
		 * Recipients of this notification
		 * @since Version 3.9.1
		 * @var array $recipients
		 */
		
		public $recipients;
		
		/**
		 * Response from the transport layer. Used for debugging and error logging
		 * @since Version 3.9.1
		 * @var string $response
		 */
		 
		public $response = NULL;
		
		/**
		 * Date that this notification was placed in the dispatch queue
		 * @since Version 3.9.1
		 * @var \DateTime $DateQueued
		 */
		
		public $DateQueued;
		
		/**
		 * Date that this notification was successfully sent
		 * @since Version 3.9.1
		 * @var \DateTime $DateSent
		 */
		
		public $DateSent;
		
		/**
		 * Constructor
		 * @since Version 3.9.1
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			
			parent::__construct(); 
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
				$this->mckey = sprintf("railpage:notification=%d", $this->id);
				$this->load(); 
			}
			
		}
		
		/**
		 * Load this object
		 * @since Version 3.9.1
		 */
		
		public function load() {
			
			$query = "SELECT * FROM notifications WHERE id = ?";
			
			$result = $this->db->fetchRow($query, $this->id);
			
			$this->transport = $result['transport'];
			$this->status = $result['status'];
			$this->subject = $result['subject'];
			$this->body = $result['body'];
			$this->response = json_decode($result['response']);
			$this->DateQueued = new DateTime($result['date_queued']);
			$this->DateSent = $result['date_sent'] != "0000-00-00 00:00:00" ? new DateTime($result['date_sent']) : NULL;
			$this->meta = empty($result['meta']) ? array() : json_decode($result['meta'], true);
			
			if (filter_var($result['author'], FILTER_VALIDATE_INT)) {
				$this->setAuthor(new User($result['author']));
			}
			
			$query = "SELECT * FROM notifications_recipients WHERE notification_id = ?";
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				$this->addRecipient($row['user_id'], $row['name'], $row['destination']);
			}
		}
        
        /**
         * Set the call-to-action URL
         * @since Version 3.10.0
         * @param string $url
         * @return \Railpage\Notifications\Notification
         */
        
        public function setActionUrl($url) {
            
            if (!is_array($this->meta)) {
                $this->meta = []; 
            }
            
            $this->meta['url'] = $url;
            
            return $this;
            
        }
		
		/**
		 * Validate this notification
		 * @since Version 3.9.1
		 * @return boolean
		 * @throws \Exception if no recipient user is set
		 * @throws \Exception if both subject and body are empty
		 */
		
		private function validate() {
			if (!filter_var($this->transport, FILTER_VALIDATE_INT)) {
				$this->transport = Notifications::TRANSPORT_EMAIL;
			}
			
			if (!filter_var($this->status, FILTER_VALIDATE_INT)) {
				$this->status = Notifications::STATUS_QUEUED;
			}
			
			if (!$this->DateQueued instanceof DateTime) {
				$this->DateQueued = new DateTime;
			}
			
			if (empty($this->recipients)) {
				throw new Exception("No recipients have been set (hint: " . __CLASS__ . "::addRecipient()");
			}
			
			if (!$this->Author instanceof User) {
				$this->setAuthor(new User(User::SYSTEM_USER_ID));
			}
			
			if (empty($this->body) && is_null($this->body) && empty($this->subject) && is_null($this->subject)) {
				throw new Exception("No body or subject has been set");
			}
            
            if ($this->status != Notifications::STATUS_QUEUED) {
                $this->DateSent = new DateTime;
            }
			
			return true;
		}
		
		/**
		 * Commit 
		 * @since Version 3.9.1
		 * @return \Railpage\Notifications\Notification
		 */
		
		public function commit() {
			
			$this->validate();
			
			$data = array(
				"author" => $this->Author->id,
				"transport" => $this->transport,
				"status" => $this->status,
				"date_queued" => $this->DateQueued->format("Y-m-d H:i:s"),
				"date_sent" => $this->DateSent instanceof DateTime ? $this->DateSent->format("Y-m-d H:i:s") : "0000-00-00 00:00:00",
				"subject" => $this->subject,
				"body" => $this->body,
				"response" => json_encode($this->response),
				"meta" => json_encode($this->meta)
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("notifications", $data, $where);
			} else {
				$this->db->insert("notifications", $data);
				$this->id = $this->db->lastInsertId();
			}
			
			/**
			 * Delete existing recipients if this is a new or updated notification
			 */
			
			if ($this->status === Notifications::STATUS_QUEUED) {
				
				$where = array(
					"notification_id = ?" => $this->id,
					"status" => Notifications::STATUS_QUEUED
				);
				
				$this->db->delete("notifications_recipients", $where);
				
				/**
				 * Insert new recipients
				 */
				
				foreach ($this->recipients as $user_id => $row) {
					$data = array(
						"user_id" => $user_id,
						"notification_id" => $this->id,
						"name" => $row['username'],
						"destination" => $row['destination'],
						"date_sent" => "0000-00-00 00:00:00",
						"status" => Notifications::STATUS_QUEUED
					);
					
					$this->db->insert("notifications_recipients", $data);
				}
			}
			
			return $this;
		}
		
		/**
		 * Dispatch this notification
		 * @since Version 3.9.1
		 * @return \Railpage\Notifications\Notification
		 * @throws \Exception if the dispatch transport does not implement \\Railpage\\Notification\\TransportInterface
		 */
		
		public function dispatch() {
			
			/**
			 * Set the dispatch transport
			 */
			
			switch ($this->transport) {
				case Notifications::TRANSPORT_EMAIL : 
					$Transport = new Transport\Email;
					break;
					
				case Notifications::TRANSPORT_PUSH : 
					$Transport = new Transport\Push;
					break;
			}
			
			/**
			 * Check that our transport object implements TransportInterface
			 */
			
			if (!$Transport instanceof TransportInterface) {
				throw new Exception("The specified transport object does not implement \\Railpage\\Notification\\TransportInterface");
			}
			
			/**
			 * Set the notification data in the transport object
			 */
			
			$Transport->setData($this->getArray());
			
			/**
			 * Dispatch and store the response
			 */
			
			$this->response = $Transport->send();
			
			if ($this->response['stat'] === true) {
				$this->DateSent = new DateTime; 
				$this->status = Notifications::STATUS_SENT;
			} else {
				$this->status = Notifications::STATUS_ERROR;
			}
			
			/**
			 * Save changes
			 */
			
			$this->commit(); 
			
			/**
			 * Update recipients
			 */
			
			if ($this->status === Notifications::STATUS_SENT) {
				if (count($this->response['failures']) === 0) {
					$data = array(
						"date_sent" => (new DateTime)->format("Y-m-d H:i:s"),
						"status" => Notifications::STATUS_SENT
					);
					
					$where = array(
						"notification_id = ?" => $this->id
					);
					
					$this->db->update("notifications_recipients", $data, $where);
				} else {
					
				}
			}
			
			return $this;
		}
		
		/**
		 * Get an associative array for templating purposes
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getArray() {
			$array = array(
				"id" => $this->id,
				"subject" => $this->subject,
				"body" => $this->body,
				"response" => empty($this->response) ? $this->response : json_decode(json_encode($this->response), true),
			);
			
			if (isset($this->meta['decoration'])) {
				$array['decoration'] = $this->meta['decoration'];
			}
			
			if (isset($this->meta['headers'])) {
				$array['headers'] = $this->meta['headers'];
			}
			
			if (isset($this->meta['unsubscribe'])) {
				$array['unsubscribe'] = $this->meta['unsubscribe'];
			}
			
			if ($this->Author instanceof User) {
				$array['author'] = array(
					"id" => $this->Author->id,
					"username" => $this->Author->username,
					"email" => $this->Author->contact_email,
					"url" => $this->Author->url->getURLs()
				);
			}
			
			$array['recipients'] = $this->recipients;
			
			if ($this->DateQueued instanceof DateTime) {
				$array['datequeued'] = array(
					"absolute" => $this->DateQueued->format("Y-m-d H:i:s")
				);
			}
			
			if ($this->DateSent instanceof DateTime) {
				$array['datesent'] = array(
					"absolute" => $this->DateSent->format("Y-m-d H:i:s")
				);
			}
			
			switch ($this->status) {
				case Notifications::STATUS_QUEUED :
					$array['status'] = array(
						"id" => $this->status,
						"name" => "Queued"
					);
					
					break;
					
				case Notifications::STATUS_SENT :
					$array['status'] = array(
						"id" => $this->status,
						"name" => "Sent"
					);
					
					break;
					
				case Notifications::STATUS_ERROR :
					$array['status'] = array(
						"id" => $this->status,
						"name" => "Error"
					);
					
					break;
			}
			
			switch ($this->transport) {
				case Notifications::TRANSPORT_EMAIL :
					$array['transport'] = array(
						"id" => $this->transport,
						"name" => "Email"
					);
					
					break;
			}
			
			return $array;
		}
		
		/**
		 * Add a recipient
		 * @since Version 3.9.1
		 * @param int $user_id
		 * @param string $username
		 * @param string $destination
		 * @return \Railpage\Notifications\Notification
		 * @throws \Exception if $user_id is not provided
		 * @throws \Exception if $username is not provided
		 * @throws \Exception if $destination is not provided
		 */
		
		public function addRecipient($user_id = false, $username = false, $destination = false) {
			if (!filter_var($user_id, FILTER_VALIDATE_INT)) {
				throw new Exception("You must specify a user ID when adding a recipient to a notification");
			}
			
			if (!$username) {
				throw new Exception("You must specify a username when adding a recipient to a notification");
			}
			
			if (!$destination || empty($destination)) {
				throw new Exception("You must specify a destination (eg email address) when adding a recipient to a notification");
			}
			
			$this->recipients[$user_id] = array(
				"username" => $username,
				"destination" => $destination
			);
			
			return $this;
		}
		
		/**
		 * Get recipients of this notification
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getRecipients() {
			$query = "SELECT * FROM notification_recipients WHERE notification_id = ?";
			
			$recipients = array(); 
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				
				$array = array(
					"user_id" => $row['user_id'],
					"name" => $row['name'], 
					"destination" => $row['destination'],
					"date_sent" => $row['date_sent']
				);
				
				switch ($row['status']) {
					case Notifications::STATUS_QUEUED :
						$array['status'] = array(
							"id" => $this->status,
							"name" => "Queued"
						);
						
						break;
						
					case Notifications::STATUS_SENT :
						$array['status'] = array(
							"id" => $this->status,
							"name" => "Sent"
						);
						
						break;
						
					case Notifications::STATUS_ERROR :
						$array['status'] = array(
							"id" => $this->status,
							"name" => "Error"
						);
						
						break;
				}
				
				$recipients[] = $array;
			}
		
			return $recipients;
		}
        
        /**
         * Add a header to the notification
         * @since Version 3.10.0
         * @param string $name
         * @param string $value
         * @return \Railpage\Notifications\Notification
         */
        
        public function addHeader($name, $value) {
            
            $this->meta['headers'][$name] = $value;
            
            return $this;
            
        }
	}