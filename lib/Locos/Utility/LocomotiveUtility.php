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
		
	}