<?php

/**
 * Image tag utility class
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Images\Utility;

use Exception;
use InvalidArgumentException;
use DateTime;
use Railpage\AppCore;
use Railpage\Debug;
use Railpage\Events\Events;
use Railpage\Events\Factory as EventsFactory;
use Railpage\Events\Event; 
use Railpage\Events\EventDate;
use Railpage\Images\Image;

class Tagger {
    
    /**
     * Suggest events to tag
     * @since Version 3.10.0
     * @param \Railpage\Images\Image $imageObject
     * @return array
     */
    
    public static function SuggestEvents(Image $imageObject) {
        
        if (!$imageObject->DateCaptured instanceof DateTime) {
            return;
        }
        
        $Database = (new AppCore)->getDatabaseConnection();
        
        $query = "SELECT COUNT(*) AS num FROM image_link WHERE namespace = ? AND image_id = ?";
        $params = [
            (new Event)->namespace,
            $imageObject->id
        ];
        
        if ($Database->fetchOne($query, $params) > 0) {
            return;
        }
        
        $Events = new Events;
        $list = $Events->getEventsForDate($imageObject->DateCaptured);
        
        foreach ($list as $k => $row) {
            $Event = new Event($row['event_id']); 
            
            printArray($Event->namespace);
            die;
            
            $list[$k]['url'] = sprintf("/services?method=railpage.image.tag&image_id=%d&object=%s&object_id=%d", $imageObject->id, "\\Railpage\\Events\\Event", $row['event_id']);
        }
        
        return $list;
        
    }
    
    /**
     * Clean dates and other crap from a string
     * Used for finding valid locomotives and loco classes in a photo for tagging
     * @since Version 3.10.0
     * @param string $string
     * @return string
     */
    
    private static function getCleanedTitleOrDesc($string) {

        // Strip dates from our lookup
        $stripdates = array(
            "[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}",  // 12/05/2015
            "[0-9]{1}\/[0-9]{2}\/[0-9]{2}",        // 1/05/2015
            "[0-9]{4}\/[0-9]{2}\/[0-9]{2}",        // 2015/05/12
            "[0-9]{2}\/[0-9]{4}",                  // 05/2015
            "[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}",      // 12-05-2015
            "[0-9]{1,2}-[0-9]{1,2}-[0-9]{2}",      // 12-05-15
            "[0-9]{4}-[0-9]{2}-[0-9]{2}",          // 2015-05-12
            "[0-9]{4}s",                           // 1990s
            "[0-9]{2}s",                           // 90s
            "[0-9]{4}-[0-9]{2}",                   // 2015-05
            "[0-9]{2}:[0-9]{2}",                   // 16:30
            "(January|February|March|April|May|June|July|August|September|October|November|December)\s[0-9]{2,4}",
            "(Jan|Feb|Mar|Apr|May|Jun|Jul|Augt|Sep|Sept|Oct|Nov|Dec)\s[0-9]{2,4}"
        );

        $stripdates = "/(" . implode("|", $stripdates) . ")/";

        $stripetc = array(
            "[\#0-9]{5}",
            "railpage:livery=[0-9]+",
            "railpage:class=[0-9]+"
        );

        $stripetc = "/(" . implode("|", $stripetc) . ")/";
        
        $string = preg_replace($stripdates, "", $string); 
        $string = preg_replace($stripetc, "", $string); 
        
        return trim($string);
        
    }
    
    /**
     * Suggest locos to tag
     * Ported from \Railpage\Images\Image
     * @since Version 3.10.0
     * @param \Railpage\Images\Image $imageObject
     * @param bool|null $skipTagged
     * @return array
     */
    
    public static function suggestLocos(Image $imageObject, $skipTagged = null) {
        
        $locolookup = array();
        $locos = array();

        $title = self::getCleanedTitleOrDesc($imageObject->title);
        $desc = self::getCleanedTitleOrDesc($imageObject->description);

        /**
         * Loop through all our possible regexes and search
         */
        
        $regexes = array(
            "[a-zA-Z0-9\w+]{4,6}",
            "[0-9\w+]{3,5}",
            "[a-zA-Z0-9\w+]{2}",
            "[a-zA-Z0-9\s\w+]{4,6}"
        );

        foreach ($regexes as $regex) {
            $regex = "/\b(" . $regex . ")\b/";

            preg_match_all($regex, $title, $matches['title']);
            preg_match_all($regex, $desc, $matches['description']);

            if (isset( $imageObject->meta['tags'] ) && count($imageObject->meta['tags'])) {
                foreach ($imageObject->meta['tags'] as $tag) {
                    // strip the tags
                    #$tag = trim(preg_replace($stripetc, "", $tag));
                    $tag = self::getCleanedTitleOrDesc($tag); 

                    if (empty($tag)) {
                        continue;
                    }
                    
                    preg_match_all($regex, $tag, $matches[]);
                }
            }

            foreach ($matches as $matched) {
                foreach ($matched as $array) {
                    foreach ($array as $v) {
                        if (empty($v) || !preg_match("/([0-9])/", $v) || preg_match("/(and|to|or|for)/", $v)) {
                            continue;
                        }
                        
                        if (in_array(trim($v), $locolookup)) {
                            continue;
                        }
                        
                        $locolookup[] = trim($v);
                        
                        /*
                        if (!empty( $v ) && preg_match("/([0-9])/", $v) && !preg_match("/(and|to|or|for)/", $v)) {
                            if (!in_array(trim($v), $locolookup)) {
                                $locolookup[] = trim($v);
                            }
                        }
                        */
                    }
                }
            }
        }

        /**
         * Try to include loco numbers with spaces (eg RT 40 vs RT40) in the lookup
         */

        foreach ($locolookup as $num) {
            if (preg_match("/(\s)/", $num)) {
                preg_match("/([a-zA-Z0-9]+)(\s)([a-zA-Z0-9]+)/", $num, $matches);

                if (isset( $matches[3] )) {
                    $prop = sprintf("%s%s", $matches[1], $matches[3]);
                    if (!in_array($prop, $locolookup)) {
                        $locolookup[] = $prop;
                    }
                }
            } elseif (strlen($num) == 5) {
                preg_match("/([a-zA-Z0-9]{2})([0-9]{3})/", $num, $matches);

                if (isset( $matches[2] )) {
                    $prop = sprintf("%s %s", $matches[1], $matches[2]);
                    if (!in_array($prop, $locolookup)) {
                        $locolookup[] = $prop;
                    }
                }
            }
        }

        $locolookup = array_unique($locolookup);

        /**
         * Prepare the SQL query
         */

        $query = "SELECT l.loco_id, l.loco_num, l.class_id, c.name AS class_name, s.name AS status_name, s.id AS status_id, t.id AS type_id, t.title AS type_name, g.gauge_id, CONCAT(g.gauge_name, ' ', g.gauge_imperial) AS gauge_formatted, o.operator_id, o.operator_name
            FROM loco_unit AS l 
                LEFT JOIN loco_class AS c ON l.class_id = c.id 
                LEFT JOIN loco_type AS t ON c.loco_type_id = t.id
                LEFT JOIN loco_status AS s ON l.loco_status_id = s.id
                LEFT JOIN loco_gauge AS g ON g.gauge_id = l.loco_gauge_id
                LEFT JOIN operators AS o ON l.operator_id = o.operator_id
            WHERE l.loco_num IN ('" . implode("','", $locolookup) . "') 
                AND l.loco_status_id NOT IN (2)";

        /**
         * Remove existing tags from our DB query
         */

        if ($skipTagged === true) {
            $tags = $imageObject->getObjects("railpage.locos.loco");

            if (count($tags)) {
                $ids = array();

                foreach ($tags as $tag) {
                    $ids[] = $tag['namespace_key'];
                }

                $query .= " AND l.loco_id NOT IN (" . implode(",", $ids) . ")";
            }

            $query .= " ORDER BY CHAR_LENGTH(l.loco_num) DESC";
        }

        /**
         * Loop through the DB results
         */

        $i = 0;

        foreach (AppCore::GetDatabase()->fetchAll($query) as $row) {
            $row['object'] = "Railpage\Locos\Locomotive";
            $locos[$row['loco_id']] = $row;

            $i++;

            if ($i == 5) {
                break;
            }
        }

        return $locos;
        
    }
    
}