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
use Railpage\Users\Admin as UserAdmin;
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
    
    public function __construct($id = null) {
        parent::__construct(); 
        
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            return;
        }
        
        $query = "SELECT f.*, fa.feedback_title, fs.name AS feedback_status
            FROM feedback AS f
            LEFT JOIN feedback_area AS fa ON f.area = fa.feedback_id 
            LEFT JOIN feedback_status AS fs ON f.status = fs.id
            WHERE f.id = ?";
        
        $row = $this->db->fetchRow($query, $id); 
        $this->id           = $row['id']; 
        $this->Date         = new DateTime("@" . $row['time']);
        $this->user_id      = $row['user_id'];
        $this->username     = $row['username'];
        $this->email        = $row['email'];
        $this->area         = $row['feedback_title'];
        $this->area_id      = $row['area']; 
        $this->message      = $row['message']; 
        $this->status       = $row['feedback_status']; 
        $this->status_id    = $row['status'];
        $this->assigned_to  = $row['assigned_to'];
        
        $this->url = new Url(sprintf("/feedback/manage/%d", $this->id));
        
        if (filter_var($this->user_id, FILTER_VALIDATE_INT)) {
            $this->Author = new User($this->user_id);
            $this->url->replypm = sprintf("/messages/new/from/feedback-%d", $this->id);
            $this->url->replyemail = sprintf("/feedback/email/%d", $this->id);
        }
        
        if (!filter_var($this->user_id, FILTER_VALIDATE_INT)) {
            $this->Author = new User(0);
            $this->Author->id = 0;
            $this->Author->username = sprintf("%s (guest)", $this->email);
            $this->Author->url = sprintf("/user?mode=lookup&email=%s", $this->email);
            $this->Author->contact_email = $this->email;
        }
    }
    
    /**
     * Validate changes to this item
     * @since Version 3.10.0
     * @return boolean
     */
    
    private function validate() {
        
        if (!$this->Date instanceof DateTime && empty($this->Date)) {
            $this->Date = new DateTime;
        }
        
        if (is_null($this->user_id)) {
            $this->user_id = 0;
        }
        
        if (is_null($this->assigned_to)) {
            $this->assigned_to = 0;
        }
        
        if (is_null($this->username)) {
            $this->username = "";
        }
        
        if (!filter_var($this->status_id, FILTER_VALIDATE_INT)) {
            $this->status_id = 1;
        }
        
        if ($this->user_id == 0 && !empty($this->username)) {
            $UserAdmin = new UserAdmin;
            
            $search = $UserAdmin->find($this->username, true); 
            
            foreach ($search as $User) {
                $this->user_id = $User->id;
                break;
            }

        }
        
        return true;
        
    }
    
    /**
     * Commit changes to this item
     * @since Version 3.10.0
     * @return \Railpage\Feedback\FeedbackItem
     */
    
    public function commit() {
        
        $this->validate();
        
        $data = [
            "time" => $this->Date->getTimestamp(),
            "user_id" => $this->user_id,
            "username" => $this->username,
            "email" => $this->email,
            "area" => $this->area_id,
            "message" => $this->message,
            "status" => $this->status_id,
            "assigned_to" => $this->assigned_to
        ];
        
        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            $where = [ "id = ?" => $this->id ];
            $this->db->update("feedback", $data, $where);
            
            return $this;
        }
        
        $this->db->insert("feedback", $data); 
        $this->id = $this->db->lastInsertId(); 
        
        return $this;
        
    }
    
    /**
     * Delete this message
     * @since Version 3.4
     * @return boolean
     */
    
    public function delete() {
        $data = array(
            "status" => 3
        );
        
        $where = array(
            "id = ?" => $this->id
        );
        
        $this->db->update("feedback", $data, $where); 
        return true;
    }
    
    /**
     * Assign a feedback item to a user
     * @since Version 3.4
     * @param int $userId
     * @return boolean
     */
    
    public function assign($userId = null) {
        if (!filter_var($userId, FILTER_VALIDATE_INT)) {
            throw new Exception("Could not assign feedback item - no user ID given"); 
        }
        
        $data = array(
            "assigned_to" => $userId,
            "status" => 2
        );
        
        $where = array(
            "id = ?" => $this->id
        );
        
        $this->db->update("feedback", $data, $where); 
        return true;
    }
}
