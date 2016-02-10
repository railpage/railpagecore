<?php

/**
 * Base user class
 * @since Version 3.0.1
 * @author Michael Greenhill
 * @package Railpage
 */

namespace Railpage\Users;

use Railpage\AppCore;
use Exception;
use DateTime;
use DateTimeZone;
use Railpage\BanControl\BanControl;
 
/**
 * Changelog
 *  - 9/4/2012      Bugfix: users could register accounts with an already-taken username
 *  - 19/4/2012     Addition: added $password_bcrypt to $User object in preparation for bcrypt password hashes
 *  - 22/4/2012     Addition: Token-based auto logins, and login history
 */

class Base extends AppCore {
    
    /**
     * Default site theme
     * @since Version 3.0.1
     * @version 3.0.1
     * @var string $default_theme
     */
    
    public $default_theme = "jiffy_simple";
    
    /**
     * User ID
     * @var int $id
     * @since Version 3.0.1
     * @version 3.0.1
     */
    
    public $id; 
    
    /**
     * Salt
     * @var string $salt
     * @since Version 3.1
     * @version 3.1
     */
    
    public $salt = "mygoodnessmyguiness";
    
    /**
     * List all online registered users
     * @since Version 3.0
     * @version 3.9
     * @return mixed
     */
    
    public function onlineUsers() {
        $mckey = "railpage:onlineusers"; 
        
        if ($return = $this->Memcached->fetch($mckey)) {
            foreach ($return as $id => $row) {
                if (isset($row['last_session_ip'])) {
                    return $return;
                }
            }
        }
                
        $return = array(); 
        
        $query = "SELECT user_id, user_level, username, last_session_ip FROM nuke_users WHERE user_session_time > ".(time() - 60 * 10)." ORDER BY username ASC"; 
        
        if ($result = $this->db->fetchAll($query)) {
            foreach ($result as $row) {
                $row['bot'] = false; $row['guest'] = false;
                $return[$row['user_id']] = $row; 
            }
            
            $this->Memcached->save($mckey, $return, strtotime("+1 minute")); 
        }
                
        return $return;
    }
    
    /**
     * Purge pending users older than $age
     * @since Version 3.2
     * @return int
     */
    
    public function purge() {
        // Purge pending accounts older than 24 hours
        $age = strtotime("-2 weeks"); 
        
        $query = "SELECT u.username, u.user_id, u.user_regdate, u.user_actkey 
                    FROM nuke_users AS u
                    WHERE u.user_actkey != '' 
                        AND u.user_active = 0
                        AND u.user_session_time = 0
                        AND u.user_session_page = 0
                        AND u.user_lastvisit = 0
                        AND u.user_level = 0
                    ORDER BY u.user_id DESC";
        
        if ($rs = $this->db->query($query)) {
            $users = array(); 
            
            while ($row = $rs->fetch_assoc()) {
                $row['regdate'] = strtotime($row['user_regdate']);
                
                if ($row['regdate'] < $age && $row['regdate'] > strtotime("january 1st 2012")) {
                    $users[$row['user_id']] = $row; 
                }
            }
            
            $to_be_deleted = count($users); 
            
            if ($to_be_deleted > 0) {
                $query = "DELETE FROM nuke_users WHERE user_id IN ('".implode("','", array_keys($users))."')";
                
                if ($this->db->query($query)) {
                    return count($users); 
                }
                
                throw new Exception($this->db->error); 
            }
        }
        
        throw new Exception($this->db->error); 
    }
    
    /**
     * Get user registration statistics
     * @since Version 3.8.7
     * @return array
     * @param \DateTime $from
     * @param \DateTime $to
     */
    
    public function getUserRegistrationStats($from = null, $to = null) {
        
        if (!$from instanceof DateTime) {
            $from = new DateTime("1 month ago");
        }
        
        if (!$to instanceof DateTime) {
            $to = new DateTime;
        }
        
        $query = "SELECT count(user_id) AS num, user_regdate_nice FROM nuke_users WHERE user_regdate_nice >= ? AND user_regdate_nice <= ? GROUP BY user_regdate_nice ORDER BY user_regdate_nice";
        
        $result = $this->db->fetchAll($query, array($from->format("Y-m-d"), $to->format("Y-m-d")));
        
        $return = array();
        
        foreach ($result as $row) {
            $return[$row['user_regdate_nice']] = $row['num'];
        }
        
        return $return;
    }
    
    /**
     * Find user from email address
     * @since Version 3.8.7
     * @param string $email
     * @param string $provider
     * @author Michael Greenhill
     */
    
    public function getUserFromEmail($email = null, $provider = "railpage") {
        if ($email == null) {
            throw new Exception("Can't find user - no email address provided");
        }
        
        $query = "SELECT user_id FROM nuke_users WHERE user_email = ?";
        
        $params = array(
            $email
        );
        
        if (!is_null($provider)) {
            $params[] = $provider;
            $query .= " AND provider = ?";
        }
        
        $user_id = $this->db->fetchOne($query, $params);
        
        if (filter_var($user_id, FILTER_VALIDATE_INT)) {
            return Factory::CreateUser($user_id);
        }
        
        throw new Exception(sprintf("No user found with an email address of %s and logging in via %s", $email, $provider));
    }
    
    /**
     * Get user registrations per day between given dates
     * @since Version 3.9
     * @param \DateTime $dateFrom
     * @param \Datetime $dateTo
     * @return array
     */
    
    public function getNumRegistrationsByMonth(DateTime $dateFrom, DateTime $dateTo) {
        
        $BanControl = new BanControl;
        $BanControl->loadUsers();
        
        $bancontrol_sql = "";
        
        if (!is_null($BanControl->users) && count($BanControl->users) > 0) {
            $bancontrol_sql = "AND user_id NOT IN (" . implode(",", array_keys($BanControl->users)) . ")";
        }
        
        $query = "SELECT YEAR(user_regdate_nice) AS year, MONTH(user_regdate_nice) AS month, count(*) AS count
                    FROM nuke_users 
                    WHERE user_regdate_nice BETWEEN ? AND ?
                    AND user_active = 1
                    " . $bancontrol_sql . "
                    GROUP BY YEAR(user_regdate_nice), MONTH(user_regdate_nice)";
        
        return $this->db->fetchAll($query, array($dateFrom->format("Y-m-d"), $dateTo->format("Y-m-d")));
    }
    
    /**
     * Get the list of ranks from the database
     * @since Version 3.9
     * @return array
     */
    
    public function getRanks() {
        $query = "SELECT * FROM nuke_bbranks ORDER BY rank_special, rank_min, rank_title";
        
        return $this->db->fetchAll($query);
    }
    
    /**
     * Add a custom rank
     * @since Version 3.9
     * @param string $rank
     */
    
    public function addCustomRank($rank = null, $image = null) {
        if ($rank == null) {
            throw new Exception("No rank text given");
        }
        
        $data = array(
            "rank_special" => 1,
            "rank_title" => $rank,
            "rank_min" => "-1",
            "rank_image" => is_string($image) && !empty($image) ? $image : ""
        );
        
        $this->db->insert("nuke_bbranks", $data);
        
        $this->Memcached->delete("railpage:ranks");
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Find a list of duplicate usernames
     * @since Version 3.9.1
     * @return array
     */
    
    public function findDuplicateUsernames() {
        $query = "SELECT 
u.user_id, u.username, u.user_regdate, u.user_regdate_nice, u.user_email, u.user_lastvisit, 
(SELECT COUNT(p.post_id) AS num_posts FROM nuke_bbposts AS p WHERE p.poster_id = u.user_id) AS num_posts,
(SELECT MAX(pt.post_time) AS post_time FROM nuke_bbposts AS pt WHERE pt.poster_id = u.user_id) AS last_post_time
FROM nuke_users AS u 
WHERE u.username IN (
    SELECT username FROM nuke_users GROUP BY username HAVING COUNT(username) > 1
)
ORDER BY u.username, u.user_id";
        
        return $this->db->fetchAll($query);
    }
    
    /**
     * Get user from Facebook ID
     * @since Version 3.9.1
     * @return \Railpage\Users\User
     * @param int $id
     */
    
    public function getUserFromFacebookID($id = null) {
        
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            throw new InvalidArgumentException("No valid Facebook user ID was found"); 
        }
        
        $query = "SELECT user_id FROM nuke_users WHERE facebook_user_id = ?"; 
        
        $user_id = $this->db->fetchOne($query, $id); 
        
        if (!filter_var($user_id, FILTER_VALIDATE_INT)) {
            return false;
        }
        
        return new User($user_id);
    }
    
    /**
     * Check username availability
     * @since Version 3.9.1
     * @version 3.10.0
     * @author Michael Greenhill
     * @return boolean
     * @param string $username
     */
    
    public function username_available($username = null) {
        
        if ($username == null) {
            return false;
        }
        
        $username = str_replace("_", "\_", $username); 
        
        $query = "SELECT * FROM nuke_users WHERE username = ?"; 
        
        $count = $this->db->fetchAll($query, $username);
        
        if (!is_array($count) || count($count) == 0 || $count == false) {
            return true;
        }
        
        return false;
        
    }
    
    /**
     * Check email address availability
     * @since Version 3.9.1
     * @version 3.10.0
     * @author Michael Greenhill
     * @return boolean
     * @param string $email
     */
    
    public function email_available($email = null) {
        
        if ($email == null) {
            return false;
        }
        
        $email = str_replace("_", "\_", $email); 
        
        $query = "SELECT * FROM nuke_users WHERE user_email = ?"; 
        
        $count = $this->db->fetchAll($query, $email);
        
        if (!is_array($count) || count($count) == 0 || $count == false) {
            return true;
        }
        
        return false;
        
    }
}
