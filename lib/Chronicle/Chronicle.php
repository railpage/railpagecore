<?php
    /**
     * Chronicle module - a history of railway events
     * @since Version 3.8.7
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Chronicle;
    
    use Railpage\AppCore;
    use Railpage\Module;
    use Railpage\Url;
    use Exception;
    use DateTime;
    
    /**
     * Chronicle base class
     */
    
    class Chronicle extends AppCore {
        
        /**
         * Sub query - all areas 
         * @since Version 3.10.0
         * @param string SubQuery_AllAreas
         */
        
        private $SubQuery_AllAreas;
        
        /**
         * Constructor
         */
        
        public function __construct() {
            
            parent::__construct();
            
            $this->Module = new Module("chronicle");
            $this->url = new Url(sprintf("%s/chronicle", RP_WEB_ROOT));
            $this->url->newest = sprintf("%s?mode=newest", $this->url->url);
            $this->url->year = sprintf("%s?mode=year", $this->url->url);
            $this->url->today = sprintf("%s?mode=today", $this->url->url);
            $this->url->thisweek = sprintf("%s?mode=thisweek", $this->url->url);
            $this->url->thismonth = sprintf("%s?mode=thismonth", $this->url->url);
            
            $this->SubQuery_AllAreas = 
                "SELECT 'locos' AS module, CONCAT(l.loco_num, ': ', dt.loco_date_text) AS title, d.text AS text, timestamp AS `date` FROM loco_unit_date AS d LEFT JOIN loco_unit AS l ON l.loco_id = d.loco_unit_id LEFT JOIN loco_date_type AS dt ON dt.loco_date_id = d.loco_date_id 
                    UNION SELECT 'locations' AS module, dt.name AS title, d.text, d.date FROM location_date AS d LEFT JOIN location_datetypes AS dt ON d.type_id = dt.id 
                    UNION SELECT 'events' AS module, e.title, e.description AS text, ed.date FROM event_dates AS ed LEFT JOIN event AS e ON ed.event_id = e.id";
            
        }
        
        /**
         * Get latest additions to the chronicle
         * @since Version 3.8.7
         * @yield \Railpage\Chronicle\Entry
         * @return \Railpage\Chronicle\Entry
         */
        
        public function getLatestAdditions() {
            $query = "SELECT id FROM chronicle_item WHERE status = ? ORDER BY id DESC LIMIT 0, 10";
            
            foreach ($this->db->fetchAll($query, Entry::STATUS_ACTIVE) as $row) {
                yield new Entry($row['id']);
            }
        }
        
        /**
         * Get events for a date
         * @since Version 3.8.7
         * @return array
         * @param \DateTime $Date
         */
        
        public function getEntriesForDate($Date = false) {
            
            if (!$Date || !$Date instanceof DateTime) {
                $Date = new DateTime;
            }
            
            $query = "
                SELECT * FROM (
                    " . $this->SubQuery_AllAreas . "
                ) AS items
                WHERE `date` = ?";
            
            return $this->db->fetchAll($query, $Date->Format("Y-m-d")); 
            
        }
        
        /**
         * Get events for today (On this day...) 
         * @since Version 3.10.0
         * @return array
         */
        
        public function getEntriesForToday($limit = false) {
            
            $Date = new DateTime;
            
            $cachekey = sprintf("railpage:chronicle.date=%s;limit=%d", $Date->getTimestamp(), $limit); 
            
            if ($result = $this->Memcached->Fetch($cachekey)) {
                return $result;
            }
            
            $query = "
                SELECT * FROM (
                    " . $this->SubQuery_AllAreas . "
                ) AS items
                WHERE `date` LIKE '%-" . $Date->format("d") . "' ORDER BY `date` DESC";
            
            if (filter_var($limit, FILTER_VALIDATE_INT)) {
                $query .= " LIMIT 0, " . $limit;
            }
            
            $this->Memcached->save($cachekey, $result, strtotime("+24 hours"));
            
            return $this->db->fetchAll($query); 
            
        }
        
        /**
         * Get chronicle entry providers
         * @since Version 3.9
         * @return array
         */
        
        public function getProviders() {
            
            $providers = array(); 
            
            foreach (glob(sprintf("%s%sProvider%s*.php", __DIR__, DS, DS)) as $file) {
                $providers[] = sprintf("\\Railpage\\Chronicle%s", str_replace("/", "\\", str_replace(__DIR__, "", str_replace(".php", "", $file))));
            }
            
            return $providers;
            
        }
        
        /**
         * Get all entries
         * @since Version 3.10.0
         * @return array
         */
        
        public function getAllEntries() {
            
            $query = "
                " . $this->SubQuery_AllAreas . "
                ORDER BY `date` DESC";
            
            return $this->db->fetchAll($query); 
            
        }
        
    }