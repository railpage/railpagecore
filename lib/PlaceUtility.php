<?php
    /**
     * Find a geographic place closest to the provided co-ordinates
     * @since Version 3.9.1
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage;
    
    use Railpage\AppCore;
    use Railpage\Debug;
    use Railpage\Place;
    use Exception;
    use InvalidArgumentException;
    use DateTime;
    use DateTimeZone;
    use Zend_Db_Expr;
    use GuzzleHttp\Client;
    use GuzzleHttp\Exception\RequestException;
    
    class PlaceUtility {
        
        /**
         * Get WoE data for a latitude/longitude lookup
         * @since Version 3.9.1
         * @param float $lat
         * @param float $lon
         * @return array
         */
        
        public static function LatLonWoELookup($lat, $lon, $force = false) {
            
            if (is_null($lon) && strpos($lat, ",") !== false) {
                $tmp = explode(",", $lat); 
                $lat = $tmp[0];
                $lon = $tmp[1];
            }
            
            $Config = AppCore::getConfig(); 
            $Redis = AppCore::getRedis();
            
            $placetypes = array(7,8,9,10,22,24); 
            
            $mckey = sprintf("railpage:woe=%s,%s;types=", $lat, $lon, implode(",", $placetypes)); 
            
            if ($force || !$return = $Redis->fetch($mckey)) {
                
                $url = sprintf("http://where.yahooapis.com/v1/places\$and(.q('%s,%s'),.type(%s))?lang=en&appid=%s&format=json", $lat, $lon, implode(",", $placetypes), $Config->Yahoo->ApplicationID);
                
                $dbresult = self::getWoeFromCache($lat, $lon); 
                
                if (is_array($dbresult)) {
                    return $dbresult;
                }
                
                /**
                 * Try and fetch using GuzzleHTTP from the web service
                 */
                
                try {
                    $GuzzleClient = new Client;
                    $response = $GuzzleClient->get($url);
                } catch (RequestException $e) {
                    switch ($e->getResponse()->getStatusCode()) {
                        case 503 : 
                            throw new Exception("Your call to Yahoo Web Services failed and returned an HTTP status of 503. That means: Service unavailable. An internal problem prevented us from returning data to you.");
                            break;
                        
                        case 403 : 
                            throw new Exception("Your call to Yahoo Web Services failed and returned an HTTP status of 403. That means: Forbidden. You do not have permission to access this resource, or are over your rate limit.");
                            break;
                        
                        case 400 : 
                            if (!$return = self::getViaCurl($url)) {
                                throw new Exception(sprintf("Your call to Yahoo Web Services failed (zomg) and returned an HTTP status of 400. That means:  Bad request. The parameters passed to the service did not match as expected. The exact error is returned in the XML/JSON response. The URL sent was: %s\n\n%s", $url, json_decode($e->getResponse()->getBody())));
                            }
                            
                            break;
                        
                        default : 
                            throw new Exception("Your call to Yahoo Web Services returned an unexpected HTTP status of: " . $response->getStatusCode());
                    }
                }
                
                if (!$return && isset($response) && $response->getStatusCode() == 200) {
                    $return = json_decode($response->getBody(), true);
                }
                
                /**
                 * Save it in the database
                 */
                
                if (!empty($lat) && !empty($lon)) {
                    $Database = (new AppCore)->getDatabaseConnection(); 
                    
                    // Stop ZF1 with MySQLi adapter from bitching about "invalid parameter: 3". Fucks sake. Sort your shit out.
                    unset($return['places']['start']); unset($return['places']['count']); unset($return['places']['total']); 
                    
                    $query = "INSERT INTO woecache (
                                lat, lon, response, stored, address
                            ) VALUES (
                                %s, %s, %s, NOW(), NULL
                            ) ON DUPLICATE KEY UPDATE
                                response = VALUES(response),
                                stored = NOW()";
                    
                    $query = sprintf($query, $Database->quote($lat), $Database->quote($lon), $Database->quote(json_encode($return))); 
                    
                    try {
                        $rs = $Database->query($query); 
                    } catch (Exception $e) {
                        // throw it away
                    }
                }
                
                $return['url'] = $url;
            
                if ($return !== false) {
                    $Redis->save($mckey, $return, 0); 
                }
            }

            return $return;
            
        }
        
        /**
         * Format WoE data
         * @since Version 3.9.1
         * @param array $woe
         * @return array
         */
        
        public static function formatWoE($woe) {
            
            $thiswoe = $woe['places']['place'][0];
            
            $data = array(
                "country_code" => $thiswoe['country attrs']['code'],
                "country_name" => $thiswoe['country'],
                "timezone" => $thiswoe['timezone'],
                "neighbourhood" => $thiswoe['name'],
            );
            
            $prev = NULL;
            foreach ($thiswoe as $key => $val) {
                if (isset($val['type']) && strtolower($val['type']) != "country") {
                    $data['region_code'] = str_replace($data['country_code'] . "-", "", $val['code']);
                    
                    $data['region_name'] = $prev;
                    break;
                }
                
                $prev = $val;
            }
            
            if ($thiswoe['placeTypeName'] == "Point of Interest") {
                foreach ($thiswoe as $key => $val) {
                    if (is_array($val) && isset($val['type']) && $val['type'] == "Town") {
                        break;
                    }
                    
                    if (!is_array($val)) {
                        $data['neighbourhood'] = $val;
                    }
                }
            }
            
            return $data;
            
        }
        
        /**
         * Get an instance of \Railpage\Place representing this geographic location
         * @since Version 3.9.1
         * @return int
         */
        
        public static function findGeoPlaceID($lat, $lon) {
            
            if (!filter_var($lat, FILTER_VALIDATE_FLOAT) || !filter_var($lon, FILTER_VALIDATE_FLOAT)) {
                throw new InvalidArgumentException("Invalid geographic co-ordinates");
            }
            
            #$Place = new Place;
            #$woe = $Place->getWOEData($lat . "," . $lon, array(7,8,9,10,22,24)); 
            $woe = self::LatLonWoELookup($lat, $lon);
            
            if (!isset($woe['places']['place'][0])) {
                return;
            }
            
            $data = self::formatWoE($woe);
            
            $thiswoe = $woe['places']['place'][0];
            
            $data = array_merge($data, array(
                "point" => $thiswoe['centroid'],
                "bb_southwest" => $thiswoe['boundingBox']['southWest'],
                "bb_northeast" => $thiswoe['boundingBox']['northEast'],
            ));
            
            /**
             * Find this place
             */
            
            $data = array_merge($data, array(
                "point" => new Zend_Db_Expr(sprintf("GeomFromText('POINT(%s %s)')", $data['point']['latitude'], $data['point']['longitude'])),
                "bb_southwest" => new Zend_Db_Expr(sprintf("GeomFromText('POINT(%s %s)')", $data['bb_southwest']['latitude'], $data['bb_southwest']['longitude'])),
                "bb_northeast" => new Zend_Db_Expr(sprintf("GeomFromText('POINT(%s %s)')", $data['bb_northeast']['latitude'], $data['bb_northeast']['longitude'])),
            ));
            
            $Database = (new AppCore)->getDatabaseConnection();
            
            $query = "SELECT id FROM geoplace WHERE SQRT(POW(X(point) - " . $thiswoe['centroid']['latitude'] . " , 2) + POW(Y(`point`) - " . $thiswoe['centroid']['longitude'] . ", 2)) * 100 < 1";
            
            $id = $Database->fetchOne($query);
            
            if (!$id) {
                $Database->insert("geoplace", $data);
                $id = $Database->lastInsertId(); 
            }
            
            return $id;
            
        }
        
        /**
         * Find the geoplace country name from a country code
         * @since Version 3.9.1
         * @param string $code
         * @return string
         */
        
        public static function getCountryNameFromCode($code) {
            
            $Database = (new AppCore)->getDatabaseConnection();
            
            $query = "SELECT country_name FROM geoplace WHERE country_code = ?";
            
            return $Database->fetchOne($query, $code); 
            
        }
        
        /**
         * Find the geoplace country name from a country code
         * @since Version 3.9.1
         * @param string $region_code
         * @param string|boolean $region_code
         * @return string
         */
        
        public static function getRegionNameFromCode($region_code, $country_code = false) {
            
            $Database = (new AppCore)->getDatabaseConnection();
            
            $query = "SELECT region_name FROM geoplace WHERE region_code = ?";
            $params = [ $region_code ];
            
            if ($country_code) {
                $query .= " AND country_code = ?";
                $params[] = $country_code;
            }
            
            return $Database->fetchOne($query, $params); 
            
        }
        
        /**
         * Try to retrieve the woedata from our local DB cache
         * @since Version 3.10.0
         * @param double $lat
         * @param double $lon
         * @return array
         */
        
        private static function getWoeFromCache($lat, $lon) {
            
            $lat = round(str_pad($lat, 12, 0), 8);
            $lon = round(str_pad($lon, 12, 0), 8);
            
            $Database = (new AppCore)->getDatabaseConnection(); 
            
            $query = "SELECT response FROM woecache WHERE lat = ? AND lon = ?";
            
            $result = $Database->fetchOne($query, array($lat, $lon));
            
            if (!$result) {
                return false;
            }
            
            return json_decode($result, true); 
            
        }
        
        /**
         * Because GuzzleHTTP is annoying and unreliable, fall back to cURL
         * @since Version 3.10.0
         * @param string $url
         * @return array
         */
        
        public static function getViaCurl($url) {
            
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_URL, $url); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            $output = curl_exec($ch); 
            curl_close($ch);
            
            $return = json_decode($output, true);
            
            return $return;
            
        }
        
        /**
         * Google Maps reverse geocode lookup - as a fallback where Yahoo WoE is missing data
         * @since Version 3.10.0
         * @param float $lat
         * @param float $lon
         * @return array
         */
        
        public static function GoogleWoELookup($lat, $lon) {
            
            $url = sprintf("https://maps.googleapis.com/maps/api/geocode/json?latlng=%s,%s&key=AIzaSyC1lUe1h-gwmFqj9xDTDYI9HYVTUxNscCA", $lat, $lon); 
            
            try {
                $GuzzleClient = new Client;
                $response = $GuzzleClient->get($url);
            } catch (RequestException $e) {
                switch ($e->getResponse()->getStatusCode()) {
                    case 503 : 
                        throw new Exception("The call to Google Maps Geocoding API failed and returned an HTTP status of 503. That means: Service unavailable. An internal problem prevented us from returning data to you.");
                        break;
                    
                    case 403 : 
                        throw new Exception("YThe call to Google Maps Geocoding API failed and returned an HTTP status of 403. That means: Forbidden. You do not have permission to access this resource, or are over your rate limit.");
                        break;
                    
                    case 400 : 
                        if (!$return = self::getViaCurl($url)) {
                            throw new Exception(sprintf("The call to Google Maps Geocoding API failed (zomg) and returned an HTTP status of 400. That means:  Bad request. The parameters passed to the service did not match as expected. The exact error is returned in the XML/JSON response. The URL sent was: %s\n\n%s", $url, json_decode($e->getResponse()->getBody())));
                        }
                        
                        break;
                    
                    default : 
                        throw new Exception("The call to Google Maps Geocoding API returned an unexpected HTTP status of: " . $response->getStatusCode());
                }
            }
            
            if (!$return && isset($response) && $response->getStatusCode() == 200) {
                $return = json_decode($response->getBody(), true);
            }
            
            return $return;
            
        }
        
        /**
         * Calculates the great-circle distance between two points, with
         * the Vincenty formula.
         * @param float $latitudeFrom Latitude of start point in [deg decimal]
         * @param float $longitudeFrom Longitude of start point in [deg decimal]
         * @param float $latitudeTo Latitude of target point in [deg decimal]
         * @param float $longitudeTo Longitude of target point in [deg decimal]
         * @param float $earthRadius Mean earth radius in [m]
         * @return float Distance between points in [m] (same as earthRadius)
         */
        
        public static function vincentyGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000) {
            
            // convert from degrees to radians
            $latFrom = deg2rad($latitudeFrom);
            $lonFrom = deg2rad($longitudeFrom);
            $latTo = deg2rad($latitudeTo);
            $lonTo = deg2rad($longitudeTo);
            
            $lonDelta = $lonTo - $lonFrom;
            $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
            $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);
            
            $angle = atan2(sqrt($a), $b);
            return $angle * $earthRadius;
            
        }
        
        /**
         * Get a center latitude,longitude from an array of like geopoints
         *
         * @param array data 2 dimensional array of latitudes and longitudes
         * For Example:
         * $data = array
         * (
         *   0 = > array(45.849382, 76.322333),
         *   1 = > array(45.843543, 75.324143),
         *   2 = > array(45.765744, 76.543223),
         *   3 = > array(45.784234, 74.542335)
         * );
        */
        
        public static function getCenterFromDegrees($data) {
            if (!is_array($data)) return FALSE;
        
            $num_coords = count($data);
        
            $X = 0.0;
            $Y = 0.0;
            $Z = 0.0;
        
            foreach ($data as $coord)
            {
                $lat = $coord[0] * pi() / 180;
                $lon = $coord[1] * pi() / 180;
        
                $a = cos($lat) * cos($lon);
                $b = cos($lat) * sin($lon);
                $c = sin($lat);
        
                $X += $a;
                $Y += $b;
                $Z += $c;
            }
        
            $X /= $num_coords;
            $Y /= $num_coords;
            $Z /= $num_coords;
        
            $lon = atan2($Y, $X);
            $hyp = sqrt($X * $X + $Y * $Y);
            $lat = atan2($Z, $hyp);
        
            return array($lat * 180 / pi(), $lon * 180 / pi());
        }
        
    }