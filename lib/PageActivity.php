<?php
    /**
     * Page activity class
     * @since Version 3.4
     * @author Michael Greenhill
     * @package Railpage
     */
    
    namespace Railpage; 
    
    /**
     * Page activity class
     */
    
    class PageActivity {
        
        /**
         * Database handle
         * @since Version 3.4
         * @var object $db
         */
        
        public $db;
        
        /**
         * Constructor
         * @since Version 3.4
         * @param object $db
         * @param object $User
         * @param string $module
         * @param string $pagetitle
         */
        
        public function __construct($db = false, $User = false, $module = NULL, $pagetitle = NULL) {
            if (!$db) {
                throw new \Exception("Cannot instantiate ".__CLASS__." - no database object given"); 
                return false;
            }
            
            $this->db = $db;
            
            // Some URIs to ignore
            $ignorelist = array(
                "/modules/Forums/images/avatars",
                "/rss/",
                "/login/"
            );
            
            // Do we continue with the insert?
            $continue = true;
            
            // Check if this URI is one we're ignoring
            foreach ($ignorelist as $url) {
                if (preg_match("@".preg_quote($url)."@i", $_SERVER['REQUEST_URI'])) {
                    $continue = false;
                    break;
                }
            }
            
            if ($continue) {
                // Delete any records older than 30 days
                $query = "DELETE FROM log_pageactivity WHERE time < '".date('Y-m-d H:i:s', strtotime("30 days ago"))."'"; 
                $this->db->query($query); 
                
                // Round off to nearest 15 minute interval
                $precision = 60 * 15; 
                $dataArray = array(); 
                
                $timestamp = round(time() / $precision) * $precision;
                
                $dataArray['time']  = date('Y-m-d H:i:s', $timestamp);
                $dataArray['url']   = $_SERVER['REQUEST_URI'];
                
                // Add the module
                if (!empty($module)) { 
                    $dataArray['module'] = $this->db->real_escape_string($module);
                }
                
                // Add the pagetitle
                if (!empty($pagetitle)) {
                    $dataArray['pagetitle'] = $this->db->real_escape_string($pagetitle); 
                }
                
                // Check if there are any existing records that simply need incrementing
                $query = "SELECT * FROM log_pageactivity WHERE time = '".$this->db->real_escape_string($dataArray['time'])."' AND url = '".$this->db->real_escape_string($dataArray['url'])."'"; 
                
                if ($rs = $this->db->query($query)) {
                    if ($rs->num_rows) {
                        // Update it!
                        $row = $rs->fetch_assoc(); 
                        
                        $where = array("time" => $dataArray['time'], "url" => $dataArray['url']);
                        
                        // Increment our hits by 1
                        $dataArray['hits'] = $row['hits'] + 1; 
                        
                        if ($User->id) {
                            $dataArray['loggedin'] = $row['loggedin'] + 1; 
                        }
                        
                        // Build the query and submit it
                        $query = $this->db->buildQuery($dataArray, "log_pageactivity", $where); 
                        $this->db->query($query);
                    }  else {
                        // Insert it!
                        if ($User->id) {
                            $dataArray['loggedin'] = 1; 
                        }
                        
                        // Set our hit number to 1
                        $dataArray['hits'] = 1;
                        
                        // Build the query and submit it
                        $query = $this->db->buildQuery($dataArray, "log_pageactivity"); 
                        $this->db->query($query);
                    }
                }
            }
        }
    }
    