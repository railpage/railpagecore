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
		 * @yield \Railpage\Chronicle\Entry
		 * @return \Railpage\Chronicle\Entry
		 */
		
		public function getEntriesForDate($date = false) {
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
		}
	}
?>