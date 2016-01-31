<?php
    /**
     * Timetables parent class
     * @since Version 3.9
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Timetables;
    
    use Exception;
    use DateTime;
    use DateInterval;
    use Railpage\AppCore;
    use Railpage\Module;
    use Railpage\Url;
    use Railpage\Place;
    use Railpage\Locations\Locations;
    use Railpage\Locations\Location;
    use Railpage\Locos\LocoClass;
    use Railpage\Locos\Locomotive;
    use Railpage\Organisations\Organisation;
    
    /**
     * Timetables
     */
    
    class Timetables extends AppCore {
        
        /**
         * Constructor
         * @since Version 3.9
         */
        
        public function __construct() {
            parent::__construct(); 
            
            $this->Module = new Module("timetables");
            
            $this->url = new Url($this->Module->url);
            $this->url->import = sprintf("%s?mode=import", $this->url->url);
            $this->url->location = sprintf("%s?mode=location", $this->url->url);
            $this->url->pointnogeo = sprintf("%s?mode=point.nogeo", $this->url->url);
        }
        
        /**
         * Set the timetabling point
         * @since Version 3.9
         * @param \Railpage\Timetables\Point $Point
         */
        
        public function setPoint(Point $Point) {
            $this->Point = $Point;
            
            return $this;
        }
        
        /**
         * Set the timetabled train
         * @since Version 3.9
         * @param \Railpage\Timetables\Train $Train
         */
        
        public function setTrain(Train $Train) {
            $this->Train = $Train;
            
            return $this;
        }
        
        /**
         * Set the organisation operating this train
         * @since Version 3.9
         * @param \Railpage\Organisations\Organisation $Organisation
         */
        
        public function setOrganisation(Organisation $Organisation) {
            $this->Organisation = $Organisation;
            
            return $this;
        }
        
        /**
         * Get upcoming timetable events
         * @since Version 3.9
         * @param \DateTime $Date If not supplied this parameter defaults to 60 minutes from now
         * @return array
         */
        
        public function getUpcoming($Date = false) {
            if (!$Date) {
                $Date = new DateTime;
                $Date->add(new DateInterval("PT60M"));
            }
            
            $Now = new DateTime; 
            
            $query = "SELECT t.id, t.provider, t.train_number, t.provider AS train_provider, t.operator_id, t.meta, t.commodity, e.point_id, p.name AS point_name, p.lat, p.lon, e.time, e.going 
                        FROM timetable_trains AS t 
                        LEFT JOIN timetable_entries AS e ON e.train_id = t.id
                        LEFT JOIN timetable_points AS p ON e.point_id = p.id
                        WHERE e.day = ? 
                            AND e.time >= ?
                            AND e.time < ?
                        GROUP BY t.id
                        ORDER BY e.time, t.train_number";
            
            $where = array(
                $Date->format("N"),
                $Now->format("H:i:s"),
                $Date->format("H:i:s")
            );
            
            $results = $this->db->fetchAll($query, $where);
            $return = array();
            
            foreach ($results as $event) {
                $Train = new Train($event['train_number'], $event['train_provider']);
                $Point = new Point($event['point_id']);
                
                $row = array(
                    "train" => array(
                        "id" => $event['id'],
                        "number" => $event['train_number'],
                        "commodity" => $event['commodity'],
                        "meta" => json_decode($event['meta'], true),
                        "operator" => array(
                            "id" => $event['operator_id']
                        ),
                        "url" => $Train->url->getURLs()
                    ),
                    "point" => array(
                        "id" => $event['point_id'],
                        "name" => $event['point_name'],
                        "lat" => $event['lat'],
                        "lon" => $event['lon'],
                        "url" => $Point->url->getURLs()
                    ),
                    "date" => array(
                        "time" => $event['time'],
                        "going" => $event['going']
                    )
                );
                
                $return[] = $row;
            }
            
            return $return;
        }
        
        /**
         * Get timetable points without any geodata
         * @since Version 3.9
         * @return \Railpage\Timetables\Point
         */
        
        public function yieldPointsWithoutGeodata() {
            $query = "SELECT id FROM timetable_points WHERE lat = '0.0000000000000' OR lon = '0.0000000000000' ORDER BY name";
            
            foreach ($this->db->fetchAll($query) as $row) {
                yield new Point($row['id']);
            }
        }
    }
    