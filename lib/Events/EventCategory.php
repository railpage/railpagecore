<?php

/**
 * EventCategory object
 * @since Version 3.8.7
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Events;

use Railpage\AppCore;
use Railpage\Module;
use Railpage\Debug;
use Railpage\ContentUtility;
use Railpage\Url;
use Exception;
use DateTime;

/**
 * EventCategory
 * @since Version 3.8.7
 */

class EventCategory extends AppCore {
    
    /**
     * Registry key
     * @since Version 3.9.1
     * @const string REGISTRY_KEY
     */
    
    const REGISTRY_KEY = "railpage:events.category=%d";
    
    /**
     * Memcached/Redis cache key
     * @since Version 3.9.1
     * @const string CACHE_KEY
     */
    
    const CACHE_KEY = "railpage:events.category=%d";
    
    /**
     * Category ID
     * @since Version 3.8.7
     * @var int $id
     */
    
    public $id;
    
    /**
     * Name
     * @since Version 3.8.7
     * @var string $name
     */
    
    public $name;
    
    /**
     * Descriptive text
     * @since Version 3.8.7
     * @var string $desc
     */
    
    public $desc;
    
    /**
     * URL Slug
     * @since Version 3.9.1
     * @var string $slug
     */
    
    public $slug;
    
    /**
     * URL
     * @since Version 3.8.7
     * @var string $url The URL of this event category, relative to the site root
     */
    
    public $url;
    
    /**
     * Constructor
     * @since Version 3.8.7
     * @param int $categoryId
     */
    
    public function __construct($categoryId = NULL) {
        parent::__construct();
        
        $timer = Debug::getTimer(); 
        
        $this->Module = new Module("events");
        $this->namespace = $this->Module->namespace;
        
        if (filter_var($categoryId, FILTER_VALIDATE_INT)) {
            $query = "SELECT * FROM event_categories WHERE id = ?";
        } elseif (is_string($categoryId) && strlen($categoryId) > 1) {
            $query = "SELECT * FROM event_categories WHERE slug = ?";
        }
        
        if (isset($query)) {
            if ($row = $this->db->fetchRow($query, $categoryId)) {
                $this->id = $row['id'];
                $this->name = $row['title'];
                $this->desc = $row['description'];
                $this->slug = $row['slug'];
                
                $this->createUrls();
            }
        }
        
        Debug::logEvent(__METHOD__, $timer); 
    }
    
    /**
     * Validate changes to this category
     * @return boolean
     * @throws \Exception if $this->name is empty
     * @throws \Exception if $this->desc is empty
     */
    
    private function validate() {
        if (empty($this->name)) {
            throw new Exception("Event name cannot be empty");
        }
        
        if (empty($this->desc)) {
            $this->desc = "";
            #throw new Exception("Event description cannot be empty");
        }
        
        if (empty($this->slug)) {
            $this->createSlug();
        }
        
        return true;
    }
    
    /**
     * Commit changes to this event category
     * @return boolean
     */
    
    public function commit() {
        $this->validate(); 
        
        $data = array(
            "title" => $this->name,
            "description" => $this->desc,
            "slug" => $this->slug
        );
        
        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            $where = array(
                "id = ?" => $this->id
            );
            
            $this->db->update("event_categories", $data, $where);
            
            return true;
        }
        
        $this->db->insert("event_categories", $data);
        $this->id = $this->db->lastInsertId();
        
        $this->createUrls();
        
        return true;
    }
    
    /**
     * Get events within a given date boundary
     * @param \DateTime $dateFrom A DateTime object representing the start boundary to search
     * @param \DateTime $dateTo A DateTime object represengint the end boundary to search
     * @param int $limit The number of events to return. Defaults to 15 if not provied
     * @return array
     */
    
    public function getEvents(DateTime $dateFrom = NULL, DateTime $dateTo = NULL, $limit = 15) {
        if (!$dateFrom instanceof DateTime) {
            $dateFrom = new DateTime;
        }
        
        $query = "SELECT ed.* FROM event_dates AS ed INNER JOIN event AS e ON ed.event_id = e.id WHERE e.category_id = ? AND ed.date >= ?";
        $params = array($this->id, $dateFrom->format("Y-m-d"));
        
        if ($dateTo instanceof DateTime) {
            $query .= " AND ed.date <= ?";
            $params[] = $dateTo->format("Y-m-d");
        }
        
        $query .= " ORDER BY ed.date LIMIT ?";
        $params[] = $limit;
        
        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Create a URL slug
     * @since Version 3.8.7
     */
    
    private function createSlug() {
        
        if (!empty($this->slug)) {
            return;
        }
        
        $proposal = ContentUtility::generateUrlSlug($this->name, 14);
        
        $result = $this->db->fetchAll("SELECT id FROM event_categories WHERE slug = ?", $proposal); 
        
        if (count($result)) {
            $proposal .= count($result);
        }
        
        $this->slug = $proposal;
        
    }
    
    /**
     * Create URLs
     * @since Version 3.8.7
     * @return $this
     */
    
    private function createUrls() {
        $this->createSlug(); 
        
        $this->url = new Url(sprintf("%s/category/%s", $this->Module->url, $this->slug));
    }
}
