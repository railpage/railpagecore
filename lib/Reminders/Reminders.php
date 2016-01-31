<?php
    /**
     * Reminders master class
     * @since Version 3.8.7
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Reminders;
    
    use Railpage\AppCore;
    use Railpage\Module;
    use Railpage\Users\User;
    use Exception;
    use DateTime;
    
    /**
     * Reminders
     */
    
    class Reminders extends AppCore {
        
        /**
         * Constructor
         * @since Version 3.8.7
         */
        
        public function __construct() {
            
            parent::__construct();
            
            $this->Module = new Module("reminders");
            
        }
        
        /**
         * Find reminders for this user
         * @since Version 3.8.7
         * @yield \Railpage\Reminders\Reminder
         * @return \Railpage\Reminders\Reminder
         */
        
        public function getRemindersForUser() {
            
            if (!$this->User instanceof User) {
                throw new Exception("Can't find reminders because we don't know which user to look up");
            }
            
            $query = "SELECT id FROM reminders WHERE user_id = ? AND reminder >= ? AND sent = ? ORDER BY reminder";
            
            foreach ($this->db->fetchAll($query, array($this->User->id, date("Y-m-d"), 0)) as $row) {
                yield new Reminder($row['id']);
            }
            
        }
        
        /**
         * Find past reminders for this user
         * @since Version 3.8.7
         * @yield \Railpage\Reminders\Reminder
         * @return \Railpage\Reminders\Reminder
         */
        
        public function getPastRemindersForUser() {
            
            if (!$this->User instanceof User) {
                throw new Exception("Can't find reminders because we don't know which user to look up");
            }
            
            $query = "SELECT id FROM reminders WHERE user_id = ? AND (reminder < ? OR sent = ?) ORDER BY reminder";
            
            foreach ($this->db->fetchAll($query, array($this->User->id, date("Y-m-d"), 1)) as $row) {
                yield new Reminder($row['id']);
            }
            
        }
        
        /**
         * Get all upcoming reminders
         * @since Version 3.8.7
         * @yield \Railpage\Reminders\Reminder
         * @return \Railpage\Reminders\Reminder
         */
        
        public function getUpcoming() {
            
            $params = array(
                date("Y-m-d"),
                date("Y-m-d", strtotime("tomorrow")),
                0
            );
            
            $query = "SELECT id FROM reminders WHERE reminder >= ? AND reminder < ? AND sent = ?";
            
            foreach ($this->db->fetchAll($query, $params) as $row) {
                yield new Reminder($row['id']);
            }
        }
    }
    