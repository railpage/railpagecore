<?php

/**
 * Create an image collage
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Images;

use Railpage\AppCore;
use Railpage\Debug;
use Railpage\Url;
use Exception;
use InvalidArgumentException;

/**
 * Collage class
 */

class Collage extends AppCore {
    
    /**
     * Orientation: portrait
     * @since Version 3.10.0
     * @const string ORIENTATION_PORTRAIT
     */
    
    const ORIENTATION_PORTRAIT = "portrait";
    
    /**
     * Orientation: landscape
     * @since Version 3.10.0
     * @const string ORIENTATION_LANDSCAPE
     */
    
    const ORIENTATION_LANDSCAPE = "landscape";
    
    /**
     * Orientation: square
     * @since Version 3.10.0
     * @const string ORIENTATION_SQUARE
     */
    
    const ORIENTATION_SQUARE = "square";
    
    /**
     * Array of Images used in this collage
     * @since Version 3.10.0
     * @var array $Images
     */
    
    private $Images;
    
    /**
     * Orientation of this collage
     * @since Version 3.10.0
     * @var string $orientation
     */
    
    private $orientation;
    
    /**
     * Target width in pixels
     * @since Version 3.10.0
     * @var int $width
     */
    
    private $width = 800;
    
    /**
     * Target height in pixels
     * @since Version 3.10.0
     * @var int $height
     */
    
    private $height = 600; 
    
    /**
     * Image canvas
     * @since Version 3.10.0
     * @var object $canvas
     */
    
    private $canvas;
    
    /**
     * Constructor
     * @since Version 3.10.0
     * @return \Railpage\Images\Collage
     */
    
    public function __construct() {
        
        parent::__construct(); 
        
        $this->setDimensions(); 
        
        return $this;
        
    }
    
    /**
     * Add an image to the collage
     * @since Version 3.10.0
     * @param \Railpage\Images\Image $imageObject
     * @return \Railpage\Images\Collage
     */
    
    public function addImage(Image $imageObject) {
        
        Debug::LogCLI("Added image ID " . $imageObject->id . " to the collage list"); 
        
        $this->Images[$imageObject->id] = $imageObject;
        
        return $this;
        
    }
    
    /**
     * Set the orientation of the collage
     * @since Version 3.10.0
     * @param string $orientation
     * @return \Railpage\Images\Collage
     */
    
    public function setOrientation($orientation) {
        
        if ($orientation != self::ORIENTATION_PORTRAIT && 
            $orientation != self::ORIENTATION_LANDSCAPE &&
            $orientation != self::ORIENTATION_SQUARE) {
            throw new InvalidArgumentException($orientation . " is not a valid orientation"); 
        }
        
        $this->orientation = $orientation; 
        
        return $this;
        
    }
    
    /**
     * Set the dimensions of the collage
     * @since Version 3.10.0
     * @param int $width
     * @param int $height
     * @return \Railpage\Images\Collage
     */
    
    public function setDimensions($width = 800, $height = 600) {
        
        $this->width = $width;
        $this->height = $height; 
        
        $this->setOrientation(self::ORIENTATION_SQUARE); 
        
        if ($this->width > $this->height) {
            $this->setOrientation(self::ORIENTATION_LANDSCAPE); 
        }
        
        if ($this->height > $this->width) {
            $this->setOrientation(self::ORIENTATION_PORTRAIT); 
        }
        
        return $this;
        
    }
    
    /**
     * Find the image size required to fully populate the canvas
     * @since Version 3.10.0
     * @return string
     */
    
    private function findThumbnailSize() {
        
        Debug::LogCLI("Finding the minimum image size required to cover the canvas"); 
        
        $Image = array_slice($this->Images, 0, 1); 
        $Image = $Image[0];
        
        $sizes = array(
            "square",
            "largesquare",
            "thumb",
            "small",
            "medium",
            "large",
            "larger",
            "fullscreen"
        );
        
        foreach ($sizes as $size) {
            
            Debug::LogCLI("Investigating size " . $size); 
            
            $thumbWidth = intval($Image->sizes[$size]['width']);
            $thumbHeight = intval($Image->sizes[$size]['height']); 
            
            $numWidth = ceil($this->width / $thumbWidth); 
            $numHeight = ceil($this->height / $thumbHeight); 
            $numRequired = ceil($numWidth * $numHeight); 
            
            if ($numRequired <= count($this->Images)) {
                Debug::LogCLI("Size required to cover canvas: " . $size); 
                
                return array(
                    "size" => $size,
                    "thumbWidth" => $thumbWidth,
                    "thumbHeight" => $thumbHeight
                );
            } 
        }
        
        throw new Exception("Not enough images are supplied to cover the canvas"); 
        
    }
    
    /**
     * Fetch an image from either Memcached or its source URL
     * @since Version 3.10.0
     * @param string $url
     * @return string
     */
    
    private function getImageString($url) {
        
        $Cache = AppCore::GetMemcached();
        $cachekey = sprintf("image=%s;v2", md5($url)); 
        
        Debug::LogCLI("Looking for image in cache provider"); 
        
        Debug::LogCLI($cachekey); 
        
        if ($string = $Cache->fetch($cachekey)) {
            
            Debug::LogCLI("Image found in cache"); 
            
            return $string;
            
        }
            
        Debug::LogCLI("Image " . $url . " not found in cache. Fetching and storing..."); 
        
        set_time_limit(20); 
        
        $string = file_get_contents($url); 
        $Cache->save($cachekey, $string, 0); 
        
        return $string;
        
    }
    
    /**
     * Assemble the collage
     * @since Version 3.10.0
     * @return \Railpage\Images\Collage
     */
    
    private function assemble() {
        
        Debug::LogCLI("Assembling the collage"); 
        
        $imageSize = $this->findThumbnailSize(); 
        $size = $imageSize['size'];
        
        $this->canvas = imagecreatetruecolor($this->width, $this->height); 
        
        $counter_x = 0; 
        $counter_y = 0; 
        $offset_x = 0; 
        $offset_y = 0;
        
        //$images_per_row = ceil($this->width / $size['thumbWidth']); 
        
        foreach ($this->Images as $Image) {
            
            Debug::LogCLI("Running Image ID " . $Image->id); 
            
            // Fetch the image
            $raw = $this->getImageString($Image->sizes[$size]['source']); 
            $thumb = imagecreatefromstring($raw); 
            
            // Place the thubmnail onto our canvas
            imagecopyresampled($this->canvas, $thumb, $offset_x, $offset_y, 0, 0, $imageSize['thumbWidth'], $imageSize['thumbHeight'], $imageSize['thumbWidth'], $imageSize['thumbHeight']);
            
            if (php_sapi_name() == "cli" && !defined("PHPUNIT_RAILPAGE_TESTSUITE")) {
                var_dump($offset_x); 
                var_dump($offset_y); 
                var_dump($offset_x + $imageSize['thumbWidth']); 
                var_dump($offset_y + $imageSize['thumbHeight']); 
                var_dump($imageSize['thumbWidth']); 
                var_dump($imageSize['thumbHeight']);
            }
            
            $offset_x += $imageSize['thumbWidth']; 
            $counter_x++; 
            
            Debug::LogCLI("counter_x: " . $counter_x); 
            Debug::LogCLI("offset_x: " . $offset_x); 
            
            if ($offset_x >= $this->width) {
                Debug::LogCLI("Wrapping line"); 
                
                $counter_x = 0; 
                $counter_y++; 
                $offset_y += $imageSize['thumbHeight']; 
                $offset_x = 0;
            }
            
        }
        
    }
    
    /**
     * Output the collage as a string
     * @since Version 3.10.0
     * @return string
     */
    
    public function __toString() {
        
        $this->assemble(); 
        
        if (php_sapi_name() == "cli") {
            return; 
        }
        
        return imagejpeg($this->canvas, null, 100); 
        
    }
    
}
