<?php

/**
 * Downloads utility
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Downloads\Utility;

use Railpage\AppCore;
use Railpage\Url;

class DownloadUtility {
    
    /**
     * Build URLs
     * @since Version 3.10.0
     * @param \Railpage\Downloads\Download $downloadOject
     * @return \Railpage\Url
     */
    
    public static function buildUrls(Download $downloadObject) {
        
        $url = new Url(sprintf("%s?mode=download.view&id=%d", $downloadObject->Module->url, $downloadObject->id));
        $url->download = sprintf("https://www.railpage.com.au/downloads/%s/get", $downloadObject->id);
        
        return $url;
        
    }
}