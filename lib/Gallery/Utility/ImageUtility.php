<?php

/**
 * Image utility class
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Gallery\Utility;

use Exception;
use DateTime;
use WideImage\WideImage;
use Railpage\AppCore;
use Railpage\Debug;
use Railpage\Gallery\Image;
use Railpage\Gallery\Album;
use Railpage\Place;

/**
 * ImageUtility
 */

class ImageUtility {
    
    /**
     * Resize the original image - we don't want to save massive copies of everything
     * @since Version 3.10.0
     * @param string $imageSource
     * @return void
     */
    
    public static function ResizeOriginal($imageSource) {
        
        $sizes = getimagesize($imageSource); 
        
        if ($sizes[0] <= Image::MAX_WIDTH && $sizes[1] <= Image::MAX_HEIGHT) {
            return true;
        }
        
        $image = file_get_contents($imageSource); 
        $Image = WideImage::loadFromString($image); 
        
        $size = $Image->resize(Image::MAX_WIDTH, Image::MAX_HEIGHT, "inside");
        
        file_put_contents($imageSource, $size->asString("jpg", 100));
        
        if (!file_exists($imageSource)) {
            throw new Exception("Resized image and saved to path, but could not find it after!"); 
        }
        
        return true;
        
    }
    
    /**
     * Run the provided image through jpegoptim
     * @since Version 3.10.0
     * @param string $imageSource
     * @return void
     */
    
    public static function OptimiseImage($imageSource) {
        
        if ($imageSource instanceof Image) {
            foreach ($imageSource->sizes as $size) {
                OptimizeImage($size['source']);
            }
        }
        
        if (!file_exists($imageSource)) {
            return;
        }
        
        exec('/usr/bin/jpegoptim --strip-none -f -q -p --all-progressive -o "' . $imageSource . '"');
        
        return;
        
    }
    
    /**
     * Get important EXIF information from the image
     * @since Version 3.10.0
     * @return array
     * @param \Railpage\Gallery\Image $imageObject
     */
    
    public static function PopulateExif($imageObject) {
        
        $imageSource = Album::ALBUMS_DIR . $imageObject->path; 
        
        /**
         * Read the IPTC data
         */
        
        #$size = getimagesize($imageSource, $info);
    
        if (is_array($info)) {
            $iptc = iptcparse($info["APP13"]);
            if (isset($iptc['2#005'])) {
                $imageObject->title = $iptc['2#005'][0];
            }
        }
        
        /**
         * Read the EXIF data
         */
        
        $exif = exif_read_data($imageSource, 0, true); 
        
        if (isset($exif['IFD0']['ImageDescription'])) {
            $imageObject->caption = $exif['IFD0']['ImageDescription'];
        }
        
        if (isset($exif['EXIF']['DateTimeOriginal'])) {
            $imageObject->DateTaken = new DateTime($exif['EXIF']['DateTimeOriginal']);
        }
        
        if (isset($exif['GPS']['GPSLatitude']) && isset($exif['GPS']['GPSLongitude'])) {
            $lat = self::getGps($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef']);
            $lon = self::getGps($exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef']);
            
            $imageObject->Place = Place::Factory($lat, $lon); 
        }
        
        return $imageObject;
        
    }
    
    /**
     * Convert stupid EXIF GPS format to decimal
     * From http://stackoverflow.com/a/2526412
     * @since Version 3.10.0
     * @param string $exifCoord
     * @param string $hemi
     * @return array
     */
    
    private static function getGps($exifCoord, $hemi) {
        
        $degrees = count($exifCoord) > 0 ? self::gps2Num($exifCoord[0]) : 0;
        $minutes = count($exifCoord) > 1 ? self::gps2Num($exifCoord[1]) : 0;
        $seconds = count($exifCoord) > 2 ? self::gps2Num($exifCoord[2]) : 0;
    
        $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;
    
        return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
        
    }
    
    /**
     * Convert something into something else
     * @since Version 3.10.0
     * @param string $coordPart
     * @return float
     */
    
    private static function gps2Num($coordPart) {
        
        $parts = explode('/', $coordPart);
        
        if (count($parts) <= 0) {
            return 0;
        }
        
        if (count($parts) == 1) {
            return $parts[0];
        }
        
        return floatval($parts[0]) / floatval($parts[1]);
        
    }
    
}