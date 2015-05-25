<?php
	/**
	 * Chronicle module - a history of railway events
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Chronicle;
	
	use Railpage\AppCore;
	use Railpage\Module;
	Use Railpage\Url;
	use Exception;
	use DateTime;
	
	/**
	 * Chronicle base class
	 */
	
	class Chronicle extends AppCore {
		
		/**
		 * Constructor
		 */
		
		public function __construct() {
			
			parent::__construct();
			
			$this->Module = new Module("chronicle");
			$this->url = new Url(sprintf("%s/chronicle", RP_WEB_ROOT));
			$this->url->newest = sprintf("%s?mode=newest", $this->url->url);
			$this->url->year = sprintf("%s?mode=year", $this->url->url);
			$this->url->today = sprintf("%s?mode=today", $this->url->url);
			$this->url->thisweek = sprintf("%s?mode=thisweek", $this->url->url);
			$this->url->thismonth = sprintf("%s?mode=thismonth", $this->url->url);
		}
		
		/**
		 * Get latest additions to the chronicle
		 * @since Version 3.8.7
		 * @yield \Railpage\Chronicle\Entry
		 * @return \Railpage\Chronicle\Entry
		 */
		
		public function getLatestAdditions() {
			$query = "SELECT id FROM chronicle_item WHERE status = ? ORDER BY id DESC LIMIT 0, 10";
			
			foreach ($this->db->fetchAll($query, Entry::STATUS_ACTIVE) as $row) {
				yield new Entry($row['id']);
			}
		}
		
		/**
		 * Get events for a date
		 * @since Version 3.8.7
		 * @return array
		 * @param \DateTime $Date
		 */
		
		public function getEntriesForDate($Date = false) {
			
			if (!$Date || !$Date instanceof DateTime) {
				$Date = new DateTime;
			}
			
			$events = array(); 
			
			foreach ($this->getProviders() as $name) {
				$Provider = new $name;
				
				$date_events = $Provider->getEventsForDate($Date); 
				
				if (is_array($date_events)) {
					$events = array_merge($events, $Provider->getEventsForDate($Date));
				}
			}
			
			return $events;
			
			/*
			if (!$date) {
				$date = date("m-d");
			}
			
			if ($date instanceof DateTime) {
				$date = $date->format("Y-m-d");
			}
			
			$date = sprintf("%%%s%%", $date);
			
			$query = "SELECT id FROM chronicle_item WHERE date LIKE ? ORDER BY date";
			
			foreach ($this->db->fetchAll($query, $date) as $row) {
				yield new Entry($row['id']);
			}
			*/
		}
		
		/**
		 * Get chronicle entry providers
		 * @since Version 3.9
		 * @return array
		 */
		
		public function getProviders() {
			
			$providers = array(); 
			
			foreach (glob(sprintf("%s%sProvider%s*.php", __DIR__, DS, DS)) as $file) {
				$providers[] = sprintf("\\Railpage\\Chronicle%s", str_replace("/", "\\", str_replace(__DIR__, "", str_replace(".php", "", $file))));
			}
			
			return $providers;
			
		}
	}
	