<?php

/**
 * Railcam footage. Replaces \Railpage\Railcams\Photo
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Railcams;

use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Railpage\AppCore;
use Railpage\Debug;
use Railpage\Url;

class Footage {
    
    /**
     * Footage ID
     * @since Version 3.10.0
     * @var int $id
     */
    
    public $id;
    
    /**
     * Timestamp of footage recording
     * @since Version 3.10.0
     * @var DateTime $Timestamp
     */
    
    public $timeStamp; 
    
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
     * Footage type
     * @since Version 3.10.0
     * @var string $type
     */
    
    private $type; 
    
    /**
     * Constructor
     * @since Version 3.10.0
     * @param \Railpage\Railcams\Camera $cameraObject
     * @param int|null $id
     */
    
    public function __construct(Camera $cameraObject, $id = null) {
        
        $this->setCamera($cameraObject); 
        
        $this->db = AppCore::GetDatabase(); 
        
        if (filter_var($id, FILTER_VALIDATE_INT)) {
            $this->id = $id; 
            $this->getFootage();
        }
        
    }
    
    /**
     * Set the railcam object
     * @since Version 3.10.0
     * @param \Railpage\Railcams\Camera $cameraObject
     * @return \Railpage\Railcams\Footage
     */
    
    public function setCamera(Camera $cameraObject) {
        
        $this->cameraObject = $cameraObject; 
        
        return $this;
        
    }
    
    /**
     * Set the timestamp of this footage
     * @since Version 3.10.0
     * @param mixed $timeStamp
     * @return \Railpage\Railcams\Footage
     */
    
    public function setTimestamp($timeStamp = null) {
        
        if ($timeStamp == null) {
            $this->timeStamp = new DateTime; 
            return $this;
        }
        
        if ($timeStamp instanceof DateTime) {
            $this->timeStamp = $timeStamp; 
            return $this;
        }
        
        if (filter_var($timeStamp, FILTER_VALIDATE_INT)) {
            $this->timeStamp = new DateTime("@" . $timeStamp); 
            return $this;
        }
        
        $this->timeStamp = new DateTime($timeStamp); 
        return $this;
        
    }
    
    /**
     * Store the footage
     * @since Version 3.10.0
     * @param string $tmpFile
     * @param string $origFile
     * @return \Railpage\Railcams\Footage
     * @todo Find the footage type (image or video)
     */
    
    public function store($tmpFile = null, $origFile = null) {
        
        $storageObject = (new Storage())->setCamera($this->cameraObject)->getPrimary(); 
        
        if (!$storageObject instanceof Storage) {
            throw new Exception("Cannot store railcam footage - could not find a suitable storage destination"); 
        }
        
        if (!$this->timeStamp instanceof DateTime) {
            $this->timeStamp = new DateTime;
        }
        
        $storageObject->setTimestamp($this->timeStamp)->putFootage($tmpFile, $origFile); 
        
        $data = $storageObject->getFootage($storageObject->footageId); 
        
        $this->id = $data['id']; 
        $this->getFootage(); 
        
    }
    
    /**
     * Load this object
     * @since Version 3.10.0
     * @return void;
     */
    
    private function getFootage() {
        
        $data = $this->db->fetchRow("SELECT * FROM railcam_footage WHERE id = ?", $this->id); 
        $data['fileinfo'] = json_decode($data['fileinfo'], true); 
        
        $this->timeStamp = new DateTime($data['datestored']); 
        $this->setCamera(new Camera($data['railcam_id'])); 
        $this->type = $data['type']; 
        
        $storageObject = new Storage($data['storage_id']); 
        
        $this->footageData = [
            "id" => $data['id'],
            "camera" => $this->cameraObject->getArray(),
            "type" => $this->type,
            "url" => [
                "original" => $storageObject->getWebUrl($data['fileinfo'])
            ]
        ];
        
        return;
        
    }
    
    /**
     * Get this as an array
     * @since Version 3.10.0
     * @return array
     */
    
    public function getArray() {
        
        return $this->footageData; 
        
    }
}