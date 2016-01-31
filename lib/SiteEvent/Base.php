<?php
    /**
     * Record a site event
     * @since Version 3.6
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\SiteEvent;
    
    use Railpage\AppCore;
    use Railpage\Users\Factory as UserFactory;
    use Exception;
    use Railpage\Debug;
    use DateTime;
    
    /**
     * Base class
     */
    
    class Base extends AppCore {
        
        /**
         * Get the latest x events within y keys
         * @since Version 3.6
         * @param int $limit
         * @param mixed $keys
         * @return array
         */
        
        public function latest($limit = 25, $keys = false, $page = 1) {
            if (is_array($keys)) {
                $sql_keys = " WHERE `key` IN ('".implode("','", $keys)."')";
            } elseif (is_string($keys)) {
                $sql_keys = $this->db instanceof sql_db ? " WHERE `key` = '".$this->db->real_escape_string($keys)."'" : " WHERE `key` = " . $keys; 
            } else {
                $sql_keys = NULL;
            }
            
            $start = ($page - 1) * $limit;
            
            $query = "SELECT SQL_CALC_FOUND_ROWS e.id, e.module, e.user_id, u.username, u.user_avatar, e.timestamp, e.title, e.args, e.key, e.value FROM log_general AS e INNER JOIN nuke_users AS u ON u.user_id = e.user_id ".$sql_keys." ORDER BY id DESC LIMIT ?, ?"; 
            
            if ($result = $this->db->fetchAll($query, array($start, $limit))) {
                $return = array(); 
                
                foreach ($result as $key => $row) {
                    $row['timestamp'] = new DateTime($row['timestamp']);
                    $row['args'] = json_decode($row['args'], true);
                    
                    $return['events'][$row['id']] = $row;
                }
                
                $return['total_events'] = $this->db_readonly->fetchOne("SELECT FOUND_ROWS() AS total"); 
                $return['page'] = $page;
                $return['items_per_page'] = $limit;
                $return['total_pages'] = ceil($return['total_events'] / $limit);
                
                return $return;
            }
        }
        
        /**
         * Find events where key = x and optionally value = y
         * @since Version 3.6
         * @param mixed $keys
         * @param mixed $value
         * @param int $limit
         * @return array
         */
        
        public function find($keys = false, $value = false, $limit = 25) {
            
            $timer = Debug::getTimer(); 
            
            if (!$keys) {
                throw new Exception("Cannot find events - \$keys cannot be empty"); 
                return false;
            }
            
            $clause = array();
            $where = array();
            
            if (is_array($keys)) {
                $clause[] = "`key` IN ('".implode("','", $keys)."')";
            } elseif (is_string($keys)) {
                $clause[] = $this->db instanceof sql_db ? " `key` = '".$this->db->real_escape_string($keys)."'" : " `key` = ?"; 
                $where[] = $keys;
            }
            
            if ($value) {
                $clause[] = $this->db instanceof sql_db ? " `value` = '".$this->db->real_escape_string($value)."'" : " `value` = ?"; 
                $where[] = $value;
            }
            
            if (count($clause)) {
                $sql_where = "WHERE " . implode(" AND ", $clause); 
            }
            
            $query = "SELECT e.id, e.user_id, u.username, e.timestamp, e.title, e.args, e.key, e.value FROM log_general AS e INNER JOIN nuke_users AS u ON u.user_id = e.user_id " . $sql_where . " ORDER BY e.timestamp DESC LIMIT 0, ?"; 
            
            $where[] = $limit;
            
            $return = array();
            
            foreach ($this->db->fetchAll($query, $where) as $row) {
                $row['timestamp'] = new DateTime($row['timestamp']);
                $row['args'] = json_decode($row['args'], true);
                    
                $return[$row['id']] = $row;
            }
            
            Debug::logEvent(__METHOD__, $timer);
            
            return $return;
        }
    }
    