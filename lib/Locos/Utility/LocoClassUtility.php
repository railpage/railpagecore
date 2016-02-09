<?php

/**
 * Utility class for LocoClass
 * @since Version 3.9.1
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Locos\Utility;

use Exception;
use InvalidArgumentException;
use DateTime;
use Railpage\AppCore;
use Railpage\Debug;
use Railpage\Url;
use Railpage\Locos\Locomotive;
use Railpage\Locos\LocoClass;
use Railpage\Locos\Factory as LocosFactory;

class LocoClassUtility {
    
    /**
     * Update last owner and operator for the fleet
     * @since Version 3.9.1
     * @return void
     * @param \Railpage\Locos\LocoClass $Class
     */
    
    public static function updateFleet_OwnerOperator(LocoClass $Class) {
        
        $Database = (new AppCore)->getDatabaseConnection(); 
        
        $members = $Class->members(); 
        
        foreach ($members['locos'] as $row) {
            
            $Loco = LocosFactory::CreateLocomotive($row['loco_id']); 
            
            $query = "(SELECT operator_id AS owner_id, link_type FROM loco_org_link WHERE loco_id = ? AND link_type = 1 ORDER BY link_weight DESC LIMIT 0,1)
UNION ALL
(SELECT operator_id, link_type FROM loco_org_link WHERE loco_id = ? AND link_type = 2 ORDER BY link_weight DESC LIMIT 0,1)";
            
            $result = $Database->fetchAll($query, array($Loco->id, $Loco->id)); 
            
            #$commit = false;
            
            foreach ($result as $row) {
                
                #printArray($row['organisation_id']); printArray($Loco->owner_id); die;
                
                if ($row['link_type_id'] == 1) {
                    $Loco->owner_id = $row['operator_id']; 
                    #$commit = true;
                }
                
                if ($row['link_type_id'] == 2) {
                    $Loco->operator_id = $row['operator_id']; 
                    #$commit = true;
                }
            }
            
            #if ($commit) {
                $Loco->commit(); 
            #}
            
            #break;
            
        }
        
        $Class->flushMemcached(); 
        
        return;
        
    }
    
    /**
     * Create loco class URLs
     * @since Version 3.10.0
     * @param \Railpage\Locos\LocoClass $locoClass
     * @return \Railpage\Url
     */
    
    public static function buildUrls(LocoClass $locoClass) {
        
        $url = new Url($locoClass->makeClassURL($locoClass->slug));
        $url->photos = sprintf("/photos/search?class_id=%d", $locoClass->id);
        $url->view = $url->url;
        $url->edit = sprintf("%s?mode=class.edit&id=%d", $locoClass->Module->url, $locoClass->id);
        $url->addLoco = sprintf("%s?mode=loco.edit&class_id=%d", $locoClass->Module->url, $locoClass->id);
        $url->sightings = sprintf("%s/sightings", $url->url);
        $url->bulkadd = sprintf("%s?mode=loco.bulkadd&class_id=%d", $locoClass->Module->url, $locoClass->id);
        $url->bulkedit = sprintf("%s?mode=class.bulkedit&id=%d", $locoClass->Module->url, $locoClass->id);
        $url->bulkedit_operators = sprintf("%s?mode=class.bulkedit.operators&id=%d", $locoClass->Module->url, $locoClass->id);
        $url->bulkedit_buildersnumbers = sprintf("%s?mode=class.bulkedit.buildersnumbers&id=%d", $locoClass->Module->url, $locoClass->id);
        $url->bulkedit_status = sprintf("%s?mode=class.bulkedit.status&id=%d", $locoClass->Module->url, $locoClass->id);
        $url->bulkedit_gauge = sprintf("%s?mode=class.bulkedit.gauge&id=%d", $locoClass->Module->url, $locoClass->id);
        
        return $url;
        
    }
    
}