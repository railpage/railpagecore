<?php

/**
 * Standard GTFS route object
 * @since Version 3.9
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\GTFS;

use Exception;
use DateTime;
use Zend\Http\Client;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;
use Railpage\GTFS\GTFSInterface;
use Railpage\GTFS\RouteInterface;
use Railpage\Url;
use Railpage\AppCore;

/**
 * Routes class
 */

class StandardRoute implements RouteInterface {
    
    /**
     * GTFS Provider
     * @since Version 3.9
     * @var object $Provider An instance of a Railpage GTFS provider
     */
    
    protected $Provider;
    
    /**
     * Constructor
     * @since Version 3.9
     * @param int $routeId
     * @param object $gtfsProvider
     */
    
    public function __construct($routeId = null, $gtfsProvider = null) {
        
        if (function_exists("getRailpageConfig")) {
            $this->Config = getRailpageConfig();
        }
        
        $this->adapter = new Adapter(array(
            "driver" => "Mysqli",
            "database" => $this->Config->GTFS->PTV->db_name,
            "username" => $this->Config->GTFS->PTV->db_user,
            "password" => $this->Config->GTFS->PTV->db_pass,
            "host" => $this->Config->GTFS->PTV->db_host
        ));
        
        $this->db = new Sql($this->adapter);
        
        if (is_object($gtfsProvider)) {
            $this->Provider = $gtfsProvider;
        }
        
        /**
         * Fetch the route
         */
        
        if (!filter_var($routeId, FILTER_VALIDATE_INT)) {
            return;
        }
        
        $query = sprintf("SELECT route_id, route_short_name, route_long_name, route_desc, route_type, route_url, route_color, route_text_color FROM %s_routes WHERE id = %s", $this->Provider->getDbPrefix(), $routeId);
        
        $result = $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
        
        if (!is_array($result)) {
            return;
        }
        
        foreach ($result as $row) {
            $row = $row->getArrayCopy();
            
            $this->id = $routeId;
            $this->route_id = $row['route_id'];
            $this->short_name = $row['route_short_name'];
            $this->long_name = $row['route_long_name'];
            $this->desc = $row['route_desc'];
            $this->type = $row['route_type'];
        }
    }
    
    /**
     * Fetch trips for this route
     * @since Version 3.9
     * @return array
     */
    
    public function getTrips() {
        $query = sprintf("SELECT id, service_id, trip_id, trip_headsign, shape_id, meta FROM %s_trips WHERE route_id = '%s'", $this->Provider->getDbPrefix(), $this->short_name);
        
        $trips = array(); 
        
        $result = $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
        
        if ($result) {
            foreach ($result as $row) {
                $trips[] = $row->getArrayCopy();
            }
        }
        
        return $trips;
    }
    
    /**
     * Fetch stops for this route
     * @since Version 3.9
     * @return array
     */
    
    public function getStops() {
        
    }
    
    /**
     * Get path of this route
     * @since Version 3.9
     * @return array
     */
    
    public function getPath() {
        $mckey = sprintf("railpage:gtfs.path;provider=%s;route=%s", $this->Provider->getProviderName(), $this->short_name);
        
        $Memcached = AppCore::GetMemcached(); 
        
        if ($path = $Memcached->fetch($mckey)) {
            return $path;
        }
        
        $query = sprintf("SELECT id, service_id, trip_id, trip_headsign, shape_id, meta FROM %s_trips WHERE route_id = '%s' LIMIT 1", $this->Provider->getDbPrefix(), $this->short_name);
        
        $result = $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
        
        if ($result) {
            foreach ($result as $row) {
                $trip = $row->getArrayCopy();
            }
        }
        
        if (!isset($trip)) {
            return false;
        }
        
        $query = "SELECT t.id, t.arrival_time, t.departure_time, t.stop_id, t.stop_sequence, t.pickup_type, t.drop_off_type, t.timepoint, t.meta AS time_meta, 
                    s.stop_code, s.stop_name, s.stop_lat, s.stop_lon, s.location_type, s.wheelchair_boarding, s.platform_code, s.meta AS stop_meta
                    FROM %s_stop_times AS t LEFT JOIN %s_stops AS s ON t.stop_id = s.stop_id
                    WHERE t.trip_id = %d ORDER BY t.stop_sequence";
        
        $query = sprintf($query, $this->Provider->getDbPrefix(), $this->Provider->getDbPrefix(), $trip['trip_id']);
        
        $result = $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
        
        $path = array(); 
        
        if ($result) {
            foreach ($result as $row) {
                $url = new Url(sprintf("/timetables?mode=stop&provider=%s&id=%d", $this->Provider->getProviderName(), $row['stop_id']));
                
                $row = $row->getArrayCopy();
                $row['stop_meta'] = json_decode($row['stop_meta'], true);
                $row['time_meta'] = json_decode($row['time_meta'], true);
                $row['url'] = $url->getURLs();
                
                $path[] = $row;
            }
        }
        
        $Memcached->save($mckey, $path, strtotime("+1 month"));
        
        return $path;
    }
}
