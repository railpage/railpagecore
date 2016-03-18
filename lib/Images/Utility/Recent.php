<?php

/**
 * Recent additions
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Images\Utility; 

use Railpage\Images\Images;
use Railpage\Images\ImageCache;
use Railpage\AppCore;
use Railpage\Debug;
use Memcached as PhpMemcached;

class Recent {
    
    /**
     * Get $num newest images
     * @since Version 3.10.0
     * @param int $num
     * @return array
     * @todo $cacheProvider doesn't seem to bloody work!
     */
    
    public static function getNewest($num = 5) {
        
        $host = defined("RP_MEMCACHE_HOST") ? RP_MEMCACHE_HOST : "cache.railpage.com.au";
        $port = defined("RP_MEMCACHE_PORT") ? RP_MEMCACHE_PORT : 11211;
        
        $cacheProvider = new PhpMemcached; 
        $cacheProvider->addServer($host, $port); 
        
        $mckey = sprintf("railpage:images.recent=%d;url.cached", $num); 
        
        if ($newphotos = $cacheProvider->get($mckey)) {
            Debug::LogCLI("Fetched new photos from cache provider using cache key " . $mckey); 
            return $newphotos; 
        }
        
		$newphotos = (new Images)->getRecentAdditions(5); 
		shuffle($newphotos); 
        
        foreach ($newphotos as $id => $data) {
            $newphotos[$id]['meta']['sizes']['medium']['source'] = ImageCache::cache($newphotos[$id]['meta']['sizes']['medium']['source']);
        }
        
        $rs = $cacheProvider->set($mckey, $newphotos, strtotime("+15 minutes")); 
        Debug::LogCLI("Saved new photos in cache provider using cache key " . $mckey); 
        
        if ($res = $cacheProvider->get($mckey)) {
            Debug::LogCLI("new photos found in cache, success"); 
        }
        
        return $newphotos; 
        
    }
}