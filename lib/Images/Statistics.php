<?php

/**
 * Photos statistics
 * @since Version 3.9.1
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Images;

use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Railpage\AppCore;
use Railpage\Debug;
use Railpage\Url;
use Railpage\Users\Utility\UrlUtility as UserUrlUtility;

class Statistics extends AppCore {
    
    /**
     * Chart: number of photos by focal length, grouped into 10mm bands
     * @since Version 3.10.0
     * @const string CHART_PHOTOS_MM
     */
    
    const CHART_PHOTOS_MM = "getPhotosByFocalLength";
    
    /**
     * Chart: number of photos by focal aperture
     * @since Version 3.10.0
     * @const string CHART_PHOTOS_APERTURE
     */
    
    const CHART_PHOTOS_APERTURE = "getPhotosByAperture";
    
    /**
     * Chart: number of photos by exposure program
     * @since Version 3.10.0
     * @const string CHART_PHOTOS_EXPOSURE_PROGRAM
     */
    
    const CHART_PHOTOS_EXPOSURE_PROGRAM = "getPhotosByExposureProgram";
    
    /**
     * Chart: number of known camera models, grouped by image author
     * @since Version 3.10.0
     * @const string CHART_UNIQUE_CAMERAS
     */
    
    const CHART_CAMERAS_POPULAR = "getCameraModelsByAuthor";
    
    /**
     * Chart: number of known camera models, grouped by total number of photos
     * @since Version 3.10.0
     * @const string CHART_CAMERAS_NUMPHOTOS
     */
    
    const CHART_CAMERAS_NUMPHOTOS = "getCameraModelsByPhotos";
    
    /**
     * Hits: daily
     * @since Version 3.10.0
     * @const string HITS_DAILY
     */
    
    const HITS_DAILY = "daily";
    
    /**
     * Hits: weekly
     * @since Version 3.10.0
     * @const string HITS_WEEKLY
     */
    
    const HITS_WEEKLY = "weekly";
    
    /**
     * Hits: overall
     * @since Version 3.10.0
     * @const string HITS_OVERALL
     */
    
    const HITS_OVERALL = "overall";
    
    /**
     * Get the number of geotagged photos in each region
     * @since Version 3.9.1
     * @return array
     */
    
    public function getNumPhotosByRegion() {
        
        $query = "SELECT g.country_code, g.country_name, g.region_code, g.region_name, COUNT(*) AS count
            FROM image AS i
            LEFT JOIN geoplace AS g ON i.geoplace = g.id
            WHERE i.geoplace != 0
            GROUP BY g.region_code
            ORDER BY g.country_code, g.region_code";
        
        return $this->db->fetchAll($query); 
        
    }
    
    /**
     * Get quantities
     * @since Version 3.9.1
     * @return array
     */
    
    public function getQuantities() {
        
        $query = 'SELECT "Total photos" AS title, FORMAT(COUNT(*), 0) AS num FROM image
            UNION SELECT "Photos with latitude/longitude" AS title, FORMAT(COUNT(*), 0) AS num FROM image WHERE ROUND(lat) != 0
            UNION SELECT "Photos without latitude/longitude" AS title, FORMAT(COUNT(*), 0) AS num FROM image WHERE ROUND(lat) = 0
            UNION SELECT "Photos with a geoplace" AS title, FORMAT(COUNT(*), 0) AS num FROM image WHERE geoplace != 0
            UNION SELECT "Photos photos of locomotives" AS title, FORMAT(COUNT(*), 0) AS num FROM image AS i LEFT JOIN image_link AS il ON i.id = il.image_id WHERE il.namespace = "railpage.locos.loco" AND ignored = 0
            UNION SELECT "Photos photos of loco liveries" AS title, FORMAT(COUNT(*), 0) AS num FROM image AS i LEFT JOIN image_link AS il ON i.id = il.image_id WHERE il.namespace = "railpage.locos.liveries.livery" AND ignored = 0
            UNION SELECT "Most photographed locomotive" AS title, l.loco_num AS num FROM loco_unit AS l LEFT JOIN loco_class AS c ON l.class_id = c.id WHERE l.loco_id = ( SELECT namespace_key FROM image_link WHERE namespace = "railpage.locos.loco" GROUP BY namespace_key ORDER BY COUNT(*) DESC, namespace_key LIMIT 0,1 )
            UNION SELECT "Most photographed loco class" AS title, c.name AS num FROM loco_class AS c WHERE c.id = ( SELECT namespace_key FROM image_link WHERE namespace = "railpage.locos.class" GROUP BY namespace_key ORDER BY COUNT(*) DESC, namespace_key LIMIT 0,1 )
            UNION SELECT * FROM (SELECT "Most popular camera" AS title, CONCAT(camera_make, " ", camera_model) AS num FROM (
SELECT DISTINCT e.camera_id, i.user_id, c.make AS camera_make, c.model AS camera_model
FROM image_exif AS e 
LEFT JOIN image_camera AS c ON c.id = e.camera_id
LEFT JOIN image AS i on e.image_id = i.id 
WHERE e.camera_id != 0 
AND i.user_id != 0
) AS dist WHERE camera_make != "Unknown" GROUP BY camera_id ORDER BY COUNT(*) DESC LIMIT 0, 1) AS camera';
        
        return $this->db->fetchAll($query); 
        
    }
    
    /**
     * Get biggest contributors
     * @since Version 3.9.1
     * @return array
     */
    
    public function getContributors() {
        
        $query = "SELECT u.username, u.user_id, CONCAT('/user/', u.user_id) AS url, COUNT(*) AS num FROM nuke_users AS u LEFT JOIN image AS i ON i.user_id = u.user_id WHERE i.user_id != 0 AND i.provider != 'rpoldgallery' GROUP BY u.user_id ORDER BY num DESC LIMIT 0, 10";
        
        return $this->db->fetchAll($query); 
        
    }
    
    /**
     * Get biggest contributors
     * @since Version 3.9.1
     * @return array
     */
    
    public function getContributorWithTaggedPhotos() {
        
        $query = "SELECT u.username, u.user_id, CONCAT('/user/', u.user_id) AS url, COUNT(*) AS num 
            FROM nuke_users AS u 
            LEFT JOIN image AS i ON i.user_id = u.user_id 
            LEFT JOIN image_link AS il ON i.id = il.image_id
            WHERE i.user_id != 0 
                AND i.provider != 'rpoldgallery' 
                AND il.id != 0
            GROUP BY u.user_id 
            ORDER BY num DESC 
            LIMIT 0, 10";
        
        return $this->db->fetchAll($query); 
        
    }
    
    /**
     * Get chart data
     * @since Version 3.10.0
     * @param string $chart
     * @return array
     */
    
    public function getChartData($chart) {
        
        if (method_exists($this, $chart)) {
            return $this->$chart(); 
        }
        
    }
    
    /**
     * Get camera models by number of unique authors
     * @since Version 3.10.0
     * @return array
     */
    
    private function getCameraModelsByAuthor() {
        
        $query = 'SELECT CONCAT(camera_make, " ", camera_model) AS `key`, COUNT(*) AS number, href FROM (
            SELECT DISTINCT e.camera_id, i.user_id, c.make AS camera_make, c.model AS camera_model, CONCAT("/photos/cameras/", c.url_slug) AS href
            FROM image_exif AS e 
            LEFT JOIN image_camera AS c ON c.id = e.camera_id
            LEFT JOIN image AS i on e.image_id = i.id 
            WHERE e.camera_id != 0 
            AND i.user_id != 0
        ) AS dist WHERE camera_make != "Unknown" GROUP BY camera_id ORDER BY COUNT(*) DESC';
        
        $result = $this->db->fetchAll($query); 
        $cameras = array_slice($result, 0, 10);
        $cameras[10] = [ "key" => "Other", "number" => 0 ];
        
        foreach (array_slice($result, 11) as $row) {
            $cameras[10]['number'] += $row['number'];
        }
        
        return $cameras;
        
    }
    
    /**
     * Get camera models by number of photos
     * @since Version 3.10.0
     * @return array
     */
    
    private function getCameraModelsByPhotos() {
        
        $query = 'SELECT CONCAT(camera_make, " ", camera_model) AS `key`, COUNT(*) AS number, href FROM (
            SELECT e.camera_id, i.user_id, c.make AS camera_make, c.model AS camera_model, CONCAT("/photos/cameras/", c.url_slug) AS href
            FROM image_exif AS e 
            LEFT JOIN image_camera AS c ON c.id = e.camera_id
            LEFT JOIN image AS i on e.image_id = i.id 
            WHERE e.camera_id != 0
        ) AS dist WHERE camera_make != "Unknown" GROUP BY camera_id ORDER BY COUNT(*) DESC';
        
        $result = $this->db->fetchAll($query); 
        $cameras = array_slice($result, 0, 10);
        $cameras[10] = [ "key" => "Other", "number" => 0 ];
        
        foreach (array_slice($result, 11) as $row) {
            $cameras[10]['number'] += $row['number'];
        }
        
        return $cameras;
        
    }
    
    /**
     * Get photos by the focal length, grouped into 10mm bands
     * @since Version 3.10.0
     * @return array
     */
    
    private function getPhotosByFocalLength() {
        
        $query = "SELECT FLOOR(focal_length / 10) * 10 AS `key`, COUNT(*) AS `val` FROM image_exif WHERE focal_length > 10 AND focal_length <= 500 GROUP BY `key`";
        
        return $this->db->fetchAll($query); 
        
    }
    
    /**
     * Get photos by their aperture
     * @since Version 3.10.0
     * @return array
     */
    
    private function getPhotosByAperture() {
        
        $query = "SELECT * FROM (SELECT aperture AS `key`, COUNT(*) AS `val` FROM image_exif WHERE aperture > 0 AND aperture < 40 GROUP BY `key`) AS ap WHERE `val` > 1";
        
        return $this->db->fetchAll($query); 
        
    }
    
    /**
     * Get photos by exposure program
     * @since Version 3.10.0
     * @return array
     */
    
    private function getPhotosByExposureProgram() {
        
        $query = "SELECT ep.program AS `key`, COUNT(*) AS `number` FROM image_exif AS ef LEFT JOIN image_exposure_program AS ep ON ef.exposure_program_id = ep.id WHERE ep.program != \"Unknown\" GROUP BY ep.id ORDER BY COUNT(*) DESC LIMIT 0, 7";
        
        $return = [];
        
        foreach ($this->db->fetchAll($query) as $row) {
            if (preg_match("/([a-zA-Z])/", $row['key'])) {
                $return[] = $row;
            }
        }
        
        return $return;
        
    }
    
    /**
     * Get stats for a given camera
     * @since Version 3.10.0
     * @param \Railpage\Images\Camera $cameraObject
     * @return array
     */
    
    public function getStatsForCamera(Camera $cameraObject) {
        
        $query = "(SELECT 'Photos on Railpage' AS label, COUNT(*) AS value FROM image_exif WHERE camera_id = ?)
            UNION (SELECT 'Most used lens' AS label, l.model AS value FROM image_exif AS e LEFT JOIN image_lens AS l ON e.lens_id = l.id WHERE e.camera_id = ? GROUP BY e.lens_id ORDER BY COUNT(*) DESC LIMIT 1)
            UNION (SELECT 'Screener\'s Choice' AS label, COUNT(*) AS value FROM image_flags AS f LEFT JOIN image_exif AS e ON e.image_id = f.image_id WHERE e.camera_id = ? AND f.screened_pick = 1)";
        
        $params[] = $cameraObject->id;
        $params[] = $cameraObject->id;
        $params[] = $cameraObject->id;
        
        $result = $this->db->fetchAll($query, $params); 
        
        foreach ($result as $key => $row) {
            if ($row['value'] === 0) {
                unset($result[$key]); 
                continue;
            }
            
            if (filter_var($row['value'], FILTER_VALIDATE_INT)) {
                $result[$key]['value'] = number_format($row['value'], 0);
            }
        }
        
        return $result;
        
    }
    
    /**
     * Get the top 5 photos viewed today/weekly/overall
     * @since Version 3.10.0
     * @param string $lookup
     * @param int $num
     * @return array
     */
    
    public function getMostViewedPhotos($lookup = self::HITS_WEEKLY, $num = 5) {
        
        $allowed = [ 
            self::HITS_DAILY => "hits_today",
            self::HITS_WEEKLY => "hits_weekly",
            self::HITS_OVERALL => "hits_overall"
        ];
        
        if (!in_array($lookup, array_keys($allowed))) {
            throw new InvalidArgumentException("Parameter supplied for lookup type is invalid"); 
        }
        
        $query = "SELECT i.*,
            u.username,
            f.*
            FROM image AS i
            LEFT JOIN image_flags AS f ON f.image_id = i.id
            LEFT JOIN nuke_users AS u ON i.user_id = u.user_id
            ORDER BY " . $allowed[$lookup] . " DESC 
            LIMIT 0, ?";
        
        $result = $this->db->fetchAll($query, $num); 
        
        foreach ($result as $key => $val) {
            $result[$key]['meta'] = json_decode($val['meta'], true); 
            $result[$key]['meta']['author']['url'] = UserUrlUtility::MakeURLs($val); 
            $result[$key]['meta']['sizes'] = Images::normaliseSizes($result[$key]['meta']['sizes']); 
            
            if ($result[$key]['meta']['author']['url'] instanceof Url) {
                $result[$key]['meta']['author']['url'] = $result[$key]['meta']['author']['url']->getURLs(); 
            }
        }
        
        return $result;
        
    }
}