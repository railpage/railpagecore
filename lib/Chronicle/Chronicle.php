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
			
			$this->Module = new Module("chronicle");
			
		}
	}
?>