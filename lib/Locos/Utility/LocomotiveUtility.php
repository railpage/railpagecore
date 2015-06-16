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
	use Railpage\Debug;
	use Railpage\Assets\Asset;
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
				
				$timer = Debug::getTimer(); 
				
				$query = "SELECT l.*, s.name AS loco_status, ow.operator_name AS owner_name, op.operator_name AS operator_name
							FROM loco_unit AS l
							LEFT JOIN loco_status AS s ON l.loco_status_id = s.id
							LEFT JOIN operators AS ow ON ow.operator_id = l.owner_id
							LEFT JOIN operators AS op ON op.operator_id = l.operator_id
							WHERE l.loco_id = ?";
				
				$row = $Database->fetchRow($query, $Loco->id);
				
				Debug::logEvent("Zend_DB: Fetch loco ID", $timer); 
					
				$Memcached->save($Loco->mckey, $row, strtotime("+1 year")); 
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
			if (in_array($Loco->class_id, array(2, 3, 4, 5, 6)) || 
					($Loco->Class instanceof LocoClass && in_array($Loco->Class->type_id, array(2, 3, 4, 5, 6))) || 
					($Loco->class instanceof LocoClass && in_array($Loco->class->type_id, array(2, 3, 4, 5, 6)))) {
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
				"asset_id" => $Loco->Asset instanceof Asset ? $Loco->Asset->id : 0
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
			
			$inservice = NULL;
			
			foreach ($dates as $row) {
				$Date = new Date($row['date_id']);
				
				if (!isset($bits['inservice']) && $row['date_type_id'] == 1) {
					$bits['inservice'] = sprintf("%s entered service %s. ", $Loco->number, $Date->Date->format("F j, Y"));
					if (is_null($inservice)) {
						$inservice = $Date->Date;
					}
				}
				
				if ($row['date_type_id'] == 7) {
					$bits[] = sprintf("On %s, it was withdrawn for preservation. ", $Date->Date->format("F j, Y"));
				}
				
				if ($row['date_type_id'] == 5) {
					$bits[] = sprintf("It was scrapped on %s", $Date->Date->format("F j, Y"));
					
					if (!is_null($inservice)) {
						$age = $inservice->diff($Date->Date)->format("%Y year");
						
						if ($inservice->diff($Date->Date)->format("%Y") > 1) {
							$age .= "s";
						}
						
						if ($inservice->diff($Date->Date)->format("%M") > 0) {
							$age .= $inservice->diff($Date->Date)->format(" and %m month");
						
							if ($inservice->diff($Date->Date)->format("%m") > 1) {
								$age .= "s";
							}
						}
						
						$bits[] = sprintf(", %s after it entered service", $age);
					}
					
					$bits[] = ".";
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
		
		/**
		 * Get the ID of this locomotive from class and loco number
		 * @since Version 3.9.1
		 * @param string $class
		 * @param string $number
		 * @return void
		 */
		
		public static function getLocoId($class, $number) {
			
			$Memcached = AppCore::getMemcached(); 
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$timer = Debug::getTimer();
			
			if (!filter_var($class, FILTER_VALIDATE_INT) && is_string($class)) {
				// Assume Zend_DB
				$slug_mckey = sprintf("railpage:loco.id;fromslug=%s;v2", $class);
				
				if ($mcresult = $Memcached->fetch($slug_mckey)) {
					$class = $mcresult;
				} else {
					$class = $Database->fetchOne("SELECT id FROM loco_class WHERE slug = ?", $class); 
					
					$Memcached->save($slug_mckey, $class, strtotime("+1 year"));
				}
			}
			
			// We are searching by loco number - we need to find it first
			if (!$loco_id = $Memcached->fetch(sprintf("railpage:loco.id;fromclass=%s;fromnumber=%s", $class, $number))) {
				
				$params = array(
					$class,
					$number
				);
				
				$query = "SELECT loco_id FROM loco_unit WHERE class_id = ? AND loco_num = ?";
				
				if (preg_match("/_/", $number)) {
					$params[1] = str_replace("_", " ", $number);
				} else {
					if (strlen($number) === 5 && preg_match("/([a-zA-Z]{1})([0-9]{4})/", $number)) {
						$params[] = sprintf("%s %s", substr($number, 0, 2), substr($number, 2, 3));
						$query = "SELECT loco_id FROM loco_unit WHERE class_id = ? AND (loco_num = ? OR loco_num = ?)";
					}
				}
				
				$loco_id = $Database->fetchOne($query, $params);
				
				$Memcached->save(sprintf("railpage:loco.id;fromclass=%s;fromnumber=%s", $class, $number), $loco_id, strtotime("+1 year"));
			}
			
			Debug::logEvent(__METHOD__, $timer); 
			
			if ($loco_id = filter_var($loco_id, FILTER_VALIDATE_INT)) {
				return $loco_id;
			}
			
			return false;
		}
		
		/**
		 * Get the ID of the locomotive class slug
		 * @since Version 3.9.1
		 * @param string $slug
		 * @return int
		 */
		
		public static function getClassId($slug) {
			
			$Memcached = AppCore::getMemcached(); 
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$timer = Debug::getTimer();
				
			$slugkey = sprintf("railpage:locos.class.id;fromslug=%s", $slug);
			
			if (!$id = $Memcached->fetch($slugkey)) {
				$id = $Database->fetchOne("SELECT id FROM loco_class WHERE slug = ?", $slug);
				
				$Memcached->save($slugkey, $id, strtotime("+1 year"));
			}
			
			Debug::logEvent(__METHOD__, $timer); 
			
			return $id;
			
		}
		
		/**
		 * Get liveries for a locomotive
		 * @since Version 3.9.1
		 * @return array
		 * @param \Railpage\Locos\Locomotive|int $Loco
		 */
		
		public static function getLiveriesForLocomotive($Loco) {
			
			if ($Loco instanceof Locomotive) {
				$Loco = $Loco->id;
			}
			
			if (!filter_var($Loco, FILTER_VALIDATE_INT)) {
				throw new InvalidArgumentException("No instance of Railpage\\Locos\\Locomotive or a valid loco ID were found"); 
			}
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$query = "SELECT DISTINCT l.livery_id, l.livery AS name, l.photo_id AS livery_photo_id
						FROM loco_livery AS l
						LEFT JOIN image_link AS il ON il.namespace_key = l.livery_id
						WHERE il.namespace = 'railpage.locos.liveries.livery'
						AND il.image_id IN (
							SELECT image_id FROM image_link WHERE namespace = 'railpage.locos.loco' AND namespace_key = ?
						)
						ORDER BY l.livery";
			
			$return = array(); 
			
			foreach ($Database->fetchAll($query, $Loco) as $row) {
				$return[$row['livery_id']] = array(
					"id" => $row['livery_id'],
					"name" => $row['name'],
					"photo" => array(
						"id" => $row['livery_photo_id'],
						"provider" => "flickr"
					)
				);
			}
			
			return $return;
		}
		
		/**
		 * Get liveries for a locomotive class
		 * @since Version 3.9.1
		 * @return array
		 * @param \Railpage\Locos\LocoClass|int $LocoClass
		 */
		
		public static function getLiveriesForLocomotiveClass($LocoClass) {
			
			if ($LocoClass instanceof LocoClass) {
				$LocoClass = $LocoClass->id;
			}
			
			if (!filter_var($LocoClass, FILTER_VALIDATE_INT)) {
				throw new InvalidArgumentException("No instance of Railpage\\Locos\\LocoClass or a valid class ID were found"); 
			}
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$query = "SELECT DISTINCT l.livery_id, l.livery AS name, l.photo_id AS livery_photo_id
						FROM loco_livery AS l
						LEFT JOIN image_link AS il ON il.namespace_key = l.livery_id
						WHERE il.namespace = 'railpage.locos.liveries.livery'
						AND il.image_id IN (
							SELECT image_id FROM image_link WHERE namespace = 'railpage.locos.class' AND namespace_key = ?
						)
						ORDER BY l.livery";
			
			$return = array(); 
			
			foreach ($Database->fetchAll($query, $LocoClass) as $row) {
				$return[$row['livery_id']] = array(
					"id" => $row['livery_id'],
					"name" => $row['name'],
					"photo" => array(
						"id" => $row['livery_photo_id'],
						"provider" => "flickr"
					)
				);
			}
			
			return $return;
		}
		
		/**
		 * Get locomotives from a given livery
		 * @since Version 3.9.1
		 * @return array
		 * @param \Railpage\Locos\Liveries\Livery|int $Livery
		 */
		
		public static function getLocosFromLivery($Livery) {
			
			if ($Livery instanceof Livery) {
				$Livery = $Livery->id;
			}
			
			if (!filter_var($Livery, FILTER_VALIDATE_INT)) {
				throw new InvalidArgumentException("No instance of Railpage\\Locos\\Liveries\\Livery or a valid livery ID were found"); 
			}
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$query = "SELECT DISTINCT l.loco_id, l.loco_num AS number
						FROM loco_unit AS l
						LEFT JOIN image_link AS il ON il.namespace_key = l.loco_id
						WHERE il.namespace = 'railpage.locos.loco'
						AND il.image_id IN (
							SELECT image_id FROM image_link WHERE namespace = 'railpage.locos.liveries.livery' AND namespace_key = ?
						)
						ORDER BY l.loco_num";
			
			$return = array(); 
			
			foreach ($Database->fetchAll($query, $Livery) as $row) {
				$return[$row['loco_id']] = array(
					"id" => $row['loco_id'],
					"name" => $row['number']
				);
			}
			
			return $return;
		}

	}