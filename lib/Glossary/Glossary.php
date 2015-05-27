<?php
	/**
	 * Glossary of railway terms, acronyms and codes
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Glossary;
	
	use Railpage\AppCore;
	use Railpage\Module;
	use Exception;
	use DateTime;
	use stdClass;
	
	/**
	 * Glossary
	 */
	
	class Glossary extends AppCore {
		
		/**
		 * Constructor
		 */
		
		public function __construct() {
			parent::__construct();
			
			/**
			 * Record this in the debug log
			 */
			
			if (function_exists("debug_recordInstance")) {
				debug_recordInstance(__CLASS__);
			}
			
			/**
			 * Load the Module object
			 */
			
			$this->Module = new Module("glossary");
		}
		
		/**
		 * Get a list of new glossary entries
		 * @since Version 3.8.7
		 * @yield \Railpage\Glossary\Entry
		 * @param int $num Number of glossary entries to return
		 */
		
		public function getNewEntries($num = 10) {
			
			$query = "SELECT id FROM glossary WHERE status = ? ORDER BY date DESC LIMIT 0, ?";
			
			foreach ($this->db->fetchAll($query, array(Entry::STATUS_APPROVED, $num)) as $row) {
				yield new Entry($row['id']);
			}
			
		}
		
		/**
		 * Get a list of pending glossary entries
		 * @since Version 3.8.7
		 * @yield \Railpage\Glossary\Entry
		 * @param int $num Number of glossary entries to return
		 */
		
		public function getPendingEntries($num = 100) {
			
			$query = "SELECT id FROM glossary WHERE status = ? ORDER BY date DESC LIMIT 0, ?";
			
			foreach ($this->db->fetchAll($query, array(Entry::STATUS_UNAPPROVED, $num)) as $row) {
				yield new Entry($row['id']);
			}
			
		}
	}
	