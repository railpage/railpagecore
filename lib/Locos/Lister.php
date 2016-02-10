<?php

/**
 * (Dave) Lister class
 * List all of the things!
 * Seriously though, this is an attempt at reducing the bulk of \Railpage\Locos\Locos 
 * by splitting shit off into utility and single-role classes
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Locos;

use Railpage\AppCore;
use Railpage\Url;
use Railpage\Debug;
use Railpage\Module;
use Exception;
use InvalidArgumentException;
use DateTime;

/**
 * Lister
 */

class Lister {
    
    /**
     * List wheel arrangements
     * Ported from \Railpage\Locos\Locos
     * @since Version 3.10.0
     * @return array
     * @param null $force Ignore Memcached and force refresh this list
     */
    
    public static function getWheelArrangements($force = null) {
        
        $cacheDriver = AppCore::getMemcached(); 
        
        $query = "SELECT * FROM wheel_arrangements ORDER BY arrangement";
        $return = array();
        
        $mckey = "railpage:loco.wheelarrangements"; 
        
        if ($force === true || !$return = $cacheDriver->fetch($mckey)) {
            $return = Utility\LocosUtility::getLocosComponents($query, "wheels"); 
            $cacheDriver->save($mckey, $return, strtotime("+1 month"));
        }
            
        return $return;
        
    }
    
    /**
     * List manufacturers
     * Ported from \Railpage\Locos\Locos
     * @since Version 3.10.0
     * @return array
     * @param $force Ignore Memcached and force refresh this list
     */
    
    public static function getManufacturers($force = null) {
        
        $cacheDriver = AppCore::getMemcached(); 
        
        $query = "SELECT *, manufacturer_id AS id FROM loco_manufacturer ORDER BY manufacturer_name";
        $mckey = Manufacturer::MEMCACHED_KEY_ALL;
        
        if ($force === true || !$return = $cacheDriver->fetch($mckey)) {
            $return = Utility\LocosUtility::getLocosComponents($query, "manufacturers"); 
            $cacheDriver->save($mckey, $return, strtotime("+1 month"));
        }
            
        return $return;
        
    }
    
    /**
     * List loco types
     * Ported from \Railpage\Locos\Locos
     * @since Version 3.10.0
     * @return array
     */
    
    public static function getTypes() {
        
        $query = "SELECT * FROM loco_type ORDER BY title";
        
        return Utility\LocosUtility::getLocosComponents($query, "types"); 
        
    }
    
    /**
     * List loco status types
     * Ported from \Railpage\Locos\Locos
     * @since Version 3.10.0
     * @return array
     */
    
    public static function getStatus() {
        
        $query = "SELECT * FROM loco_status ORDER BY name";
        
        return Utility\LocosUtility::getLocosComponents($query, "status"); 
        
    }
    
    /**
     * List years and the classes in each year
     * Ported from \Railpage\Locos\Locos
     * @since Version 3.10.0
     * @return array
     */
    
    public static function getYears() {
        
        $classes = (new Locos)->listClasses();
        $return = array(
            "stat" => "err"
        );
        
        $Module = new Module("locos"); 
        
        if ($classes['stat'] === "ok") {
            $return['stat'] = "ok";
            
            foreach ($classes['class'] as $id => $data) {
                $data['loco_type_url'] = sprintf("%s/type/%s", $Module->url, $data['loco_type_slug']);

                $return['years'][$data['class_introduced']][$id] = $data;
            }
            
            ksort($return['years']);
        }
        
        return $return;
        
    }
    
    /**
     * List operators
     * Ported from \Railpage\Locos\Locos
     * @since Version 3.10.0
     * @return array
     */
    
    public static function getOperators() {
        
        $query = "SELECT * FROM operators ORDER BY operator_name";
        $return = array(); 
        
        $return['stat'] = "ok"; 
        $return['count'] = 0; 
        
        foreach (AppCore::GetDatabase()->fetchAll($query) as $row) {
            $return['operators'][$row['operator_id']] = $row;
            $return['count']++; 
        }
        
        return $return;
        
    }
            
    /** 
     * List all locos
     * Ported from \Railpage\Locos\Locos
     * @since Version 3.10.0
     * @return array
     */
    
    public static function getAllLocos() {
        
        $query = "SELECT * FROM loco_unit ORDER BY loco_id DESC";
        
        $return = array(); 
        
        $return['stat'] = "ok";
        
        foreach (AppCore::GetDatabase()->fetchAll($query) as $row) {
            $return['locos'][$row['loco_id']] = $row; 
        }
        
        return $return;
        
    }
    
    /**
     * List all liveries
     * Ported from \Railpage\Locos\Locos
     * @since Version 3.10.0
     * @return array
     */
    
    public static function getLiveries() {
        
        $query = "SELECT * FROM loco_livery ORDER BY livery";
        
        $return = array(); 
        
        foreach (AppCore::GetDatabase()->fetchAll($query) as $row) {
            $return[$row['livery_id']] = $row['livery']; 
        }
        
        return $return;
        
    }
    
    /**
     * Get loco gauges
     * Ported from \Railpage\Locos\Locos
     * @since Version 3.4
     * @return array
     */
    
    public static function getGauges() {
        
        $query = "SELECT * FROM loco_gauge ORDER BY gauge_name, gauge_imperial";
        
        $return = array(); 
        
        foreach (AppCore::GetDatabase()->fetchAll($query) as $row) {
            $return[$row['gauge_id']] = $row; 
        }
        
        return $return;
        
    }
    
    /**
     * List all organisation types
     * Ported from \Railpage\Locos\Locos
     * @since Version 3.4
     * @return array
     */
    
    public static function getOrgLinkTypes() {
        
        $query = "SELECT * FROM loco_org_link_type ORDER BY name";
        
        $return = array(); 
        
        foreach (AppCore::GetDatabase()->fetchAll($query) as $row) {
            $return[$row['id']] = $row; 
        }
        
        return $return;
        
    }
    
    /**
     * List production models
     * Ported from \Railpage\Locos\Locos
     * @since Version 3.4
     * @return array
     */
    
    public static function getModels() {
        
        $query = "SELECT DISTINCT Model from loco_class ORDER BY Model";
        
        $return = array(); 
        
        foreach (AppCore::GetDatabase()->fetchAll($query) as $row) {
            if (trim($row['Model']) != "") {
                $return[] = $row['Model'];
            }
        }
        
        return $return;
        
    }
    
    /**
     * List locomotive groupings
     * Ported from \Railpage\Locos\Locos
     * @since Version 3.5
     * @return array
     */
    
    public static function getGroupings() {
        
        $query = "SELECT * FROM loco_groups ORDER BY group_name"; 
        
        $return = array("stat" => "ok"); 
        
        foreach (AppCore::GetDatabase()->fetchAll($query) as $row) {
            $return['groups'][$row['group_id']] = $row; 
        }
        
        return $return;
    }
    
}