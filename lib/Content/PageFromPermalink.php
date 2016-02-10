<?php

/**
 * Load a page from its permalink
 * @package Railpage
 * @author Michael Greenhill
 * @since Version 3.5
 */

namespace Railpage\Content; 

use Exception;

//require_once(__DIR__ . DIRECTORY_SEPARATOR . "Page.php");

/**
 * Fetch page by permalink
 */
 
class PageFromPermalink extends Page {
    
    /**
     * Constructor
     * @since Version 3.5
     * @param string $permalink
     */
    
    public function __construct($permalink = false) {
        if (!$permalink) {
            throw new Exception("Cannot fetch page from its permalink - no permalink given");
            return false;
        }
        
        try {
            parent::__construct(); 
        } catch (Exception $e) {
            throw new Exception($e->getMessage()); 
        }
        
        if ($this->db instanceof \sql_db) {
            // Start loading the permalink
            $query = "SELECT pid AS id FROM nuke_pages WHERE shortname = '".$this->db->real_escape_string($permalink)."'"; 
            
            if ($rs = $this->db->query($query)) {
                if ($rs->num_rows == 0) {
                    throw new Exception("Could not find a page for permalink of ".$permalink); 
                    return false; 
                }
                
                if ($rs->num_rows == 1) {
                    $row = $rs->fetch_assoc();
                    
                    try {
                        $this->id = $row['id']; 
                        
                        $this->mckey = "railpage:page=" . $this->id;
                        
                        $this->fetch(); 
                    } catch (Exception $e) {
                        throw new Exception($e->getMessage()); 
                    }
                }
                
                if ($rs->num_rows > 1) {
                    throw new Exception("Found more than one page for permalink of ".$permalink." - this should never happen"); 
                    return false;
                }
            }
        } else {
            $query = "SELECT pid AS id FROM nuke_pages WHERE shortname = ?";
            
            $this->id = $this->db->fetchOne($query, $permalink);
            
            $this->mckey = "railpage:page=" . $this->id;
            
            $this->fetch(); 
        }
    }
}
