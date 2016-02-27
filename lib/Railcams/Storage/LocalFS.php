<?php

/**
 * Local file storage for railcam footage
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Railcams\Storage; 

use Exception;
use InvalidArgumentException;
use Railpage\AppCore;
use Railpage\Debug;
use Railpage\Railcams\StorageInterface;
use Railpage\Railcams\Storage as StorageGizmo;

class LocalFS implements StorageInterface {
    
    /**
     * Configuration
     * @since Version 3.10.0
     * @var array $config
     */
    
    private $config = [];
    
    /**
     * File info
     * @since Version 3.10.0
     * @var array $fileInfo
     */
    
    private $fileInfo = [];
    
    /**
     * Check configuration
     * @since Version 3.10.0
     * @throws \Exception if $this->config['storageRoot'] is not set
     * @return void
     */
    
    private function checkConfig() {
        
        if (empty($this->config) || count($this->config) === 0) {
            throw new Exception("Configuration has not been set"); 
        }
        
        if (!isset($this->config['storageRoot']) || $this->config['storageRoot'] == null) {
            throw new Exception("storageRoot not set in configuration"); 
        }
        
    }
    
    /**
     * Set configuration
     * @since Version 3.10.0
     * @param array $config
     * @return \Railpage\Railcams\Storage\LocalFS
     */
    
    public function setConfig($config) {
        
        if (!is_array($config) || count($config) === 0) {
            throw new InvalidArgumentException("No configuration array was supplied"); 
        }
        
        if (!isset($config['storageRoot'])) {
            throw new InvalidArgumentException("No storage root has been configured"); 
        }
        
        $this->config = $config; 
        
        return $this;
        
    }
    
    /**
     * Send the supplied temporary file path to the storage destination
     * @since Version 3.10.0
     * @param string $tmpFile
     * @param string $origFile
     * @return \Railpage\Railcams\Storage\LocalFS
     */
    
    public function putFile($tmpFile, $origFile) {
        
        $this->checkConfig(); 
        $this->storeFile($tmpFile, $origFile); 
        
        return $this;
        
    }
    
    /**
     * Actually store the file
     * @since Version 3.10.0
     * @param string $tmpFile
     * @param string $origFile
     * @return void
     */
    
    private function storeFile($tmpFile, $origFile) {
        
        if (!file_exists($tmpFile)) {
            throw new InvalidArgumentException("Supplied file path " . $tmpFile . " does not exist"); 
        }
        
        $dstPath = vsprintf(
            "%s%s/%s/%s/%s",
            [ 
                $this->config['storageRoot'],
                date("Y"),
                date("m"),
                date("d"),
                $origFile
            ]
        );
        
        $ext = pathinfo($dstPath, PATHINFO_EXTENSION); 
        
        $dstPath = str_replace("." . $ext, microtime(true) . "." . $ext, $dstPath);
        
        $type = mime_content_type($dstPath);
        
        if (file_exists($dstPath)) {
            throw new Exception("Could not store railcam footage in LocalFS - destination file already exists"); 
        }
        
        if (!is_dir(dirname($dstPath))) {
            mkdir(dirname($dstPath), 0755, true); 
        }
        
        if (!is_writable(dirname($dstPath))) {
            throw new Exception("The destination path " . $dstPath . " is not writable"); 
        }
        
        move_uploaded_file($tmpFile, $dstPath); 
        
        $this->fileInfo = [
            "path" => str_replace($this->config['storageRoot'], "", $dstPath),
            "filesize" => filesize($dstPath),
            "mime" => mime_content_type($dstPath),
            "type" => StorageGizmo::getTypeFromMime($type),
            "duration" => 0,
            "remote_id" => 0
        ];
        
    }
    
    /**
     * Get file info
     * @since Version 3.10.0
     * @return array
     */
    
    public function getFileInfo() {
        
        if (count($this->fileInfo)) {
            return $this->fileInfo; 
        }
        
    }
}