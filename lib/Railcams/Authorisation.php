<?php

/**
 * Railcam authorisation
 * Authenticate railcam footage submissions by auth token or allowed IP address
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

class Authorisation {
    
    /**
     * Get authorisation token for a railcam
     * @since Version 3.10.0
     * @param \Railpage\Railcams\Camera $Camera
     * @return string
     */
    
    public static function getAuthToken(Camera $Camera) {
        
        if (!isset($Camera->meta['auth_token'])) {
            $Camera->meta['auth_token'] = bin2hex(openssl_random_pseudo_bytes(16));
            $Camera->commit(); 
        }
        
        return $Camera->meta['auth_token'];
        
    }
    
    /**
     * Validate authentication token
     * @since Version 3.10.0
     * @param \Railpage\Railcams\Camera $Camera
     * @param string $authToken
     * @return boolean
     */
    
    public static function validateAuthToken(Camera $Camera, $token = null) {
        
        $token = trim(preg_replace("/(Basic|Token)/i", "", $token)); 
        
        if ($token == self::getAuthToken($Camera)) {
            return true;
        }
        
        return false;
        
    }
    
    /**
     * Get authorisation IP addresses for a railcam
     * @since Version 3.10.0
     * @param \Railpage\Railcams\Camera $Camera
     * @return string
     */
    
    public static function getAuthIPs(Camera $Camera) {
        
        if (!isset($Camera->meta['auth_ips'])) {
            return "";
        }
        
        return $Camera->meta['auth_ips'];
        
    }
    
}