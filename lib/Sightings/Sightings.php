<?php
    /**
     * Sightings module
     * @since Version 3.2
     * @package Railpage
     * @author Michael Greenhill, Dean Stalker
     */
    
    namespace Railpage\Sightings;
    
    use Railpage\AppCore;
    use Railpage\Debug;
    use Railpage\Url;
    use Railpage\Place;
    use Exception;
    use InvalidArgumentException;
    use DateTime;
    use DateTimeZone;
    use PDO;
    
    /**
     * Base sightings class
     * @since Version 3.2
     */
    
    class Sightings extends AppCore {
        
        /**
         * Get latest sightings from the database
         * @since Version 3.5
         * @param int $items_per_page
         * @param int $page
         * @return array
         */
        
        public function latest($items_per_page = 25, $page = 1) {
            $thispage = ($page - 1) * $items_per_page;
            
            $query = "SELECT SQL_CALC_FOUND_ROWS id FROM sighting ORDER BY date DESC LIMIT ?, ?";
            
            $sightings = $this->db->fetchAll($query, array($thispage, $items_per_page)); 
            
            $return = array(); 
            $return['total'] = $this->db->fetchOne("SELECT FOUND_ROWS() AS total"); 
            $return['page'] = $page; 
            $return['perpage'] = $items_per_page; 
            $return['sightings'] = array(); 
            
            foreach ($sightings as $row) {
                $return['sightings'][] = $row['id']; 
            }
            
            return $return;
        }
        
        /**
         * Find sightings for a loco ID
         * @since Version 3.5
         * @return array
         */
        
        public function findLoco($loco_id) {
            if (!filter_var($loco_id, FILTER_VALIDATE_INT)) {
                return false;
            }
            
            $query = "SELECT s.id AS sighting_id FROM sighting AS s INNER JOIN sighting_locos AS sl ON sl.sighting_id = s.id WHERE sl.loco_id = ?";
            
            $return = array(); 
            
            foreach ($this->db->fetchAll($query, $loco_id) as $row) {
                $return[] = $row['sighting_id'];
            }
            
            return $return;
        }
        
        /**
         * Find sightings for a loco class
         * @since Version 3.5
         * @return array
         */
        
        public function findLocoClass($class_id) {
            if (!filter_var($class_id, FILTER_VALIDATE_INT)) {
                return false;
            }
            
            $query = "SELECT s.id AS sighting_id FROM sighting AS s INNER JOIN sighting_locos AS sl ON sl.sighting_id = s.id WHERE sl.loco_id IN (SELECT loco_id FROM loco_unit WHERE class_id = ?)";
            
            $return = array(); 
            
            foreach ($this->db->fetchAll($query, $class_id) as $row) {
                $return[] = $row['sighting_id'];
            }
            
            return $return;
        }
        
        /**
         * Get sightings for a locomotive
         * @since Version 3.10.0
         * @param \Railpage\Locos\Locomotive $Loco
         * @return array
         */
        
        public static function getLocoSightings(Locomotive $Loco) {
            
            $Config = AppCore::GetConfig(); 
            $SphinxPDO = new PDO("mysql:host=" . $Config->Sphinx->Host . ";port=9312"); 
            
            $stmt = $SphinxPDO->prepare("SELECT *, IN(loco_ids, :loco_id) AS p FROM idx_sightings WHERE p = 1 ORDER BY date_unix DESC");
            $stmt->bindParam(":loco_id", $Loco->id, PDO::PARAM_INT);
            $stmt->execute(); 
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sightings = array(); 

            foreach ($rs as $row) {
                $Sighting = new Sighting($row['id']); 
                $sightings[] = $Sighting->getArray(); 
            }
            
            return $sightings;
            
        }
    }
    
    