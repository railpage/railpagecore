<?php
	/**
	 * Events management class
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Events;
	
	use Railpage\AppCore;
	use Railpage\Module;
	use DateTime;
	use DateTimeZone;
	use Railpage\Organisations\Organisation;
	
	/**
	 * Events
	 * 
	 * The Events master class
	 * @since Version 3.8.7
	 */
	
	class Events extends AppCore {
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 */
		
		public function __construct() {
			parent::__construct(); 
			
			$this->Module = new Module("events");
			$this->namespace = $this->Module->namespace;
		}
		
		/**
		 * Find an event based on name
		 * @param string $name The name of the event to search for
		 * @return array
		 */
		
		public function findEvent($name = NULL) {
			if (!is_string($name) || is_null($name)) {
				return false;
			}
			
			return $this->db->fetchAll("SELECT * FROM event WHERE title = ?", $name);
		}
		
		/**
		 * Get events for a given DateTime object
		 * @param \DateTime $Date An optional DateTime instance to search for. Will default to today if not provided
		 * @return array
		 */
		
		public function getEventsForDate(DateTime $Date = NULL) {
			if (!$Date instanceof DateTime) {
				$Date = new DateTime;
			}
			
			return $this->db->fetchAll("SELECT * FROM event_dates WHERE date = ?", $Date->format("Y-m-d"));
		}
		
		/**
		 * Get upcoming events
		 * @param int $items_per_page The number of events to return
		 * @param int $page The "page" number of events
		 * @return array
		 */
		
		public function getUpcomingEvents($items_per_page = 25, $page = 1) {
			$Now = new DateTime();
			$args = array($Now->format("Y-m-d"), ($page - 1) * $items_per_page, $items_per_page); 
			
			return $this->db->fetchAll("SELECT * FROM event_dates WHERE date >= ? ORDER BY date LIMIT ?, ?", $args);
		}
		
		/**
		 * Get event categories
		 * @since Version 3.8.7
		 * @return array
		 */
		
		public function getCategories() {
			return $this->db->fetchAll("SELECT * FROM event_categories ORDER BY title");
		}
		
		/**
		 * Get upcoming events for an organisation
		 * @since Version 3.8.7
		 * @return array
		 */
		
		public function getUpcomingEventsForOrganisation(Organisation $Org) {
			if (!filter_var($Org->id, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot fetch upcoming events because the specified organisation is invalid or doesn't exist");
			}
			
			$query = "SELECT id, event_id, date FROM event_dates WHERE date >= ?";
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query, date("Y-m-d")) as $row) {
				$Event = new Event($row['event_id']);
				$return[$row['date']][] = array(
					"id" => $Event->id,
					"name" => $Event->name,
				);
			}
			
			return $return;
		}
	}
?>