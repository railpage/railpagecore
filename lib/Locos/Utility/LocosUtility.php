<?php

/**
 * Locomotive / loco class cover photo utility
 * @since Version 3.9.1
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Locos\Utility;

use Railpage\Locos\LocoClass;
use Railpage\Locos\Locomotive;
use Railpage\ContentUtility; 
use Railpage\Asset;
use Railpage\AppCore;
use Railpage\Module;
use Exception;
use Railpage\Debug;
use Zend_Db_Expr;


class LocosUtility {
    
    /**
     * Add an asset
     * @since Version 3.9.1
     * @param string $namespace
     * @param int $id
     * @param array $data
     * @return void
     */
    
    public static function addAsset($namespace, $id, $data) {
        
        if (!is_array($data)) {
            throw new Exception("Cannot add asset - \$data must be an array"); 
            return false;
        }
        
        $Database = (new AppCore)->getDatabaseConnection(); 
        
        $data = array_merge($data, array(
            "date" => new Zend_Db_Expr("NOW()"),
            "namespace" => $namespace,
            "namespace_key" => $id
        ));
        
        $meta = json_encode($data['meta']);
        
        /**
         * Handle UTF8 errors
         */
        
        if (!$meta && json_last_error() === JSON_ERROR_UTF8) {
            $data['meta'] = ContentUtility::FixJSONEncode_UTF8($data['meta']); 
        } else {
            $data['meta'] = $meta;
        }
        
        $Database->insert("asset", $data);
        return true;
        
    }
    
    /**
     * Get loco components from the database
     * @since Version 3.9.1
     * @return array
     * @param $query
     * @param $key
     */
    
    public static function getLocosComponents($query, $key) {
        
        $Database = (new AppCore)->getDatabaseConnection(); 
        
        $return['stat'] = "ok"; 
        $return['count'] = 0;
        
        foreach ($Database->fetchAll($query) as $row) {
            $return['count']++;
            $return[$key][$row['id']] = $row;
        }
        
        return $return;
        
    }
    
    /**
     * Create a base URL from the given parameters
     * @since Version 3.9.1
     * @param string $type
     * @param array $parts
     */
    
    public static function CreateUrl($type, $parts) {
        
        $Module = new Module("locos"); 
        
        $base = array($Module->url);
        
        $base = array_merge($base, $parts); 
        $url = strtolower(implode("/", $base)); 
        
        return $url;
        
    }
    
}