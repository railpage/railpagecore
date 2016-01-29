<?php
    /**
     * User-specific functions for the PrivMsgs module
     * @since Version 3.2
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\PrivateMessages;
    
    use Exception;
    use DateTime;
    
    /**
     * Private messages user class
     */
    
    class User extends PrivateMessages {
        
        /**
         * User object
         * @since Version 3.2
         * @var object $User
         */
        
        public $User;
        
        /**
         * Constructor
         * @since Version 3.2
         * @var object $db
         * @var object $User
         */
        
        public function __construct($User = false) {
            parent::__construct();
            
            if (!$User || !$User->id || $User->id == NULL || empty($User->id)) {
                throw new \Exception("Cannot instantiate ".__CLASS__." - user object is empty or not loaded".printArray(debug_backtrace()));
            } 
            
            $this->User = $User;
        }
        
        /**
         * Get unread messages for this user
         * @since Version 3.2
         * @return array
         */
        
        public function unread() {
            if ($this->db instanceof \sql_db) {
                $query = "SELECT DISTINCT privmsgs_id 
                            FROM nuke_bbprivmsgs 
                            WHERE privmsgs_to_userid = ".$this->db->real_escape_string($this->User->id)." 
                            AND privmsgs_type IN (".PRIVMSGS_NEW_MAIL.", ".PRIVMSGS_UNREAD_MAIL.")
                            ORDER BY privmsgs_date DESC";
                
                if ($rs = $this->db->query($query)) {
                    $return = array(); 
                    
                    while ($row = $rs->fetch_assoc()) {
                        $return[] = $row['privmsgs_id']; 
                    }
                    
                    return $return;
                } else {
                    throw new \Exception($this->db->error."\n".$query); 
                    return false;
                }
            } else {
                $query = "SELECT DISTINCT privmsgs_id 
                            FROM nuke_bbprivmsgs 
                            WHERE privmsgs_to_userid = ? 
                            AND privmsgs_type IN (?, ?)
                            ORDER BY privmsgs_date DESC";
                
                $return = array(); 
                
                foreach ($this->db->fetchAll($query, array($this->User->id, PRIVMSGS_NEW_MAIL, PRIVMSGS_UNREAD_MAIL)) as $row) {
                    $return[] = $row['privmsgs_id'];
                }
                
                return $return;
            }
        }
    }
    