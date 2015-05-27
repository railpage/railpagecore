<?php
	/**
	 * Chronicle entry provider interface
	 * @since Version 3.9
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Chronicle;
	
	/**
	 * Chronicle entry provider interface
	 */
	
	interface ProviderInterface {
		
		/**
		 * Get events from a given date range
		 * @since Version 3.9
		 * @param \DateTime $From
		 * @param \DateTime $To
		 * @return \Railpage\Chronicle\Entry
		 * @yield \Railpage\Chronicle\Entry
		 */
		
		public function getEventsForDates($From, $To);
		
		/**
		 * Load an event from this provider
		 * @since Version 3.9
		 * @param int $id
		 * @return array
		 */
		
		public function getEvent($id);
		
		/**
		 * Get events from a given date
		 * @since Version 3.9
		 * @param \DateTime $Date
		 * @return \Railpage\Chronicle\Entry
		 * @yield \Railpage\Chronicle\Entry
		 */
		
		public function getEventsForDate($Date);
		
		/**
		 * Get events from the week surrounding the given date
		 * @since Version 3.9
		 * @param \DateTime $Date
		 * @return \Railpage\Chronicle\Entry
		 * @yield \Railpage\Chronicle\Entry
		 */
		
		public function getEventsForWeek($Date);
		
		/**
		 * Get events from the month surrounding the given date
		 * @since Version 3.9
		 * @param \DateTime $Date
		 * @return \Railpage\Chronicle\Entry
		 * @yield \Railpage\Chronicle\Entry
		 */
		
		public function getEventsForMonth($Date);
		
		/**
		 * Get events from the year surrounding the given date
		 * @since Version 3.9
		 * @param \DateTime $Date
		 * @return \Railpage\Chronicle\Entry
		 * @yield \Railpage\Chronicle\Entry
		 */
		
		public function getEventsForYear($Date);
		
	}
	