<?php
    /**
     * Geoplace utility
     * @since Version 3.9.1
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Images\Utility;
    
    use Exception;
    use InvalidArgumentException;
    use DateTime;
    use DateTimeZone;
    use Railpage\AppCore;
    use Railpage\Images\ImageFactory;
    use Railpage\Images\Images;
    use Railpage\Images\Image;
    use Railpage\Place;
    use Railpage\Locations\Country;
    use Railpage\Locations\Region;
    
    class Geoplace {
        
        /**
         * Get photo neighbourhoods including photo counts
         * @since Version 3.9.1
         * @return array
         * @param string|null $country
         * @param string|null $region
         */
        
        public static function getNeighbourhoods($country = NULL, $region = NULL) {
            
            $params = array(); 
            
            if (!is_null($country)) {
                $params[] = strtoupper($country);
                $country = " AND country_code = ?";
            }
            
            if (!is_null($region)) {
                $params[] = strtoupper($region);
                $region = " AND region_code = ?";
            }
            
            $Database = (new AppCore)->getDatabaseConnection(); 
            
            $query = "SELECT g.country_code, g.country_name, g.region_code, g.region_name, g.neighbourhood, g.id, 
                    X(g.point) AS lat, Y(g.point) AS lon, g.timezone, COUNT(*) AS count,
                    GROUP_CONCAT(i.id) AS image_ids, CONCAT('/place?lat=', X(g.point), '&lon=', Y(g.point)) AS url
                FROM image AS i
                LEFT JOIN geoplace AS g ON i.geoplace = g.id
                WHERE i.geoplace != 0
                " . $country . "
                " . $region . "
                GROUP BY g.id
                ORDER BY g.country_code, g.region_code, g.neighbourhood";
            
            $result = $Database->fetchAll($query, $params); 
            
            foreach ($result as $key => $val) {
                $result[$key]['image_ids'] = array_slice(explode(",", $val['image_ids']), 0, 5);
                //$result[$key]['photos'] = ImageFactory::GetThumbnails($result[$key]['image_ids']);
                //$result[$key]['photos_html'] = implode("", array_column($result[$key]['photos'], "html"));
            }
            
            return $result;

        }
        
        /**
         * Get regions
         * @since Version 3.9.1
         * @return array
         * @param string|null $country
         */
        
        public static function getRegions($country = NULL) {
            
            $params = []; 
            $where = [];
            
            if (!is_null($country)) {
                $where[] = " country_code = ?";
                $params[] = strtoupper($country); 
            }
            
            $query = "SELECT DISTINCT region_code, region_name, country_code, country_name FROM geoplace";
            
            if (count($where)) {
                $query .= " WHERE " . implode(" AND ", $where); 
            }
            
            $query .= " ORDER BY country_code, region_code";
            
            $Database = (new AppCore)->getDatabaseConnection(); 
            
            $return = array(); 
            
            foreach ($Database->fetchAll($query, $params) as $row) {
                if (!isset($return[$row['country_code']])) {
                    $return[$row['country_code']] = array(
                        "country_code" => $row['country_code'],
                        "country_name" => $row['country_name'],
                        "regions" => []
                    );
                }
                
                $return[$row['country_code']]['regions'][$row['region_code']] = array(
                    "region_code" => $row['region_code'],
                    "region_name" => $row['region_name']
                );
            }
            
            return $return;
            
        }
    }