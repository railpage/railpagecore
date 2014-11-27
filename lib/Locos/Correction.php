<?php
	/**
	 * Locomotive suggestions/corrections
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos;
	
	use Railpage\AppCore;
	use Railpage\Users\User;
	use Railpage\PrivateMessages\Message;
	use Exception;
	use DateTime;
	use stdClass;
	
	/**
	 * Locomotive suggestion or correction
	 */
	
	class Correction extends AppCore {
		
		/**
		 * Open status
		 * @since Version 3.8.7
		 * @const int STATUS_OPEN
		 */
		
		const STATUS_OPEN = 0;
		
		/**
		 * Corrected status
		 * @since Version 3.8.7
		 * @const int STATUS_CLOSED
		 */
		
		const STATUS_CLOSED = 1;
		
		/**
		 * Ignored status
		 * @since Version 3.8.7
		 * @const int STATUS_IGNORED
		 */
		
		const STATUS_IGNORED = 2;
		
		/**
		 * Correction ID
		 * @since Version 3.8.7
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Correction text
		 * @since Version 3.8.7
		 * @var string $text
		 */
		
		public $text;
		
		/**
		 * Date submitted
		 * @since Version 3.8.7
		 * @var \DateTime $Date
		 */
		
		public $Date;
		
		/**
		 * Status
		 * @since Version 3.8.7
		 * @var int $status
		 */
		
		public $status;
		
		/**
		 * User object of submitter
		 * @since Version 3.8.7
		 * @var \Railpage\Users\User $User
		 */
		
		public $User;
		
		/**
		 * Affected locomotive object
		 * @since Version 3.8.7
		 * @var \Railpage\Locos\Locomotive $Loco
		 */
		
		public $Loco;
		
		/**
		 * Resolution
		 * @since Version 3.8.7
		 * @var \stdClass $Resolution
		 */
		
		public $Resolution;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param int $id
		 */
		
		public function __construct($id = NULL) {
			parent::__construct(); 
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$query = "SELECT * FROM loco_unit_corrections WHERE correction_id = ?";
				
				$row = $this->db->fetchRow($query, $id);
				
				if (count($row)) {
					$this->id = $id;
					$this->text = $row['text'];
					$this->Loco = new Locomotive($row['loco_id']);
					$this->User = new User($row['user_id']);
					$this->Date = new DateTime($row['date']);
					$this->status = $row['status'];
					$this->Resolution = new stdClass;
					
					if (filter_var($row['resolved_by'], FILTER_VALIDATE_INT)) {
						$this->Resolution->User = new User($row['resolved_by']);
						$this->Resolution->Date = new DateTime($row['resolved_date']);
					}
				}
			}
		}
		
		/**
		 * Validate changes to this correction
		 * @since Version 3.8.7
		 * @return boolean
		 * @throws \Exception if $this->text is empty
		 * @throws \Exception if $this->Loco is not an instance of \Railpage\Locos\Locomotive
		 * @throws \Exception if $this->User is not an instance of \Railpage\Users\User
		 */
		
		private function validate() {
			if (empty($this->text)) {
				throw new Exception("Cannot validate changes to this correction: no text provided");
			}
			
			if (!$this->Loco instanceof Locomotive) {
				throw new Exception("Cannot validate changes to this correction: no locomotive provided");
			}
			
			if (!$this->User instanceof User) {
				throw new Exception("Cannot validate changes to this correction: no valid user provided");
			}
			
			if (!$this->Date instanceof DateTime) {
				$this->Date = new DateTime;
			}
			
			if (!filter_var($this->status)) {
				$this->status = self::STATUS_OPEN;
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this correction
		 * @since Version 3.8.7
		 * @return boolean
		 */
		
		public function commit() {
			$this->validate();
			
			$data = array(
				"loco_id" => $this->Loco->id,
				"user_id" => $this->User->id,
				"date" => $this->Date->format("Y-m-d H:i:s"),
				"status" => $this->status,
				"resolved_by" => (isset($this->Resolution->User) && $this->Resolution->User instanceof User) ? $this->Resolution->User : 0,
				"resolved_date" => (isset($this->Resolution->Date) && $this->Resolution->Date instanceof DateTime) ? $this->Resolution->Date->format("Y-m-d H:i:s") : NULL,
				"text" => $this->text
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"correction_id = ?" => $this->id
				);
				
				$this->db->update("loco_unit_corrections", $data, $where); 
			} else {
				$this->db->insert("loco_unit_corrections", $data);
				$this->id = $this->db->lastInsertId();
			}
			
			return true;
		}
		
		/**
		 * Close this correction
		 * @since Version 3.8.7
		 * @throws \Exception if $this->Resolution->User is not an instance of \Railpage\Users\User
		 * @return boolean
		 * @param string $reason
		 */
		
		public function close($reason = NULL) {
			if (!$this->Resolution->User instanceof User) {
				throw new Exception("Cannot close correction - User resolving this correction not specified");
			}
			
			$this->Resolution->Date = new DateTime;
			
			$data = array(
				"status" => self::STATUS_CLOSED,
				"resolved_by" => $this->Resolution->User->id,
				"resolved_date" => $this->Resolution->Date->format("Y-m-d H:i:s")
			);
			
			$where = array(
				"correction_id = ?" => $this->id
			);
			
			$this->db->update("loco_unit_corrections", $data, $where);
			
			/**
			 * Send a PM to the author of the correction
			 */
			
			$Message = new Message;
			$Message->setAuthor($this->Resolution->User);
			$Message->setRecipient($this->User);
			$Message->subject = "Your Locomotives database correction has been accepted";
			$Message->body = "Your suggested correction for [url=" . $this->Loco->url->url . "]" . $this->Loco->number . "[/url] in [url=" . $this->Loco->Class->url->url . "]" . $this->Loco->Class->name . "[/url] has been accepted by " . $this->Resolution->User->username . ".";
			
			if (!empty($this->text)) {
				$Message->body .= "\n\n[quote=Your suggestion]" . $this->text . "[/quote]";
			}
			
			if (!is_null($reason)) {
				$Message->body .= "\n\n" . $reason;
			}
			
			$Message->body .= "\n\nThis is an automated message - there is no need to reply.";
			
			$Message->send();
			
			return true;
		}
		
		/**
		 * Ignore this correction
		 * @since Version 3.8.7
		 * @throws \Exception if $this->Resolution->User is not an instance of \Railpage\Users\User
		 * @return boolean
		 * @param string $reason
		 */
		
		public function ignore($reason = NULL) {
			if (!$this->Resolution->User instanceof User) {
				throw new Exception("Cannot ignore correction - User resolving this correction not specified");
			}
			
			$this->Resolution->Date = new DateTime;
			
			$data = array(
				"status" => self::STATUS_IGNORED,
				"resolved_by" => $this->Resolution->User->id,
				"resolved_date" => $this->Resolution->Date->format("Y-m-d H:i:s")
			);
			
			$where = array(
				"correction_id = ?" => $this->id
			);
			
			$this->db->update("loco_unit_corrections", $data, $where);
			
			/**
			 * Send a PM to the author of the correction
			 */
			
			$Message = new Message;
			$Message->setAuthor($this->Resolution->User);
			$Message->setRecipient($this->User);
			$Message->subject = "Your Locomotives database correction was not accepted";
			$Message->body = "Your suggested correction for [url=" . $this->Loco->url->url . "]" . $this->Loco->number . "[/url] in [url=" . $this->Loco->Class->url->url . "]" . $this->Loco->Class->name . "[/url] was not accepted by " . $this->Resolution->User->username . ".";
			
			if (!empty($this->text)) {
				$Message->body .= "\n\n[quote=Your suggestion]" . $this->text . "[/quote]";
			}
			
			if (!is_null($reason)) {
				$Message->body .= "\n\n" . $reason;
			}
			
			$Message->body .= "\n\nThis is an automated message - there is no need to reply.";
			
			$Message->send();
			
			return true;
		}
	}
?>