<?php

/**
 * Maintenance and housekeeping on user objects, 
 * to deal with compatibility shit from older versions
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Users\Utility;

use Exception;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use Railpage\AppCore;
use Railpage\Users\User; 

/**
 * UserMaintenance
 */

class UserMaintenance {
    
    /**
     * Update the user regdate if required
     * @since Version 3.10.0
     * @param array $data
     * @return array
     */
    
    public static function checkUserRegdate($data) {
        
        if (!empty($data['user_regdate_nice']) && $data['user_regdate_nice'] != "0000-00-00") {
            return $data;
        }
        
        if ($data['user_regdate'] == 0) {
            $data['user_regdate'] = date("Y-m-d");
        }

        $datetime = new DateTime($data['user_regdate']);

        $data['user_regdate_nice'] = $datetime->format("Y-m-d");
        $update['user_regdate_nice'] = $data['user_regdate_nice'];

        AppCore::GetDatabase()->update("nuke_users", $update, array( "user_id = ?" => $data['user_id'] ));

        return $data;
        
    }
    
}