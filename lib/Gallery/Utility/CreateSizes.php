<?php

/**
 * Create missing image sizes
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

class CreateSizes {
    
    /**
     * Insert original size into the sizes table
     * @since Version 3.10.0
     * @return void
     */
    
    public static function InsertOriginalSize() {
        
        $Database = (new AppCore)->getDatabaseConnection(); 
        
        $query = "SELECT id, path FROM gallery_mig_image WHERE hidden = 0 AND id NOT IN (SELECT photo_id FROM gallery_mig_image_sizes WHERE size = 'original')";
        
        foreach ($Database->fetchAll($query) as $k => $row) {
            
            $data = [
                "photo_id" => $row['id'],
                "size" => "original",
                "source" => $row['path']
            ];
            
            $filename = sprintf("%s%s", Album::ALBUMS_DIR, $row['path']); 
            
            if (!file_exists($filename)) {
                continue; 
            }
            
            Debug::LogCLI("Photo path: " . $filename); 
            Debug::LogCLI("Loading image dimensions"); 
            
            $dims = getimagesize($filename);
            
            if (!is_array($dims)) {
                continue;
            }
            
            // we have dimensions, let's insert the default image
            $data['width'] = $dims[0];
            $data['height'] = $dims[1];
            
            $Database->insert("gallery_mig_image_sizes", $data); 
            
            Debug::LogCLI("Size added to database");
            
            Debug::LogCLI("-------------");
            
            if ($k % 50 === 0) {
                Debug::LogCLI("Sleeping for 2 seconds"); 
                sleep(2);
            
                Debug::LogCLI("-------------");
            }
            
        }
        
    }
    
    /**
     * Create other sizes
     * @since Version 3.10.0
     * @return void
     */
    
    public static function createOtherSizes() {
        
        $sleep = 2; 
        $sleep = false;
        
        $Database = (new AppCore)->getDatabaseConnection(); 
        
        $query = "SELECT i.id,
                square.size AS square, square.source AS square_src, square.width AS square_w, square.height AS square_h,
                large_square.size AS large_square, large_square.source AS large_square_src, large_square.width AS large_square_w, large_square.height AS large_square_h,
                small.size AS small, small.source AS small_src, small.width AS small_w, small.height AS small_h,
                small_320.size AS small_320, small_320.source AS small_320_src, small_320.width AS small_320_w, small_320.height AS small_320_h,
                medium.size AS medium, medium.source AS medium_src, medium.width AS medium_w, medium.height AS medium_h,
                medium_640.size AS medium_640, medium_640.source AS medium_640_src, medium_640.width AS medium_640_w, medium_640.height AS medium_640_h,
                medium_800.size AS medium_800, medium_800.source AS medium_800_src, medium_800.width AS medium_800_w, medium_800.height AS medium_800_h,
                original.size AS original, original.source AS original_src, original.width AS original_w, original.height AS original_h
            FROM gallery_mig_image AS i
                LEFT JOIN gallery_mig_image_sizes AS square ON square.photo_id = i.id AND square.size = 'square'
                LEFT JOIN gallery_mig_image_sizes AS large_square ON large_square.photo_id = i.id AND large_square.size = 'large_square'
                LEFT JOIN gallery_mig_image_sizes AS small ON small.photo_id = i.id AND small.size = 'small'
                LEFT JOIN gallery_mig_image_sizes AS small_320 ON small_320.photo_id = i.id AND small_320.size = 'small_320'
                LEFT JOIN gallery_mig_image_sizes AS medium ON medium.photo_id = i.id AND medium.size = 'medium'
                LEFT JOIN gallery_mig_image_sizes AS medium_640 ON medium_640.photo_id = i.id AND medium_640.size = 'medium_640'
                LEFT JOIN gallery_mig_image_sizes AS medium_800 ON medium_800.photo_id = i.id AND medium_800.size = 'medium_800'
                LEFT JOIN gallery_mig_image_sizes AS original ON original.photo_id = i.id AND original.size = 'original'
            WHERE i.hidden = 0
            AND square.size IS NULL
            AND large_square.size IS NULL
            AND small.size IS NULL
            AND small_320.size IS NULL
            AND medium.size IS NULL
            AND medium_640.size IS NULL
            AND medium_800.size IS NULL
            LIMIT 0, 250";
        
        $result = $Database->fetchAll($query); 
        
        /**
         * Set our desired sizes
         */
        
        $sizes = [
            "square" =>       [ "width" => 75,  "height" => 75 ],
            "large_square" => [ "width" => 150, "height" => 150 ],
            "small" =>        [ "width" => 240, "height" => 0 ],
            "small_320" =>    [ "width" => 320, "height" => 0 ],
            "medium" =>       [ "width" => 500, "height" => 0 ],
            "medium_640" =>   [ "width" => 640, "height" => 0 ],
            "medium_800" =>   [ "width" => 800, "height" => 0 ]
        ];
        
        /** 
         * Loop through the results and start building the sizes
         */
        
        foreach ($result as $row) {
            
            /**
             * Load the original image from disk. If it doesn't exist then continue to the next array item
             */
            
            $filename = sprintf("%s%s", Album::ALBUMS_DIR, $row['original_src']); 
            
            if (!file_exists($filename)) {
                continue; 
            }
            
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            $allowedtypes = [ "jpeg", "jpg", "png", "gif" ];
            
            if (!in_array($ext, $allowedtypes)) {
                continue;
            }
            
            $noext = str_replace("." . $ext, "", $filename); 
            $image = file_get_contents($filename); 
            
            Debug::LogCLI("Source image " . $filename);
            
            /**
             * Loop through each required size
             */
            
            foreach ($sizes as $key => $dims) {
                
                /**
                 * If the size already exists in DB then proceed to the next size
                 */
                
                if (!is_null($row[$key]) || $key == "original") {
                    continue;
                }
                
                /**
                 * Break out of the loop if the desired size is larger than than the original image 
                 */
                
                if ($dims['width'] > $row['original_w']) {
                    continue;
                }
                
                $dstfile = sprintf("%s.%s.%s", $noext, $key, $ext); 
                
                if (file_exists($dstfile)) {
                    unlink($dstfile); 
                }
                
                Debug::LogCLI("  Creating " . $key . " from image " . $filename);
                Debug::LogCLI("");
                
                $Image = WideImage::loadFromString($image); 
                
                if ($dims['width'] == $dims['height']) {
                    $size = $Image->resize($dims['width'], $dims['height'], "outside");
                    $size = $size->crop(0, "middle", $dims['width'], $dims['height']);
                } else {
                    $size = $Image->resize($dims['width'], $dims['width'], "inside");
                }
                
                $quality = $dims['width'] <= 240 ? 80 : 100;
                
                file_put_contents($dstfile, $size->asString("jpg", $quality));
                
                if (file_exists($dstfile)) {
                    
                    Debug::LogCLI("  Image created, inserting into DB");
                    Debug::LogCLI("  " . $dstfile);
                    
                    $data = [
                        "photo_id" => $row['id'],
                        "size" => $key,
                        "source" => $dstfile,
                        "width" => $size->getWidth(),
                        "height" => $size->getHeight()
                    ];
                    
                    $Database->insert("gallery_mig_image_sizes", $data); 
                    
                }
                
                Debug::LogCLI("  ---");
                
            }
            
            if ($sleep) {
                Debug::LogCLI("-------------------------------");
                Debug::LogCLI("");
                Debug::LogCLI("Sleeping for two seconds");
                Debug::LogCLI(""); 
                sleep($sleep);
            }
            
            Debug::LogCLI("-------------------------------");
            Debug::LogCLI("");
            
        }
        
    }
    
}