<?php

/**
 * Railcam footage storage class
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Railcams;

use Exception;
use InvalidArgumentException;
use DateTime;
use DateTimeZone;
use Railpage\AppCore;
use Railpage\Debug;
use Railpage\Url;
use Zend_Db_Expr;

class Storage {
    
    /**
     * Railcam storage ID
     * @since Version 3.10.0
     * @var int $id
     */
    
    public $id; 
    
    /**
     * Is this the primary storage destination for this railcam?
     * @since Version 3.10.0
     * @var bool $primary
     */
    
    private $primary = false;
    
    /**
     * Railcam object
     * @since Version 3.10.0
     * @var \Railpage\Railcams\Camera $cameraObject
     */
    
    private $cameraObject; 
    
    /**
     * Database object
     * @since Version 3.10.0
     * @var object $db
     */
    
    private $db;
    
    /**
     * Storage type
     * @since Version 3.10.0
     * @var string $type
     */
    
    private $type;
    
    /**
     * Storage configuration
     * @since Version 3.10.0
     * @var array $config
     */
    
    private $config;
    
    /**
     * Constructor
     * @since Version 3.10.0
     * @return \Railpage\Railcams\Storage
     */
    
    public function __construct($id = null) {
        
        $this->db = AppCore::GetDatabase(); 
        
        if (filter_var($id, FILTER_VALIDATE_INT)) {
            $this->id = $id; 
            $this->getConfig();
        }
        
    }
    
    /**
     * Set our railcam object
     * @since Version 3.10.0
     * @param \Railpage\Railcams\Camera $cameraObject
     * @return \Railpage\Railcams\Storage
     */
    
    public function setCamera(Camera $cameraObject) {
        
        $this->cameraObject = $cameraObject; 
        
        return $this;
        
    }
    
    /**
     * Set the image capture time
     * @since Version 3.10.0
     * @param \DateTime $timeStamp
     * @return \Railpage\Railcams\Storage
     */
    
    public function setTimestamp(DateTime $timeStamp) {
        
        $this->timeStamp = $timeStamp; 
        
        return $this;
        
    }
    
    /**
     * Set our storage type
     * @since Version 3.10.0
     * @param string $type
     * @return \Railpage\Railcams\Storage
     */
    
    public function setType($type = null) {
        
        $class = sprintf("\\Railpage\\Railcams\\Storage\\%s", $type); 
        
        if (!class_exists($class)) {
            throw new InvalidArgumentException("The desired storage type " . $type . " is invalid"); 
        }
        
        $this->type = $type; 
        
        return $this;
        
    }
    
    /**
     * Set our storage type configuration
     * @since Version 3.10.0
     * @param array $config
     * @return \Railpage\Railcams\Storage
     */
    
    public function setConfig($config = null) {
        
        if ($this->type == "LocalFS") {
            if (substr($config['storageRoot'], -1) != "/") {
                $config['storageRoot'] .= "/"; 
            }
            
            if (substr($config['storageRoot'], -3) != $this->cameraObject->id . "/") {
               $config['storageRoot'] .= $this->cameraObject->id . "/"; 
            }
            
            if (substr($config['webRoot'], -1) != "/") {
                $config['webRoot'] .= "/"; 
            }
            
            if (substr($config['webRoot'], -3) != $this->cameraObject->id . "/") {
               $config['webRoot'] .= $this->cameraObject->id . "/"; 
            }
            
        }
        
        $this->config = $config; 
        
        $data = [
            "camera_id" => $this->cameraObject->id,
            "type" => $this->type, 
            "config" => json_encode($this->config)
        ];
        
        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            $where = [ "id = ?" => $this->id ]; 
            $this->db->update("railcam_storage", $data, $where);
            
            return $this;
        }
        
        $this->db->insert("railcam_storage", $data, $where); 
        $this->id = $this->db->lastInsertId();
        
        return $this;
        
    }
    
    /**
     * Get the configuration for this storage destination
     * @since Version 3.10.0
     * @return array
     */
    
    public function getConfig() {
        
        if (!count($this->config)) {
            $query = "SELECT * FROM railcam_storage WHERE id = ?"; 
            $row = $this->db->fetchRow($query, $this->id); 
            $this->type = $row['type']; 
            $this->config = json_decode($row['config'], true); 
            $this->primary = (bool) $row['camera_primary']; 
            
            $this->setCamera(new Camera($row['camera_id']));
        }
        
        return $this->config; 
        
    }
    
    /**
     * Set this as the primary storage destination for our railcam
     * @since Version 3.10.0
     * @throws \Exception if $this->cameraObject has not been set
     * @return \Railpage\Railcams\Storage
     */
    
    public function setPrimary() {
        
        if (!$this->cameraObject instanceof Camera) {
            throw new Exception("Cannot set as primary storage destination, as no railcam has been set"); 
        }
        
        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot set this as the primary storage destination as it has not been saved to database yet"); 
        }
        
        $data = [ "camera_primary" => 0 ];
        $where = [ "camera_id = ?" => $this->cameraObject->id ]; 
        
        $this->db->update("railcam_storage", $data, $where); 
        
        $data = [ "camera_primary" => 1 ];
        $where = [ "id = ?" => $this->id ];
        
        $this->db->update("railcam_storage", $data, $where); 
        $this->primary = true; 
        
        return $this;
        
    }
    
    /**
     * Is this storage destination the primary destination for this railcam?
     * @since Version 3.10.0
     * @return boolean
     */
    
    public function isPrimary() {
        
        return $this->primary; 
        
    }
    
    /**
     * Get the primary storage destination for this railcam
     * @since Version 3.10.0
     * @return \Railpage\Railcams\Storage
     */
    
    public function getPrimary() {
        
        if ($this->isPrimary()) {
            return $this;
        }
        
        $query = "SELECT id FROM railcam_storage WHERE camera_id = ? AND camera_primary = 1"; 
        if ($id = $this->db->fetchOne($query, $this->cameraObject->id)) {
            return new Storage($id); 
        }
        
        $this->setPrimary(); 
        
        return $this;
        
    }
    
    /**
     * Put new railcam footage in our storage destination
     * @since Version 3.10.0
     * @param string $tmpFile
     * @param string $origFile
     * @return int 
     */
    
    public function putFootage($tmpFile = null, $origFile = null) {
        
        if (count($this->config) === 0) {
            throw new Exception("Cannot put railcam footage - storage configuration has not been set"); 
        }
        
        if ($this->type == null) {
            throw new Exception("Cannot put railcam footage - storage type has not been set"); 
        }
        
        $storageType = sprintf("\\Railpage\\Railcams\\Storage\\%s", $this->type); 
        $storageType = new $storageType; 
        $storageType->setConfig($this->getConfig())->putFile($tmpFile, $origFile); 
        
        $fileInfo = $storageType->getFileInfo(); 
        
        // insert
        $data = [
            "datestored" => $this->timeStamp->format("Y-m-d H:i:s"),
            "railcam_id" => $this->cameraObject->id,
            "type" => $fileInfo['type'],
            "duration" => $fileInfo['duration'],
            "remote_id" => $fileInfo['remote_id'],
            "storage_id" => $this->id,
            "fileinfo" => json_encode($fileInfo)
        ];
        
        $this->db->insert("railcam_footage", $data); 
        
        $this->footageId = $this->db->lastInsertId();
        
    }
    
    /**
     * Get information about a piece of railcam footage from the database
     * @since Version 3.10.0
     * @param int $id
     * @return \Railpage\Railcams\Footage
     */
    
    public function getFootage($id = null) {
        
        $query = "SELECT * FROM railcam_footage WHERE id = ?"; 
        
        return $this->db->fetchRow($query, $id); 
        
    }
    
    /**
     * Get the footage type from the mime type
     * @since Version 3.10.0
     * @param string $mime
     * @return string
     */
    
    public static function getTypeFromMime($mime) {
        
        if (strpos($mime, "image") !== false) {
            return "image";
        }
        
        return "video";
        
    }
    
    /**
     * Get the web URL for a piece of footage
     * @since Version 3.10.0
     * @param array $fileInfo
     * @return string
     */
    
    public function getWebUrl($fileInfo) {
        
        return $this->config['webRoot'] . $fileInfo['path'];
        
    }
    
}