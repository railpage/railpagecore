<?php
	/**
	 * Locomotive utility class
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos\Utility;
	
	use Railpage\Url;
	use Railpage\AppCore;
	use Railpage\Module;
	use Railpage\Locos\Locomotive;
	use Railpage\Locos\Date;
	use DateTime;
	use Exception;
	use InvalidArgumentException;
	
	class LocomotiveUtility {
		
		/**
		 * Fetch locomotive data from Memcached/Redis/Database
		 * @since Version 3.9.1
		 * @param \Railpage\Locos\Locomotive $Loco
		 * @return array
		 */
		
		public static function fetchLocomotive(Locomotive $Loco) {
			
			$Memcached = AppCore::getMemcached();
			$Database = (new AppCore)->getDatabaseConnection();
			
			if (!$row = $Memcached->fetch($Loco->mckey)) {
				if (RP_DEBUG) {
					global $site_debug;
					$debug_timer_start = microtime(true);
				}
				
				$query = "SELECT l.*, s.name AS loco_status, ow.operator_name AS owner_name, op.operator_name AS operator_name
							FROM loco_unit AS l
							LEFT JOIN loco_status AS s ON l.loco_status_id = s.id
							LEFT JOIN operators AS ow ON ow.operator_id = l.owner_id
							LEFT JOIN operators AS op ON op.operator_id = l.operator_id
							WHERE l.loco_id = ?";
				
				$row = $Database->fetchRow($query, $Loco->id);
				
				if (RP_DEBUG) {
					if ($row === false) {
						$site_debug[] = "Zend_DB: FAILED select loco ID " . $Loco->id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
					} else {
						$site_debug[] = "Zend_DB: SUCCESS select loco ID " . $Loco->id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
					}
				}
					
				$Memcached->save($Loco->mckey, $row, strtotime("+1 month")); 
			}
			
			return $row;
			
		}
		
		/**
		 * Prepare the locomotive object for updating
		 * @since Version 3.9.1
		 * @param \Railpage\Locos\Locomotive $Loco
		 * @return array
		 */
		
		public static function getSubmitData(Locomotive $Loco) {
			
			// Drop whitespace from loco numbers of all types except steam
			if (in_array($Loco->class_id, array(2, 3, 4, 5, 6)) || in_array($Loco->Class->type_id, array(2, 3, 4, 5, 6))) {
				$Loco->number = str_replace(" ", "", $Loco->number);
			}
			
			$data = array(
				"loco_num" => $Loco->number,
				"loco_gauge_id" => $Loco->gauge_id,
				"loco_status_id" => $Loco->status_id,
				"class_id" => $Loco->class_id,
				"owner_id" => $Loco->owner_id,
				"operator_id" => $Loco->operator_id,
				"entered_service" => $Loco->entered_service,
				"withdrawn" => $Loco->withdrawal_date,
				"builders_number" => $Loco->builders_num,
				"photo_id" => $Loco->photo_id,
				"manufacturer_id" => $Loco->manufacturer_id,
				"loco_name" => $Loco->name,
				"meta" => json_encode($Loco->meta),
				"asset_id" => $Loco->Asset instanceof \Railpage\Assets\Asset ? $Loco->Asset->id : 0
			);
			
			if (empty($Loco->date_added)) {
				$data['date_added'] = time(); 
			} else {
				$data['date_modified'] = time(); 
			}

			return $data;
			
		}
		
		/**
		 * Generate description: get dates
		 * @since Version 3.9.1
		 * @param \Railpage\Locos\Locomotive $Loco
		 * @param array $bits
		 * @return array
		 */
		
		public static function getDescriptionBits_Dates(Locomotive $Loco, $bits) {
			
			$dates = $Loco->loadDates();
			$dates = array_reverse($dates);
			
			foreach ($dates as $row) {
				$Date = new Date($row['date_id']);
				
				if (!isset($bits['inservice']) && $row['date_type_id'] == 1) {
					$bits['inservice'] = sprintf("%s entered service %s. ", $Loco->number, $Date->Date->format("F j, Y"));
				}
				
				if ($row['date_type_id'] == 7) {
					$bits[] = sprintf("On %s, it was withdrawn for preservation. ", $Date->Date->format("F j, Y"));
				}
			}
			
			return $bits;
			
		}
		
		/**
		 * Generate description: get manufacturer
		 * @since Version 3.9.1
		 * @param \Railpage\Locos\Locomotive $Loco
		 * @param array $bits
		 * @return array
		 */
		
		public static function getDescriptionBits_Manufacturer(Locomotive $Loco, $bits) {
			
			$bits[] = "Built ";
			
			if (!empty($Loco->builders_num)) {
				$bits[] = sprintf("as %s ", $Loco->builders_num); 
			}
			
			$bits[] = sprintf("by %s, ", (string) $Loco->getManufacturer());
			
			return $bits;
			
		}
		
		/**
		 * Generate description: get status
		 * @since Version 3.9.1
		 * @param \Railpage\Locos\Locomotive $Loco
		 * @param array $bits
		 * @return array
		 */
		
		public static function getDescriptionBits_Status(Locomotive $Loco, $bits) {
			
			switch ($Loco->status_id) {
				case 4: // Preserved - static
					$bits[] = sprintf("\n%s is preserved statically", $Loco->number); 
					break;
					
				case 5: // Preserved - operational
					$bits[] = sprintf("\n%s is preserved in operational condition", $Loco->number);
					
					// Get the latest operator
					if (!empty($Loco->operator)) {
						$bits[] = sprintf(" and can be seen on trains operated by %s", $Loco->operator);
					}
					
					break;
				
				case 9: // Under restoration
					$bits[] = sprintf("\n%s is currently under restoration.", $Loco->number);
					break;
			}
			
			return $bits;
			
		}
	}