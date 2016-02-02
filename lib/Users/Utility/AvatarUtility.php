<?php

/**
 * User avatar utility class
 * @since Version 3.9.1
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Users\Utility;

use Exception;
use DateTime;
use Railpage\ContentUtility;
use Railpage\Users\User;
use Railpage\AppCore;
use Railpage\Debug;

class AvatarUtility {
    
    /**
     * Default avatar
     * @since Version 3.9.1
     * @const string DEFAULT
     */
    
    const DEFAULT_AVATAR = "http://static.railpage.com.au/modules/Forums/images/avatars/gallery/blank.png";
    
    /**
     * Format an avatar
     * @since Version 3.9.1
     * @return string
     * @param string $userAvatar
     * @param int $width
     * @param width $height
     */

    public static function format($userAvatar = null, $width = 100, $height = 100) {
        if (is_null($userAvatar)) {
            return false;
        }
        
        $cacheHandler = AppCore::getMemcached(); 
        
        $timer = Debug::getTimer(); 
        
        if ($userAvatar == "http://www.railpage.com.au/modules/Forums/images/avatars/https://static.railpage.com.au/image_resize") {
            $userAvatar = self::DEFAULT_AVATAR;
        }
        
        if (empty($userAvatar) || stristr($userAvatar, "blank.gif") || stristr($userAvatar, "blank.png")) {
            $userAvatar = self::DEFAULT_AVATAR;
            return $userAvatar;
        }
        
        $parts = parse_url($userAvatar);
        
        if (isset($parts['host']) && $parts['host'] == "static.railpage.com.au" && isset($parts['query'])) {
            parse_str($parts['query'], $query);
        
            if (isset($query['w']) && isset($query['h']) && isset($query['image'])) {
                if ($query['w'] == $width && $query['h'] == $height) {
                    return $userAvatar;
                }
                
                return sprintf("http://static.railpage.com.au/image_resize.php?w=%d&h=%d&image=%s", $width, $height, $query['image']); 
            }
        }
        
        if (isset($parts['host']) && $parts['host'] == "www.gravatar.com" && isset($parts['query'])) {
            parse_str($parts['query'], $query);
            
            $query['s'] = $width;
            $bits = array(); 
            
            foreach ($query as $key => $val) {
                $bits[] = sprintf("%s=%s", $key, $val); 
            }
            
            $userAvatar = sprintf("%s://%s%s?%s", $parts['scheme'], $parts['host'], $parts['path'], implode("&", $bits));
            return self::GravatarHTTPS($userAvatar); 
        }
        
        $mckey = sprintf("railpage.user:avatar=%s;width=%s;height=%s", $userAvatar, $width, $height);
        
        /**
         * Check if this shit is in Memcache first
         */
        
        if ($result = $cacheHandler->fetch($mckey)) {
            return self::GravatarHTTPS($result);
        }
        
        /**
         * It's not in Memcached, so let's process and cache it
         */
        
        parse_str(parse_url($userAvatar, PHP_URL_QUERY), $args);
        
        if (isset($args['base64_args'])) {
            if (!@unserialize(base64_decode($args['base64_args']))) {
                // Malformed string!
                
                $userAvatar = self::DEFAULT_AVATAR;
            } else {
                // Do other stuff...
                
                $base64 = unserialize(base64_decode($args['base64_args']));
            }
        }
        
        if (preg_match("@modules/Forums/images/avatars/(http\:\/\/|https\:\/\/)@", $userAvatar)) {
            $userAvatar = self::DEFAULT_AVATAR;
        }
        
        if (!preg_match("@(http\:\/\/|https\:\/\/)@", $userAvatar)) {
            $userAvatar = "http://static.railpage.com.au/modules/Forums/images/avatars/".$userAvatar;
        }
        
        if (!ContentUtility::url_exists($userAvatar)) {
            $userAvatar = self::DEFAULT_AVATAR;
        }
        
        if ($width && !$height) {
            $height = $width;
        }
        
        // Is this an anigif?
        if (substr($userAvatar, -4, 4) == ".gif") {
            // Fetch the dimensions
            
            $mckey = "railpage:avatar.size=" . md5($userAvatar); 
            
            if ($dimensions = $cacheHandler->fetch($mckey)) {
                // Do nothing
            } else {
                $dimensions = @getimagesize($userAvatar); 
                
                $cacheHandler->save($mckey, $dimensions);
            }
            
            if (isset($dimensions['mime']) && $dimensions['mime'] == "image/gif") {
                // Great, it's a gif
                if ($width && $height) {
                    if ($dimensions[0] <= $width && $dimensions[1] <= $height) {
                        // It fits within the width and height - return it as-is
                        return self::GravatarHTTPS($userAvatar);
                    }
                }
            }
        }
        
        // Assume that all avatars created on dev.railpage.com.au are shit and should be re-directed to static.railpage.com.au
        $userAvatar = str_replace("dev.railpage.com.au", "static.railpage.com.au", $userAvatar);
        
        if ($width && $height) {
            $args['width']  = $width;
            $args['height'] = $height;
            $args['url']    = $userAvatar;
            
            if (empty($userAvatar)) {
                $args['url'] = self::DEFAULT_AVATAR; 
            }
            
            #$userAvatar = "https://static.railpage.com.au/image_resize.php?base64_args=".base64_encode(serialize($args)); 
            $userAvatar = sprintf("https://static.railpage.com.au/image_resize.php?w=%d&h=%d&image=%s", $args['width'], $args['height'], $args['url']);
            
            if ($width == $height) {
                $userAvatar .= "&square=true";
            }
        }
        
        $cacheHandler->save($mckey, $userAvatar, 0);
        
        Debug::logEvent(__METHOD__, $timer) ;
        
        return self::GravatarHTTPS($userAvatar);
    }
    
    /**
     * Get an array of avatar sizes
     * @since Version 3.10.0
     * @param string $avatar
     * @return array
     */
    
    public static function getAvatarSizes($avatar) {
        
        return array(
            "tiny"   => self::Format($avatar, 25, 25),
            "thumb"  => self::Format($avatar, 50, 50),
            "small"  => self::Format($avatar, 75, 75),
            "medium" => self::Format($avatar, 100, 100)
        );
        
    }

    /**
     * Check for Gravatar and convert to HTTPS if required
     * @since Version 3.10.0
     * @param string $avatar
     * @return string
     */
    
    public static function gravatarHTTPS($avatar) {
        
        if (!preg_match("/gravatar.com/i", $avatar)) {
            return $avatar; 
        }
        
        $avatar = str_replace("http://", "https://", $avatar); 
        
        return $avatar;
        
    }
}
