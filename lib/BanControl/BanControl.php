<?php

/**
 * Ban controller
 * @since Version 3.2
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
use Railpage\Users\Factory as UserFactory;
use Railpage\Debug;
use Exception;
use DateTime;

class BanControl extends AppCore {
    
    /**
     * Cache key for all banned objects
     * @since Version 3.9.1
     * @const string CACHE_KEY_ALL
     */
    
    const CACHE_KEY_ALL = "railpage:bancontrol.all;v1";
    
    /**
     * Cache key for individual user ban
     * @since Version 3.9.1
     * @const string CACHE_KEY_USER
     */
    
    const CACHE_KEY_USER = "railpage:ban.user=%d";
    
    /**
     * Cche key for individual IP ban
     * @since Version 3.9.1
     * @const string CACHE_KEY_IP
     */
    
    const CACHE_KEY_IP = "railpage:ban.addr=%s";
    
    /**
     * Gzip level for caching
     * @since Version 3.9.1
     * @const int CACHE_GZIP_LEVEL
     */
    
    const CACHE_GZIP_LEVEL = 9;
     
    /**
     * Banned users
     * @since Version 3.2
     * @var array $users
     */
     
    public $users = array();
     
    /**
     * Banned IP addresses
     * @since Version 3.2
     * @var array $ip_addresses
     */
     
    public $ip_addresses = array();
    
    /**
     * Banned domain names
     * @since Version 3.2
     * @version 3.2
     * @var array $domains
     */
    
    public $domains = array();
    
    /**
     * Load all 
     * @since Version 3.9.1
     * @return \Railpage\BanControl\BanControl
     */
    
    public function loadAll() {
        // Attempt to load combined users & IPs first
        if (empty($this->users) || empty($this->ip_addresses)) {
            if ($this->Memcached->contains(self::CACHE_KEY_ALL) && $array = json_decode(gzuncompress($this->Memcached->Fetch(self::CACHE_KEY_ALL)), true)) {
                $this->users = $array['users'];
                $this->ip_addresses = $array['ips'];
            } 
            
            return $this;
        }
        
        $this->loadUsers(); 
        $this->loadIPs(); 
        
        $this->cacheAll(); 
        
        return $this;
    }
    
    /**
     * Save loaded ban arrays into our cache provider
     * @since Version 3.9.1
     * @return \Railpage\BanControl\BanControl
     * @param boolean $reloadFirst
     */
    
    public function cacheAll($reloadFirst = null) {
        if ($reloadFirst) {
            $this->loadUsers($reloadFirst); 
            $this->loadIPs($reloadFirst);
        }
        
        $store = array(
            "users" => $this->users,
            "ips" => $this->ip_addresses
        );
        
        $this->Memcached->delete(self::CACHE_KEY_ALL);
        $this->Memcached->save(self::CACHE_KEY_ALL, gzcompress(json_encode($store), self::CACHE_GZIP_LEVEL));
        
        if (is_object($this->Redis)) {
            try {
                $this->Redis->delete("railpage:bancontrol");
                $this->Redis->save("railpage:bancontrol", $this);
            } catch (Exception $e) {
                // throw it away
            }
        }
        
        return $this;
    }
    
    /**
     * Get banned users
     * @since Version 3.2
     * @version 3.2
     * @return boolean
     * @param boolean $skipCache
     */
    
    public function loadUsers($skipCache = null) {
        $mckey = "railpage:bancontrol.users;v5"; 
        
        if ($skipCache || !$this->Memcached->contains($mckey) || !$this->users = json_decode(gzuncompress($this->Memcached->Fetch($mckey)), true)) {
            $query = "SELECT b.id, b.user_id, b.ban_time, b.ban_expire, b.ban_reason, b.banned_by AS admin_user_id, bu.username, bu.reported_to_sfs, au.username AS admin_username
                FROM bancontrol AS b
                LEFT JOIN nuke_users AS bu ON b.user_id = bu.user_id
                LEFT JOIN nuke_users AS au ON b.banned_by = au.user_id
                WHERE b.ip = ''
                AND ban_active = 1";
            
            foreach ($this->db->fetchAll($query) as $row) {
                $this->users[$row['user_id']] = $row;
            }
            
            $this->Memcached->save($mckey, gzcompress(json_encode($this->users), self::CACHE_GZIP_LEVEL));
        }
        
        if (is_null($this->users)) {
            $this->users = array(); 
        }
        
        return true;
    }
    
    /**
     * Get banned IP addresses
     * @since Version 3.2
     * @version 3.2
     * @return boolean
     * @param boolean skipCache
     */
    
    public function loadIPs($skipCache = null) {
        $mckey = "railpage:bancontrol.ips;v4"; 
        
        if ($skipCache || !$this->Memcached->contains($mckey) || !$this->ip_addresses = json_decode(gzuncompress($this->Memcached->Fetch($mckey)), true)) {
            $query = "SELECT b.id, b.ip, b.ban_time, b.ban_expire, b.ban_reason, b.banned_by AS admin_user_id, au.username AS admin_username
                FROM bancontrol AS b
                LEFT JOIN nuke_users AS au ON b.banned_by = au.user_id
                    WHERE b.user_id = ''
                AND ban_active = 1";
            
            foreach ($this->db->fetchAll($query) as $row) {
                $this->ip_addresses[$row['ip']] = $row;
            }
            
            $this->Memcached->save($mckey, gzcompress(json_encode($this->ip_addresses), self::CACHE_GZIP_LEVEL));
        }
        
        if (is_null($this->ip_addresses)) {
            $this->ip_addresses = array(); 
        }
        
        return true;
    }
    
    /**
     * Get banned domain names
     * @since Version 3.2
     * @version 3.2
     * @return boolean
     */
    
    public function loadDomains() {
        $query = "SELECT * FROM ban_domains ORDER BY domain_name";
        
        if ($result = $this->db->fetchAll($query)) {
            foreach ($result as $row) {
                $this->domains[$row['domain_id']] = $row;
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Ban user
     * @since Version 3.2
     * @version 3.2
     * @param int|boolean $userId
     * @param string|boolean $reason
     * @param int|boolean $expiry
     * @param int|boolean $adminUserId
     * @return boolean
     */
    
    public function banUser($userId = null, $reason = null, $expiry = "0", $adminUserId = null) {
        
        if (!filter_var($userId, FILTER_VALIDATE_INT)) {
            throw new InvalidArgumentException("No user ID supplied"); 
        }
        
        if (is_null($reason)) {
            throw new InvalidArgumentException("No reason was supplied"); 
        }
        
        if (!filter_var($adminUserId, FILTER_VALIDATE_INT)) {
            throw new InvalidArgumentException("No administrative user ID was supplied");
        }
        
        /**
         * Empty the cache
         */
        
        $this->Memcached = AppCore::getMemcached();
        
        try {
            if ($this->Memcached->Fetch("railpage:bancontrol.users")) {
                $this->Memcached->delete("railpage:bancontrol.users"); 
            }
            
            if ($this->Memcached->Fetch(self::CACHE_KEY_ALL)) {
                $this->Memcached->delete(self::CACHE_KEY_ALL);
            }
        } catch (Exception $e) {
            // throw it away
        }
        
        try {
            $this->Redis->delete("railpage:bancontrol");
        } catch (Exception $e) {
            // throw it away
        }
        
        $data = array(
            "user_id"       => $userId,
            "ban_active"    => 1,
            "ban_time"      => time(),
            "ban_reason"    => $reason,
            "banned_by"     => $adminUserId,
            "ban_expire"    => $expiry
        );
        
        $this->db->insert("bancontrol", $data);
        
        $cachekey_user = sprintf(self::CACHE_KEY_USER, $userId);
        
        $expire = $expiry > 0 ? $expiry : 0; 
        $this->Memcached->save($cachekey_user, true, $expire); 

        
        /**
         * Update the cache
         */
         
        $this->cacheAll(true);
        
        /**
         * Tell the world that they've been naughty
         */
        
        $ThisUser = UserFactory::CreateUser($userId);
        $ThisUser->active       = 0;
        $ThisUser->location     = "Banned"; 
        $ThisUser->signature    = "Banned";
        $ThisUser->avatar       = "";
        $ThisUser->interests    = "";
        $ThisUser->occupation   = "";
        
        $ThisUser->commit(true); 
        
        $ThisUser->addNote("Banned", $adminUserId);
        
        $Smarty = AppCore::GetSmarty(); 
    
        // Send the ban email
        $Smarty->Assign("userdata_username", $ThisUser->username);
        $Smarty->Assign("ban_reason", $reason);
        
        if ($expiry > 0) {
            $Smarty->Assign("ban_expire_nice", date($ThisUser->date_format, $expiry));
        }
        
        // Send the confirmation email
        $Notification = new Notification;
        
        if ($adminUserId !== false) {
            $Notification->setAuthor(UserFactory::CreateUser($adminUserId));
        }
        
        $Notification->addRecipient($ThisUser->id, $ThisUser->username, $ThisUser->contact_email);
        $Notification->body = $Smarty->Fetch($Smarty->ResolveTemplate("email.ban"));
        $Notification->subject = "Railpage account suspension";
        $Notification->transport = Notifications::TRANSPORT_EMAIL;
        $Notification->commit()->dispatch(); 
        
        return true;
        
    }
    
    /**
     * Ban IP address
     * @since Version 3.2
     * @version 3.2
     * @param string|bool $ipAddress
     * @param string|bool $reason
     * @param int|bool $expiry
     * @param int|bool $adminUserId
     * @return boolean
     */
    
    public function banIP($ipAddress = null, $reason = null, $expiry = "0", $adminUserId = null) {
        
        if (is_null($ipAddress)) {
            throw new InvalidArgumentException("No IP address supplied"); 
        }
        
        if (is_null($reason)) {
            throw new InvalidArgumentException("No reason was supplied"); 
        }
        
        if (!filter_var($adminUserId, FILTER_VALIDATE_INT)) {
            throw new InvalidArgumentException("No administrative user ID was supplied");
        }
        
        /**
         * Empty the cache
         */
        
        try {
            $this->Memcached->delete("railpage:bancontrol.ips"); 
            $this->Memcached->delete(self::CACHE_KEY_ALL);
        } catch (Exception $e) {
            // throw it away
        }
        
        try {
            $this->Redis->delete("railpage:bancontrol");
        } catch (Exception $e) {
            // throw it away
        }
        
        $data = array(
            "ip"            => $ipAddress,
            "ban_active"    => 1,
            "ban_time"      => time(),
            "ban_reason"    => $reason,
            "banned_by"     => $adminUserId,
            "ban_expire"    => $expiry
        );
        
        $this->db->insert("bancontrol", $data);
        
        $cachekey_ip = sprintf(self::CACHE_KEY_IP, $ipAddress);
        $expire = $expiry > 0 ? $expiry : 0; 
        $this->Memcached->save($cachekey_ip, true, $expire); 
        
        /**
         * Update the cache
         */
         
        $this->cacheAll(true);
        
        return true;
        
    }
    
    /**
     * Unban user
     * @since Version 3.2
     * @version 3.2
     * @param int $banId
     * @param int|bool $userId
     * @return boolean
     */
    
    public function unBanUser($banId, $userId = null) {
        $success = false;
        
        /**
         * Empty the cache
         */
        
        try {
            $this->Memcached->delete("railpage:bancontrol.users"); 
            $this->Memcached->delete(self::CACHE_KEY_ALL);
        } catch (Exception $e) {
            // throw it away
        }
        
        try {
            $this->Redis->delete("railpage:bancontrol");
        } catch (Exception $e) {
            // throw it away
        }
        
        if ($banId instanceof User) {
            $userId = $banId->id;
        }
        
        if ($userId == null) {
            $query = "SELECT user_id FROM bancontrol WHERE id = ?"; 
        
            $userId = $this->db->fetchOne($query, $banId);
        }
        
        if ($userId > 0) {
            $data = array(
                "ban_active" => 0
            );
            
            $where = array(
                "user_id = " . $userId
            );
            
            $this->db->update("bancontrol", $data, $where);
            $success = true;
        
            $cachekey_user = sprintf(self::CACHE_KEY_USER, $userId);
            $this->Memcached->save($cachekey_user, false, strtotime("+5 weeks")); 
        }
        
        if ($success) {
            // Tell the world that they've been unbanned
            $ThisUser = UserFactory::CreateUser($userId);
            $ThisUser->active       = 1;
            $ThisUser->location     = ""; 
            $ThisUser->signature    = "";
            $ThisUser->avatar       = "";
            $ThisUser->interests    = "";
            $ThisUser->occupation   = "";
            
            try {
                $ThisUser->commit(); 
                
                $Smarty = AppCore::getSmarty(); 
    
                // Send the ban email
                $Smarty->Assign("userdata_username", $ThisUser->username);
                
                // Send the confirmation email
                $Notification = new Notification;
                
                $Notification->addRecipient($ThisUser->id, $ThisUser->username, $ThisUser->contact_email);
                $Notification->body = $Smarty->Fetch($Smarty->ResolveTemplate("email_unban"));
                $Notification->subject = "Railpage account re-activation";
                $Notification->transport = Notifications::TRANSPORT_EMAIL;
                $Notification->commit()->dispatch(); 
                
                return true;
                
            } catch (Exception $e) {
                global $Error;
                
                if (isset($Error)) {
                    $Error->save($e, $_SESSION['user_id']);
                }
                
                Debug::logException($e);
            }
        } 
        
        return false;
    }
    
    /**
     * Unban IP address
     * @since Version 3.5
     * @param int $banId
     * @param string|bool $ipAddress
     * @return boolean
     */
    
    public function unBanIp($banId, $ipAddress = null) {
        
        /**
         * Empty the cache
         */
        
        try {
            $this->Memcached->delete("railpage:bancontrol.ips"); 
            $this->Memcached->delete(self::CACHE_KEY_ALL);
        } catch (Exception $e) {
            // throw it away
        }
        
        try {
            $this->Redis->delete("railpage:bancontrol");
        } catch (Exception $e) {
            // throw it away
        }
        
        $data = array(
            "ban_active" => "0"
        );
        
        if ($ipAddress == null) {
            $where = array(
                "id = ?" => $banId
            );
        
            $query = "SELECT ip FROM bancontrol WHERE id = ?"; 
            $ipAddress = $this->db->fetchOne($query, $banId);
            
        }
        
        if ($ipAddress != null) {
            $where = array(
                "ip = ?" => $ipAddress
            );
        }
        
        $this->db->update("bancontrol", $data, $where);
        
        $cachekey_ip = sprintf(self::CACHE_KEY_IP, $ipAddress);
        $this->Memcached->delete($cachekey_ip);
        $this->Memcached->save($cachekey_ip, false, strtotime("+5 weeks")); 
        
        $this->cacheAll();
        
        return true;
    }
    
    /**
     * Edit a ban 
     * @since Version 3.4
     * @param int|bool $banId
     * @param int|bool $expire
     * @return bool
     * @throws \Exception if no ban ID is given
     */
    
    public function editUserBan($banId = null, $expire = null) {
        if (!filter_var($banId, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot change user ban - no ban ID given"); 
        }
        
        /**
         * Empty the cache
         */
        
        try {
            $this->Memcached->delete("railpage:bancontrol.users"); 
            $this->Memcached->delete(self::CACHE_KEY_ALL);
        } catch (Exception $e) {
            // throw it away
        }
        
        try {
            $this->Redis->delete("railpage:bancontrol");
        } catch (Exception $e) {
            // throw it away
        }
        
        if ($expire != null) {
            $expire = "0"; 
        }
        
        $data = array(
            "ban_expire" => $expire
        );
        
        $where = array(
            "id = ?" => $banId
        );
        
        $cachekey_user = sprintf(self::CACHE_KEY_USER, $banId);
        $this->Memcached->save($cachekey_user, false, $expire); 
        
        $this->db->update("bancontrol", $data, $where);
        return true;
    }
    
    /**
     * Lookup IP address
     * @since Version 3.6
     * @param string|bool $ip
     * @param boolean $activeOnly
     * @throws \Exception if no IP address is given
     * @returns bool
     */
    
    public function lookupIP($ipAddress = null, $activeOnly = null) {
        
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new Exception("Cannot peform IP ban lookup - no IP address given"); 
            return false;
        }
        
        return LookupUtility::lookup("ip", $ipAddress, $activeOnly); 
        
    }
    
    /**
     * Lookup IP user
     * @since Version 3.6
     * @param string|bool $userId
     * @param boolean $activeOnly
     * @returns bool
     * @throws \Exception if no user ID is given
     */
    
    public function lookupUser($userId = null, $activeOnly = null) {
        
        if ($userId == null) {
            throw new Exception("Cannot peform user ban lookup - no user ID given"); 
        }
        
        return LookupUtility::lookup("user", $userId, $activeOnly); 
        
    }
    
    /**
     * Check if an IP address is banned
     * @since Version 3.9
     * @param string|bool $ipaddr
     * @param bool $force Force a check
     * @return bool
     * @throws \Exception if no IP address is given
     */
    
    public function isIPBanned($ipAddress = null, $force = null) {
        
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new Exception("Cannot check for banned IP address because no or an invaild IP address was given");
        }
        
        if ($force == true || empty($this->ip_addresses)) {
            $this->loadAll(); 
        }
        
        return isset($this->ip_addresses[$ipAddress]);
        
    }
    
    /**
     * Check if user ID is banned
     * @since Version 3.9
     * @param string|\Railpage\Users\User $userObject
     * @return boolean
     */
    
    public function isUserBanned($userObject = null) {
        
        if (is_null($userObject) || (!$userObject instanceof User && !filter_var($userObject, FILTER_VALIDATE_INT))) {
            return false;
        }
        
        if ($userObject instanceof User) {
            $userObject = $userObject->id;
        }
        
        if (empty($this->users)) {
            $this->loadAll(); 
        }
        
        return isset($this->users[$userObject]);
        
    }
    
    /**
     * Check if the client is banned
     * @since Version 3.9.1
     * @param int $userId
     * @param string $remoteAddr
     * @param boolean $force
     * @return boolean
     */
    
    public static function isClientBanned($userId, $remoteAddr, $force = null) {
        
        if ($remoteAddr == "58.96.64.238" || $userId == 71317) {
            $force = true;
        }
        
        if ($force == null) {
            $force = false;
        }
        
        if (!$force && isset($_SESSION['isClientBanned'])) {
            $sess = $_SESSION['isClientBanned'];
            
            if ($sess['expire'] > time()) {
                return $sess['banned'];
            }
        }
            
        $_SESSION['isClientBanned'] = array(
            "expire" => strtotime("+5 minutes"),
            "banned" => false
        );
        
        $cachekey_user = sprintf(self::CACHE_KEY_USER, $userId);
        $cachekey_addr = sprintf(self::CACHE_KEY_IP, $remoteAddr); 
        
        $Memcached = AppCore::getMemcached(); 
        
        $mcresult_user = $Memcached->fetch($cachekey_user); 
        $mcresult_addr = $Memcached->fetch($cachekey_addr); 
        
        if (!$force && ($mcresult_user === 1 || $mcresult_addr === 1)) {
            return true;
        }
        
        if (!$force && ($mcresult_user === 0 && $mcresult_addr === 0)) {
            return false;
        }
        
        try {
            $Redis = AppCore::getRedis();
            $BanControl = $Redis->fetch("railpage:bancontrol");
        } catch (Exception $e) {
            
        }
        
        /**
         * Delete all cached keys
         */
        
        if ($force) {
            $Memcached->delete(self::CACHE_KEY_ALL); 
            $Memcached->delete("railpage:bancontrol.users;v5");
            $Memcached->delete("railpage:bancontrol.ips;v4"); 
        }
        
        /**
         * Continue with the lookup
         */
        
        if ($force || !$BanControl instanceof BanControl) {
            $BanControl = new BanControl;
        }
        
        if ($BanControl->isUserBanned($userId)) {
            $Memcached->save($cachekey_user, 1, strtotime("+5 weeks"));
             
            $_SESSION['isClientBanned']['banned'] = true;
            
            return true;
        }
        
        if ($BanControl->isIPBanned($remoteAddr)) {
            $Memcached->save($cachekey_user, 0, strtotime("+5 weeks")); 
            $Memcached->save($cachekey_addr, 1, strtotime("+5 weeks")); 
             
            $_SESSION['isClientBanned']['banned'] = true;
            
            return true;
        }
        
        $Memcached->save($cachekey_addr, 0, strtotime("+5 weeks")); 
        
        return false;
    }
}
