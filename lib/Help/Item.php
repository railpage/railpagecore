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
 * Item
 */

class Item extends Help {
    
    /**
     * Item ID
     * @since Version 3.5
     * @var int $id
     */
     
    public $id;
     
    /** 
     * Item title
     * @since Version 3.5
     * @var string $title
     */
     
    public $title;
     
    /** 
     * Item text
     * @since Version 3.5
     * @var string $text
     */
     
    public $text;
    
    /**
     * Last updated
     * @since Version 3.5
     * @var object $timestamp
     */
    
    public $timestamp;
    
    /**
     * URL slug
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
     * Category object
     * @since Version 3.5
     * @var object $category
     */
    
    public $category;
     
    /** 
     * Constructor
     * @since Version 3.5
     * @param int $id
     */
     
    public function __construct($id = null) {
        parent::__construct(); 
         
        if ($id == null) {
            return;
        }
        
        if (filter_var($id, FILTER_VALIDATE_INT)) {
            $this->id = $id; 
        } elseif (is_string($id)) {
            $query = "SELECT id FROM nuke_faqAnswer WHERE url_slug = ?";
            $this->id = $this->db->fetchOne($query, $id);
        }
         
        if ($this->id = filter_var($this->id, FILTER_VALIDATE_INT)) {
            $this->fetch(); 
        }
    }
    
    /**
     * Fetch item details
     */
    
    private function fetch() {
        
        $query = "SELECT id_cat AS category_id, id AS item_id, question AS item_title, answer AS item_text, url_slug AS item_url_slug, timestamp AS item_timestamp FROM nuke_faqAnswer WHERE id = ?";
        
        if ($row = $this->db->fetchRow($query, $this->id)) {
            $this->title        = $row['item_title']; 
            $this->text         = $row['item_text'];
            $this->url_slug     = $row['item_url_slug']; 
            $this->timestamp    = new DateTime($row['item_timestamp']); 
            $this->category     = new Category($row['category_id']); 
            $this->validate(); 
            $this->makeUrls();  
        }
        
    }
    
    /**
     * Validate changes to a help item
     * @since Version 3.5
     * @return boolean
     */
    
    public function validate() {
        if (empty($this->title)) {
            throw new Exception("Cannot validate help item - title is empty");
        } 
        
        if (empty($this->text)) {
            throw new Exception("Cannot validate help item - text is empty"); 
        }
        
        if (!$this->category instanceof Category) {
            throw new Exception("No valid category has been set"); 
        }
        
        if (filter_var($this->url_slug, FILTER_VALIDATE_INT) || empty($this->url_slug) || !is_string($this->url_slug)) {
            $this->url_slug = $this->createSlug(); 
        }
        
        return true;
    }
    
    /**
     * Commit changes to this help item
     * @since Version 3.5
     * @return boolean
     */
    
    public function commit() {
        $this->validate();
        
        $data = array(
            "id_cat" => $this->category->id,
            "question" => $this->title,
            "answer" => $this->text,
            "url_slug" => $this->url_slug
        );
        
        if ($this->id) {
            $where = array("id = ?" => $this->id); 
            
            $this->db->update("nuke_faqAnswer", $data, $where); 
        } else {
            $this->db->insert("nuke_faqAnswer", $data); 
            $this->id = $this->db->lastInsertId(); 
        }
        
        $this->makeUrls();
        
        return true;
    }
    
    /**
     * Generate the URL slug for this help item
     * @since Version 3.8.6
     * @return string
     */
    
    private function createSlug() {
        
        $proposal = ContentUtility::generateUrlSlug($this->title);
        
        /**
         * Check that we haven't used this slug already
         */
        
        $result = $this->db->fetchAll("SELECT id FROM nuke_faqAnswer WHERE url_slug = ?", $proposal); 
        
        if (count($result)) {
            $proposal .= count($result);
        }
        
        /**
         * Return it
         */
        
        return $proposal;
    }
    
    /**
     * Get contributors to this help item
     * @since Version 3.7.5
     * @return array
     */
    
    public function getContributors() {
        $return = array(); 
        
        $query = "SELECT DISTINCT l.user_id, u.username FROM log_general AS l LEFT JOIN nuke_users AS u ON u.user_id = l.user_id WHERE l.module = ? AND l.key = ? AND l.value = ?";
        
        foreach ($this->db->fetchAll($query, array("help", "help_id", $this->id)) as $row) {
            $return[$row['user_id']] = $row['username']; 
        }
        
        return $return;
    }
    
    /**
     * Create URLs
     * @since Version 3.8.7
     * @return void
     */
    
    private function makeUrls() {
        
        $this->url = new Url(sprintf("%s/%s/%s", $this->Module->url, $this->category->url_slug, $this->url_slug));
        
    }
}
