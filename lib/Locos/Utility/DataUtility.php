<?php
	/**
	 * Data formatter and such
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos\Utility;
	
	class DataUtility {
		
		/**
		 * Get locomotive var => column mapping
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public static function getLocoColumnMapping() {
			
			return array(
				"loco_num" => "number",
				"loco_name" => "name",
				"loco_gauge_id" => "gauge_id",
				"loco_status_id" => "status_id",
				"loco_status" => "status",
				"class_id" => "class_id",
				"owner_id" => "owner_id",
				"owner_name" => "owner",
				"operator_id" => "operator_id",
				"operator_name" => "operator",
				"entered_service" => "entered_service",
				"withdrawn" => "withdrawal_date",
				"date_added" => "date_added",
				"date_modified" => "date_modified",
				"builders_number" => "builders_num",
				"photo_id" => "photo_id",
				"manufacturer_id" => "manufacturer_id"
			);

		}
		
	}