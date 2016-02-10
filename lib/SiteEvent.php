<?php

/**
 * Lot site events
 * @package Railpage
 * @since Version 3.6
 * @author Michael Greenhill
 */

namespace Railpage;

use Railpage\SiteEvent\Base;
use Exception;
use DateTime;
use Railpage\Debug;
use Railpage\Url;

/**
 * Show / create an event
 */

class SiteEvent extends Base {
    
    /**
     * Event ID
     * @since Version 3.6
     * @var int $id
     */
     
    public $id;
    
    /**
     * Module name
     * @since Version 3.6
     * @var string $module_name
     */
    
    public $module_name;
    
    /**
     * Event User ID
     * @since Version 3.6
     * @var int $user_id
     */
    
    public $user_id;
    
    /**
     * Event username
     * @since Version 3.6
     * @var string $username
     */
    
    public $username;
    
    /**
     * Timestamp
     * @since Version 3.6
     * @var object $timestamp
     */
    
    public $timestamp; 
    
    /**
     * Event title
     * @since Version 3.6
     * @var string $title
     */
    
    public $title;
    
    /**
     * Event args 
     * @since Version 3.6
     * @var array $args
     */
    
    public $args;
    
    /**
     * Event key
     * @since Version 3.6
     * @var mixed $key
     */
    
    public $key;
    
    /**
     * Event key value
     * @since Version 3.6
     * @var mixed $value
     */
    
    public $value;
    
    /**
     * Constructor
     * @since Version 3.6
     * @param int $event_id
     */
    
    public function __construct($event_id = false) {
        
        parent::__construct(); 
        
        /**
         * Record this in the debug log
         */
        
        Debug::RecordInstance(); 
        
        if ($event_id) {
            $this->id = $event_id; 
            
            $this->fetch(); 
        }
    }
    
    /**
     * Fetch an event
     * @since Version 3.6
     * @return void
     */
    
    private function fetch() {
        
        $query = "SELECT e.id, e.module, e.user_id, u.username, e.timestamp, e.title, e.args, e.key, e.value FROM log_general AS e INNER JOIN nuke_users AS u ON u.user_id = e.user_id WHERE e.id = ?"; 
        
        $row = $this->db->fetchRow($query, $this->id);
        $this->module_name = $row['module'];
        $this->user_id = $row['user_id'];
        $this->username = $row['username'];
        $this->timestamp = $row['timestamp'];
        $this->title = $row['title'];
        $this->args = json_decode($row['args'], true);
        $this->key = $row['key'];
        $this->value = $row['value'];
        
    }
    
    /**
     * Validate changes to this event
     * @since Version 3.6
     * @return boolean
     */
    
    public function validate() {
        if (empty($this->title)) {
            throw new Exception("Cannot validate site event - title cannot be empty!"); 
            return false;
        }
        
        if (empty($this->user_id)) {
            throw new Exception("Cannot validate site event - user ID cannot be empty!"); 
            return false;
        }
        
        if (empty($this->key)) {
            throw new Exception("Cannot validate site event - key cannot be empty!"); 
            return false;
        }
        
        if (empty($this->args)) {
            $this->args = "";
        }
        
        return true;
    }
    
    /**
     * Commit changes to an event
     * @since Version 3.6
     * @return boolean
     */
    
    public function commit() {
        $this->validate(); 
        
        $data = array(
            "module" => $this->module_name,
            "user_id" => $this->user_id,
            "title" => $this->title,
            "args" => is_array($this->args) ? json_encode($this->args) : $this->args,
            "key" => $this->key,
            "value" => $this->value
        );
        
        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            $where = array(
                "id = ?" => $this->id
            );
            
            $this->db->update("log_general", $data, $where);
        } else {
            $this->db->insert("log_general", $data);
            $this->id = $this->db->lastInsertId();
        }
        
        return true;
    }
}
