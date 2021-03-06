<?php

/**
 * EXIF data handler
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */
 
namespace Railpage\Images;

use Railpage\AppCore;
use Railpage\Url;
use Railpage\Debug;
use Railpage\ContentUtility;
use Exception;
use InvalidArgumentException;
use DateTime;

class Exif extends AppCore {
    
    /**
     * EXIF data format version
     * @since Version 3.10.0
     * @const float EXIF_FORMAT_VERSION
     */
    
    const EXIF_FORMAT_VERSION = 1.2202;
    
    /**
     * Make a URL slug for a camera from brand and model
     * @since Version 3.10.0
     * @param string $make
     * @param string $model
     * @return string
     */
    
    public static function makeCameraUrlSlug($make, $model) {
        
        $prop = ContentUtility::generateUrlSlug(sprintf("%s %s", $make, $model), 30); 
        
        return $prop;
        
    }
    
    /**
     * Get EXIF data from an image
     * @since Version 3.10.0
     * @param \Railpage\Images\Image $imageObject
     * @param boolean $force
     * @return array
     */
    
    public function getImageExif(Image $imageObject, $force = false) {
        
        Debug::LogCLI("Fetching EIXF data for image ID " . $imageObject->id);
        
        if (!$force && isset($imageObject->meta['exif']) && $imageObject->meta['exif_format_version'] >= self::EXIF_FORMAT_VERSION) {
            $imageObject->meta['exif']['camera_make'] = self::normaliseCameraMake($imageObject->meta['exif']['camera_make']); 
            $imageObject->meta['exif']['camera_model'] = self::normaliseCameraModel($imageObject->meta['exif']['camera_model']); 
            
            $imageObject->meta['exif']['camera'] = ImageFactory::CreateCamera($imageObject->meta['exif']['camera_id'])->getArray();
            
            return $imageObject->meta['exif'];
        }
        
        /**
         * Fetch EXIF from the image provider API
         */
        
        $Provider = $imageObject->getProvider(); 
        
        $exif = $Provider->getExif($imageObject->photo_id);
        
        $exif_formatted = $this->getExifIDs($exif);
        $imageObject->meta['exif'] = $exif_formatted;
        $imageObject->meta['exif_format_version'] = self::EXIF_FORMAT_VERSION;
        $imageObject->commit(); 
        
        /**
         * Insert into our database
         */
        
        $query = "INSERT INTO image_exif ( 
                      image_id, camera_id, lens_id, lens_sn_id,
                      aperture, exposure_id, exposure_program_id, 
                      focal_length, iso, white_balance_id
                  ) VALUES (
                      %d, %d, %d, %d, 
                      %s, %d, %d, 
                      %s, %s, %s
                  ) ON DUPLICATE KEY UPDATE
                      camera_id = VALUES(camera_id), lens_id = VALUES(lens_id),
                      lens_sn_id = VALUES(lens_sn_id), aperture = VALUES(aperture),
                      exposure_id = VALUES(exposure_id), exposure_program_id = VALUES(exposure_program_id),
                      focal_length = VALUES(focal_length), iso = VALUES(iso), 
                      white_balance_id = VALUES(white_balance_id)";
        
        $query = sprintf(
            $query, 
            $this->db->quote($imageObject->id), 
            $this->db->quote($exif_formatted['camera_id']),
            $this->db->quote($exif_formatted['lens_id']), 
            $this->db->quote($exif_formatted['lens_sn_id']),
            $this->db->quote($exif_formatted['aperture']), 
            $this->db->quote($exif_formatted['exposure_id']),
            $this->db->quote($exif_formatted['exposure_program_id']), 
            $this->db->quote($exif_formatted['focal_length']),
            $this->db->quote($exif_formatted['iso_speed']), 
            $this->db->quote($exif_formatted['white_balance_id'])
        );
        
        $this->db->query($query); 
        
        return $exif_formatted;
        
    }
    
    /**
     * Get IDs for EXIF values
     * @since Version 3.10.0
     * @param array $exif
     * @return array
     */
    
    private function getExifIDs($exif) {
        
        $required = [ 
            "camera_make", 
            "camera_model",
            "lens_model", 
            "lens_serial_number",
            "exposure", 
            "exposure_program",
            "white_balance",
            "software"
        ];
        
        foreach ($required as $key) {
            if (!isset($exif[$key])) {
                $exif[$key] = "Unknown";
            }
        }
        
        $exif['camera_make'] = self::normaliseCameraMake($exif['camera_make']); 
        $exif['camera_model'] = self::normaliseCameraModel($exif['camera_model']); 
        $exif['software'] = self::normaliseSoftware($exif['software']);
        
        $query = "SELECT 
            (SELECT id FROM image_camera WHERE make = ? AND model = ?) AS camera_id,
            (SELECT id FROM image_lens WHERE model = ?) AS lens_id,
            (SELECT id FROM image_lens_sn WHERE sn = ?) AS lens_sn_id,
            (SELECT id FROM image_exposure WHERE exposure = ?) AS exposure_id,
            (SELECT id FROM image_exposure_program WHERE program = ?) AS exposure_program_id,
            (SELECT id FROM image_whitebalance WHERE whitebalance = ?) AS white_balance_id,
            (SELECT id FROM image_software WHERE name = ?) AS software_id";
        
        $params = [
            $exif['camera_make'],
            $exif['camera_model'],
            $exif['lens_model'],
            $exif['lens_serial_number'],
            $exif['exposure'],
            $exif['exposure_program'],
            $exif['white_balance'],
            $exif['software'],
        ];
        
        $row = $this->db->fetchRow($query, $params); 
        
        foreach ($row as $column => $val) {
            if (!filter_var($val, FILTER_VALIDATE_INT)) {
                $row[$column] = $this->createNewExif($column, $exif); 
            }
        }
        
        $exif = array_merge($exif, $row);
        ksort($exif); 
        
        return $exif;
        
    }
    
    /**
     * Normalise the camera make
     * @since Version 3.10.0
     * @param string $make
     * @return string
     */
    
    private static function normaliseCameraMake($make) {
        
        $findTheseMakes = [ 
            "NIKON CORPORATION",
            "NIKON",
            "EASTMAN KODAK COMPANY",
            "DIGITAL CAMERA",
            "OLYMPUS CORPORATION",
            "FUJIFILM",
            "FUJI PHOTO FILM CO., LTD.",
            "PENTAX Corporation",
            "PENTAX",
            "Samsung Electronics",
            "SAMSUNG",
            "SONY",
            "RICOH",
            "Samsung Techwin",
            "Fujifilm Corporation",
        ];
        
        $replaceWith = [
            "Nikon",
            "Nikon",
            "Kodak",
            "",
            "Olympus",
            "Fujifilm",
            "Fujifilm",
            "Pentax",
            "Pentax",
            "Samsung",
            "Samsung",
            "Sony",
            "Ricoh",
            "Samsung",
            "Fujifilm",
        ];
        
        $make = preg_replace("/([0-9]+)(D DIGITAL)/", "$1D", $make);
        $make = str_replace($findTheseMakes, $replaceWith, $make); 
        
        return trim($make);
        
    }
    
    /**
     * Normalise the software program name
     * @since Version 3.10.0
     * @param string $software
     * @return string
     */
    
    private static function normaliseSoftware($software) {
        
        if (preg_match("/(Adobe Photoshop|Adobe Photoshop Elements) (CS1|CS2|CS3|CS4|CS5|CS5.1|CS5.5|CS6|CS6.5|CC|[0-9\.]+)/", $software, $matches)) {
            $software = sprintf("%s %s", $matches[1], $matches[2]);
            
            if (preg_match("/(Windows|Macintosh|Mac)/", $software, $matches)) {
                $software = sprintf("%s %s", $software, $matches[1]);
            }
        }
        
        return $software;
    }
    
    /**
     * Normalise the camera model
     * @since Version 3.10.0
     * @param string $model
     * @return string
     */
    
    private static function normaliseCameraModel($model) {
        
        $model = preg_replace("/(CANON|Canon|NIKON|NIKON CORPORATION|KODAK|KODAK EASYSHARE|PENTAX) /", "", $model);
        $model = preg_replace("/([0-9]+)(D DIGITAL)/", "$1D", $model);
        $model = preg_replace("/(EASYSHARE )([A-Z0-9]+)( ZOOM DIGITAL)/", "Easyshare $2 Zoom", $model);
        $model = preg_replace("/([A-Z0-9]+)( ZOOM DIGITAL)/", "$1 Zoom", $model);
        $model = str_replace("CAMERA", "", $model);
        $model = str_replace("EOS DIGITAL REBEL XT", "EOS 350D", $model);
        $model = str_replace("EOS Kiss Digital N", "EOS 350D", $model);
        $model = str_replace("EOS Rebel T1i", "EOS 500D", $model);
        $model = str_replace("EOS Kiss X3", "EOS 500D", $model);
        $model = str_replace("COOLPIX", "Coolpix", $model);
        $model = str_replace("DIGITAL IXUS", "IXUS", $model);
        
        return trim($model);
        
    }
    
    /**
     * Create a new EXIF value in our database
     * @since Version 3.10.0
     * @param string $type
     * @param array $exif
     * @return int
     */
    
    private function createNewExif($type, $exif) {
        
        switch ($type) {
            
            case "camera_id" :
                $data = [ "make" => $exif['camera_make'], "model" => $exif['camera_model'] ];
                $table = "image_camera";
                break;
            
            case "lens_id" : 
                $data = [ "model" => $exif['lens_model'] ];
                $table = "image_lens";
                break;
            
            case "lens_sn_id" : 
                $data = [ "sn" => $exif['lens_serial_number'] ];
                $table = "image_lens_sn";
                break;
            
            case "exposure_id" : 
                $data = [ "exposure" => $exif['exposure'] ];
                $table = "image_exposure";
                break;
            
            case "exposure_program_id" : 
                $data = [ "program" => $exif['exposure_program'] ];
                $table = "image_exposure_program";
                break;
            
            case "white_balance_id" : 
                $data = [ "whitebalance" => $exif['white_balance'] ];
                $table = "image_whitebalance";
                break;
            
            case "software_id" :
                $data = [ "name" => $exif['software'] ];
                $table = "image_software";
                break;
        
        }
        
        $this->db->insert($table, $data); 
        
        $id = $this->db->lastInsertId(); 
        
        return $id;
        
    }
    
    /**
     * Format EXIF data
     * @since Version 3.10.0
     * @param array $exif
     * @return array
     */
    
    public function formatExif($exif) {
        
        $format = array();
        
        // Aperture
        if (isset($exif['aperture'])) {
            $format[] = array(
                "icon" => "https://static.railpage.com.au/i/icons/camera119.svg",
                "label" => "Aperture",
                "value" => sprintf("<em>ƒ</em>/%s", $exif['aperture'])
            );
        }
        
        if (isset($exif['exposure'])) {
            $format[] = array(
                "icon" => "https://static.railpage.com.au/i/icons/clock218.svg", 
                "label" => "Exposure", 
                "value" => $exif['exposure']
            );
        }
        
        if (isset($exif['camera'])) {
            $format[] = array(
                "icon" => "https://static.railpage.com.au/i/icons/camera3.svg", 
                "label" => "Camera", 
                "value" => sprintf("%s %s", $exif['camera_make'], $exif['camera_model']),
                "url" => $exif['camera']['url']
            );
        }
        
        if (isset($exif['lens_model'])) {
            $format[] = array(
                "icon" => "https://static.railpage.com.au/i/icons/photo-camera31.svg", 
                "label" => "Lens", 
                "value" => $exif['lens_model']
            );
        }
        
        if (isset($exif['iso_speed'])) {
            $format[] = array(
                "icon" => "https://static.railpage.com.au/i/icons/iso7.svg", 
                "label" => "ISO", 
                "value" => $exif['iso_speed']
            );
        }
        
        if (isset($exif['focal_length'])) {
            $format[] = array(
                "icon" => "https://static.railpage.com.au/i/icons/tool292.svg", 
                "label" => "Focal length", 
                "value" => sprintf("%smm", $exif['focal_length'])
            );
        }
        
        foreach ($format as $key => $val) {
            if ($val['value'] == "Unknown" || $val['value'] == "Unknown Unknown" || is_null($val['value'])) {
                unset($format[$key]);
            }
        }
        
        
        return $format;
        
    }
    
    /**
     * Flag this image for an EXIF scrape
     * @since Version 3.10.0
     * @param \Railpage\Images\Image
     * @return void
     */
    
    public function queueExifScreening(Image $imageObject) {
        
        $data = [
            "exifqueue" => "1"
        ];
        
        $where = [ 
            "image_id = ?" => $imageObject->id
        ];
        
        $this->db->update("image_flags", $data, $where); 
        
        return;
        
    }
    
    /**
     * Fetch EXIF data for the queued images
     * @since Version 3.10.0
     * @return void
     */
    
    public static function scrapeExifQueue() {
        
        $sleep = 10;
        $break = 50;
        
        $Database = (new AppCore)->getDatabaseConnection();
        
        $query = "SELECT f.image_id FROM image_flags AS f LEFT JOIN image AS i ON f.image_id = i.id WHERE f.exifqueue = 1 AND i.provider IS NOT NULL ORDER BY f.image_id DESC";
        $exif = new Exif;
        $ids = [];
        
        foreach ($Database->fetchAll($query) as $row) {
            $imageObject = new Image($row['image_id']); 
            
            $exif->getImageExif($imageObject); 
            $ids[] = $imageObject->id;
            
            if (count($ids) == $break) {
                Debug::LogCLI("Updating " . $break . " records");
                $query = "UPDATE image_flags SET exifqueue = 0 WHERE image_id IN (" . implode(",", $ids) . ")";
                
                $Database->query($query);
                $ids = [];
                
                break;
                sleep($sleep);
            }
            
        }
        
        Debug::LogCLI("Mark all queued images as scraped");
        
        $query = "UPDATE image_flags SET exifqueue = 0 WHERE exifqueue = 1";
        
        $Database->query($query);
        
        return;
        
    }
    
}