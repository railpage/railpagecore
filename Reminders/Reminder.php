<?php
	/**
	 * An object representing a reminder alert
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Reminders;
	
	use DateTime;
	use DateTimeZone;
	use Exception;
	use Railpage\AppCore;
	use Railpage\Module;
	use Railpage\Users\User;
	use Railpage\Url;
	
	use Swift_Message;
	use Swift_Mailer;
	use Swift_SmtpTransport;
	
	/**
	 * Reminder
	 */
	
	class Reminder extends AppCore {
		
		/**
		 * Reminder ID
		 * @since Version 3.8.7
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Namespace that this reminder applies to 
		 * @since Version 3.8.7
		 * @var string $namespace;
		 */
		
		public $namespace;
		
		/**
		 * Class name (including namespace) that this reminder applies to
		 * @since Version 3.8.7
		 * @var string $object
		 */
		
		public $object;
		
		/**
		 * Object (class) id
		 * @since Version 3.8.7
		 * @var int $object_id
		 */
		
		public $object_id;
		
		/**
		 * Reminder title
		 * @since Version 3.8.7
		 * @var string $title
		 */
		
		public $title;
		
		/**
		 * Reminder text
		 * @since Version 3.8.7
		 * @var string $text
		 */
		
		public $text;
		
		/**
		 * Has the reminder been sent?
		 * @since Version 3.8.7
		 * @var boolean $sent
		 */
		
		public $sent = false;
		
		/**
		 * Module that this reminder is associated with
		 * @since Version 3.8.7
		 * @var \Railpage\Module $Module
		 */
		
		public $Module;
		
		/**
		 * The private identifier of this module
		 * @since Version 3.8.7
		 * @var \Railpage\Module $ThisModule
		 */
		
		private $ThisModule;
		
		/**
		 * User object to send the reminder to
		 * @since Version 3.8.7
		 * @var \Railpage\Users\User $User
		 */
		
		public $User;
		
		/**
		 * Date on which the reminder will be sent
		 * @since Version 3.8.7
		 * @var \DateTime $Date
		 */
		
		public $Date;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			
			parent::__construct(); 
			
			$this->ThisModule = new Module("reminders");
			
			if (filter_var($id, FILTER_VALIDATE_INT) && $id > 0) {
				$this->id = $id;
			}
			
			/**
			 * Load the reminder
			 */
			
			if (filter_var($this->id)) {
				$query = "SELECT * FROM reminders WHERE id = ?";
				
				if ($row = $this->db->fetchRow($query, $this->id)) {
					$this->object = $row['object'];
					$this->object_id = $row['object_id'];
					$this->Module = new Module($row['module']);
					$this->User = new User($row['user_id']);
					$this->Date = new DateTime($row['reminder']);
					$this->title = $row['title'];
					$this->text = $row['text'];
					$this->sent = (boolean) $row['sent'];
					
					$this->url = new Url;
					$this->url->send = sprintf("/reminders?id=%d&mode=send", $this->id);
				}
			}
		}
		
		/**
		 * Validate changes to this reminder
		 * @since Version 3.8.7
		 * @return boolean
		 * @throws \Exception if $this->Module not a valid instance of \Railpage\Module
		 * @throws \Exception if $this->User is not a valid instance of \Railpage\Users\User
		 * @throws \Exception if $this->Date is not a valid instance of \DateTime
		 * @throws \Exception if $this->title is empty
		 * @throws \Exception if $this->text is empty
		 */
		
		private function validate() {
			if (!$this->Module instanceof Module) {
				throw new Exception("Cannot save this reminder because no valid Module was supplied");
			}
			
			if (!$this->User instanceof User) {
				throw new Exception("Cannot save this reminder because no valid User object was supplied");
			}
			
			if (!$this->Date instanceof DateTime) {
				throw new Exception("Cannot save this reminder because we don't know when to send it");
			}
			
			if (empty($this->title)) {
				throw new Exception("Cannot save this reminder because no reminder title was supplied");
			}
			
			if (empty($this->text)) {
				throw new Exception("Cannot save this reminder because no reminder text was supplied");
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this reminder
		 * @since Version 3.8.7
		 */
		
		public function commit() {
			
			$this->validate(); 
			
			$data = array(
				"module" => $this->Module->name,
				"namespace" => $this->Module->namespace,
				"object" => $this->object,
				"object_id" => $this->object_id,
				"user_id" => $this->User->id,
				"reminder" => $this->Date->format("Y-m-d H:i:s"),
				"title" => $this->title,
				"text" => $this->text,
				"sent" => $this->sent
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("reminders", $data, $where);
			} else {
				if (!$this->exists()) {
					$this->db->insert("reminders", $data);
					$this->id = $this->db->lastInsertId();
				}
			}
		}
		
		/**
		 * Check if a reminder already exists for this user + object
		 * @since Version 3.8.7
		 * @return boolean
		 * @throws Exception if $this->User is not an instance of \Railpage\Users\User
		 * @throws Exception if $this->object is empty
		 * @throws Exception if $this->object_id is not an integer greater than zero
		 */
		
		public function exists() {
			
			if (!$this->User instanceof User) {
				throw new Exception("Can't lookup a reminder because no user was specified");
			}
			
			if (empty($this->object)) {
				throw new Exception("Can't lookup a reminder because no object was specified");
			}
			
			if (!filter_var($this->object_id) || $this->object_id == 0) {
				throw new Exception("Can't lookup a reminder because no object ID was specified");
			}
			
			$query = "SELECT id FROM reminders WHERE user_id = ? AND object = ? AND object_id = ? AND reminder >= ?";
			$params = array($this->User->id, $this->object, $this->object_id, date("Y-m-d"));
			
			$id = $this->db->fetchOne($query, $params);
			
			if ($id && filter_var($id) && $id > 0) {
				$this->id = $id;
				return true;
			} else {
				return false;
			}
		}
		
		/**
		 * Delete this reminder
		 * @since Version 3.8.7
		 * @return boolean
		 */
		
		public function delete() {
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {	
				$this->db->delete("reminders", array("id = ?" => $this->id));
			}
			
			return true;
		}
		
		/**
		 * Send this reminder
		 * @since Version 3.8.7
		 * @return $this
		 * @param boolean $markAsSent Flag to indicate if this is a test notification or not
		 */
		
		public function send($markAsSent = false) {
			
			$this->validate(); 
			
			$message = Swift_Message::newInstance()
				->setSubject($this->title)
				->setFrom(array(
					"reminders@railpage.com.au" => "Railpage Reminder Alerts"
				))
				->setTo(array(
					$this->User->contact_email => !empty($this->User->realname) ? $this->User->realname : $this->User->username
				))
				->setBody($this->text);
			
			$transport = Swift_SmtpTransport::newInstance($this->Config->SMTP->host, $this->Config->SMTP->port, $this->Config->SMTP->TLS = true ? "tls" : NULL)
				->setUsername($this->Config->SMTP->username)
				->setPassword($this->Config->SMTP->password);
			
			$mailer = Swift_Mailer::newInstance($transport);
			
			if ($result = $mailer->send($message)) {
				if ($markAsSent) {
					$this->sent = true;
					$this->commit();
				}
			}
			
			return $this;
		}
	}
?>