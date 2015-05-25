<?php
	/**
	 * GTFS interface for Railpage
	 * @author Michael Greenhill
	 * @package Raipage
	 * @since Version 3.8.7
	 */
	
	namespace Railpage\GTFS;
	
	/**
	 * An interface for GTFS providers
	 * @since Version 3.8.7
	 */
	
	interface GTFSInterface {
		
		/**
		 * Fetch data from an API
		 *
		 * Not implemented on most GTFS providers
		 * @since Version 3.8.7
		 * @param string $method
		 * @param string $parameters
		 * @param string $other
		 * @return array
		 */
		
		public function fetch($method, $parameters, $other);
		
		/**
		 * Get the API health
		 *
		 * Not implemented on most GTFS providers
		 * @since Version 3.8.7
		 * @return string
		 */
		
		public function Health();
		
		/**
		 * Find GTFS stops near a given latitude and longitude pair
		 * @since Version 3.8.7
		 * @param double $latitude
		 * @param double $longitude
		 * @return array
		 */
		
		public function StopsNearLocation($latitude, $longitude);
		
		/**
		 * Get routes from GTFS data
		 * @since Version 3.9
		 * @return array
		 */
		
		public function GetRoutes();
	}
	