<?php

/**
 * Forums API
 * @since Version 3.0.1
 * @version 3.2
 * @package Railpage
 * @author James Morgan, Michael Greenhill
 */

namespace Railpage\Forums;

/**
 * phpBB per-user permissions
 * @since Version 3.2
 * @version 3.2
 * @author Michael Greenhill
 */

class Permissions extends Forums {
    
    /**
     * User object
     * @since Version 3.2
     * @version 3.2
     * @var object $user
     */
    
    public $user;
    
    /**
     * Groups permissions for this user
     * @since Version 3.2
     * @version 3.2
     * @var array $group_permissions
     */
    
    public $group_permissions;
    
    /**
     * Constructor
     * @since Version 3.2
     * @version 3.2
     * @param object $db 
     * @param object $user
     */
    
    public function __construct($user = false) {
        if (!$user || !($user instanceof \Railpage\Users\User)) {
            throw new \Exception("Cannot instantiate " . __CLASS__ . "::" . __FUNCTION__ . " - no \$user object given"); 
            return false;
        }
        
        parent::__construct(); 
        
        $this->user = $user;
        
        $query = "SELECT * FROM nuke_bbforums";
        
        if ($this->db instanceof \sql_db) {
            if ($rs = $this->db->query($query)) {
                while ($row = $rs->fetch_assoc()) {
                    $forum =& $this->forums[$row['forum_id']];
                    
                    foreach ($row as $key => $val) {
                        $forum[$key] = $val;
                    }
                }
            
                // Get group permissions for user
                $query = "SELECT * FROM nuke_bbauth_access WHERE group_id IN (SELECT group_id FROM nuke_bbuser_group WHERE user_id = ".$this->db->real_escape_string($this->user->id)." AND user_pending = 0)";
                
                if ($rs = $this->db->query($query)) {
                    while ($row = $rs->fetch_assoc()) {
                        foreach ($row as $key => $val) {
                            if (strstr($key, "auth_")) {
                                // This is a permission, so let's check it
                                
                                $forum_perm =& $this->forums[$row['forum_id']][$key];
                                
                                if ($val > 0 && $forum_perm < $val) {
                                    $forum_perm = $val;
                                }
                            }
                        }
                    }
                }
            } else {
                trigger_error("phpBB User permissions : Unable to fetch forum list");
                trigger_error($this->db->error);
                trigger_error($query);
            }
        } else {
            foreach ($this->db->fetchAll($query) as $row) {
                $forum =& $this->forums[$row['forum_id']];
                    
                foreach ($row as $key => $val) {
                    $forum[$key] = $val;
                }
            }
            
            $query = "SELECT * FROM nuke_bbauth_access WHERE group_id IN (SELECT group_id FROM nuke_bbuser_group WHERE user_id = ? AND user_pending = 0)";
            
            foreach ($this->db->fetchAll($query, $this->user->id) as $row) {
                foreach ($row as $key => $val) {
                    if (strstr($key, "auth_")) {
                        // This is a permission, so let's check it
                        
                        $forum_perm =& $this->forums[$row['forum_id']][$key];
                        
                        if ($val > 0 && $forum_perm < $val) {
                            $forum_perm = $val;
                        }
                    }
                }
            }
        }
    }
}
