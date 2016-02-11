<?php

/**
 * Transport for SA AdelaideMetro GTFS interface
 * @since Version 3.9
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\GTFS\AU\SA\AdelaideMetro;

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
     * @param int $routeId
     * @param object $providerObject
     */
    
    public function __construct($routeId = null, $providerObject = null) {
        
        parent::__construct($routeId, $providerObject);
        
    }
}
