<?php

/** 
 * Loco database
 * @since Version 3.2
 * @author Michael Greenhill
 * @package Railpage
 */

namespace Railpage\Locos;

use Railpage\Locos\Liveries\Livery;
use Railpage\Users\User;
use Railpage\Users\Factory as UserFactory;
use Railpage\Users\Utility\UrlUtility as UserUrlUtility;
use Railpage\Images\Images;
use Railpage\Images\Image;
use Railpage\Assets\Asset;
use Railpage\ContentUtility;
use Railpage\Url;
use Railpage\Debug;
use Railpage\AppCore;
use DateTime;
use Exception;
use stdClass;
use Railpage\Registry;
use Railpage\Users\Utility\AvatarUtility;
use Railpage\Sightings\Sightings;
use Railpage\Sightings\Sighting;
    
/**
 * Locomotive
 */

class Locomotive extends Locos {
    
    /**
     * Registry cache key
     * @since Version 3.9.1
     * @const string REGISTRY_KEY
     */
    
    const REGISTRY_KEY = "railpage.locos.loco=%d";
    
    /**
     * Memcached/Redis cache key
     * @since Version 3.9.1
     * @const string CACHE_KEY
     */
    
    const CACHE_KEY = "railpage:locos.loco_id=%d";
    
    /**
     * Memcached key for loco descriptive text
     * @since Version 3.10.0
     * @const string CACHE_KEY_DESC
     */
    
    const CACHE_KEY_DESC = "railpage:locos.loco_id=%d;desc";
    
    /**
     * Loco ID
     * @since Version 3.2
     * @var int $id
     */
    
    public $id;
    
    /**
     * Loco number
     * @since Version 3.2
     * @var string $number Locomotive fleet number, eg R761 or NR101
     */
    
    public $number;
    
    /**
     * Flickr tag
     * @since Version 3.2
     * @var string $flickr_tag
     */
    
    public $flickr_tag;
    
    /**
     * Gauge
     * @since Version 3.2
     * @var string $gauge
     */
    
    public $gauge;
    
    /**
     * Gauge ID
     * @since Version 3.4
     * @var int $gauge_id
     */
    
    public $gauge_id;
    
    /**
     * Gauge - formatted to look nicer
     * @since Version 3.2
     * @var string $gauge_formatted
     */
    
    public $gauge_formatted;
    
    /**
     * Status ID
     * @since Version 3.2
     * @var int $status_id
     */
    
    public $status_id;
    
    /**
     * Loco status
     * @since Version 3.2
     * @var string $status
     */
    
    public $status;
    
    /**
     * Class ID
     * @since Version 3.2
     * @var int $class_id
     */
    
    public $class_id;
    
    /**
     * Class object
     * @since Version 3.8.7
     * @var \Railpage\Locos\LocoClass $class Instance of \Railpage\Locos\LocoClass that this locomotive belongs to
     */
    
    public $Class;
    
    /**
     * Alias of $this->Class
     * @since Version 3.2
     * @var \Railpage\Locos\LocoClass $class Instance of \Railpage\Locos\LocoClass that this locomotive belongs to
     */
    
    public $class;
            
    /**
     * All owners
     * @since Version 3.4
     * @var array $owners An array of owners of this locomotive
     */
    
    public $owners; 
    
    /**
     * Owner ID
     * @since Version 3.2
     * @var int $owner_id The ID of the current/newest owner
     */
    
    public $owner_id;
    
    /**
     * Owner name
     * @since Version 3.2
     * @var string $owner The formatted name of the current/newest owner
     */
    
    public $owner;
    
    /**
     * All operators
     * @since Version 3.4
     * @var array $operators An array of operators of this locomotive
     */
    
    public $operators; 
    
    /**
     * Operator ID
     * @since Version 3.2
     * @var int $operator_id The ID of the current/newest operator
     */
    
    public $operator_id;
    
    /**
     * Operator name
     * @since Version 3.2
     * @var string $operator The formatted name of the current/newest operator
     */
    
    public $operator;
    
    /**
     * Entered service date
     * @deprecated Deprecated since Version 3.8.7 - replaced by \Railpage\LocoClass\Date objects
     * @since Version 3.2
     * @var int $entered_service
     */
    
    public $entered_service;
    
    /**
     * Withdrawal date
     * @deprecated Deprecated since Version 3.8.7 - replaced by \Railpage\LocoClass\Date objects
     * @since Version 3.2
     * @var int $withdrawal_date
     */
    
    public $withdrawal_date;
    
    /**
     * Builders number
     * @since Version 3.2
     * @var string $builders_num The builders number
     */
    
    public $builders_num;
    
    /**
     * Loco photo ID
     * @since Version 3.2
     * @var int $photo_id ID of a Flickr photo to show as the cover photo of this locomotive
     */
    
    public $photo_id;
    
    /**
     * Loco builder ID
     * @since Version 3.2
     * @var int $manufacturer_id
     */
    
    public $manufacturer_id;
    
    /**
     * Loco builder name
     * @since Version 3.2
     * @var int $manufacturer
     */
    
    public $manufacturer;
    
    /**
     * Date added
     * @since Version 3.2
     * @var int $date_added When this locomotive was added to the database
     */
    
    public $date_added;
    
    /**
     * Date modified
     * @since Version 3.2
     * @var int $date_modified When this locomotive was last modified in the database
     */
    
    public $date_modified;
    
    /**
     * Locomotive name
     * @since Version 3.2
     * @var string $name
     */
    
    public $name;
    
    /**
     * Locomotive data rating
     * @since Version 3.2
     * @var float $rating
     */
    
    public $rating;
    
    /**
     * Memcache key
     * @since Version 3.7.5
     * @var string $mckey The unique Memcached identifier of this locomotive
     */
    
    public $mckey; 
    
    /**
     * Loco URL
     * @since Version 3.8
     * @var string $url The link to this locomotive's page, relative to the site root of Railpage
     */
    
    public $url;
    
    /**
     * Asset ID for non-Flickr cover photo
     * @since Version 3.8.7
     * @var \Railpage\Assets\Asset $Asset An instance of \Railpage\Assets\Asset identified as the "primary" asset for this locomotive. Could be featuring the cover photo.
     */
    
    public $Asset;
    
    /**
     * Array of liveries worn by this locomotive
     * @since Version 3.8.7
     * @var array $liveries
     */
    
    public $liveries;
    
    /**
     * Loco meta data
     * @since Version 3.8.7
     * @var array $meta
     */
    
    public $meta;
    
    /**
     * Constructor
     * @since Version 3.2
     * @param int $id
     * @param int|string $classIdOrSlug
     * @param string $number
     */
    
    public function __construct($id = NULL, $classIdOrSlug = NULL, $number = NULL) {
        parent::__construct(); 
        
        $timer = Debug::getTimer();
        
        /**
         * Record this in the debug log
         */
            
        Debug::RecordInstance(NULL, $id);
        
        $this->bootstrap(); 
        
        if (filter_var($id, FILTER_VALIDATE_INT)) {
            $this->id = filter_var($id, FILTER_VALIDATE_INT);
        } else {
            $this->id = Utility\LocomotiveUtility::getLocoId($classIdOrSlug, $number); 
        }
        
        // Load the loco object
        if (filter_var($this->id, FILTER_VALIDATE_INT)) {
            $this->fetch(); 
        }
        
        $this->id = intval($this->id);
        
        Debug::logEvent(sprintf("%s(%d)", __METHOD__, $this->id), $timer); 
    }
    
    /**
     * Bootstrap this class
     * @since Version 3.9.1
     * @return void
     */
    
    private function bootstrap() {
        $this->namespace = sprintf("%s.%s", $this->Module->namespace, "loco");
        
        /**
         * List of templates
         */
        
        $this->Templates = new stdClass;
        $this->Templates->view = "loco";
        $this->Templates->edit = "loco.edit";
        $this->Templates->sightings = "loco.sightings";
    }
    
    /**
     * Populate this object with data returned from Memcached/Redis/DB
     * @since Version 3.9.1
     * @return void
     */
    
    private function populate() {
        
        $timer = Debug::getTimer();
        
        $row = Utility\LocomotiveUtility::fetchLocomotive($this); 
        
        if (!is_array($row) || count($row) === 0) {
            throw new Exception("Data for this locomotive could not be retrieved") ;
        }
        
        $lookup = Utility\DataUtility::getLocoColumnMapping(); 
        
        foreach ($row as $key => $val) {
            if (isset($lookup[$key])) {
                $var = $lookup[$key];
                $this->$var = $val;
            }
        }
        
        $ints = [ "gauge_id", "status_id", "class_id", "owner_id", "operator_id", "photo_id", "manufacturer_id" ];
        
        foreach ($ints as $int) {
            $this->$int = filter_var($this->$int, FILTER_VALIDATE_INT); 
        }
        
        $this->Class = Factory::CreateLocoClass($this->class_id); 
        $this->class = &$this->Class;
        
        $this->flickr_tag = trim(str_replace(" ", "", $this->Class->flickr_tag . "-" . $this->number));
        $this->gauge_formatted = format_gauge($this->gauge);
        $this->makeLinks();
        
        Debug::logEvent(__METHOD__, $timer); 
        
        return $row; 
    }
    
    /**
     * Load the URL object
     * @since Version 3.9.1
     * @return void
     */
    
    private function makeLinks() {
        
        if (!$this->Class instanceof LocoClass) {
            return;
        }
        
        $this->url = new Url(strtolower($this->makeLocoURL($this->Class->slug, $this->number)));
        $this->url->edit = sprintf("%s?mode=loco.edit&id=%d", $this->Module->url, $this->id);
        $this->url->sightings = sprintf("%s/sightings", $this->url->url);
        $this->url->photos = sprintf("%s/photos", $this->url->url);
        $this->fwlink = $this->url->short;
        
    }
    
    /**
     * Load the locomotive object
     * @since Version 3.2
     * @version 3.2
     * @return boolean
     */
    
    public function fetch() {
        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot load loco object - loco ID not provided");
            return false;
        }
        
        $timer = Debug::getTimer();
        
        $this->mckey = sprintf(self::CACHE_KEY, $this->id);
        
        $row = $this->populate(); 
            
        /**
         * Set the meta data
         */
        
        $this->meta = isset($row['meta']) ? json_decode($row['meta'], true) : array(); 
        
        /**
         * Fetch a nicely formatted gauge
         */
        
        #$this->setGauge(new Gauge($row['loco_gauge_id'])); 
        $this->setGauge(Factory::Create("Gauge", $row['loco_gauge_id'])); 
        
        /**
         * If an asset ID exists and is greater than 0, create the asset object
         */
        
        if (isset($row['asset_id']) && $row['asset_id'] > 0) {
            try {
                $this->Asset = new Asset($row['asset_id']);
            } catch (Exception $e) {
                // throw it away
            }
        }
        
        /**
         * Do we need to update the database and Memcached records?
         */
        
        $doUpdate = false;
        
        /**
         * Get all owners of this locomotive
         */
        
        $this->reloadOrganisations("owners"); 
        $this->reloadOrganisations("operators"); 
        
        /**
         * Get the manufacturer
         */
        
        $this->loadManufacturer(); 
        
        /**
         * Set the StatsD namespaces
         */
        
        $this->StatsD->target->view = sprintf("%s.%d.view", $this->namespace, $this->id);
        $this->StatsD->target->edit = sprintf("%s.%d.view", $this->namespace, $this->id);
        
        /**
         * Update the database and Memcached records if required
         */
        
        if ($doUpdate) {
            $this->commit(); 
        }
        
        Debug::logEvent(__METHOD__, $timer); 
    }
    
    /**
     * Load the manufacturer for this locomotive
     * @since Version 3.9.1
     * @return void
     */
    
    private function loadManufacturer() {
        
        if (empty($this->manufacturer_id)) {
            $this->manufacturer_id  = $this->Class->manufacturer_id;
            $this->manufacturer     = $this->Class->manufacturer;
            
            return;
        }
        
        try {
            $builders = $this->listManufacturers(); 
            
            if (count($builders['manufacturers'])) {
                $this->manufacturer = $builders['manufacturers'][$this->manufacturer_id]['manufacturer_name'];
            }
        } catch (Exception $e) {
            // throw it away
        }
        
        return;
        
    }
    
    /**
     * Update the owners/operators
     * @since Version 3.9.1
     * @param string $type
     * @return void
     */
    
    private function reloadOrganisations($type) {
        
        if (substr($type, -1) !== "s") {
            $type .= "s";
        }
        
        $allowed = [ "owners", "operators" ];
        
        if (!in_array($type, $allowed)) {
            throw new InvalidArgumentException("Cannot update owners/operators/organisations: " . $type . " is an invalid organisation type"); 
        }
        
        $lookup = [
            "owners" => 1,
            "operators" => 2
        ];
        
        $type_id = $lookup[$type];
        
        $var_name = substr($type, 0, -1);
        $var_name_id = substr($type, 0, -1) . "_id";
        
        $this->$type = $this->getOrganisations($type_id); 
            
        reset($this->$type);
        $array = $this->$type;
        
        if (isset($array[0]['organisation_id']) && isset($array[0]['organisation_name'])) {
            $this->$var_name_id = $array[0]['organisation_id']; 
            $this->$var_name    = $array[0]['organisation_name']; 
            Debug::LogEvent(__METHOD__ . "() : Latest " . $var_name . " ID requires updating");
            
            return;
        }
        
        $this->$var_name_id = 0;
        $this->$var_name    = "Unknown";
        
        return;

    }
    
    /**
     * Validate
     * @since Version 3.2
     * @version 3.2
     * @return boolean
     */
    
    private function validate() {
        
        if ($this->class instanceof LocoClass && !$this->Class instanceof LocoClass) {
            $this->Class = &$this->class;
        }
        
        if (empty($this->number)) {
            throw new Exception("No locomotive number specified");
        }
        
        if (!filter_var($this->class_id, FILTER_VALIDATE_INT) || $this->class_id === 0) {
            if ($this->Class instanceof LocoClass) {
                $this->class_id = $this->Class->id;
            } else {
                throw new Exception("Cannot add locomotive because we don't know which class to add it into");
            }
        }
        
        if (!filter_var($this->gauge_id, FILTER_VALIDATE_INT)) {
            throw new Exception("No gauge has been set");
        }
        
        if (!filter_var($this->status_id, FILTER_VALIDATE_INT)) {
            throw new Exception("No status has been set");
        }
        
        /**
         * Validate integers and set to zero if neccessary
         */
        
        $ints = [ "owner_id", "operator_id", "photo_id", "manufacturer_id" ];
        
        foreach ($ints as $int) {
            if (!filter_var($this->$int, FILTER_VALIDATE_INT)) {
                $this->$int = 0;
            }
        }
        
        /**
         * The database doesn't like NULLs so set them to an empty character
         */
        
        $texts = [ "entered_service", "withdrawal_date", "builders_num", "name" ];
        
        foreach ($texts as $text) {
            if (is_null($this->$text)) {
                $this->$text = "";
            }
        }
        
        return true;
    }
    
    /**
     * Commit changes to database
     * @since Version 3.2
     * @version 3.8.7
     * @return boolean
     */
    
    public function commit() {
        
        $timer = Debug::getTimer();
        
        $this->validate();
        
        $data = Utility\LocomotiveUtility::getSubmitData($this);
        
        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            $rs = $this->db->insert("loco_unit", $data); 
            $this->id = $this->db->lastInsertId(); 
            
            $verb = "Insert";
        } else {
            $this->Memcached->delete($this->mckey);
            $this->Redis->delete($this->mckey); 
            
            $where = array(
                "loco_id = ?" => $this->id
            );
            
            $verb = "Update";
            
            $rs = $this->db->update("loco_unit", $data, $where); 
        }
        
        // Update the registry
        $Registry = Registry::getInstance(); 
        $regkey = sprintf(self::REGISTRY_KEY, $this->id); 
        $Registry->remove($regkey)->set($regkey, $this); 
        $this->Memcached->delete(sprintf(self::CACHE_KEY_DESC, $this->id));
        
        Debug::logEvent("Zend_DB: commit loco ID " . $this->id, $timer); 
        
        $this->makeLinks(); 
        
        return true;
    }
    
    /**
     * Add note to this loco or edit an existing one
     * @since Version 3.2
     * @param string $noteText
     * @param int $userId
     * @param int $noteId
     */
    
    public function addNote($noteText = false, $userId = false, $noteId = false) {
        if (!$noteText || empty($noteText)) {
            throw new Exception("No note text given"); 
        } 
        
        if (!$userId instanceof User && !filter_var($userId, FILTER_VALIDATE_INT)) {
            throw new Exception("No user provided"); 
        }
        
        if ($userId instanceof User) {
            $userId = $userId->id;
        }
        
        $data = array(
            "loco_id" => $this->id,
            "note_date" => time(),
            "note_text" => $noteText
        );
        
        if (!empty($userId)) {
            $data['user_id'] = $userId;
        }
        
        if ($noteId) {
            $where = array(
                "note_id = ?" => $noteId
            );
            
            $this->db->update("loco_notes", $data, $where);
            return true;
        } else {
            $this->db->insert("loco_notes", $data);
            return $this->db->lastInsertId(); 
        }
    }
    
    /**
     * Load notes
     * @since Version 3.2
     * @version 3.2
     * @return array
     */
    
    public function loadNotes() {
        $query = "SELECT n.*, u.username, user_avatar FROM loco_notes AS n LEFT JOIN nuke_users AS u ON n.user_id = u.user_id WHERE n.loco_id = ?";
        
        $notes = array(); 
        
        foreach ($this->db->fetchAll($query, $this->id) as $row) {
            if (!empty($row['user_avatar'])) {
                try {
                    #$User = UserFactory::CreateUser($row['user_id']);
                    
                    $row['user_avatar'] = AvatarUtility::Format($row['user_avatar'], 50, 50);
                    #$row['user_url'] = $User->url;
                    
                    $row['user_url'] = UserUrlUtility::MakeURLs($row);
                } catch (Exception $e) {
                    global $Error; 
                    $Error->save($e); 
                }
            }
            
            $notes[$row['note_id']] = $row; 
        }
        
        return $notes;
    }
    
    /**
     * Load dates
     * @since Version 3.2
     * @version 3.2
     * @return array
     */
    
    public function loadDates() {
        $query = "SELECT d.date_id, d.date, d.text, dt.loco_date_text AS title, dt.loco_date_id AS date_type_id
                    FROM loco_unit_date AS d
                    LEFT JOIN loco_date_type AS dt ON d.loco_date_id = dt.loco_date_id
                    WHERE d.loco_unit_id = ?
                    ORDER BY d.date DESC";
        
        return $this->db->fetchAll($query, $this->id);
    }
    
    /**
     * Add a date to this loco
     * @since Version 3.2
     * @param int $dateId
     * @param string $dateDate
     * @param string $dateText
     * @return boolean
     */
    
    public function addDate($dateId = false, $dateDate = false, $dateText = false) {
        
        $Date = new Date;
        $Date->action = $dateText;
        $Date->action_id = $dateId;
        $Date->Date = new DateTime($dateDate); 
        
        $Date->commit(); 
        
        return true;
        
    }
    
    /**
     * Get link(s) of this loco
     * @since Version 3.2
     * @return array
     */
    
    public function links() {
        $query = "SELECT * FROM loco_link WHERE loco_id_a = ? OR loco_id_b = ?";
        $return = array();
        
        foreach ($this->db->fetchAll($query, array($this->id, $this->id)) as $row) {
            $article = $row['loco_id_a'] === $this->id ? "to" : "from";
            $key = $row['loco_id_a'] === $this->id ? "loco_id_b" : "loco_id_a";
            
            if ($row['link_type_id'] === RP_LOCO_RENUMBERED) {
                $return[$row['link_id']][$row[$key]] = "Renumbered " . $article;
            } elseif ($row['link_type_id'] === RP_LOCO_REBUILT) {
                $return[$row['link_id']][$row[$key]] = "Rebuilt " . $article;
            }
        }
        
        return $return;
    }
    
    /**
     * Save a correction for this loco
     * @since Version 3.2
     * @param string $text
     * @param int $userId
     */
    
    public function newCorrection($text = false, $userId = false) {
        
        $Correction = new Correction;
        $Correction->text = $text;
        $Correction->setUser(UserFactory::CreateUser($userId)); 
        $Correction->setObject($this);
        $Correction->commit(); 
        
        return true;
    }
    
    /**
     * Get corrections for this loco
     * @since Version 3.2
     * @param boolean $active
     * @return array
     */
    
    public function corrections($active = true) {
        if ($active) {
            $active_sql = " AND c.status = 0 ";
        } else {
            $active_sql = "";
        }
        
        $query = "SELECT c.correction_id, c.user_id, UNIX_TIMESTAMP(c.date) as date, c.status, c.text , u.username
            FROM loco_unit_corrections AS c
            LEFT JOIN nuke_users AS u ON c.user_id = u.user_id
            WHERE c.loco_id = ? " . $active_sql;
        
        $return = array(); 
        
        foreach ($this->db->fetchAll($query, $this->id) as $row) {
            $return[$row['correction_id']] = $row; 
        }
        
        return $return;
    }
    
    /**
     * Get ratings for this loco
     * @since Version 3.2
     * @param boolean $detailed
     * @return float
     */
    
    public function getRating($detailed = false) {
        if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot fetch rating - no loco ID given"); 
        }
        
        if ($detailed) {
            $query = "SELECT 
                        COALESCE(AVG(rating), 0) as dec_avg, 
                        COALESCE(ROUND(AVG(rating)), 0) AS whole_avg,
                        COALESCE(COUNT(rating), 0) AS number_votes, 
                        COALESCE(SUM(rating), 0) AS total_points 
                        FROM rating_loco 
                        WHERE loco_id = ?"; 
            
            return $this->db->fetchRow($query, $this->id); 
            
            /*
            $row = array(
                "dec_avg" => 0,
                "whole_avg" => 0,
                "total_points" => 0,
                "number_votes" => 0
            );
            
            $row = $this->db->fetchRow($query, $this->id); 
            
            $row['dec_avg'] = empty($row['dec_avg']) ? 0 : $row['dec_avg'];
            $row['total_points'] = empty($row['total_points']) ? 0 : $row['total_points'];
            $row['number_votes'] = empty($row['number_votes']) ? 0 : $row['number_votes'];
            $row['whole_avg'] = round($row['dec_avg']);
            
            return $row;
            */
        }
        
        $query = "SELECT COALESCE(AVG(rating), '2.5') as average_rating FROM rating_loco WHERE loco_id = ?"; 
        
        return $this->db->fetchOne($query, $this->id); 
        
        /*
        $row = $this->db->fetchRow($query, $this->id);
        
        return isset($row['average_rating']) ? $row['average_rating'] : floatval("2.5"); 
        */
    }
    
    /**
     * Get this user's rating for this loco
     * @since Version 3.2
     * @param int $userId
     * @return float|boolean
     */
    
    public function userRating($userId = false) {
        if (!$userId instanceof User && !filter_var($userId, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot fetch user rating for this loco - no user given"); 
        }
        
        if ($userId instanceof User) {
            $userId = $userId->id;
        }
        
        $query = "SELECT rating FROM rating_loco WHERE user_id = ? AND loco_id = ? LIMIT 1"; 
        
        $rating = $this->db->fetchOne($query, array($userId, $this->id)); 
        
        return $rating;
    }
    
    /**
     * Set user rating for this loco
     * @since Version 3.2
     * @param int $userId
     * @param float $rating
     * @return boolean
     */
     
    public function setRating($userId = false, $rating = false) {
        if (!$userId instanceof User && !filter_var($userId, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot set user rating for this loco - no user given"); 
        }
        
        if ($userId instanceof User) {
            $userId = $userId->id;
        }
        
        if (!filter_var($rating, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot set user rating for this loco - no rating given"); 
        }
        
        $rating = floatval($rating); 
        
        $data = array(
            "loco_id" => $this->id,
            "user_id" => $userId,
            "rating" => $rating,
            "date" => new \Zend_Db_Expr('NOW()')
        );
        
        if ($this->userRating($userId)) {
            $where = array(
                "user_id = ?" => $userId,
                "loco_id = ?" => $this->id
            );
            
            $this->db->update("rating_loco", $data, $where);
        } else {
            $this->db->insert("rating_loco", $data);
        }
        
        return true;
    }
    
    /**
     * Get liveries carried by this loco
     * Based on tagged Flickr photos
     * @since Version 3.2
     * @param object $f
     * @return array|boolean
     */
    
    public function getLiveries($f = false) {
        
        return Utility\LocomotiveUtility::getLiveriesForLocomotive($this->id); 
        
    }
    
    /**
     * Get organisation links by the given type
     * @since Version 3.4
     * @param int $orgType
     * @param int $limit
     * @return array
     */
    
    public function getOrganisations($orgType = null, $limit = null) {
        
        $limit_sql = NULL;
        $org_sql = "";
        
        if (!is_null($limit)) {
            $limit_sql = "LIMIT 0, 1"; 
        }
        
        $params = array($this->id); 
        
        if (filter_var($orgType, FILTER_VALIDATE_INT)) {
            $org_sql = " AND ot.id = ?";
            $params[] = $orgType;
        }
        
        $query = "SELECT o.*, op.operator_id AS organisation_id, op.operator_name AS organisation_name FROM loco_org_link AS o LEFT JOIN loco_org_link_type AS ot ON ot.id = o.link_type LEFT JOIN operators AS op ON op.operator_id = o.operator_id WHERE o.loco_id = ? " . $org_sql . " ORDER BY ot.id, o.link_weight DESC ".$limit_sql.""; 
        
        $return = $this->db->fetchAll($query, $params);
        return $return;
        
    }
    
    /**
     * Add an organisation link
     * @since Version 3.4
     * @param int $orgId
     * @param int $orgType
     * @param int $date
     * @param int $weight
     */
    
    public function addOrganisation($orgId = null, $orgType = null, $date = null, $weight = 0) {
        if (!filter_var($orgId, FILTER_VALIDATE_INT)) {
            throw new Exception("Could not add new organisation link - no org_id given"); 
            return false;
        }
        
        if (!filter_var($orgType, FILTER_VALIDATE_INT)) {
            throw new Exception("Could not add new organisation link - no org_type_id given"); 
            return false;
        }
        
        $data = array(
            "loco_id" => $this->id,
            "operator_id" => $orgId,
            "link_type" => $orgType,
            "link_weight" => $weight
        );
        
        if (!is_null($date)) {
            $timestamp = strtotime($date); 
            
            $data['link_date'] = date("Y-m-d H:i:s", $timestamp);
        }
        
        return $this->db->insert("loco_org_link", $data);
    }
    
    /**
     * Delete an organisation link
     * @since Version 3.4
     * @param int $orgLinkId
     * @return boolean
     */
    
    public function deleteOrgLink($orgLinkId = null) {
        if (!filter_var($orgLinkId, FILTER_VALIDATE_INT)) {
            throw new Exception("Could not delete org link - no org_link_id specified"); 
            return false;
        }
        
        $where = array("id = ?" => $orgLinkId); 
        
        $this->db->delete("loco_org_link", $where);
        
        return true;
    }
    
    /** 
     * Log an event 
     * @since Version 3.5
     * @param int $userId
     * @param string $title
     * @param array $args
     */
    
    public function logEvent($userId = false, $title = false, $args = false) {
        if (!$user_id) {
            throw new Exception("Cannot log event, no User ID given"); 
            return false;
        }
        
        if (!$title) {
            throw new Exception("Cannot log event, no title given"); 
            return false;
        }
        
        $Event = new \Railpage\SiteEvent; 
        $Event->user_id = $userId; 
        $Event->title = $title;
        $Event->args = $args; 
        $Event->key = "loco_id";
        $Event->value = $this->id;
        $Event->module_name = "locos";
        
        if ($title === "Photo tagged") {
            $Event->module_name = "flickr";
        }
        
        $Event->commit();
        
        return true;
    }
    
    /**
     * Get events recorded against this class
     * @since Version 3.5
     * @return array
     */
    
    public function getEvents() {
        $query = "SELECT ll.*, u.username FROM log_locos AS ll LEFT JOIN nuke_users AS u ON ll.user_id = u.user_id WHERE ll.loco_id = ? ORDER BY timestamp DESC"; 
        
        $return = array(); 
        
        foreach ($this->db->fetchAll($query, $this->id) as $row) {
            $row['timestamp'] = DateTime::createFromFormat("Y-m-d H:i:s", $row['timestamp']); 
            $row['args'] = json_decode($row['args'], true);
            $return[] = $row; 
        }
        
        return $return;
    }
    
    /**
     * Get a locomotive org link
     * @since Version 3.5
     * @param int $id
     * @return array
     */
    
    public function getOrgLink($id = false) {
        if (!$id) {
            throw new Exception("Cannot fetch organisation link - no org link ID given"); 
            return false;
        }
        
        $query = "SELECT o.*, ot.name AS link_type_name, op.operator_name FROM loco_org_link AS o LEFT JOIN loco_org_link_type AS ot ON o.link_type = ot.id LEFT JOIN operators AS op ON op.operator_id = o.operator_id WHERE o.id = ?"; 
        
        return $this->db->fetchRow($query, $id);
    }
    
    /**
     * Loco sightings
     * @since Version 3.5
     * @return array
     */
    
    public function sightings() {
        $Sightings = new Sightings;
        
        return $Sightings->findLoco($this->id); 
    }
    
    /**
     * Get contributors of this locomotive
     * @since Version 3.7.5
     * @return array
     */
    
    public function getContributors() {
        
        $key = sprintf(self::CACHE_KEY, $this->id) . ";contributors";
        
        if ($contributors = $this->Redis->fetch($key)) {
            return $contributors;
        }
        
        $return = array(); 
        
        $Sphinx = AppCore::getSphinx();
        
        $query = $Sphinx->select("user_id", "username")
                        ->from("idx_logs")
                        ->match("module", "locos")
                        ->where("key", "=", "loco_id")
                        ->where("value", "=", intval($this->id))
                        ->groupBy("user_id");
        
        $result = $query->execute();
        
        foreach ($result as $row) {
            $return[$row['user_id']] = $row['username'];
        }
        
        $this->Redis->save($key, $return, strtotime("+2 hours"));
        
        return $return;
        
    }
    
    /**
     * Return an array of tags appliccable to this loco
     * @since Version 3.7.5
     * @return array
     */
    
    public function getTags() {
        $tags = $this->Class->getTags(); 
        $tags[] = "railpage:loco=" . $this->number;
        $tags[] = $this->flickr_tag;
        $tags[] = $this->number;
        
        asort($tags);
        
        return $tags;
    }
    
    /**
     * Add an asset to this locomotive
     * @since Version 3.8
     * @param array $data
     * @return boolean
     */
    
    public function addAsset($data = false) {
        
        return Utility\LocosUtility::addAsset($this->namespace, $this->id, $data); 
        
    }
    
    /**
     * Get next locomotive
     * @since Version 3.8.7
     * @return \Railpage\Locos\Locomotive
     */
    
    public function next() {
        $members = $this->Class->members(); 
        
        if ($members['stat'] === "ok") {
            // Get the previous loco in this class
            
            $break = false;
            
            foreach ($members['locos'] as $row) {
                if ($break === true) {
                    return new Locomotive($row['loco_id']);
                }
                
                if ($row['loco_id'] === $this->id) {
                    $break = true;
                }
            }
        }
    }
    
    /**
     * Get previous locomotive
     * @since Version 3.8.7
     * @return \Railpage\Locos\Locomotive
     */
    
    public function previous() {
        $members = $this->Class->members(); 
        
        // Get the next loco in this class
        if ($members['stat'] === "ok") {
            
            $break = false;
            
            $members['locos'] = array_reverse($members['locos']);
            foreach ($members['locos'] as $row) {
                if ($break === true) {
                    return new Locomotive($row['loco_id']);
                }
                
                if ($row['loco_id'] === $this->id) {
                    $break = true;
                }
            }
        }
    }
    
    /**
     * Set the cover photo for this locomotive
     * @since Version 3.8.7
     * @param $Image Either an instance of \Railpage\Images\Image or \Railpage\Assets\Asset
     * @return $this
     */
    
    public function setCoverImage($Image) {
        
        /**
         * Zero out any existing images
         */
        
        $this->photo_id = NULL;
        $this->Asset = NULL;
        
        if (isset($this->meta['coverimage'])) {
            unset($this->meta['coverimage']);
        }
        
        /**
         * $Image is a Flickr image
         */
        
        if ($Image instanceof Image && $Image->provider === "flickr") {
            $this->photo_id = $Image->photo_id;
            $this->commit(); 
            
            return $this;
        }
        
        /**
         * Image is a site asset
         */
        
        if ($Image instanceof Asset) {
            $this->Asset = clone $Image;
            $this->commit(); 
            
            return $this;
        }
        
        /**
         * Image is a generic image, so we'll just store the Image ID and fetch it later with $this->getCoverImage()
         */
        
        $this->meta['coverimage'] = array(
            "id" => $Image->id,
            "title" => $Image->title,
            "sizes" => $Image->sizes,
            "url" => $Image->url instanceof Url ? $Image->url->getURLs() : $Image->url
        );
        
        $this->commit(); 
        
        return $this;
    }
    
    /**
     * Get the cover photo for this locomotive
     * @since Version 3.8.7
     * @return array
     * @todo Set the AssetProvider (requires creating AssetProvider)
     */
    
    public function getCoverImage() {
        
        return Utility\CoverImageUtility::getCoverImageOfObject($this);
        
    }
    
    /**
     * Check if this loco class has a cover image
     * @since Version 3.9
     * @return boolean
     */
    
    public function hasCoverImage() {
        
        return Utility\CoverImageUtility::hasCoverImage($this); 
        
    }
    
    /**
     * Get locomotive data as an associative array
     * @since Version 3.9
     * @return array
     */
    
    public function getArray() {
        return array(
            "id" => $this->id,
            "number" => $this->number,
            "name" => $this->name,
            "gauge" => $this->gauge,
            "status" => array(
                "id" => $this->status_id,
                "text" => strval(new Status($this->status_id))
            ),
            "manufacturer" => array(
                "id" => $this->manufacturer_id,
                "text" => $this->manufacturer
            ),
            "class" => $this->Class->getArray(),
            "url" => $this->url->getURLs()
        );
    }
    
    /**
     * Set the locomotive class
     * @since Version 3.9.1
     * @param \Railpage\Locos\LocoClass $LocoClass
     * @return \Railpage\Locos\Locomotive
     */
    
    public function setLocoClass(LocoClass $LocoClass) {
        $this->Class = $LocoClass;
        $this->class = $LocoClass;
        $this->class_id = $LocoClass->id;
        
        return $this;
    }
    
    /**
     * Set the locomotive gauge
     * @since Version 3.9.1
     * @param \Railpage\Locos\Gauge $Gauge
     * @return \Railpage\Locos\Locomotive
     */
    
    public function setGauge(Gauge $Gauge) {
        $this->gauge_id = $Gauge->id;
        $this->gauge = $Gauge->getArray(); 
        $this->gauge_formatted = (string) $Gauge;
        
        return $this;
    }
    
    /**
     * Get the gauge
     * @since Version 3.9.1
     * @return \Railpage\Locos\Gauge
     */
    
    public function getGauge() {
        return new Gauge($this->gauge_id);
    }
    
    /**
     * Set the manufacturer
     * @since Version 3.9.1
     * @param \Railpage\Locos\Manufacturer $Manufacturer
     * @return \Railpage\Locos\LocoClass
     */
    
    public function setManufacturer(Manufacturer $Manufacturer) {
        $this->manufacturer_id = $Manufacturer->id;
        $this->manufacturer = $Manufacturer->name;
        
        return $this;
    }
    
    /**
     * Get the loco manufacturer
     * @since Version 3.9.1
     * @return \Railpage\Locos\Manufacturer
     */
    
    public function getManufacturer() {
        
        return Factory::Create("Manufacturer", $this->manufacturer_id);
        
    }
    
    /**
     * Generate descriptive text
     * @since Version 3.9.1
     * @return string
     */
    
    public function generateDescription() {
        
        $mckey = sprintf(self::CACHE_KEY_DESC, $this->id); 
        
        if ($str = $this->Memcached->fetch($mckey)) {
            return $str;
        }
        
        $bits = array(); 
        
        /**
         * Built as... by...
         */
        
        $bits = Utility\LocomotiveUtility::getDescriptionBits_Manufacturer($this, $bits); 
        
        /**
         * Process the dates
         */
        
        $bits = Utility\LocomotiveUtility::getDescriptionBits_Dates($this, $bits); 
                    
        /**
         * The loco is currently...
         */
        
        $bits = Utility\LocomotiveUtility::getDescriptionBits_Status($this, $bits); 
        
        /**
         * Join it all together
         */
                    
        $str = trim(implode("", $bits)); 
        
        if (preg_match("/([a-zA-Z0-9]+)/", substr($str, -1))) {
            $str .= ".";
        }
        
        if (substr($str, -1) === ",") {
            $str = substr($str, 0, -1) . ".";
        }
        
        $this->Memcached->save($mckey, $str, strtotime("+1 year"));
        
        return $str;
    }
    
    /**
     * Echo this locomotive as a string
     * @since Version 3.9.1
     * @return string
     */
    
    public function __toString() {
        return (string) $this->number;
    }
}