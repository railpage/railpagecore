<?php

/**
 * Railcam footage storage interface
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Railcams;

interface StorageInterface {
    
    /**
     * @param array $config
     * @return $this
     */
    
    public function setConfig($config);
    
    /**
     * @param string $tmpFile
     * @param string $origFile
     * @return $this
     */
    
    public function putFile($tmpFile, $origFile); 
    
    /**
     * @return array
     */
    
    public function getFileInfo();
    
}