<?php

/**
 * Locally cache a remote image
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Images;

use Railpage\AppCore;
use Railpage\Debug;
use Exception;
use InvalidArgumentException;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Cache
 */

class ImageCache extends AppCore {
    
    /**
     * Image hash / cache key
     * @since Version 3.10.0
     * @var string $hash
     */
    
    private $hash;
    
    /**
     * Configuration options
     * @since Version 3.10.0
     * @var array $config
     */
    
    private $config; 
    
    /**
     * Image resource
     * @since Version 3.10.0
     * @var resource|string|object $image
     */
    
    private $image;
    
    /**
     * Set config parameters
     * @since Version 3.10.0
     * @param array $config
     * @return \Railpage\Images\ImageCache
     */
    
    public function setConfiguration($config) {
        
        $DefaultConfig = [ 
            "cachePathAbsolute" => "/srv/railpage.com.au/www/public_html/images/cache" , // absolute cache path relative to / 
            "cachePathWeb" => "images/cache", // cache path relative to the web root
            "cacheTTL" => "30 days",
            "urlScheme" => "HTTPS", // HTTP or HTTPS
            "hostname" => "static.railpage.com.au"
        ];
        
        $this->config = $DefaultConfig;
        
        foreach ($config as $key => $val) {
            if (!in_array($key, array_keys($DefaultConfig))) {
                throw new InvalidArgumentException($key . " is not a valid configuration parameter"); 
            }
            
            $this->config[$key] = $val;
        }
        
        return $this;
        
    }
    
    /**
     * Get the cached URL for a supplied remote image
     * @since Version 3.10.0
     * @param string $url
     * @return string
     */
    
    public function getCachedUrl($url) {
        
        Debug::LogCLI("Generating local cache URL for remote URL $url");
        
        if (empty($this->config)) {
            throw new Exception("Config parameters have not been set (hint: setConfiguration()"); 
        }
        
        $md5name = md5($url); 
        $filepath  = sprintf("%s%s%s", $this->config['cachePathAbsolute'], DIRECTORY_SEPARATOR, $md5name) . "." . pathinfo($url, PATHINFO_EXTENSION); 
        $filepath  = strtolower($filepath); 
        
        $webpath = sprintf("%s://%s/%s/%s", $this->config['urlScheme'], $this->config['hostname'], $this->config['cachePathWeb'], $md5name) . "." . pathinfo($url, PATHINFO_EXTENSION); 
        $webpath = strtolower($webpath); 
        
        $data = [
            "local_file" => $filepath,
            "remote_file" => $url,
            "web_file" => $webpath
        ];
        
        $this->Memcached->save($md5name, $data, 0); 
        
        $this->hash = $md5name;
        
        if (file_exists($filepath)) {
            return $webpath;
        }
        
        $cache = strtolower(sprintf("%s://%s/i.php?%s", $this->config['urlScheme'], $this->config['hostname'], $md5name)); 
        return $cache;
        
    }
    
    /**
     * Set the cache key / hash of the URL
     * @since Version 3.10.0
     * @param string $hash
     * @return \Railpage\Images\ImageCache
     */
    
    public function setHash($hash) {
        
        $this->hash = $hash;
        
        return $this;
        
    }
    
    /**
     * Get the local URL of an image, if we have a cache key / hash available
     * @since Version 3.10.0
     * @return string
     */
    
    public function getLocalUrl() {
        
        if (empty($this->hash)) {
            throw new Exception("Cannot get the local URL of a cached image as no cache key or hash was supplied (hint: setHash()"); 
        }
        
        if (!$data = $this->Memcached->fetch($this->hash)) {
            throw new Exception("Could not fetch data for image hash " . $this->hash);
        }
        
        if (!file_exists($data['local_file'])) {
            $this->grab($data['remote_file'], $data['local_file']); 
            $this->optimise($data['local_file']); 
        }
        
        return $data['web_file'];
        
    }
    
    /**
     * Grab an image from the web and store it locally
     * @since Version 3.10.0
     * @param string $remote_file
     * @param string $local_file
     * @return void
     */
    
    private function grab($remote_file, $local_file) {
        
        $GuzzleClient = new GuzzleClient;
        
        Debug::LogCLI("Fetching $remote_file");
        
        $response = $GuzzleClient->get($remote_file); 
        
        if ($response->getStatusCode() != 200 && $response->getStatusCode() != 304) {
            throw new Exception("Unexpected HTTP status code " . $response->getStatusCode() . " encountered when fetching " . $remote_file); 
        }
        
        $image = $response->getBody();
        
        if (!file_put_contents($local_file, $image)) {
            throw new Exception("File was fetched from remote source, but could not save to destination file " . $local_file); 
        }
        
        return;
        
    }
    
    /**
     * Optimise the local file 
     * @since Version 3.10.0
     * @param string $local_file
     * @return void
     */
    
    private function optimise($local_file) {
        
        $filetype = exif_imagetype($local_file); 
        
        switch ($filetype) {
            
            case IMAGETYPE_JPEG :
                $this->optimiseJPEG($local_file); 
                break;
                
            case IMAGETYPE_PNG : 
                $this->optimisePNG($local_file); 
                break;
                
        }
        
        return;
        
    }
    
    /**
     * Optimise a JPEG file
     * @since Version 3.10.0
     * @param string $local_file
     * @return void
     */
    
    private function optimiseJPEG($local_file) {
        
        if (!file_exists("/usr/bin/jpegoptim")) {
            return;
        }
        
        $output = system("/usr/bin/jpegoptim -m 100 -o -p -q --strip-all --all-progressive " . $local_file, $return_code); 
        
        printArray($return_code);
        
        return;
        
    }
    
    /**
     * Optimise a PNG file
     * @since Version 3.10.0
     * @param string $local_file
     * @return void
     */
    
    private function optimisePNG($local_file) {
        
        
        
    }
    
    /**
     * Shorthand / lazy function to get the redirect URL
     * @since Version 3.10.0
     * @param string $url
     * @return string
     */
    
    public static function cache($url) {
        
        $ImageCache = new ImageCache;
        $ImageCache->setConfiguration(); 
        
        return $ImageCache->getCachedUrl($url); 
        
    }
    
}
