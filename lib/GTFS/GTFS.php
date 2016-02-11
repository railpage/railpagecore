<?php

/**
 * GTFS Master class
 * @since Version 3.9
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\GTFS;

use Railpage\AppCore;
use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Exception;
use DateTime;
use DateTimeZone;
use DateInterval;
use Railpage\Place;
use Railpage\Module;
use Railpage\Url;

/**
 * GTFS
 */

class GTFS extends AppCore {
    
    /**
     * Get all providers
     * @since Version 3.9
     * @return array
     * @param string $region The region to filter by: eg AU or AU/VIC
     */
    
    public function getProviders($region = null) {
        $ritit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__), RecursiveIteratorIterator::CHILD_FIRST); 
        $providers = array(); 
        
        foreach ($ritit as $splFileInfo) {
            //$path = $splFileInfo->isDir() ? array($splFileInfo->getFilename() => array()) : array($splFileInfo->getFilename()); 
            
            for ($depth = $ritit->getDepth() - 1; $depth >= 0; $depth--) {
                
                $test = sprintf("%s/%s.php", $ritit->getSubIterator($depth)->current()->getPathname(), $ritit->getSubIterator($depth)->current()->getBasename());
                
                if (file_exists($test)) {
                    $name = str_replace(dirname(__DIR__), "", sprintf("%s/%s", $ritit->getSubIterator($depth)->current()->getPathname(), $ritit->getSubIterator($depth)->current()->getBasename())); 
                    $name = sprintf("\\Railpage%s", str_replace("/", "\\", $name)); 
                    
                    if (!in_array($name, $providers)) {
                        if ($region == null) {
                            $providers[] = $name;
                            continue;
                        }
                        
                        if (preg_match(sprintf("/(%s)/i", str_replace("/", "\\\\", $region)), $name)) {
                            $providers[] = $name;
                        }
                    }
                }
            }
        }
                    
        sort($providers);
        
        return $providers;
    }
    
    /**
     * Yield providers as new objects
     * @since Version 3.9
     * @yield object A Railpage GTFS instance, eg \Railpage\GTFS\AU\TFNSW\TFNSW
     * @return object A Railpage GTFS instance, eg \Railpage\GTFS\AU\TFNSW\TFNSW
     */
    
    public function yieldProviders($region = null) {
        foreach ($this->getProviders($region) as $provider) {
            yield new $provider;
        }
    }
    
    /**
     * Get a single provider matching a given string
     * @since Version 3.9
     * @param string $name
     * @return object A Railpage GTFS instance, eg \Railpage\GTFS\AU\SA\AdelaideMetro\AdelaideMetro
     */
    
    public function getProvider($name = null) {
        if ($name == null) {
            throw new Exception("No name provided to filter by");
        }
        
        foreach ($this->getProviders($name) as $provider) {
            return new $provider;
        }
        
        return false;
    }
}
