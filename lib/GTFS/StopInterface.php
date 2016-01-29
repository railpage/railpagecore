<?php
    /**
     * GTFS interface for Railpage
     * @author Michael Greenhill
     * @package Raipage
     * @since Version 3.8.7
     */
    
    namespace Railpage\GTFS;
    
    /**
     * An interface for GTFS stops
     * @since Version 3.8.7
     */
    
    interface StopInterface {
        
        /**
         * Get the list of next departures from this stop
         * @return array
         */
        
        function NextDepartures();
        
    }
    