<?php
	/**
	 * General timeline processing functions
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Users\Timeline\Utility;
	
	use Railpage\Module;
	
	class General {
		
		/**
		 * Get the site module for a specified timeline entry
		 * @since Version 3.9.1
		 * @param array $row
		 * @return string
		 */
		
		static public function getModuleNamespace($row) {
			$Module = new Module($row['module']);
			
			return $Module->namespace;
		}
	}