<?php
    /**
     * Locations module 
     * @since Version 3.0
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Locations;
    
    use Exception;
    use Railpage\AppCore;
    use Railpage\Module;
    use Railpage\Place;
    use Railpage\Users\User;
    use Railpage\Users\Factory as UsersFactory;
    use Railpage\Debug;
    use Railpage\ISO\ISO_3166;
    
    /**
     * Base Locations class
     * @since Version 3.0
     */
    
    class Locations extends AppCore {
        
        /**
         * Database table
         * @since Version 3.0
         * @var string $table
         */
        
        public $table = "location";
        
        /**
         * Radius around location to find photos within
         * @since Version 3.0
         * @var float $photoRadius
         */
        
        public $photoRadius = 1; 
        
        /**
         * Constructor
         * @since Version 3.8.7
         */
        
        public function __construct() {
            parent::__construct(); 
            
            $this->Module = new Module("locations");
            $this->namespace = $this->Module->namespace;
        }
        
        /**
         * Get a list of countries in the locations database
         * @since Version 3.0
         * @return array
         */
         
        public function getCountries() {
            
            $timer = Debug::GetTimer();
            
            $mckey = "railpage:locations.countries";
            
            if ($return = $this->Memcached->fetch($mckey)) {
                // Do nothing
            } else {
                $return = array(); 
                
                foreach ($this->db->fetchAll("SELECT DISTINCT country FROM location ORDER BY country") as $row) {
                    $return[] = $row['country']; 
                } 
                    
                $this->Memcached->save($mckey, $return, strtotime("+1 day"));
            }
            
            Debug::LogEvent(__METHOD__, $timer);
            
            return $return;
        }
        
        /**
         * Get the regions in the locations database.
         * If $country is not specified, it will return all regions for all countries
         * @since Version 3.0
         * @param string $country An optional two letter country code we want to search for
         * @return array
         */
         
        public function getRegions($country = false) {
            
            $timer = Debug::GetTimer();
            
            $return = false;
            $mckey  = ($country) ? "railpage:locations.regions.country=" . $country : "railpage:locations.regions";
            
            if ($return = $this->Memcached->fetch($mckey)) {
                return $return;
            }
            
            $return = array(); 
            
            if ($country) {
                foreach ($this->db->fetchAll("SELECT DISTINCT region FROM location WHERE country = ? AND active = 1 ORDER BY region ASC", $country) as $row) {
                            
                    $woe = Place::getWOEData($country);
                    if (isset($woe['places']['place'][0])) {
                        $return[$country]['woe'] = $woe['places']['place'][0];
                    }
                    
                    $datarow = array(
                        "region" => $row['region'],
                        "url" => $this->makeRegionPermalink($country, $row['region']),
                        "count" => $this->db->fetchOne("SELECT COUNT(id) FROM location WHERE country = ? AND region = ?", array($country, $row['region'])),
                    );
                    
                    $woe = Place::getWOEData($row['region'] . "," . $country); 
                    if (isset($woe['places']['place'][0])) {
                        $datarow['woe'] = $woe['places']['place'][0];
                    }
                    
                    $return[$country]['children'][] = $datarow;
                }
                
                $this->Memcached->save($mckey, $return, strtotime("+1 day"));
                
                Debug::LogEvent(__METHOD__ . "(" . $country . ")", $timer);
                
                return $return;
            }
                
            $query = "SELECT DISTINCT l.region, l.country, g.country_name, g.region_name 
                FROM location AS l 
                LEFT JOIN geoplace AS g ON l.geoplace = g.id 
                WHERE l.active = 1 
                GROUP BY l.country 
                ORDER BY l.region DESC";
            
            foreach ($this->db->fetchAll($query) as $row) {
                if (empty($row['country'])) {
                    continue;
                }
                
                $return[$row['country']]['woe'] = array(
                    "country" => $row['country_name']
                );
                
                if (empty($return[$row['country']]['woe']['country'])) {
                    $woe = Place::getWOEData(strtoupper($row['region']));
                    $return[$row['country']]['woe'] = array(
                        "country" => $woe['places']['place'][0]['country']
                    );
                }
                
                $return[$row['country']]['children'][] = $row['region']; 
            }
            
            // Cache it
            $this->Memcached->save($mckey, $return, strtotime("+1 day"));
            
            Debug::LogEvent(__METHOD__ . "(" . $country . ")", $timer);
            
            return $return;
        }
        
        /**
         * Get locations within region
         * @since Version 3.0
         * @param string $region
         * @param string $country
         * @return array
         */
         
        public function getLocations($region = false, $country = false) {
            
            if (!$region || !$country) {
                return false;
            }
            
            $timer = Debug::GetTimer();
            
            $mckey = "railpage:locations";
            if ($country) $mckey .= ".country=" . $country;
            if ($region) $mckey .= ".region=" . $region; 
            
            $query = "SELECT * FROM location WHERE country = ? AND region = ? AND active = 1 ORDER BY locality, neighbourhood";
            
            $return = $this->db->fetchAll($query, array($country, $region));
            
            Debug::LogEvent(__METHOD__, $timer);
            
            return $return; 
            
        }
        
        /**
         * Get all pending locations
         * @since Version 3.0
         * @return array
         */
         
        public function getPending() {
            
            $query = "SELECT l.*, u.username 
                    FROM location AS l 
                    INNER JOIN nuke_users AS u ON u.user_id = l.user_id 
                    WHERE l.active = 0 
                    ORDER BY l.date_added";
            return $this->db->fetchAll($query);
            
        }
        
        /**
         * Get a specific location
         * @since Version 3.0
         * @param int $id
         * @param boolean $pending
         * @return array
         */
         
        public function getSite($id = false, $pending = false) {
            if (!$this->db) {
                return false;
            }
            
            $timer = Debug::GetTimer(); 
            
            $query  = "SELECT location.*, count(locations_like.location_id) AS likes FROM location LEFT JOIN locations_like ON location.id = locations_like.location_id";
            $params = array();
            $args   = array();
            
            if ($id) {
                $args[]     = "location.id = ?";
                $params[]   = $id;
            }
            
            if (!$pending) {
                $args[] = "location.active = 1";
            }
            
            if (count($args)) {
                $query .= " WHERE ".implode(" AND ", $args); 
            }
            
            $query .= " GROUP BY 1";
            
            $return = array(); 
            
            foreach ($this->db->fetchAll($query, $params) as $row) {
                $row['url'] = sprintf("%s/%s", $this->makeRegionPermalink($row['country'], $row['region']), $row['slug']);
                $return[$row['id']] = $row; 
            }
            
            if (count($return) && $id) {
                // Set the photo radius based on the zoom level
                $zoom = $return[$id]['zoom']; 
                
                // Rough guide, my mathematics sucks - 11 = 1
                $photo_radius = 11; 
                
                $zoom = 100 - ($photo_radius / $zoom * 100); 
                $zoom = round(1 - $zoom / 100, 2); 
                $this->photoRadius = $zoom; 
            }
            
            Debug::LogEvent(__METHOD__, $timer);
            
            return $return;
        }
        
        /**
         * Find a location from a given latitude and longitude
         * 
         * This function uses mysqli::multi_query() as it was buggering up all subsequent SQL queries on the page
         * @since Version 3.0.1
         * @version 3.0.1
         * @param string $lat
         * @param string $lon
         * @param int $distance
         * @param int $num
         * @return array
         */
         
        public function getSiteFromCoords($lat = false, $lon = false, $distance = 1, $num = 5) {
            if (!$lat || !$lon || !$this->db) {
                return false;
            }
            
            $timer = Debug::GetTimer(); 
            
            $mckey = "rp-locations-geolookup-lat:" . $lat . "-lon:" . $lon . "-dist:" . $distance . "-num:" . $num;
            
            if (!$return = $this->Memcached->fetch($mckey)) {
                $query = "SELECT location.*, 
                    3956 * 2 * ASIN(
                        SQRT(
                            POWER(
                            SIN(
                                (" . $lat . " - location.lat) * pi() / 180 / 2
                            ), 2
                            ) + COS(
                                " . $lat . " * pi() / 180
                            ) * COS(
                                location.lat * pi() / 180
                            ) * POWER(
                                SIN(
                                    (" . $lon . " - location.long) * pi() / 180 / 2
                                ), 2
                            )
                        )
                    ) AS distance 
                    FROM location 
                    WHERE 
                        location.long BETWEEN (
                            " . $lon . " - " . $distance . " / abs(cos(radians(" . $lat . ")) * 69)
                        ) AND ( 
                            " . $lon . " + " . $distance . " / abs(cos(radians(" . $lat . ")) * 69)
                        )
                        AND location.lat BETWEEN (
                            " . $lat . " - (" . $distance . " / 69) 
                        ) AND ( 
                            " . $lat . " + (" . $distance . " / 69)
                        )
                    HAVING distance < ? 
                    ORDER BY distance
                    LIMIT ?";
                
                $params = array(
                    $distance,
                    $num
                );
                
                $return = $this->db->fetchAll($query, $params); 
                
                $this->Memcached->save($mckey, $return, 0);
            }
            
            Debug::LogEvent(__METHOD__, $timer);
            
            return $return;
        }
        
        /**
         * Find a nearby location
         * @since Version 3.0
         * @param float $lat
         * @param float $lon
         * @param int $radius
         * @return array
         * @throws \Exception if $lat has not been provided
         * @throws \Exception if $lon has not been provided
         */
         
        public function nearby($lat = false, $lon = false, $radius = false) {
            
            if (!$lat) {
                throw new Exception("Cannot fetch locations near co-ordinates: no latitude value given");
            }
            
            if (!$lon) {
                throw new Exception("Cannot fetch locations near co-ordinates: no longitude value given");
            }
            
            if (!$radius) {
                $radius = $this->photoRadius; 
            }
            
            $timer = Debug::GetTimer(); 
            
            $result = false;
                
            $min_lat    = $lat - $radius;
            $max_lat    = $lat + $radius;
            $min_long   = $lon - $radius;
            $max_long   = $lon + $radius;
            
            $query = "SELECT * FROM location WHERE `lat` > ? AND `lat` < ? AND `long` > ? AND `long` < ?"; 
            $params = array(
                $min_lat, 
                $max_lat,
                $min_long,
                $max_long
            );
            
            $return = array(); 
            
            foreach ($this->db->fetchAll($query, $params) as $row) {
                $row['url'] = sprintf("%s/%s", $this->makeRegionPermalink($row['country'], $row['region']), $row['slug']);
                $return[$row['id']] = $row; 
            }
            
            Debug::LogEvent(__METHOD__, $timer);
            
            return $return;
        }       
        /**
         * Get newest locations
         * @param int $limit
         */
        
        public function newest($limit = 5) {
            
            $timer = Debug::GetTimer(); 
            
            $query = "SELECT * FROM location WHERE active = 1 ORDER BY date_added DESC LIMIT ?";
            
            $return = $this->db->fetchAll($query, $limit); 
                
            Debug::LogEvent(__METHOD__, $timer);
            
            return $return;
        }
        
        /**
         * Get location ID from slug
         * @since Version 3.7.5
         * @param string $slug
         * @return int
         * @throws \Exception if $slug has not been provided
         */
        
        public function getIdFromSlug($slug = false) {
            if (!$slug || empty($slug)) {
                throw new Exception("\$slug cannot be empty or unset - required to find location ID");
                return false;
            }
            
            $query = "SELECT id FROM location WHERE slug = ?";
            
            return $this->db->fetchOne($query, $slug); 
        }
        
        /**
         * Make a permalink for this location
         * @since Version 3.7.5
         * @return string
         * @param int $id Optional location ID - inherited by 
         *    Railpage\Locations\Location so will attempt to use $this->id if none provided
         */
        
        public function makePermalink($id = false) {
            $mckey = $id ? "railpage:locations.permalink.id=" . $id : "railpage:locations.permalink.id=" . $this->id;
            
            $timer = Debug::GetTimer(); 
            
            if (!$string = $this->Memcached->fetch($mckey)) {
                if ((!isset($this->country) || !isset($this->region) || !isset($this->slug)) && $id) {
                    // Fetch it from the database
                    
                    $query = "SELECT country, region, slug FROM location WHERE id = ?";
                    $data = $this->db->fetchRow($query, $id); 
                
                    if (empty($data['slug'])) {
                        $Location = new Location($id);
                        $data['slug'] = $Location->slug; 
                    }
                } else {
                    $data['country'] = $this->country;
                    $data['region'] = $this->region; 
                    $data['slug'] = $this->slug; 
                }
                
                $params = [ 
                    $this->Module->url, 
                    str_replace(" ", "-", $data['country']), 
                    str_replace(" ", "-", $data['region']), 
                    $data['slug']
                ];
                
                $string = strtolower(vsprintf("%s/%s/%s/%s", $params));
                
                $this->Memcached->save($mckey, $string, strtotime("+1 year"));
            }
            
            Debug::LogEvent(__METHOD__, $timer);
            
            return $string;
        }
        
        /**
         * Region permalink
         * @since Version 3.7.5
         * @return string
         * @param string $country
         * @param string $region
         */
        
        public function makeRegionPermalink($country = false, $region = false) {
            if (!$region && isset($this->region) && !empty($this->region)) {
                $region = $this->region;
            }
            
            if (!$country && isset($this->country) && !empty($this->country)) {
                $country = $this->country;
            }
            
            if (!$region || !$country) {
                return false;
            }
            
            return strtolower(sprintf("%s/%s/%s", $this->Module->url, self::create_slug($country), $this->makeRegionSlug($region)));
        }
        
        /**
         * Make a region slug
         * @since Version 3.8.7
         * @param string $region
         * @return string
         */
        
        public function makeRegionSlug($region = false) {
            if (!$region && isset($this->region) && !empty($this->region)) {
                $region = $this->region;
            }
            
            if (!$region) {
                return false;
            }
            
            return self::create_slug($region);
        }
        
        /**
         * Get date types
         * @since Version 3.8.7
         * @return array
         */
        
        public function getDateTypes() {
            $query = "SELECT * FROM location_datetypes ORDER BY name";
            
            return $this->db->fetchAll($query);
        }
        
        /**
         * Get a random location from the database
         */
        
        public function getRandomLocation() {
            $query = "SELECT id, `desc` FROM location WHERE active = 1";
            $locations = array();
            
            foreach ($this->db->fetchAll($query) as $row) {
                if (!empty($row['desc']) && strlen($row['desc']) > 5) {
                    $locations[] = $row['id'];
                }
            }
            
            return new Location(array_rand($locations));
        }
        
        /**
         * Get all open corrections
         * @since Version 3.9.1
         * @return array
         */
        
        public function getOpenCorrections() {
            
            $query = "SELECT l.name AS location_name, u.username, c.* 
                    FROM location_corrections AS c 
                    LEFT JOIN location AS l ON c.location_id = l.id 
                    LEFT JOIN nuke_users AS u ON u.user_id = c.user_id 
                    WHERE c.status = ? 
                    ORDER BY c.date_added DESC";
            
            $return = array(); 
            
            foreach ($this->db->fetchAll($query, Correction::STATUS_NEW) as $row) {
                $return[] = array(
                    "location" => (new Location($row['location_id']))->getArray(),
                    "author" => UsersFactory::CreateUser($row['user_id'])->getArray(),
                    "correction" => array(
                        "comments" => $row['comments'],
                        "date" => array(
                            "added" => $row['date_added'],
                            "closed" => $row['date_closed']
                        )
                    )
                );
            }
            
            return $return;
            
        }
    }
    