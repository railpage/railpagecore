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
     * Administration module
     * @since Version 3.0
     */
    
    class Admin extends SiteMessage {
        
        /**
         *
         * Return a list of all messages
         * @since Version 3.0
         * @version 3.0
         * @param int $start 
         * @param int $perpage 
         * @return mixed
         *
         */
        
        public function listMessages($start = 0, $perpage = 25) {
            if ($this->db instanceof \sql_db) {
                $query = "SELECT * FROM messages ORDER BY message_id LIMIT ".$start.", ".$perpage."";
                
                if ($rs = $this->db->query($query)) {
                    $return = array(); 
                    
                    while ($row = $rs->fetch_assoc()) {
                        $return[] = $row; 
                    }
                    
                    return $return;
                } else {
                    trigger_error("Messages: Unable to retrieve list of messages");
                    trigger_error($this->db->error); 
                    trigger_error($query); 
                    return false;
                }
            } else {
                return $this->db->fetchAll("SELECT * FROM messages ORDER BY message_id LIMIT ?, ?", array($start, $perpage));
            }
        }
        
        
        /**
         *
         * Create a new message
         * @since Version 3.0.1
         * @version 3.0
         * @param string $message_text
         * @return mixed
         *
         */
         
        public function add($message_text = false) {
            if (!$message_text) {
                return false;
            }
            
            if ($this->db instanceof \sql_db) {
                $query = "INSERT INTO messages (message_text) VALUES ('".$this->db->real_escape_string($message_text)."')";
                
                if ($rs = $this->db->query($query)) {
                    return $db->insert_id; 
                } else {
                    trigger_error("Messages: could not add new message"); 
                    trigger_error($this->db->error); 
                    trigger_error($query); 
                    return false;
                }
            } else {
                $data = array(
                    "message_text" => $message_text
                );
                
                if ($this->db->insert("messages", $data)) {
                    return $this->db->lastInsertId(); 
                }
            }
        }
    }
