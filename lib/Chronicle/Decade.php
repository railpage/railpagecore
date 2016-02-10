<?php

/** 
 * An object representing a decade in the Chronicle
 * @since Version 3.9
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Chronicle;

use Railpage\Url;
use Railpage\Users\User;
use Railpage\Place;
use Railpage\Module;
use Exception;
use DateTime;
use Zend_Db_Expr;

/**
 * Decade
 */

class Decade extends Chronicle {
    
    /**
     * The starting year of this decade
     * @since Version 3.9
     * @var int $decade
     */
    
    public $decade;
    
    /**
     * Constructor
     * @since Version 3.9
     * @param int $decade
     */
    
    public function __construct($decade = null) {
        
        parent::__construct(); 
        
        if ($decade == null) {
            return;
        }
        
        $decade = floor($decade / 10) * 10;
        
        if (checkdate(1, 1, $decade)) {
            $this->decade = $decade;
        }
    }
    
    /**
     * Get events from this decade
     * @since Version 3.9
     */
    
    public function yieldEntries() {
        
        $query = "SELECT id FROM chronicle_item WHERE date BETWEEN ? AND ?";
        
        $decadeStart = $this->decade . "-01-01"; 
        $decadeEnd = $this->decade + 9 . "-12-31";
        
        foreach ($this->db->fetchAll($query, array($decadeStart, $decadeEnd)) as $row) {
            yield new Entry($row['id']);
        }
        
    }
}
