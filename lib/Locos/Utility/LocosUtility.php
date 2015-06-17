<?php
	/**
	 * Locomotive / loco class cover photo utility
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos\Utility;
	
	use Railpage\Locos\LocoClass;
	use Railpage\Locos\Locomotive;
	use Railpage\ContentUtility; 
	use Railpage\Asset;
	use Railpage\AppCore;
	use Exception;
	use Railpage\Debug;
	use Zend_Db_Expr;
	
	
	class LocosUtility {
		
		/**
		 * Add an asset
		 * @since Version 3.9.1
		 * @param string $namespace
		 * @param int $id
		 * @param array $data
		 * @return void
		 */
		
		public static function addAsset($namespace, $id, $data) {
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$data = array_merge($data, array(
				"date" => new Zend_Db_Expr("NOW()"),
				"namespace" => $namespace,
				"namespace_key" => $id
			));
			
			$meta = json_encode($data['meta']);
			
			/**
			 * Handle UTF8 errors
			 */
			
			if (!$meta && json_last_error() === JSON_ERROR_UTF8) {
				$data['meta'] = ContentUtility::FixJSONEncode_UTF8($data['meta']); 
			} else {
				$data['meta'] = $meta;
			}
			
			$Database->insert("asset", $data);
			return true;
			
		}
		
	}