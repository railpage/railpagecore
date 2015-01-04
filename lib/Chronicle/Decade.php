<?php
	/** 
	 * An object representing a decade in the Chronicle
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
	 * Decade
	 */
	
	class Decade extends Chronicle {
		
		/**
		 * The starting year of this decade
		 * @since Version 3.9
		 * @var int $decade
		 */
		
		public $decade;
		
		/**
		 * Constructor
		 * @since Version 3.9
		 * @param int $decade
		 */
		
		public function __construct($decade = false) {
			
			parent::__construct(); 
			
			if ($decade != false) {
				$decade = floor($decade / 10) * 10;
				
				if (checkdate(1, 1, $decade)) {
					$this->decade = $decade;
				}
			}
		}
		
		/**
		 * Get events from this decade
		 * @since Version 3.9
		 */
		
		public function yieldEntries() {
			
		}
	}