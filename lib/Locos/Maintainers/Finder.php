<?php

/**
 * Find things for maintainers to do
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Locos\Maintainers;

use Railpage\AppCore;
use Railpage\Locos\Locos;
use Railpage\Locos\LocoClass;
use Railpage\Locos\Locomotive;
use Exception;
use InvalidArguementException;

/**
 * Finder
 */

class Finder extends AppCore {
        
    /**
     * Find : Locos classes with no photos
     * @since Version 3.9
     * @const FIND_CLASS_NOPHOTO
     */
    
    const FIND_CLASS_NOPHOTO = "class_nophoto";
    
    /**
     * Find : Locos with no photos
     * @since Version 3.9
     * @const FIND_LOCO_NOPHOTO
     */
    
    const FIND_LOCO_NOPHOTO = "loco_nophoto";
    
    /**
     * Find : Locos in an array of numbers
     * @since Version 3.10.0
     * @const FIND_LOCO_FROMNUMBERS
     */
    
    const FIND_LOCO_FROMNUMBERS = "loco_findfromnumbers";
    
    /**
     * Find : loco or loco class from a supplied tag or list of tags
     * @since Version 3.10.0
     * @const FIND_FROM_TAGS
     */
    
    const FIND_FROM_TAGS = "find_from_tags";
    
    /**
     * Find data for managers to fix
     * @since Version 3.2
     * @version 3.5
     * @param string $search_type
     * @param string $args
     * @return array
     */
    
    public function find($searchType = null, $args = null) {
        if (is_null($searchType)) {
            throw new Exception("Cannot find data - no search type given");
            return false;
        }
        
        /**
         * Find loco classes without a cover photo
         */
        
        if ($searchType === self::FIND_CLASS_NOPHOTO) {
            return $this->findClassNoPhotos(); 
        }
        
        /**
         * Find locomotives without a cover photo
         */
        
        if ($searchType === self::FIND_LOCO_NOPHOTO) {
            return $this->findLocosNoPhotos();
        }
        
        /**
         * Find locomotives from a comma-separated list of numbers
         */
        
        if ($searchType === self::FIND_LOCO_FROMNUMBERS && !is_null($args)) {
            return $this->findLocosFromList($args); 
        }
        
        if ($searchType == self::FIND_FROM_TAGS && !is_null($args)) {
            return $this->findFromTags($args); 
        }
    }
    
    /**
     * Find locos from a list of supplied numbers
     * @since Version 3.10.0
     * @param array $args
     * @return array
     */
    
    private function findLocosFromList($args) {
        $numbers = explode(",", $args); 
        foreach ($numbers as $id => $num) {
            $numbers[$id] = trim($num); 
        }
        
        $query = "SELECT l.loco_id, l.loco_num, l.loco_status_id, s.name AS loco_status, l.loco_gauge_id, 
                         CONCAT(g.gauge_name, ' (', g.gauge_metric, ')') AS loco_gauge, c.loco_type_id, 
                         t.title AS loco_type, c.id AS class_id, c.name AS class_name 
                  FROM loco_unit AS l
                  LEFT JOIN loco_class AS c ON l.class_id = c.id
                  LEFT JOIN loco_status AS s ON s.id = l.loco_status_id
                  LEFT JOIN loco_gauge AS g ON g.gauge_id = l.loco_gauge_id
                  LEFT JOIN loco_type AS t ON c.loco_type_id = t.id
                  WHERE l.loco_num IN ('".implode("','", $numbers)."')
                  ORDER BY l.loco_num, c.name";
        
        $return = array(); 
        
        foreach ($this->db->fetchAll($query) as $row) {
            $return[$row['loco_id']] = $row; 
        }
        
        return $return;

    }
    
    /**
     * Find locos with no photo
     * @since Version 3.10.0
     * @return array
     */
    
    private function findClassNoPhotos() {
        
        $return = array(); 
        
        $classes = $this->listClasses();
        
        foreach ($classes['class'] as $row) {
            
            $LocoClass = new LocoClass($row['class_id']);
            
            if (!$LocoClass->hasCoverImage()) {
                $return[$LocoClass->id] = array(
                    "id" => $LocoClass->id,
                    "name" => $LocoClass->name,
                    "flickr_tag" => $LocoClass->flickr_tag,
                    "url" => $LocoClass->url->getURLs()
                );
            }
        }
        
        return $return;
        
    }
    
    /**
     * Find locomotives with no photo
     * @since Version 3.10.0
     * @return array
     */
    
    private function findLocosNoPhotos() {
        
        $query = "SELECT l.loco_id, l.loco_num, l.loco_status_id, s.name AS loco_status, c.id AS class_id, c.name AS class_name 
                    FROM loco_unit AS l
                    LEFT JOIN loco_class AS c ON l.class_id = c.id
                    LEFT JOIN loco_status AS s ON s.id = l.loco_status_id
                    WHERE l.photo_id = 0
                    ORDER BY c.name, l.loco_num";
        
        $return = array(); 
        
        foreach ($this->db->fetchAll($query) as $row) {
            $return[$row['loco_id']] = $row; 
        }
        
        return $return;
        
    }
    
    /**
     * Find shit from a supplied tag or list of tags
     * @since Version 3.10.0
     * @param array $tags
     * @return array
     */
    
    private function findFromTags($tags) {
        if (!is_array($tags)) {
            $tags = array($tags);
        }
        
        $craptags = array("rp3");
        
        foreach ($tags as $id => $tag) {
            if (in_array($tag, $craptags)) {
                unset($tags[$id]); 
            }
            
            if (preg_match("@railpage\:class\=([0-9]+)@", $tag, $matches)) {
                $classes[] = $matches[1];
            }
            
            if (preg_match("@railpage\:loco\=([a-zA-Z0-9\s\-]+)@", $tag, $matches)) {
                $locos[] = $matches[1];
            }
        }
        
        $return = array(); 
        $liveries = array(); 
        
        // Load class tags from the database
        $query = "SELECT REPLACE(flickr_tag, '-', '') AS flickr_tag, name, id FROM loco_class WHERE flickr_tag != ''";

        $liveries = $this->db->fetchAll($query);
        
        $liveries = array_map(function($row) {
            if (!in_array($row['flickr_tag'], $tags) && !in_array($row['id'], $classes)) {
                 return $row; 
            }
             
            $row['flickr_tag'] = strtolower($row['flickr_tag']);
            
            foreach ($tags as $tag) {
                if (!isset($locos) || !is_array($locos) || count($locos) == 0) {
                    continue;
                }
                
                if ($tag != $row['flickr_tag'] && stristr($tag, $row['flickr_tag']) && !@in_array($tag, $row['locos'])) {
                    $row['locos'][] = strtoupper(str_replace($row['flickr_tag'], "", $tag));
                }
            }
            
            if (isset($row['locos']) && is_array($row['locos'])) {
                natsort($row['locos']);
            }
            
            $return[$row['id']] = $row; 
             
        }, $liveries); 
        
        /*
        foreach ($liveries as $row) {
            if (@in_array($row['flickr_tag'], $tags) || @in_array($row['id'], $classes)) {
                // Back to lowercase, flickr is silly
                $row['flickr_tag'] = strtolower($row['flickr_tag']);
                foreach ($tags as $tag) {
                    if (isset($locos) && is_array($locos) && count($locos) > 0) {
                        if ($tag != $row['flickr_tag'] && stristr($tag, $row['flickr_tag']) && !@in_array($tag, $row['locos'])) {
                            $row['locos'][] = strtoupper(str_replace($row['flickr_tag'], "", $tag));
                        }
                    }
                }
                
                if (isset($row['locos']) && is_array($row['locos'])) {
                    natsort($row['locos']);
                }
                
                $return[$row['id']] = $row; 
            }
        }
        */
        
        return $return;

    }
    
}