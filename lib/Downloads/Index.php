<?php

/** 
 * Downloads 
 * @since Version 3.0
 * @version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Downloads;

use DateTime;
use DateTimeZone;
use Exception;
    
/**
 * Downloads index
 * @since Version 3.2
 * @version 3.8.7
 */

class Index extends Base {
    
    /**
     * Get the latest additions to the database
     * @since Version 3.2
     * @return array
     * @param int $num Number of items to return
     */
    
    public function latest($num = 10) {
        
        return $this->getFromDatabase("d.date", "DESC", 0, $num);
        
    }
    
    /**
     * Get the most downloaded files in the database
     * @since Version 3.2
     * @return array
     * @param int $num Number of items to return
     */
    
    public function popular($num = 10) {
        
        return $this->getFromDatabase("d.hits", "DESC", 0, $num);
        
    }
    
    /**
     * Get downloads, ordered by key and direction with offset and number of items
     * @since Version 3.9.1
     * @param string $key
     * @param string $direction
     * @param int $offset
     * @param int $num
     */
    
    private function getFromDatabase($key, $direction = "DESC", $offset = 0, $num = 10) {
        $direction = strtoupper($direction); 
        
        if ($direction != "ASC" && $direction != "DESC") {
            $direction = "DESC";
        }
        
        $query = "SELECT d.id AS download_id, d.title AS download_title, d.description AS download_desc, d.date, c.category_id, c.category_title
                    FROM download_items AS d
                    LEFT JOIN download_categories AS c ON d.category_id = c.category_id
                    WHERE d.approved = 1
                    AND d.active = 1
                    ORDER BY " . $key . " " . $direction . "
                    LIMIT " . $offset . ", " . $num . ""; 
        
    
        $return = array(
            "stat" => "ok",
            "downloads" => array()
        );
        
        foreach ($this->db->fetchAll($query) as $row) {
            $row['date'] = new DateTime($row['date'], new DateTimeZone("Australia/Melbourne"));
            $return['downloads'][$row['download_id']] = $row; 
        }
        
        return $return;

    }
}
