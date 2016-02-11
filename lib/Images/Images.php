<?php

/**
 * Images master class for Railpage
 * 
 * Find an image by provider ID etc
 * @since Version 3.8.7
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Images;

use Railpage\AppCore;
use Railpage\Debug;
use Exception;
use InvalidArgumentException;
use DateTime;
use DateTimeZone;

/**
 * Images class
 * @since Version 3.8.7
 */

class Images extends AppCore {
    
    /**
     * Option: Do not load a \Railpage\Place object
     * @since Version 3.9.1
     * @const int OPT_NOPLACE
     */
    
    const OPT_NOPLACE = 7;
    
    /**
     * Flag: Force a refresh of this image data
     * @since Version 3.9.1
     * @const int OPT_REFRESH
     */
    
    const OPT_REFRESH = 1432;
    
    /**
     * Photo sizes
     * @since Version 3.9.1
     * @var array $sizes
     */
    
    private static $sizes = array(); 
    
    /**
     * Constructor
     */
    
    public function __construct() {
        
        parent::__construct(); 
        
        Debug::recordInstance() ;
        
    }
    
    /**
     * Delete a photo from the geodata cache
     * @since Version 3.9.1
     * @param string $provider
     * @param string $photoId
     */
    
    public function deleteFromCache($provider, $photoId = null) {
        
        if ($photoId == null) {
            throw new InvalidArgumentException("No photo ID was provided"); 
        }
        
        $where = [ "photo_id = ?" => $photoId ];
        $this->db->delete("flickr_geodata", $where); 
        
        return $this;
        
    }
    
    /**
     * Find an image by provider and provider image ID
     * @since Version 3.8.7
     * @param string $provider
     * @param int $photoId
     * @param mixed $option
     * @throws \Exception if $provider is null
     * @throws \Exception if $photoId is null
     * @param int $option
     */
    
    public function findImage($provider = null, $photoId = null, $option = null) {
        if (is_null($provider)) {
            throw new Exception("Cannot lookup image from image provider - no provider given (hint: Flickr, WestonLangford)");
        }
        
        if (!preg_match("/([a-zA-Z0-9]+)/", $photoId) || $photoId === 0) {
            throw new Exception("Cannot lookup image from image provider - no provider image ID given");
        }
        
        $mckey = sprintf("railpage:image;provider=%s;id=%s", $provider, $photoId);
        
        if ((defined("NOREDIS") && NOREDIS == true)  || ($option != self::OPT_REFRESH && !$id = $this->Redis->fetch($mckey))) {
            Debug::LogCLI("Found photo ID " . $photoId . " in database"); 
            
            $id = $this->db->fetchOne("SELECT id FROM image WHERE provider = ? AND photo_id = ?", array($provider, $photoId));
            $this->Redis->save($mckey, $id, strtotime("+1 month"));
        }
        
        if (isset($id) && filter_var($id, FILTER_VALIDATE_INT)) {
            return new Image($id, $option);
        }
        
        Debug::LogCLI("Photo ID " . $photoId . " not found in local cache"); 
        
        $Image = new Image;
        $Image->provider = $provider;
        $Image->photo_id = $photoId;
        
        $Image->populate(true, $option);
        
        return $Image;
    }
    
    /**
     * Find images of a locomotive
     * @since Version 3.8.7
     * @param int $locoId
     * @param int $liveryId
     * @return array
     */
    
    public function findLocoImage($locoId = null, $liveryId = null) {
        if (is_null($locoId)) {
            throw new Exception("Cannot find loco image - no loco ID given");
        }
        
        if (is_null($liveryId)) {
            $query = "SELECT i.id FROM image_link AS il INNER JOIN image AS i ON il.image_id = i.id WHERE il.namespace = ? AND il.namespace_key = ? AND il.ignored = 0";
            $args = array(
                "railpage.locos.loco",
                $locoId
            );
            
            $image_id = $this->db->fetchOne($query, $args); 
        } else {
            $query = "SELECT il.image_id FROM image_link AS il WHERE il.namespace = ? AND il.namespace_key = ? AND il.image_id IN (SELECT i.id FROM image_link AS il INNER JOIN image AS i ON il.image_id = i.id WHERE il.namespace = ? AND il.namespace_key = ? AND il.ignored = 0)";
            $args = array(
                "railpage.locos.liveries.livery",
                $liveryId,
                "railpage.locos.loco",
                $locoId
            );
            
            $image_id = $this->db->fetchOne($query, $args); 
        }
        
        if (isset($image_id) && filter_var($image_id, FILTER_VALIDATE_INT)) {
            $Image = new Image($image_id);
            #$Image->populate();
            
            return $Image;
        }
        
        return false;
    }
    
    /**
     * Get a photo from its source URL
     * @since Version 3.9.1
     * @param string $url
     * @param int|null $option
     * @return boolean|\Railpage\Images\Image
     */
    
    public function getImageFromUrl($url = null, $option = null) {
        
        /**
         * Flickr
         */
        
        if (preg_match("#flickr.com/photos/([a-zA-Z0-9\-\_\@]+)/([0-9]+)#", $url, $matches) && $Image = $this->findImage("flickr", $matches[2], $option)) {
            return $Image;
        }
        
        if (preg_match("#flic.kr/p/([a-zA-Z0-9]+)#", $url, $matches)) {
            $photo_id = self::getBase58PhotoId($matches[1]);
                            
            if ($Image = $this->findImage("flickr", $photo_id, $option)) {
                return $Image;
            }
        }
        
        /**
         * SmugMug
         */
        
        if (preg_match("#smugmug.com#", $url)) {
            $parts = explode("/", $url); 
            array_reverse($parts); 
            
            foreach ($parts as $part) {
                if (preg_match("#i-([a-zA-Z0-9]+)#", $part, $matches)) {
                    if ($Image = $this->findImage("SmugMug", $matches[1], $option)) {
                        if (is_null($Image->photo_id) || $Image->photo_id == 0) {
                            $Image->photo_id = $matches[1]; 
                            $Image->commit(); 
                        }
                        
                        return $Image;
                    }
                }
            }
        }
        
        /**
         * Vicsig
         */
        
        if (preg_match("#vicsig.net/photo#", $url, $matches)) {
            // Do nothing yet
        }
        
        /**
         * 500px.com
         */
        
        if (preg_match("#500px.com/photo/([0-9]+)/#", $url, $matches)) {
            $photo_id = $matches[1]; 
            
            if ($Image = $this->findImage("FiveHundredPx", $photo_id, $option)) {
                return $Image;
            }
        }
        
    }
    
    /**
     * Get a photo ID from a base58-encoded Flickr photo ID
     * @since Version 3.9.1
     * @param string $base58
     * @return string
     */
    
    private static function getBase58PhotoId($base58) {
        
        $alphabet = "123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
        $decoded = 0;
        $multi = 1;
        
        while (strlen($base58) > 0) {
            $digit = $base58[strlen($base58)-1];
            $decoded += $multi * strpos($alphabet, $digit);
            $multi = $multi * strlen($alphabet);
            $base58 = substr($base58, 0, -1);
        }
        
        return $base58;
        
    }
    
    /**
     * Normalise image sizes
     * @since Version 3.9.1
     * @param array $sizes
     * @return array
     */
    
    public static function normaliseSizes($sizes) {
        
        self::$sizes = $sizes;
        
        self::normaliseSizes_addMissingSizes("thumb", 280, 150);
        self::normaliseSizes_addMissingSizes("small", 500, 281);
        self::normaliseSizes_addMissingSizes("square", 75, 75); 
        self::normaliseSizes_addMissingSizes("largesquare", 150, 150);  
        
        self::normaliseSizes_addShorthands("fullscreen", 1920);
        self::normaliseSizes_addShorthands("largest");
        self::normaliseSizes_addShorthands("larger", 1024, 1920);
        self::normaliseSizes_addShorthands("large", 1024, 1024);
        self::normaliseSizes_addShorthands("medium", 800, 800);
        self::normaliseSizes_addShorthands("square", 75, 75);   
        self::normaliseSizes_addShorthands("largesquare", 150, 150);    
        
        if (!isset(self::$sizes['larger'])) {
            self::$sizes['larger'] = self::$sizes['largest'];
        }
        
        if (!isset($sizes['large'])) {
            self::$sizes['large'] = self::$sizes['larger'];
        }
        
        if (!isset($sizes['medium'])) {
            self::$sizes['medium'] = self::$sizes['large'];
        }
        
        return self::$sizes;
    }
    
    /**
     * Normalise sizes: add missing sizes
     * @since Version 3.9.1
     * @param string $missingSize
     * @param int $minWidth
     * @param int $minHeight
     * @return void
     */
    
    private static function normaliseSizes_addMissingSizes($missingSize, $minWidth, $minHeight) {
        
        if (isset(self::$sizes[$missingSize])) {
            return;
        }
        
        if (!is_array(self::$sizes)) {
            return;
        }
        
        foreach (self::$sizes as $size) {
            if ($size['width'] >= $minWidth && $size['height'] >= $minHeight) {
                self::$sizes[$missingSize] = $size;
                break;
            }
        }
        
        return;
        
    }
    
    /**
     * Normalise sizes: add shorthand names to sizes
     * @since Version 3.9.1
     * @param string $missingSize
     * @param int $minWidth
     * @param int $maxWidth
     * @return void
     */
    
    private static function normaliseSizes_addShorthands($missingSize, $minWidth = 0, $maxWidth = 99999) {
        
        if (!count(self::$sizes)) {
            return;
        }
        
        if (isset(self::$sizes[$missingSize])) {
            return;
        }
        
        foreach (self::$sizes as $size) {
            if ($missingSize != "largest" && $size['width'] >= $minWidth && $size['width'] <= $maxWidth) {
                self::$sizes[$missingSize] = $size;
                return;
            }
        }
        
        if ($missingSize == "largest") {
            $largest = current(self::$sizes); 
            
            foreach (self::$sizes as $size) {
                if ($size['width'] > $largest['width']) {
                    $largest = $size;
                }
            }
            
            self::$sizes[$missingSize] = $largest;
        }
        
        return;
        
    }
    
    /**
     * Find untagged photos
     * @since Version 3.9.1
     * @return array
     * @param int $page
     * @param int $itemsPerPage
     */
    
    public function findUntagged($page = 1, $itemsPerPage = 25) {
        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM image WHERE id NOT IN (SELECT DISTINCT image_id FROM image_link) LIMIT ?, ?";
        
        $params = array(
            ($page - 1) * $itemsPerPage,
            $itemsPerPage
        );
        
        $result = $this->db->fetchAll($query, $params); 
        
        $return = [ 
            "stat" => "ok",
            "page" => $page, 
            "perpage" => $itemsPerPage,
            "total" => 0,
            "photos" => array()
        ];
        
        $return['total'] = $this->db->fetchOne("SELECT FOUND_ROWS() AS total"); 
        
        foreach ($result as $k => $v) {
            $result[$k]['meta'] = json_decode($v['meta'], true);
            $result[$k]['meta']['sizes'] = self::normaliseSizes($result[$k]['meta']['sizes']);
        }
        
        $return['photos'] = $result;
        
        return $return;
        
    }
    
    /**
     * Get the most recent additions to our gallery
     * @since Version 3.9.1
     * @return array
     */
    
    public function getRecentAdditions($limit = 10) {
        
        //$cachekey = sprintf("railpage:photos.latest.num=%d", $limit); 
        
        $query = "SELECT i.* FROM image AS i LEFT JOIN image_flags AS f ON i.id = f.image_id WHERE i.hidden = 0 AND f.published = 1 ORDER BY i.id DESC LIMIT 0, ?";
        
        $return = array(); 
        
        foreach ($this->db->fetchAll($query, $limit) as $row) {
            $row['meta'] = json_decode($row['meta'], true); 
            $row['meta']['sizes'] = self::normaliseSizes($row['meta']['sizes']); 
            
            $return[] = $row;
        }
        
        return $return;
    }
}
