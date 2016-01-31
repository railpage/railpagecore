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
 * phpBB index
 * List all categories
 * @since Version 3.2
 * @version 3.2
 * @author Michael Greenhill
 */
 
class Index extends Forums {
    
    /**
     * List of categories
     * @since Version 3.2
     * @version 3.2
     * @var array $categories
     */
    
    public $categories;
    
    /**
     * Constructor
     * @since Version 3.0.1
     * @version 3.0.1
     * @param object $db
     */
    
    public function __construct() {
        parent::__construct(); 
        
        // If memcache is enabled, check there first
        $mckey = "railpage:forums.index";
        
        if ($this->categories = $this->getCache($mckey)) {
            // Do nothing
        } else {
            // Grab the index from the database
            $query = "SELECT * FROM nuke_bbcategories ORDER BY cat_order";
            
            if ($this->db instanceof \sql_db) {
                if ($rs = $this->db->query($query, true)) {
                    while ($row = $rs->fetch_assoc()) {
                        $result[] = $row;
                    }
                    
                    foreach ($result as $row) {
                        $this->categories[$row['cat_id']]['title'] = $row['cat_title'];
                        $this->categories[$row['cat_id']]['order'] = $row['cat_order'];
                    }
                } else {
                    trigger_error("phpBB_index : Could not fetch list of categories");
                }
            } else {
                foreach ($this->db->fetchAll($query) as $row) {
                    $this->categories[$row['cat_id']]['title'] = $row['cat_title'];
                    $this->categories[$row['cat_id']]['order'] = $row['cat_order'];
                }
            }
                    
            $this->setCache($mckey, $this->categories, strtotime("+2 hours"));
        }
    }
    
    /**
     * Get the forum index
     * @since Version 3.2
     * @version 3.2
     * @return array
     * @todo Include newest post username, user id, post id, thread name, thread id
     */
    
    public function forums() {
        $query = "SELECT * FROM nuke_bbforums";
        
        if ($this->db instanceof \sql_db) {
            if ($rs = $this->db->query($query)) {
                while ($row = $rs->fetch_assoc()) {
                    $row['forum_name'] = html_entity_decode_utf8($row['forum_name']);
                    $this->forums[$row['forum_id']] = $row;
                }
                
                return $this->forums;
            } else {
                trigger_error("phpBB Index : Could not retrieve forums");
                return false;
            }
        } else {
            foreach ($this->db->fetchAll($query) as $row) {
                $row['forum_name'] = html_entity_decode_utf8($row['forum_name']);
                $this->forums[$row['forum_id']] = $row;
            }
            
            return $this->forums; 
        }
    }
}
