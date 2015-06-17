<?php
	/**
	 * Get an ID from a given slug
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Jobs; 
	
	use Railpage\AppCore;
	use Railpage\Module;
	use Railpage\Organisations\Organisation;
	use Exception;
	use DateTime;
	use Zend_Db_Expr;
	
	class SlugUtility {
		
		/**
		 * Get the ID
		 * @since Version 3.9.1
		 * @param string $type
		 * @param string $slug
		 * @return int
		 */
		
		public static function getID($type, $slug) {
			
			$query = "SELECT jn_" . $type . "_id FROM jn_" . $type . "s WHERE jn_" . $type . "_name = ?";
			
			if ($id = $this->db->fetchOne($query, $slug)) {
				return $id;
			}
			
			return false;
			
		}
	}