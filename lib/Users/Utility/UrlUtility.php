<?php

/**
 * Create user URLs
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Users\Utility;

use Railpage\Url;
use Railpage\AppCore;
use Railpage\Debug;
use Railpage\Users\User;
use Railpage\Module;
use Exception;
use InvalidArgumentException;

class UrlUtility {
    
    /**
     * Create URLs
     * @since Version 3.10.0
     * @param \Railpage\Users\User|array $userData
     * @return \Railpage\Url
     */
    
    public static function MakeURLs($userData) {
        
        if ($userData instanceof User) {
            $userData = [
                "user_id" => $userData->id,
                "username" => $userData->username
            ];
        }
        
        if (!isset($userData['user_id']) || !filter_var($userData['user_id'], FILTER_VALIDATE_INT)) {
            return false;
        }
        
        $Module = new Module("users");
        $PMs = new Module("pm");
        
        $Url = new Url(sprintf("%s/%d", $Module->url, $userData['user_id']));
        $Url->view = $Url->url;
        $Url->account = "/account";
        $Url->sendpm = sprintf("%s/new/to/%d", $PMs->url, $userData['user_id']);
        $Url->newpm = sprintf("%s/new/to/%d", $PMs->url, $userData['user_id']);
        $Url->ideas = sprintf("%s?mode=contributions-ideas", $Url->url);
        $Url->forums = sprintf("/f-s-u-%s.htm", $userData['username']);
        $Url->photos = sprintf("/photos/search?user_id=%d", $userData['user_id']);
        
        return $Url;
        
    }
    
}