<?php

/**
 * FAQ base class
 * @since Version 3.5
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Help; 

use Railpage\AppCore;
use Railpage\Module;
use Exception;

/** 
 * Base class
 */

class Help extends AppCore {
    
    /**
     * Constructor
     * @since Version 3.8.7
     */
    
    public function __construct() {
        
        parent::__construct(); 
        
        $this->Module = new Module("help");
        
    }
    
    /** 
     * List all categories
     * @since Version 3.5
     * @return array
     */
    
    public function getCategories() {
        $query = "SELECT id_cat AS category_id, categories AS category_name, url_slug AS category_url_slug FROM nuke_faqCategories ORDER BY categories";
        
        $return = array();
        
        foreach ($this->db->fetchAll($query) as $row) {
            $row['url'] = RP_WEB_ROOT . "/help/" . $row['category_url_slug'];
            
            $return[$row['category_id']] = $row; 
        }
        
        return $return;
    }
    
    /**
     * Get category ID from URL slug
     * @since Version 3.5
     * @return int
     * @param string $urlSlug
     */
    
    public function getCategoryIDFromSlug($urlSlug = null) {
        if ($urlSlug == null) {
            throw new Exception("Cannot fetch category ID - no URL slug given"); 
        }
        
        $query = "SELECT id_cat AS category_id FROM nuke_faqCategories where url_slug = ?";
        
        return $this->db->fetchOne($query, $urlSlug); 
    }
    
    /**
     * Delete a help item
     * @since Version 3.5
     * @param int $helpId
     * @return boolean
     */
    
    public function deleteItem($helpId = false) {
        
        if (!$helpId = filter_var($helpId, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot delete help item - no ID given"); 
        }
        
        $where = array(
            "id = ?" => $helpId
        );
        
        $this->db->delete("nuke_faqAnswer", $where); 
        return true;
        
    }
    
    /**
     * Delete a help category
     * @since Version 3.5
     * @param int $categoryId
     * @return boolan
     */
    
    public function deleteCategory($categoryId = false) {
        
        if (!$categoryId = filter_var($categoryId, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot delete category - no ID given"); 
        }
        
        $where = array(
            "id_cat = ?" => $categoryId
        );
        
        $this->db->delete("nuke_faqAnswer", $where); 
        
        $where = array(
            "id_cat = ?" => $categoryId
        );
        
        $this->db->delete("nuke_faqCategories", $where); 
        
        return true;
        
    }
}
