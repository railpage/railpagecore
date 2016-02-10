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
    
    public function __construct($permalink = null) {
        if ($permalink == null) {
            throw new Exception("Cannot fetch page from its permalink - no permalink given");
        }
        
        parent::__construct(); 
        
        $query = "SELECT pid AS id FROM nuke_pages WHERE shortname = ?";
        
        $this->id = $this->db->fetchOne($query, $permalink);
        
        $this->mckey = "railpage:page=" . $this->id;
        
        $this->fetch(); 
    }
}
