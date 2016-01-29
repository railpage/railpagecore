<?php
    /**
     * Create URLs for the given timeline item
     * @since Version 3.9.1
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Users\Timeline\Utility;
    
    class Url {
        
        /**
         * Create the URL pointing to the relevant timeline entry
         * @since Version 3.9.1
         * @param array $row
         * @return string
         */
        
        static public function createUrl($row) {
            switch ($row['key']) {
                
                case "post_id" : 
                    $row['meta']['url'] = "/f-p" . $row['value'] . ".htm#" . $row['value'];
                    break;
                
                case "loco_id" : 
                    $Loco = new \Railpage\Locos\Locomotive($row['value']); 
                    $row['meta']['url'] = $Loco->url;
                    break;
                
                case "class_id" : 
                    $LocoClass = new \Railpage\Locos\LocoClass($row['value']); 
                    $row['meta']['url'] = $LocoClass->url;
                    break;
            }
            
            return isset($row['meta']['url']) ? $row['meta']['url'] : "";
        }
    }