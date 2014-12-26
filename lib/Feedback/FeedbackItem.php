<?php
	/**
	 * Feedback module
	 * @since Version 3.4
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Feedback;
	
	use Exception;
	use DateTime;
	use Railpage\Users\User;
	use Railpage\Url;
	use stdClass;
	
	/**
	 * Feedback class
	 * @since Version 3.4
	 */
	
	class FeedbackItem extends Feedback {
		
		/**
		 * ID
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * User ID
		 * @var int $user_id
		 */
		
		public $user_id;
		
		/**
		 * Username
		 * @var string $username
		 */
		
		public $username;
		
		/**
		 * Email address
		 * @var string $email
		 */
		
		public $email;
		
		/**
		 * Area
		 * @var string $area
		 */
		
		public $area;
		
		/**
		 * Area ID
		 * @var int $area_id
		 */
		 
		public $area_id;
		
		/**
		 * Message
		 * @var string $message
		 */
		
		public $message;
		
		/**
		 * Status
		 * @var string $status
		 */
		
		public $status;
		
		/**
		 * Status ID
		 * @var int $status_id
		 */
		
		public $status_id;
		
		/**
		 * Assigned to user ID
		 * @var int $assigned_to
		 */
		
		public $assigned_to;
		
		/**
		 * Date
		 * @var \DateTime $Date
		 */
		
		public $Date;
		
		/**
		 * Author
		 * @since Version 3.8.7
		 * @var \Railpage\Users\User $Author;
		 */
		
		public $Author;
		
		/**
		 * Constructor
		 * @since Version 3.4
		 * @param $id;
		 */
		
		public function __construct($id = false) {
			parent::__construct(); 
			
			if ($id) {
				$query = "SELECT f.*, fa.feedback_title, fs.name AS feedback_status
					FROM feedback AS f
					LEFT JOIN feedback_area AS fa ON f.area = fa.feedback_id 
					LEFT JOIN feedback_status AS fs ON f.status = fs.id
					WHERE f.id = ?";
				
				$row = $this->db->fetchRow($query, $id); 
				$this->id 			= $row['id']; 
				$this->Date 		= new DateTime("@" . $row['time']);
				$this->user_id 		= $row['user_id'];
				$this->username		= $row['username'];
				$this->email 		= $row['email'];
				$this->area 		= $row['feedback_title'];
				$this->area_id 		= $row['area']; 
				$this->message 		= $row['message']; 
				$this->status 		= $row['status']; 
				$this->status_id 	= $row['feedback_status'];
				$this->assigned_to	= $row['assigned_to'];
				
				$this->url = new Url(sprintf("/feedback/manage/%d", $this->id));
				
				if ($this->user_id > 0) {
					$this->Author = new User($this->user_id);
				} else {
					$this->Author = new User(0);
					$this->Author->id = 0;
					$this->Author->username = sprintf("%s (guest)", $this->email);
					$this->Author->url = sprintf("/user?mode=lookup&email=%s", $this->email);
					$this->Author->contact_email = $this->email;
				}
				
				if ($this->Author->id > 0) {
					$this->url->replypm = sprintf("/messages/new/from/feedback-%d", $this->id);
				}
				
				if (!empty($this->Author->contact_email)) {
					$this->url->replyemail = sprintf("/feedback/email/%d", $this->id);
				}
			}
		}
		
		/**
		 * Delete this message
		 * @since Version 3.4
		 * @return boolean
		 */
		
		public function delete() {
			if ($this->db instanceof \sql_db) {
				$dataArray = array("status" => 3); 
				$where = array("id" => $this->db->real_escape_string($this->id));
				
				$query = $this->db->buildQuery($dataArray, "feedback", $where);
				
				if ($this->db->query($query)) {
					return true;
				} else {
					throw new \Exception($this->db->error."\n\n".$query);
				}
			} else {
				$data = array(
					"status" => 3
				);
				
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("feedback", $data, $where); 
				return true;
			}
		}
		
		/**
		 * Assign a feedback item to a user
		 * @since Version 3.4
		 * @param int $user_id
		 * @return boolean
		 */
		
		public function assign($user_id = false) {
			if (!$user_id) {
				throw new \Exception("Could not assign feedback item - no user ID given"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$dataArray = array(); 
				$dataArray['assigned_to'] = $this->db->real_escape_string($user_id);
				$dataArray['status'] = 2;  
				
				$where = array("id" => $this->id); 
				
				$query = $this->db->buildQuery($dataArray, "feedback", $where); 
				
				if ($this->db->query($query)) {
					return true; 
				} else {
					throw new \Exception("Could not assign feedback item - ".$this->db->error); 
					return false;
				}
			} else {
				$data = array(
					"assigned_to" => $user_id,
					"status" => 2
				);
				
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("feedback", $data, $where); 
				return true;
			}
		}
	}
?>