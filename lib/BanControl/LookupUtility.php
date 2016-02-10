<?php

/**
 * Info lookup utility for BanControl
 * @since Version 3.9.1
 * @version 3.10.0
 * @author Michael Greenhill
 * @package Railpage
 */
 
namespace Railpage\BanControl;

use Railpage\AppCore;
use Railpage\Notifications\Notifications;
use Railpage\Notifications\Notification;
use Railpage\Notifications\Transport\Email;
use Railpage\Users\User;
use Railpage\Debug;
use Exception;
use DateTime;

class LookupUtility {
    
    /**
     * Lookup data
     * @since Version 3.9.1
     * @param string $type
     * @param mixed $id
     * @return array
     */
    
    public static function lookup($type, $id, $activeOnly) {
        
        $Database = (new AppCore)->getDatabaseConnection(); 
        
        if ($type == "ip") {
            $query = "SELECT b.id, b.ip, b.ban_active, b.ban_time, b.ban_expire, b.ban_reason, b.banned_by, u.username AS banned_by_username FROM bancontrol AS b INNER JOIN nuke_users AS u ON b.banned_by = u.user_id WHERE b.ip = ?";
        }
        
        if ($type == "user") {
            $query = "SELECT b.id, b.user_id, un.username AS username, b.ban_active, b.ban_time, b.ban_expire, b.ban_reason, b.banned_by, u.username AS banned_by_username FROM bancontrol AS b INNER JOIN nuke_users AS u ON b.banned_by = u.user_id INNER JOIN nuke_users AS un ON b.user_id = un.user_id WHERE b.user_id = ?";
        }
        
        $return = array(); 
        
        foreach ($Database->fetchAll($query, $id) as $row) {
            if ($activeOnly === false || ($activeOnly === true && $row['ban_active'] == 1)) {
                if (!$row['ban_time'] instanceof DateTime) {
                    $row['ban_time'] = new DateTime("@" . $row['ban_time']); 
                }
                
                $row['ban_time_nice'] = $row['ban_time']->format("F j, Y");
                
                if ($row['ban_expire'] > 0) {
                    if (!$row['ban_expire'] instanceof DateTime) {
                        $row['ban_expire'] = new DateTime("@" . $row['ban_expire']); 
                    }
                    
                    $row['ban_expire_nice'] = $row['ban_expire']->format("F j, Y");
                } else {
                    $row['ban_expire'] = 0; 
                }
                
                $return[$row['id']] = $row; 
            }
        }
        
        return $return;
        
    }
    
    /**
     * Recent bans
     * @since Version 3.10.0
     * @return array
     */
    
    public static function recent($activeOnly = true) {
        
        $Database = (new AppCore)->getDatabaseConnection(); 
        
        $query = "SELECT b.id, b.user_id, un.username AS username, b.ban_active, b.ban_time, b.ban_expire, b.ban_reason, b.banned_by, u.username AS banned_by_username 
                FROM bancontrol AS b 
                LEFT JOIN nuke_users AS u ON b.banned_by = u.user_id 
                LEFT JOIN nuke_users AS un ON b.user_id = un.user_id 
                ORDER BY b.id DESC 
                LIMIT 0, 10";

        
        $return = array(); 
        
        foreach ($Database->fetchAll($query) as $row) {
            if ($activeOnly === false || ($activeOnly === true && $row['ban_active'] == 1)) {
                if (!$row['ban_time'] instanceof DateTime) {
                    $row['ban_time'] = new DateTime("@" . $row['ban_time']); 
                }
                
                $row['ban_time_nice'] = $row['ban_time']->format("F j, Y");
                
                if ($row['ban_expire'] > 0) {
                    if (!$row['ban_expire'] instanceof DateTime) {
                        $row['ban_expire'] = new DateTime("@" . $row['ban_expire']); 
                    }
                    
                    $row['ban_expire_nice'] = $row['ban_expire']->format("F j, Y");
                } else {
                    $row['ban_expire'] = 0; 
                }
                
                $return[$row['id']] = $row; 
            }
        }
        
        return $return;
        
    }
    
    /**
     * Check SpamCop for the given IP address
     * @since Version 3.10.0
     * @return boolean
     * @param string $ip
     */
    
    public static function spamCop($ip) {
        
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['REMOTE_ADDR']; 
        }
        
        $timer = Debug::GetTimer(); 
        
        $reversedIp = implode(".", array_reverse(explode(".", $ip)));
        $host = $reversedIp.".bl.spamcop.net";
        $response = gethostbyname($host);
        
        if (stristr($response, "127.0.0")) {
            return true;
        }
        
        Debug::LogEvent(__METHOD__, $timer);
        
        return false;

    }
    
}
