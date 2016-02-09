<?php

/**
 * Downloads factory 
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Downloads;

use Railpage\AppCore;
use Railpage\Registry;
use Exception;
use InvalidArgumentException;

/**
 * DownloadsFactory
 */

class DownloadsFactory {
    
    /**
     * Create a download
     * @since Version 3.10.0
     * @param int $id
     * @return \Railpage\Downloads\Download
     */
    
    public function createDownload($id = null) {
        
        $Registry = Registry::GetInstance(); 
        
        $cachekey = sprintf("railpage:download=%d", intval($id));
        
        try {
            $Download = $Registry->get($cachekey); 
        } catch (Exception $e) {
            $Download = new Download($id); 
            $Registry->set($cachekey, $Download); 
        }
        
        return $Download;
        
    }
    
}