<?php
    /**
     * Manage the downloads module
     * @since Version 3.2
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Downloads;
    
    /**
     * Downloads module management class
     * @since Version 3.2
     */
    
    class Manage extends Base {
        
        /**
         * Get a list of downloads pending approval
         * @since Version 3.2
         * @return array
         */
        
        public function pending() {
            $query = "SELECT id AS download_id, title AS download_title, user_id, UNIX_TIMESTAMP(date) AS download_date, category_id
                        FROM download_items
                        WHERE approved = 0
                        AND active = 1
                        ORDER BY date";
            
            $return = array(); 
            
            foreach ($this->db->fetchAll($query) as $row) {
                $return[$row['download_id']] = $row; 
            }
            
            return $return;
        }
    }
    