<?php

/**
 * A camera used to take a photo found on Railpage
 * 
 * This data is gathered from EXIF data found in photos
 * @since Version 3.10.0
 * @pacakge Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Images;

use Railpage\AppCore;
use Railpage\Debug;
use Railpage\ContentUtility;
use Railpage\Url;
use Exception;
use InvalidArgumentException;
use Railpage\Users\Utility\AvatarUtility;

class Camera extends AppCore {
    
    /**
     * Registry key
     * @since Version 3.10.0
     * @const CACHE_KEY
     */
    
    const CACHE_KEY = "railpage:images.camera=%d";
    
    /**
     * Camera ID
     * @since Version 3.10.0
     * @var int $id
     */
    
    public $id;
    
    /**
     * Camera manufacturer
     * @since Version 3.10.0
     * @var string $manufacturer
     */
    
    public $manufacturer;
    
    /**
     * Name of this camera model
     * @since Version 3.10.0
     * @var string $name
     */
    
    public $name;
    
    /**
     * URL slug
     * @since Version 3.10.0
     * @var string $slug
     */
    
    public $slug;
    
    /**
     * Descriptive text
     * @since Version 3.10.0
     * @var string $text
     */
    
    public $text;
    
    /**
     * URL to a photo of this camera
     * @since Version 3.10.0
     * @var string $url
     */
    
    public $image;
    
    /**
     * Array of other miscellaneous data
     * @since Version 3.10.0
     * @var array $meta
     */
     
    public $meta;
    
    /**
     * Constructor
     * @since Version 3.10.0
     * @param int $id
     */
    
    public function __construct($id) {
        
        parent::__construct();
        
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            return;
        }
        
        $this->id = $id;
        
        $query = "SELECT * FROM image_camera WHERE id = ?";
        $row = $this->db->fetchRow($query, $this->id); 
        
        if (!$row) {
            throw new Exception("The specified camera ID " . $id . " does not exist in our database"); 
        }
        
        $this->manufacturer = $row['make'];
        $this->name = $row['model'];
        $this->slug = $row['url_slug'];
        $this->text = $row['text'];
        $this->image = $row['image']; 
        $this->meta = json_decode($row['meta'], true); 
        
        $this->makeURLs(); 
        
    }
    
    /**
     * Generate the URLs for this camera
     * @since Version 3.10.0
     * @return void
     */
    
    private function makeURLs() {
        
        $this->url = new Url(sprintf("/photos/cameras/%s", $this->slug));
        $this->url->edit = sprintf("%s?mode=camera.edit", $this->url); 
        $this->url->dpreview = sprintf("http://www.dpreview.com/search?query=%s+%s", $this->manufacturer, $this->name);
        
    }
    
    /**
     * Validate changes to this camera
     * @since Version 3.10.0
     * @throws \Exception if $this->name is empty
     * @throws \Exception if $this->manufacturer is empty
     * @return boolean
     */
    
    private function validate() {
        
        if (empty($this->name)) {
            throw new Exception("Camera name is empty"); 
        }
        
        if (empty($this->manufacturer)) {
            throw new Exception("Manufacturer name is empty"); 
        }
        
        if (empty($this->slug)) {
            $this->slug = ContentUtility::generateUrlSlug(sprintf("%s %s", $this->manufacturer, $this->name)); 
            
            try {
                $count = $this->db->fetchAll("SELECT id FROM image_camera WHERE url_slug = ?", $this->slug); 
                
                if (count($count)) {
                    $this->slug .= count($count); 
                }
            } catch (Exception $e) {
                // Don't care
            }
            
        }
        
        return true;
        
    }
    
    /**
     * Commit changes to this camera
     * @since Version 3.10.0
     * @return \Railpage\Images\Camera
     */
    
    public function commit() {
        
        $this->validate(); 
        
        $data = [
            "make" => $this->manufacturer,
            "model" => $this->name,
            "url_slug" => $this->slug,
            "image" => $this->image,
            "text" => $this->text,
            "meta" => json_encode($this->meta)
        ];
        
        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            $where = [ "id = ?" => $this->id ];
            
            $this->db->update("image_camera", $data, $where); 
        }
        
        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            $this->db->insert("image_camera", $data); 
            $this->id = $this->db->lastInsertId(); 
        }
        
        /**
         * Flush cache
         */
        
        $regkey = sprintf(self::CACHE_KEY, $this->id);
        
        $Redis = AppCore::GetRedis(); 
        $Memcached = AppCore::GetMemcached();
        
        $Redis->delete($regkey);
        $Memcached->delete($regkey);
        
        $this->makeURLs(); 
        
        return $this;
        
    }
    
    /**
     * Get this camera as an array
     * @since Version 3.10.0
     * @return array
     */
    
    public function getArray() {
        
        $this->validate();
        
        return array(
            "id" => $this->id,
            "name" => $this->name,
            "manufacturer" => $this->manufacturer,
            "text" => $this->text,
            "image" => $this->image,
            "url" => $this->url->getURLs()
        );
        
    }
    
    /**
     * Get all photos taken by this camera which are pinnable to a map
     * @since Version 3.10.0
     * @return array
     */
    
    public function getPhotosForMap() {
        
        $query = "SELECT image.id, image.lat, image.lon, image.title
                    FROM image
                    LEFT JOIN image_exif AS f ON f.image_id = image.id
                    WHERE f.camera_id = ?
                    AND image.lat != 0.0000000000000
                    AND image.lon != 0.0000000000000";
        
        return $this->db->fetchAll($query, $this->id); 
        
    }
    
    /**
     * Get users of this camera, and order them by quanity of photos
     * @since Version 3.10.0
     * @return array
     */
    
    public function getRailpageUsersByQuantity() {
        
        $query = "SELECT COUNT(*) AS num_photos, u.user_id, u.username, u.user_avatar
                    FROM nuke_users AS u
                    LEFT JOIN image AS i ON i.user_id = u.user_id
                    LEFT JOIN image_exif AS f ON f.image_id = i.id
                    WHERE f.camera_id = ?
                    AND i.user_id != 0
                    GROUP BY u.user_id
                    ORDER BY COUNT(*) DESC";
        
        $result = $this->db->fetchAll($query, $this->id); 
        
        foreach ($result as $id => $row) {
            
            if ($row['username'] == "phpunit6") {
                unset($result[$id]); 
                continue;
            }
            
            $row['avatar_sizes'] = array(
                "tiny"   => AvatarUtility::Format($row['user_avatar'], 25, 25),
                "thumb"  => AvatarUtility::Format($row['user_avatar'], 50, 50),
                "small"  => AvatarUtility::Format($row['user_avatar'], 75, 75),
                "medium" => AvatarUtility::Format($row['user_avatar'], 100, 100)
            );
            
            $result[$id] = $row;
            
        }
        
        return $result;
        
    }
    
}