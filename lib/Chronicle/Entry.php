<?php

/**
 * Chronicle entry
 * @since Version 3.8.7
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Chronicle;

use Railpage\Url;
use Railpage\Users\User;
use Railpage\Place;
use Railpage\Module;
use Exception;
use DateTime;
use Zend_Db_Expr;

/**
 * Entry
 */

class Entry extends Chronicle {
    
    /**
     * Status: active
     * @const STATUS_ACTIVE
     * @since Version 3.8.7
     */
    
    const STATUS_ACTIVE = 1;
    
    /**
     * Status: inactive
     * @const STATUS_INACTIVE
     * @since Version 3.8.7
     */
    
    const STATUS_INACTIVE = 0;
    
    /**
     * Entry ID
     * @since Version 3.8.7
     * @var int $id
     */
    
    public $id;
    
    /**
     * Latitude
     * @since Version 3.8.7
     * @var double $lat
     */
    
    public $lat;
    
    /**
     * Longitude
     * @since Version 3.8.7
     * @var double $lon
     */
    
    public $lon;
    
    /**
     * Blurb
     * @since Version 3.8.7
     * @var string $blurb
     */
    
    public $blurb;
    
    /**
     * Text
     * @since Version 3.8.7
     * @var string $text
     */
     
    public $text;
    
    /**
     * Status
     * @since Version 3.8.7
     * @var int $status
     */
    
    public $status;
    
    /**
     * Meta data for this chronicle event
     * @since Version 3.8.7
     * @var array $meta
     */
    
    public $meta;
    
    /**
     * Place of this event
     * @since Version 3.8.7
     * @var \Railpage\Place $Place
     */
    
    public $Place;
    
    /**
     * Date of this event
     * @since Version 3.8.7
     * @var \DateTime $Date
     */
    
    public $Date;
    
    /**
     * Submitted by
     * @since Version 3.8.7
     * @var \Railpage\Users\User $Author
     */
    
    public $Author;
    
    /**
     * Entry type
     * @since Version 3.8.7
     * @var \Railpage\Chroncile\EntryType
     */
    
    public $EntryType;
    
    /**
     * Constructor
     * @since Version 3.8.7
     * @param int $id
     */
    
    public function __construct($id = null) {
        
        parent::__construct(); 
        
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            return;
        }
        
        $this->id = $id;
        
        $query = "SELECT id, status, date, type_id, blurb, text, X(point) as lat, Y(point) AS lon, user_id, meta FROM chronicle_item WHERE id = ?";
        
        if ($row = $this->db->fetchRow($query, $this->id)) {
            $this->status = $row['status'];
            $this->blurb = $row['blurb'];
            $this->text = $row['text'];
            $this->Date = new DateTime($row['date']);
            $this->lat = $row['lat'];
            $this->lon = $row['lon'];
            $this->Author = new User($row['user_id']);
            $this->meta = json_decode($row['meta'], true);
            
            if (filter_var($row['type_id']) && $row['type_id'] > 0) {
                $this->EntryType = new EntryType($row['type_id']);
            }
            
            if (filter_var($this->lat, FILTER_VALIDATE_FLOAT) && filter_var($this->lon, FILTER_VALIDATE_FLOAT)) {
                $this->Place = new Place($this->lat, $this->lon);
            }
            
            $this->makeURLs();
        }
    }
    
    /**
     * Load the URLs for this entry
     * @since Version 3.8.7
     * @return \Railpage\Chronicle\Entry
     */
    
    public function makeURLs() {
        
        $baseurl = sprintf("%s?id=%d", $this->Module->url, $this->id);
        
        $this->url = new Url(sprintf("%s&mode=%s", $baseurl, "entry.view"));
        $this->url->approve = sprintf("%s&mode=%s", $baseurl, "entry.approve");
        $this->url->edit = sprintf("%s&mode=%s", $baseurl, "entry.edit");
        $this->url->delete = sprintf("%s&mode=%s", $baseurl, "entry.delete");
        $this->url->year = sprintf("%s?year=%d", $this->Module->url, $this->Date->format("Y"));
        $this->url->decade = sprintf("%s?decade=%d", $this->Module->url, substr($this->Date->format("Y"), 0, 3) . "0");
        
    }
    
    /**
     * Validate changes to this entry
     * @since Version 3.8.7
     * @return boolean
     * @throws \Exception if $this->Author is not an instance of \Railpage\Users\User
     * @throws \Exception if $this->Date is not an instance of \DateTime
     * @throws \Exception if $this->blurb is empty
     */
    
    private function validate() {
        
        if (!$this->Author instanceof User) {
            throw new Exception("A valid user object must be set using " . __CLASS__ . "::setAuthor()");
        }
        
        if (!$this->Date instanceof DateTime) {
            throw new Exception("A valid instance of DateTime must be set for this chronicle entry");
        }
        
        if (!$this->EntryType instanceof EntryType) {
            throw new Exeption("A valid instance of \Railpage\Chronicle\EntryType must be set for this chronicle entry");
        }
        
        if (empty($this->blurb)) {
            throw new Exception("Cannot save changes to this chronicle entry - blurbs (short text) cannot be empty");
        }
        
        if (empty($this->status)) {
            $this->status = self::STATUS_INACTIVE;
        }
        
        if (empty($this->text) || trim($this->text) == "x") {
            $this->text = "";
        }
        
        return true;
        
    }
    
    /**
     * Commit changes to this entry
     * @since Version 3.8.7
     * @return \Railpage\Chronicle\Entry
     */
    
    public function commit() {
        
        $this->validate(); 
        
        /**
         * Check if this entry already exists, and if it does let's just switch to that one
         */
         
        $query = "SELECT id FROM chronicle_item WHERE date = ? AND type_id = ? AND blurb = ? AND user_id = ?";
        $params = array($this->Date->format("Y-m-d H:i:s"), $this->EntryType->id, $this->blurb, $this->Author->id);
        
        if ($id = $this->db->fetchOne($query, $params)) {
            $this->id = $id;
        }
        
        $data = array(
            "status" => $this->status,
            "date" => $this->Date->format("Y-m-d H:i:s"),
            "type_id" => $this->EntryType->id,
            "blurb" => $this->blurb,
            "text" => $this->text,
            "user_id" => $this->Author->id,
            "meta" => json_encode($this->meta)
        );
        
        if (filter_var($this->lat, FILTER_VALIDATE_FLOAT) && filter_var($this->lon, FILTER_VALIDATE_FLOAT)) {
            $data['point'] = new Zend_Db_Expr(sprintf("POINT(%s, %s)", $this->lat, $this->lon));
        }
        
        if (filter_var($this->id, FILTER_VALIDATE_INT) && $this->id > 0) {
            $where = array(
                "id = ?" => $this->id
            );
            
            $this->db->update("chronicle_item", $data, $where); 
            
            return $this;
        }
        
        $this->db->insert("chronicle_item", $data); 
        $this->id = $this->db->lastInsertId(); 
        $this->makeURLs();
        
        return $this;
    }
    
    /**
     * Link something to this entry
     * @since Version 3.8.7
     * @param object $object
     * @throws \Exception if $object is not an object
     * @throws \Exception if $object does not have a valid instance of \Railpage\Module
     * @return \Railpage\Chronicle\Entry
     */
    
    public function AddLink($object) {
        if (!is_object($object)) {
            throw new Exception("Can't link something to this chronicle entry because an invalid target object was supplied");
        }
        
        if (!$object->Module instanceof Module) {
            throw new Exception("Can't link something to this chronicle entry because the target object doesn't have a valid module attached");
        }
        
        /**
         * Check if this link already exists
         */
         
        $query = "SELECT id FROM chronicle_link WHERE id = ? AND object = ? AND object_id = ?";
        $params = array($this->id, get_class($object), $object->id);
        
        if (!$this->db->fetchOne($query, $params)) {
            $data = array(
                "object" => get_class($object),
                "object_id" => $object->id,
                "item_id" => $this->id,
                "module" => $object->Module->name
            );
            
            $this->db->insert("chronicle_link", $data);
        }
        
        return $this;
    }
}
