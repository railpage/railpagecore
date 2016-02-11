<?php

/**
 * TransPerth GTFS stop/place class
 * @since Version 3.8.7
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\GTFS\AU\WA\TransPerth;

use Exception;
use DateTime;
use Zend\Http\Client;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;
use Railpage\GTFS\StopInterface;
use Railpage\Place;
use Railpage\GTFS\StandardStop;

/**
 * GTFS stop for the AU\TransPerth GTFS provider
 * @since Version 3.8.7
 */

class Stop extends StandardStop {
    
    /**
     * Constructor
     * @since Version 3.8.7
     * @param mixed $id
     */
    
    public function __construct($id = null) {
        $this->Provider = new TransPerth; 
        
        parent::__construct($id);
    }
    
    /**
     * Get the next departures for this stop
     * @return array
     */
    
    public function NextDepartures() {
        
    }
}
