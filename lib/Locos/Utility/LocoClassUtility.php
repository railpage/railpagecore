<?php
	/**
	 * Utility class for LocoClass
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos\Utility;
	
	use Exception;
	use InvalidArgumentException;
	use DateTime;
	use Railpage\AppCore;
	use Railpage\Debug;
	use Railpage\Locos\Locomotive;
	use Railpage\Locos\LocoClass;
	use Railpage\Locos\Factory as LocosFactory;
	
	class LocoClassUtility {
		
		/**
		 * Update last owner and operator for the fleet
		 * @since Version 3.9.1
		 * @return void
		 * @param \Railpage\Locos\LocoClass $Class
		 */
		
		public static function updateFleet_OwnerOperator(LocoClass $Class) {
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$members = $Class->members(); 
			
			foreach ($members['locos'] as $row) {
				
				$Loco = LocosFactory::CreateLocomotive($row['loco_id']); 
				
				$query = "(SELECT operator_id, link_type FROM loco_org_link WHERE loco_id = ? AND link_type = 1 ORDER BY link_weight DESC LIMIT 0,1)
UNION ALL
(SELECT operator_id, link_type FROM loco_org_link WHERE loco_id = ? AND link_type = 2 ORDER BY link_weight DESC LIMIT 0,1)";
				
				$result = $Database->fetchAll($query, array($Loco->id, $Loco->id)); 
				
				#$commit = false;
				
				foreach ($result as $row) {
					
					#printArray($row['organisation_id']); printArray($Loco->owner_id); die;
					
					#if ($row['link_type_id'] == 1 && $row['organisation_id'] != $Loco->owner_id) {
						$Loco->owner_id = $row['operator_id']; 
						#$commit = true;
					#}
					
					#if ($row['link_type_id'] == 2 && $row['organisation_id'] != $Loco->operator_id) {
						$Loco->operator_id = $row['operator_id']; 
						#$commit = true;
					#}
				}
				
				#if ($commit) {
					$Loco->commit(); 
				#}
				
				#break;
				
			}
			
			$Class->flushMemcached(); 
			
			return;
			
		}
		
	}