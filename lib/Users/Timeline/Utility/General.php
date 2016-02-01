<?php

/**
 * General timeline processing functions
 * @since Version 3.9.1
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Users\Timeline\Utility;

use Railpage\Module;

class General {
    
    /**
     * Get the site module for a specified timeline entry
     * @since Version 3.9.1
     * @param array $row
     * @return string
     */
    
    static public function getModuleNamespace($row) {
        
        $Module = new Module($row['module']);
        
        return $Module->namespace;
        
    }
    
    /**
     * Compact the events into an action
     * @since Version 3.9.1
     * @param array $row
     * @return string
     */
    
    static public function compactEvents($row) {
        
        foreach ($row['event'] as $k => $v) {
            if (empty($v)) {
                unset($row['event'][$k]);
            }
        }
        
        return implode(" ", $row['event']);
        
    }
    
    /**
     * Determine which icon to show against this timeline entry
     * @since Version 3.9.1
     * @param array $row
     * @return string
     */
    
    static public function getIcon($row) {
        
        $lookup = array(
            "edited" => "pencil",
            "modified" => "pencil",
            "added" => "plus",
            "created" => "plus",
            "linked" => "link",
            "re-ordered" => "random",
            "removed" => "minus",
            "commented" => "comment",
            "photo" => "picture",
            "cover photo" => "picture",
            "tagged" => "tag",
            "sighting" => "eye-open",
            "suggested" => "thumbs-up",
            "closed" => "ok"
        );
        
        $keys = [ "action", "object" ];
        foreach ($keys as $key) {
            $key = strtolower($row['event'][$key]);
            if (isset($lookup[$key])) {
                return $lookup[$key];
            }
        }
        
        return "";
        
    }
    
    /**
     * Format the "object"
     * @since Version 3.9.1
     * @param array $row
     * @return array
     */
    
    static public function formatObject($row) {
        if ($row['module'] == "locos" && $row['event']['object'] == "class") {
            $row['event']['object'] = "locomotive class";
            
            if ($row['event']['action'] == "modified") {
                unset($row['event']['preposition']);
                unset($row['event']['article']);
                unset($row['event']['object']);
            }
        }
        
        if (isset($row['event']['object']) && $row['module'] == "locos" && $row['event']['object'] == "loco photo") {
            $row['event']['object'] = "cover photo";
        }
        
        return $row;
    }
}