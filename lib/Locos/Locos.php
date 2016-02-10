<?php

/** 
 * Loco database
 * @since Version 3.2
 * @version 3.10.0
 * @author Michael Greenhill
 * @package Railpage
 */

namespace Railpage\Locos;

define("LOCOS_FIND_CLASS_NOPHOTO", "class_nophoto");
define("LOCOS_FIND_CLASS_NOLOCOS", "class_nolocos");
define("LOCOS_FIND_LOCO_NOPHOTO", "loco_nophoto");
define("LOCOS_FIND_LOCO_NODATES", "loco_nodates"); 
define("LOCOS_FIND_FROM_NUMBERS", "loco_findfromnumbers");

define("RP_LOCO_RENUMBERED", 1); 
define("RP_LOCO_REBUILT", 2);

use Exception;
use DateTime;
use stdClass;
use Railpage\AppCore;
use Railpage\Module;
use Railpage\Debug;
use Zend_Db_Expr;

require_once(__DIR__ . DIRECTORY_SEPARATOR . "functions.php");

/**
 * Base locos class
 * @since Version 3.2
 * @version 3.8.7
 * @todo This will be used by the management class and the loco object class
 */

class Locos extends AppCore {
    
    /**
     * Status : Operational
     * @since Version 3.8.7
     * @const STATUS_OPERATIONAL
     */
    
    const STATUS_OPERATIONAL = 1;
    
    /**
     * Status : Scrapped
     * @since Version 3.8.7
     * @const STATUS_SCRAPPED
     */
    
    const STATUS_SCRAPPED = 2;
    
    /**
     * Status : Stored
     * @since Version 3.8.7
     * @const STATUS_STORED
     */
    
    const STATUS_STORED = 3;
    
    /**
     * Status : Preserved (static)
     * @since Version 3.8.7
     * @const STATUS_PRESERVED_STATIC
     */
    
    const STATUS_PRESERVED_STATIC = 4;
    
    /**
     * Status : Preserved (operational)
     * @since Version 3.8.7
     * @const STATUS_PRESERVED_OPERATIONAL
     */
    
    const STATUS_PRESERVED_OPERATIONAL = 5;
    
    /**
     * Status : Unknown
     * @since Version 3.8.7
     * @const STATUS_UNKNOWN
     */
    
    const STATUS_UNKNOWN = 6;
    
    /**
     * Status : Rebuilt
     * @since Version 3.8.7
     * @const STATUS_REBUILT
     */
    
    const STATUS_REBUILT = 7;
    
    /**
     * Status : Non-rail use
     * @since Version 3.8.7
     * @const STATUS_NONRAIL
     */
    
    const STATUS_NONRAIL = 8;
    
    /**
     * Status : Under restoration
     * @since Version 3.8.7
     * @const STATUS_RESTORATION
     */
    
    const STATUS_RESTORATION = 9;
    
    /**
     * Status : Under overhaul
     * @since Version 3.8.7
     * @const STATUS_OVERHAUL
     */
    
    const STATUS_OVERHAUL = 10;
    
    /**
     * Status : Re-numbered
     * @since Version 3.8.7
     * @const STATUS_RENUMBERED
     */
    
    const STATUS_RENUMBERED = 11;
    
    /**
     * Status : Sold overseas
     * @since Version 3.8.7
     * @const STATUS_OVERSEAS
     */
    
    const STATUS_OVERSEAS = 12;
    
    /**
     * Constructor
     * @since Version 3.8.7
     */
    
    public function __construct() {
        parent::__construct(); 
        
        $this->Module = new Module("locos");
        $this->namespace = $this->Module->namespace;
        
        $this->Template = new stdClass;
        $this->Template->index = "index";
    }
    
    /**
     * Find data for managers to fix
     * @since Version 3.2
     * @version 3.5
     * @param string $search_type
     * @param string $args
     * @return array
     * @deprecated Deprecated since 3.10.1 in favour of Railpage\Locos\Maintainers\Finder::find()
     */
    
    public function find() {
        
        throw new Exception(__METHOD__ . " has been replaced with \\Railpage\\Locos\\Maintainers\\Finder::find()"); 
        
    }
    
    /**
     * List classes
     * @since Version 3.2
     * @version 3.5
     * @return array
     * @param array $types
     */
    
    public function listClasses($types = null) {
        $params = array(); 
        $return = array(); 
        
        $suffix = $types === false ? "all" : (is_array($types) ? implode(",", $types) : $types); 
        $mckey = sprintf("railpage:loco.class.bytype=%s", $suffix);
        
        /**
         * Memcached lookup
         */
        
        /*
        if ($return = $this->Memcached->fetch($mckey)) {
            
            $update = false;
            
            foreach ($return['class'] as $id => $row) {
                if (!isset($row['class_url'])) {
                    $return['class'][$id]['class_url'] = $this->makeClassURL($row['slug']);
                    $update = true;
                }
            }
            
            if ($update) {
                $this->Memcached->save($mckey, $return, strtotime("+1 week"));
            }
            
            return $return;
        }
        */
        
        /**
         * Database lookup
         */
        
        $motive_power_types = ""; 
        
        if (is_array($types)) {
            $motive_power_types = " WHERE c.loco_type_id IN (".implode(",", $types).")";
        } elseif (is_int($types)) {
            $motive_power_types = " WHERE c.loco_type_id IN (?)";
            $params[] = $types;
        }
        
        $query = "SELECT c.parent AS parent_class_id, c.source_id AS source, c.id AS class_id, c.flickr_tag, c.slug, c.flickr_image_id, c.introduced AS class_introduced, c.name AS class_name, c.desc AS class_desc, c.manufacturer_id AS class_manufacturer_id, m.manufacturer_name AS class_manufacturer, w.arrangement AS wheel_arrangement, w.id AS wheel_arrangement_id, t.title AS loco_type, c.loco_type_id AS loco_type_id, t.slug AS loco_type_slug
                    FROM loco_class AS c
                    LEFT JOIN loco_type AS t ON c.loco_type_id = t.id
                    LEFT JOIN wheel_arrangements AS w ON c.wheel_arrangement_id = w.id
                    LEFT JOIN loco_manufacturer AS m ON m.manufacturer_id = c.manufacturer_id
                    " . $motive_power_types . "
                    ORDER BY c.name";
        
        $return['stat'] = "ok";
        $return['count'] = 0;
        
        foreach ($this->db->fetchAll($query, $params) as $row) {
            $row['class_url'] = $this->makeClassUrl($row['slug']);
            $return['class'][$row['class_id']] = $row;
        }
        
        $return['count'] = count($return['class']);
        
        $this->Memcached->save($mckey, $return, strtotime("+1 week"));
        
        return $return;
    }
    
    /**
     * Loco classes by builder
     * @since Version 3.2
     * @version 3.2
     * @return array
     */
    
    public function classByManufacturer() {
        $classes = $this->listClasses();
        
        $return = array(
            "stat" => "err"
        );
        
        if ($classes['stat'] === "ok") {
            $return['stat'] = "ok";
            
            foreach ($classes['class'] as $id => $data) {
                $return['manufacturer'][$data['class_manufacturer_id']][$id] = $data;
            }
            
            ksort($return['manufacturer']);
        }
        
        return $return;
    }
    
    /**
     * Loco classes by builder
     * @since Version 3.2
     * @version 3.2
     * @return array
     */
    
    public function classByWheelset() {
        $classes = $this->listClasses();
        
        $return = array(
            "stat" => "err"
        );
        
        if ($classes['stat'] === "ok") {
            $return['stat'] = "ok";
            
            foreach ($classes['class'] as $id => $data) {
                $return['wheels'][$data['wheel_arrangement_id']][$id] = $data;
            }
            
            ksort($return['wheels']);
        }
        
        return $return;
    }
    
    /**
     * Loco classes by traction type
     * @since Version 3.2
     * @version 3.2
     * @return array
     */
    
    public function classByType() {
        $classes = $this->listClasses();
        
        $return = array(
            "stat" => "err"
        );
        
        if ($classes['stat'] === "ok") {
            $return['stat'] = "ok";
            
            foreach ($classes['class'] as $id => $data) {
                $return['type'][$data['loco_type_id']][$id] = $data;
            }
            
            ksort($return['type']);
        }
        
        return $return;
    }
    
    /**
     * Locos by operator
     * @since Version 3.2
     * @version 3.2
     * @param int $operatorId
     * @return array
     */
    
    public function locosByOperator($operatorId = null) {
        
        if (!filter_var($operatorId, FILTER_VALIDATE_INT)) {
            return false;
        }
        
        $query = "SELECT l.loco_id, l.loco_num, l.loco_gauge, l.loco_status_id AS status_id, s.name AS status_name, l.class_id AS class_id, c.slug AS class_slug, c.name AS class_name, c.flickr_tag AS class_flickr_tag, l.owner_id, o.operator_name AS owner_name, c.wheel_arrangement_id, w.arrangement AS wheel_arrangement, c.loco_type_id, t.title AS loco_type
                    FROM loco_unit AS l 
                    LEFT JOIN loco_org_link AS lo ON lo.loco_id = l.loco_id
                    LEFT JOIN loco_status AS s ON s.id = l.loco_status_id
                    LEFT JOIN loco_class AS c ON l.class_id = c.id
                    LEFT JOIN operators AS o ON o.operator_id = lo.operator_id
                    LEFT JOIN wheel_arrangements AS w ON c.wheel_arrangement_id = w.id
                    LEFT JOIN loco_type AS t ON c.loco_type_id = t.id
                    WHERE lo.operator_id = ?
                    AND lo.link_type = 2
                    ORDER BY c.name, l.loco_num";
        
        $return = array("stat" => "ok", "count" => 0, "locos" => array()); 
        
        foreach ($this->db->fetchAll($query, $operatorId) as $row) {
            if (!empty($row['class_flickr_tag'])) {
                $row['flickr_tag'] = $row['class_flickr_tag']."-".$row['loco_num'];
            }
            
            $row['loco_url'] = $this->makeLocoURL($row['class_slug'], $row['loco_num']);
            
            $return['locos'][$row['loco_id']] = $row;
            $return['count']++; 
        }
        
        return $return;
        
    }
    
    /**
     * Locos by owner
     * @since Version 3.2
     * @version 3.2
     * @param int $ownerId
     * @return array
     */
    
    public function locosByOwner($ownerId = null) {
        
        if (!filter_var($ownerId, FILTER_VALIDATE_INT)) {
            return false;
        }
        
        $query = "SELECT l.loco_id, l.loco_num, l.loco_gauge, l.loco_status_id AS status_id, s.name AS status_name, l.class_id AS class_id, c.slug AS class_slug, c.name AS class_name, c.flickr_tag AS class_flickr_tag, l.operator_id, o.operator_name AS operator_name, c.wheel_arrangement_id, w.arrangement AS wheel_arrangement, c.loco_type_id, t.title AS loco_type
                    FROM loco_unit AS l 
                    LEFT JOIN loco_org_link AS lo ON lo.loco_id = l.loco_id
                    LEFT JOIN loco_status AS s ON s.id = l.loco_status_id
                    LEFT JOIN loco_class AS c ON l.class_id = c.id
                    LEFT JOIN operators AS o ON o.operator_id = lo.operator_id
                    LEFT JOIN wheel_arrangements AS w ON c.wheel_arrangement_id = w.id
                    LEFT JOIN loco_type AS t ON c.loco_type_id = t.id
                    WHERE lo.operator_id = ?
                    AND lo.link_type = 1
                    ORDER BY c.name, l.loco_num";
        
        $return = array("stat" => "ok", "count" => 0, "locos" => array()); 
        
        foreach ($this->db->fetchAll($query, $ownerId) as $row) {
            if (!empty($row['class_flickr_tag'])) {
                $row['flickr_tag'] = $row['class_flickr_tag']."-".$row['loco_num'];
            
            }
            
            $row['loco_url'] = $this->makeLocoURL($row['class_slug'], $row['loco_num']);
            
            $return['locos'][$row['loco_id']] = $row;
            $return['count']++; 
        }
        
        return $return;
        
    }
    
    /**
     * List wheel arrangements
     * @since Version 3.2
     * @version 3.10.0
     * @return array
     * @param boolean $force Ignore Memcached and force refresh this list
     * @todo Remove this helper function, redirect all frontend code to \Railpage\Locos\Lister::getWheelArrangements()
     */
    
    public function listWheelArrangements($force = null) {
        
        return Lister::getWheelArrangements($force); 
        
    }
    
    /**
     * List manufacturers
     * @since Version 3.2
     * @version 3.10.0
     * @return array
     * @param boolean $force Ignore Memcached and force refresh this list
     * @todo Remove this helper function, redirect all frontend code to \Railpage\Locos\Lister::getManufacturers()
     */
    
    public function listManufacturers($force = null) {
        
        return Lister::getManufacturers($force); 
        
    }
    
    /**
     * List loco types
     * @since Version 3.2
     * @version 3.10.0
     * @return array
     * @todo Remove this helper function, redirect all frontend code to \Railpage\Locos\Lister::getTypes()
     */
    
    public function listTypes() {
        
        return Lister::getTypes(); 
        
    }
    
    /**
     * List loco status types
     * @since Version 3.2
     * @version 3.10.0
     * @return array
     * @todo Remove this helper function, redirect all frontend code to \Railpage\Locos\Lister::getStatus()
     */
    
    public function listStatus() {
        
        return Lister::getStatus(); 
        
    }
    
    /**
     * List years and the classes in each year
     * @since Version 3.2
     * @version 3.10.0
     * @return array
     * @todo Remove this helper function, redirect all frontend code to \Railpage\Locos\Lister::getYears()
     */
    
    public function listyears() {
        
        return Lister::getYears(); 
        
    }
    
    /**
     * List operators
     * @since Version 3.2
     * @version 3.10.0
     * @return array
     * @todo Remove this helper function, redirect all frontend code to \Railpage\Locos\Lister::getOperators()
     */
    
    public function listOperators() {
        
        return Lister::getOperators(); 
        
    }
            
    /** 
     * List all locos
     * @since Version 3.2
     * @version 3.10.0
     * @return array
     * @todo Remove this helper function, redirect all frontend code to \Railpage\Locos\Lister::getOperators()
     */
    
    public function listAllLocos() {
        
        return Lister::getAllLocos(); 
        
    }
    
    /**
     * List all liveries
     * @since Version 3.2
     * @return array
     * @todo Remove this helper function, redirect all frontend code to \Railpage\Locos\Lister::getLiveries()
     */
    
    public function listLiveries() {
        
        return Lister::getLiveries(); 
        
    }
    
    /**
     * Get loco gauges
     * @since Version 3.4
     * @return array
     * @todo Remove this helper function, redirect all frontend code to \Railpage\Locos\Lister::getGauges()
     */
    
    public function listGauges() {
       
        return Lister::getGauges(); 
        
    }
    
    /**
     * List all organisation types
     * @since Version 3.4
     * @return array
     * @todo Remove this helper function, redirect all frontend code to \Railpage\Locos\Lister::getOrgLinkTypes()
     */
    
    public function listOrgLinkTypes() {
        
        return Lister::getOrgLinkTypes(); 
        
    }
    
    /**
     * List production models
     * @since Version 3.4
     * @return array
     * @todo Remove this helper function, redirect all frontend code to \Railpage\Locos\Lister::getModels()
     */
    
    public function listModels() {
        
        return Lister::getModels(); 
        
    }
    
    /**
     * List locomotive groupings
     * @since Version 3.5
     * @return array
     * @todo Remove this helper function, redirect all frontend code to \Railpage\Locos\Lister::getGroupings()
     */
    
    public function listGroupings() {
        
        return Lister::getGroupings(); 
        
    }
    
    /**
     * Edit note
     * @since Version 3.2
     * @param int $noteId
     * @param string $noteText
     * @return boolean
     */
    
    public function editNote($noteId = null, $noteText = null) {
        
        if (!filter_var($noteId, FILTER_VALIDATE_INT)) {
            throw new InvalidArgumentException("No valid note ID was provided"); 
        }
        
        if (is_null($noteText)) {
            throw new InvalidArgumentException("No note text was provided"); 
        }
        
        $data = array(
            "note_text" => $noteText
        ); 
        
        $where = array(
            "note_id = ?" => $noteId
        );
        
        $this->db->update("loco_notes", $data, $where); 
        
        return true;
        
    }
    
    /**
     * Delete note
     * @since Version 3.2
     * @param int $noteId
     * @return boolean
     */
    
    public function deleteNote($noteId = null) {
        
        if (!filter_var($noteId, FILTER_VALIDATE_INT)) {
            throw new InvalidArgumentException("No valid note ID was provided"); 
        } 
        
        $where = array(
            "note_id = ?" => $noteId
        );
        
        $this->db->delete("loco_notes", $where); 
        
        return true;
        
    }
    
    /**
     * Get the type of dates
     * @since Version 3.2
     * @return array
     */
    
    public function dateTypes() {
        
        $query = "SELECT * FROM loco_date_type ORDER BY loco_date_text ASC";
        $return = array();
        
        foreach ($this->db->fetchAll($query) as $row) {
            $return[$row['loco_date_id']] = $row['loco_date_text']; 
        }
        
        return $return;
        
    }
    
    /**
     * Load a single date entry
     * @since Version 3.2
     * @param int $dateId
     * @return mixed
     */
    
    public function loadDate($dateId = null) {
        
        throw new Exception("Deprecated function: use \Railpage\Locos\Date"); 
        
        /*
        if (!filter_var($dateId, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot load date - no date ID provided");
        }
        
        $query = "SELECT * FROM loco_unit_date WHERE date_id = ?"; 
        
        return $this->db->fetchRow($query, $dateId);
        */
    }
    
    /**
     * Delete a date
     * @since Version 3.2
     * @param int $dateId
     * @return boolean
     */
    
    public function deleteDate($dateId = null) {
        
        throw new Exception("Deprecated function: use \Railpage\Locos\Date"); 
        
        /*
        if (!filter_var($dateId, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot delete date - no date ID provided"); 
        }
        
        $where = array(
            "date_id = ?" => $dateId
        );
        
        $this->db->delete("loco_unit_date", $where);
        return true;
        */
    }
    
    /**
     * Get a random photo 
     * @since Version 3.2
     * @return string
     */
    
    public function randomPhoto() {
        $query = "SELECT photo_id FROM loco_unit WHERE photo_id > 0";
        
        $return = array(); 
        
        foreach ($this->db->fetchAll($query) as $row) {
            $return[] = $row['photo_id']; 
        }
        
        return $return;
    }
    
    /**
     * Find locos, classes from a tag or array of tags
     * @since Version 3.2
     * @param string|array $tags
     * @return array
     */
    
    public function findFromTag($tags) {
        return (new Maintainers\Finder)->find(Maintainers\Finder::FIND_FROM_TAGS, $tags); 
    }
    
    /**
     * Get the ID from the livery name
     * @since Version 3.2
     * @author Michael Greenhill
     * @param string $livery
     */
    
    public function liveryID($livery = null) {
        
        if ($livery == null) {
            throw new InvalidArgumentException("No livery name was provided"); 
        }
        
        $query = "SELECT livery_id FROM loco_livery WHERE livery = ?";
        
        $result = $this->db->fetchOne($query, $livery); 
        
        if (count($result) === 0) {
            $data = array(
                "livery" => $livery
            );
            
            $this->db->insert("loco_livery", $data); 
            return $this->db->lastInsertId(); 
        }
        
        return $result['livery_id'];
        
    }
    
    /**
     * Link loco A to loco B using relationship C
     * @since Version 3.2
     * @param \Railpage\Locos\Locomotive $locoObjectA
     * @param \Railpage\Locos\Locomotive $locoObjectB
     * @param int $link_type_id
     * @return boolean
     * @throws \Exception if $LocoA is not an instance of \Railpage\Locos\Locomotive
     * @throws \Exception if $LocoB is not an instance of \Railpage\Locos\Locomotive
     * @throws \Exception if $linkTypeId is missing
     */
    
    public function link(Locomotive $locoObjectA, Locomotive $locoObjectB, $linkTypeId = null) {
        if (!$locoObjectA instanceof Locomotive) {
            throw new Exception("Cannot establish link between locomotives - Paramter 1 (Loco A) missing");
        }
        if (!$locoObjectB instanceof Locomotive) {
            throw new Exception("Cannot establish link between locomotives - Paramter 2 (Loco B) missing");
        }
        
        if (!filter_var($linkTypeId, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot establish link between locomotives - Parameter 3 (link type) missing");
        }
        
        $data = array(
            "loco_id_a" => $locoObjectA->id,
            "loco_id_b" => $locoObjectB->id,
            "link_type_id" => $linkTypeId
        );
        
        $this->db->insert("loco_link", $data);
        return true;
    }
    
    /**
     * Delete loco 
     * @since Version 3.2
     * @param int $id
     * @return boolean
     */
    
    public function deleteLink($id = null) {
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot delete loco  - no ID given"); 
        } 

        $where = array(
            "link_id = ?" => $id
        );
        
        $this->db->delete("loco_link", $where); 
        
        return true;
    }
    
    /**
     * Get loco 
     * @since Version 3.5
     * @param int $id
     * @return array
     */
    
    public function getLink($id = null) {
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot fetch loco  - no ID given"); 
        }
        
        $query = "SELECT * FROM loco_link WHERE link_id = ?";
        
        return $this->db->fetchRow($query, $id); 
    }
    
    /**
     * Get suggested corrections
     * @since Version 3.2
     * @return array
     * @param boolean $active
     */
    
    public function corrections($active = null) {
        $active_sql = " WHERE c.status != 0 ";
        
        if ($active != null) {
            $active_sql = " WHERE c.status = 0 ";
        }
        
        $query = "SELECT c.*, l.loco_num, lc.name AS class_name, lc.id AS class_id, u.username, u.user_avatar
                    FROM loco_unit_corrections AS c
                    LEFT JOIN loco_unit AS l ON c.loco_id = l.loco_id
                    LEFT JOIN loco_class AS lc ON lc.id = l.class_id
                    LEFT JOIN nuke_users AS u ON c.user_id = u.user_id
                    ".$active_sql."
                    ORDER BY c.date DESC";
        
        $return = array(); 
        
        foreach ($this->db->fetchAll($query) as $row) {
            $return[$row['loco_id']][] = $row; 
        }
        
        return $return;
    }
    
    /**
     * Get latest owner of a locomotive
     * @since Version 3.4
     * @param int $locoId
     * @return array
     */
    
    public function getLastOwner($locoId = null) {
        if (!filter_var($locoId, FILTER_VALIDATE_INT)) {
            throw new Exception("Could not get latest owner - no loco ID given"); 
        }
        
        $query = "SELECT l.*, o.operator_name FROM loco_org_link AS l LEFT JOIN operators AS o ON l.operator_id = o.operator_id WHERE l.loco_id = ? AND l.link_type = 1 ORDER BY l.link_weight DESC LIMIT 1";
        
        return $this->db->fetchRow($query, $locoId); 
    }
    
    /**
     * Get latest operator of a locomotive
     * @since Version 3.4
     * @param int $locoId
     * @return array
     */
    
    public function getLastOperator($locoId = null) {
        if (!filter_var($locoId, FILTER_VALIDATE_INT)) {
            throw new Exception("Could not get latest operator - no loco ID given"); 
        }
        
        $query = "SELECT l.*, o.operator_name FROM loco_org_link AS l LEFT JOIN operators AS o ON l.operator_id = o.operator_id WHERE l.loco_id = ? AND l.link_type = 2 ORDER BY l.link_weight DESC LIMIT 1";
        
        return $this->db->fetchRow($query, $locoId); 
    }
    
    /**
     * Get classes by production model
     * @since Version 3.4
     * @param string $model
     * @return array
     */
    
    public function getClassesByModel($model = null) {
        if (is_null($model)) {
            throw new Exception("Could not fetch list of classes - no production model given"); 
        }
        
        $query = "SELECT id, name, slug FROM loco_class WHERE Model = ? ORDER BY name";
        
        $return = array(); 
        
        foreach ($this->db->fetchAll($query, $model) as $row) {
            $row['class_url'] = $this->makeClassUrl($row['slug']);
            $return[$row['id']] = $row; 
        }
        
        return $return;
    }
    
    /**
     * Add a new locomotive group
     * @since Version 3.5
     * @param string $name
     * @return int
     */
    
    public function addGrouping($name = null) {
        if (is_null($name)) {
            throw new Exception("Cannot add group - group name cannot be empty"); 
        }
        
        $data = array(
            "group_name" => $name
        );
        
        $this->db->insert("loco_groups", $data); 
        return $this->db->lastInsertId();
    }
    
    /**
     * Get group membership for this loco
     * @since Version 3.5
     * @param int $locoUnitId
     * @param boolean $includeInactive
     * @return array
     */
    
    public function getGroupsMembership($locoUnitId = null, $includeInactive = null) {
        if (!filter_var($locoUnitId, FILTER_VALIDATE_INT)) {
            throw new Exception("Could not fetch group membership - no loco ID given"); 
        }

        $query = "SELECT lg.* FROM loco_groups AS lg LEFT JOIN loco_groups_members AS lgm ON lgm.group_id = lg.group_id WHERE lgm.loco_unit_id = ?"; 
        
        if ($includeInactive == null) {
            $query .= " AND lg.active = 1"; 
        }
        
        $return = array(); 
        
        foreach ($this->db->fetchAll($query, $locoUnitId) as $row) {
            $return[$row['group_id']] = $row; 
        }
        
        return $return;
    }
    
    /**
     * Get members of a group
     * @since Version 3.5
     * @param int $groupId
     */
     
    public function getGroupMembers($groupId) {
        if (!filter_var($groupId, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot fetch group members - no group ID given"); 
        }
        
        $query = "SELECT loco_unit_id FROM loco_groups_members WHERE group_id = ?";
        
        $return = array(); 
        
        foreach ($this->db->fetchAll($query, $groupId) as $row) {
            $return[] = $row['loco_unit_id']; 
        }
        
        return $return;
    }
    
    /** 
     * Log an event 
     * @since Version 3.5
     * @param int $userId
     * @param string $title
     * @param array $args
     */
    
    public function logEvent($userId = null, $title = null, $args = null) {
        
        if (!filter_var($userId, FILTER_VALIDATE_INT)) {
            throw new Exception("Cannot log event, no User ID given"); 
        }
        
        if ($title == null) {
            throw new Exception("Cannot log event, no title given"); 
        }
        
        if (is_array($args)) {
            $args = json_encode($args); 
        }
        
        $dataArray = [
            "user_id" => $userId,
            "timestamp" => new \Zend_Db_Expr("NOW()"),
            "title" => $title, 
            "args" => $args
        ];
        
        $this->db->insert("log_locos", $dataArray); 
        return true;
        
    }
    
    /**
     * Make a class URL
     * @since Version 3.8.7
     * @param string $slug
     * @return string
     */
    
    public function makeClassURL($slug) {
        return sprintf("%s/%s", $this->Module->url, $slug);
    }
    
    /**
     * Make a loco URL
     * @since Version 3.8.7
     * @param string $classSlug
     * @param string $locoNumber
     * @return string
     */
    
    public function makeLocoURL($classSlug, $locoNumber) {
        
        $locoNumber = str_replace(" ", "_", $locoNumber);
        return sprintf("%s/%s/%s", $this->Module->url, $classSlug, $locoNumber);
        
    }
    
    /**
     * Get a random class
     * @since Version 3.8.7
     * @return \Railpage\Locos\LocoClass;
     */
    
    public function getRandomClass() {
        $query = "SELECT `id`, `desc`, flickr_image_id FROM loco_class WHERE `desc` != '' AND flickr_image_id > 0";
        
        $ids = array();
        
        foreach ($this->db->fetchAll($query) as $row) {
            if (strlen(trim($row['desc'])) > 5 && $row['flickr_image_id'] > 0) {
                $ids[] = $row['id'];
            }
        }
        
        shuffle($ids);
        
        foreach ($ids as $id) {
            $LocoClass = new LocoClass($id);
            
            if (!empty($LocoClass->desc) && $LocoClass->getCoverImage()) {
                return $LocoClass;
            }
        }
    }
    
    /**
     * Year introduced URL
     * @since Version 3.8.7
     * @param int $year
     * @return string
     */
    
    public function makeYearURL($year = null) {
        return sprintf("/locos/year/%d", $year);
    }
    
    /**
     * Get statistics from the database
     * @since Version 3.8.7
     * @param string $statistic
     * @return mixed
     */
    
    public function getStatistic($statistic, $param1 = null, $param2 = null) {
        
        if (!$statistic) {
            throw new Exception("Cannot get module statistics - no statistic requested");
        }
        
        switch ($statistic) {
            
            case "railpage.locos.class.count" :
                
                $query = "SELECT count(id) AS count FROM loco_class";
                return $this->db->fetchOne($query);
                
                break;
            
            case "railpage.locos.loco.count" : 
                
                $query = "SELECT count(loco_id) AS count FROM loco_unit";
                return $this->db->fetchOne($query);
                
                break;
            
            case "railpage.locos.status.count" : 
                
                $query = "SELECT count(loco_id) AS count FROM loco_unit WHERE loco_status_id = ? OR loco_status_id = ?";
                return $this->db->fetchOne($query, array($param1, $param2));
                
                break;
        }
    }
    
    /**
     * Get locomotive dates from a given date range
     * @since Version 3.9
     * @param \DateTime $DateFrom
     * @param \DateTime $DateTo
     * @return \Railpage\Locos\Date
     * @yield \Railpage\Locos\Date
     */
    
    public function yieldDatesWithinRange(DateTime $dateFrom, DateTime $dateTo) {
        $query = "SELECT date_id FROM loco_unit_date WHERE timestamp >= ? AND timestamp <= ? ORDER BY timestamp";
        
        foreach ($this->db->fetchAll($query, array($dateFrom->format("Y-m-d"), $dateTo->format("Y-m-d"))) as $row) {
            yield new Date($row['date_id']);
        }
    }
    
    /**
     * Converts a height value given in cm to feet and inches
     *
     * @param int $cm
     * @return array
     */
    
    public static function convert_to_inches($cm) {
        $inches = round($cm * 0.393701);
        $result = [
            'ft' => intval($inches / 12),
            'in' => $inches % 12,
        ];
    
        return $result;
    }
    
    /**
     * Converts a height value given in feet/inches to cm
     *
     * @param int $feet
     * @param int $inches
     * @return int
     */
    
    public static function convert_to_cm($feet, $inches = 0) {
        $inches = ($feet * 12) + $inches;
        return (int) round($inches / 0.393701);
    }

    /**
     * Add a locomotive status
     * @since Version 3.9.1
     * @var string $name
     * @return \Railpage\Locos\Locos;
     */
    
    public function addStatus($name = null) {
        if ($name == null) {
            throw new Exception("No name was given for this status");
        }
        
        $data = array(
            "name" => $name
        );
        
        $this->db->insert("loco_status", $data); 
        
        return $this;
    }
}