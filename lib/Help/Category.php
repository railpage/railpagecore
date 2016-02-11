<?php

/**
 * Help class
 * @since Version 3.5
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Help; 

use Exception;
use DateTime;
use Railpage\Url;
use Railpage\ContentUtility;

/**
 * Category
 */

class Category extends Help {
    
    /**
     * Category ID
     * @since Version 3.5
     * @var int $id
     */
     
    public $id;
     
    /** 
     * Category name
     * @since Version 3.5
     * @var string $name
     */
     
    public $name;
     
    /** 
     * Category URL slug
     * @since Version 3.5
     * @var string $url_slug
     */
     
    public $url_slug;
    
    /**
     * URL relative to the site domain
     * @since Version 3.8.6
     * @var string $url
     */
    
    public $url;
     
    /** 
     * Constructor
     * @since Version 3.5
     * @param int $id
     */
     
    public function __construct($id = null) {
        parent::__construct();
         
        if ($id == null) {
            return $this;
        }
        
        $this->id = $id; 
         
        if ($this->id = filter_var($this->id, FILTER_VALIDATE_INT)) {
            $this->fetch(); 
        }
    }
    
    /**
     * Fetch category details
     */
    
    private function fetch() {
        
        $query = "SELECT id_cat AS category_id, categories AS category_name, url_slug AS category_url_slug FROM nuke_faqCategories WHERE id_cat = ?"; 
        
        if ($row = $this->db->fetchRow($query, $this->id)) {
            $this->name = $row['category_name']; 
            $this->url_slug = $row['category_url_slug'];
        }
        
        if (is_string($this->url_slug)) {
            $this->url = RP_WEB_ROOT . "/help/" . $this->url_slug;
        }
    }
    
    /**
     * Get items in this category
     * @since Version 3.5
     * @return array
     */
    
    public function getItems() {
        $query = "SELECT id AS item_id, question AS item_title, answer AS item_text, url_slug AS item_url_slug FROM nuke_faqAnswer WHERE id_cat = ? ORDER BY item_title";
        
        $return = array();
        
        foreach ($this->db->fetchAll($query, $this->id) as $row) {
            $row['url'] = RP_WEB_ROOT . "/help/" . $this->url_slug . "/" . $row['item_id'];
            
            if (!filter_var($row['item_url_slug'], FILTER_VALIDATE_INT) && is_string($row['item_url_slug']) && !empty($row['item_url_slug'])) {
                $row['url'] = RP_WEB_ROOT . "/help/" . $this->url_slug . "/" . $row['item_url_slug'];
            }

            $return[$row['item_id']] = $row; 
        }
        
        return $return;
    }
    
    /**
     * Validate changes to this category
     * @since Version 3.5
     * @return boolean
     */
    
    private function validate() {
        if (empty($this->name)) {
            throw new Exception("Cannot validate category - name cannot be empty"); 
        }
        
        if (empty($this->url_slug)) {
            $this->createSlug();
        }
        
        return true;
    }
    
    /**
     * Create a URL slug
     * @since Version 3.9.1
     */
    
    private function createSlug() {
        
        $proposal = ContentUtility::generateUrlSlug($this->name);
        
        $result = $this->db->fetchAll("SELECT id_cat FROM nuke_faqCategories WHERE url_slug = ?", $proposal); 
        
        if (count($result)) {
            $proposal .= count($result);
        }
        
        $this->url_slug = $proposal;
    }
    
    /**
     * Commit changes to this category
     * @since Version 3.6
     * @return boolean
     */
    
    public function commit() {
        $this->validate();
        
        $data = array(
            "categories" => $this->name,
            "url_slug" => $this->url_slug
        );
        
        if ($this->id) {
            $where = array("id_cat = ?" => $this->id); 
            $this->db->update("nuke_faqCategories", $data, $where);
            
            return true;
        }
        
        $this->db->insert("nuke_faqCategories", $data);
        $this->id = $this->db->lastInsertId(); 
        
        return true;
    }
}