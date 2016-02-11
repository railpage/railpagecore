<?php

/**
 * Transport for NSW GTFS interface
 * @since Version 3.9
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\GTFS\AU\NSW\TFNSW;

use Exception;
use DateTime;
use Zend\Http\Client;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;
use Railpage\GTFS\GTFSInterface;
use Railpage\GTFS\StandardRoute;
use Railpage\Url;

/**
 * Routes class
 */

class Route extends StandardRoute {
    
    /**
     * Constructor
     * @since Version 3.9
     * @param int $route_id
     */
    
    public function __construct($route_id = false, $Provider = false) {
        
        parent::__construct($route_id, $Provider);
        
    }
}
