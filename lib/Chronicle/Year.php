<?php
	/** 
	 * An object representing a year in the Chronicle
	 * @since Version 3.9
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Chronicle;
	
	use Railpage\Url;
	use Railpage\Users\User;
	use Railpage\Place;
	use Railpage\Module;
	use Exception;
	use DateTime;
	use Zend_Db_Expr;
	
	/**
	 * Year
	 */
	
	class Year extends Chronicle {
		
		/**
		 * This year in history
		 * @since Version 3.9
		 * @var int $year
		 */
		
		public $year;
		
		/**
		 * Constructor
		 * @since Version 3.9
		 * @param int $year
		 */
		
		public function __construct($year = false) {
			
			parent::__construct(); 
			
			if ($year != false) {
				if (checkdate(1, 1, $year)) {
					$this->year = $year;
				}
			}
		}
		
		/**
		 * Get the decade this year is in
		 * @since Version 3.9
		 * @return \Railpage\Chronicle\Decade
		 */
		
		public function getDecade() {
			return new Decade($this->year);
		}
		
		/**
		 * Get events from this year
		 * @since Version 3.9
		 */
		
		public function yieldEntries() {
			
		}
	}