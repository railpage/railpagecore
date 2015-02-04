<?php
	/** 
	 * Loco database
	 * @since Version 3.2
	 * @version 3.8.7
	 * @author Michael Greenhill
	 * @package Railpage
	 */
	
	namespace Railpage\Locos;
	
	define("LOCOS_FIND_CLASS_NOPHOTO", "class_nophoto");
	define("LOCOS_FIND_CLASS_NOLOCOS", "class_nolocos");
	define("LOCOS_FIND_LOCO_NOPHOTO", "loco_nophoto");
	define("LOCOS_FIND_LOCO_NODATES", "loco_nodates"); 
	define("LOCOS_FIND_FROM_NUMBERS", "loco_findfromnumbers");
	
	define("RP_LOCO_RENUMBERED", 1); 
	define("RP_LOCO_REBUILT", 2);
	
	use Exception;
	use DateTime;
	use stdClass;
	use Railpage\AppCore;
	use Railpage\Module;
	use Zend_Db_Expr;
	
	require_once(__DIR__ . DIRECTORY_SEPARATOR . "functions.php");
	
	/**
	 * Base locos class
	 * @since Version 3.2
	 * @version 3.8.7
	 * @todo This will be used by the management class and the loco object class
	 */
	
	class Locos extends AppCore {
		
		/**
		 * Find : Locos classes with no photos
		 * @since Version 3.9
		 * @const FIND_CLASS_NOPHOTO
		 */
		
		const FIND_CLASS_NOPHOTO = "class_nophoto";
		
		/**
		 * Find : Locos with no photos
		 * @since Version 3.9
		 * @const FIND_LOCO_NOPHOTO
		 */
		
		const FIND_LOCO_NOPHOTO = "loco_nophoto";
		
		/**
		 * Status : Operational
		 * @since Version 3.8.7
		 * @const STATUS_OPERATIONAL
		 */
		
		const STATUS_OPERATIONAL = 1;
		
		/**
		 * Status : Scrapped
		 * @since Version 3.8.7
		 * @const STATUS_SCRAPPED
		 */
		
		const STATUS_SCRAPPED = 2;
		
		/**
		 * Status : Stored
		 * @since Version 3.8.7
		 * @const STATUS_STORED
		 */
		
		const STATUS_STORED = 3;
		
		/**
		 * Status : Preserved (static)
		 * @since Version 3.8.7
		 * @const STATUS_PRESERVED_STATIC
		 */
		
		const STATUS_PRESERVED_STATIC = 4;
		
		/**
		 * Status : Preserved (operational)
		 * @since Version 3.8.7
		 * @const STATUS_PRESERVED_OPERATIONAL
		 */
		
		const STATUS_PRESERVED_OPERATIONAL = 5;
		
		/**
		 * Status : Unknown
		 * @since Version 3.8.7
		 * @const STATUS_UNKNOWN
		 */
		
		const STATUS_UNKNOWN = 6;
		
		/**
		 * Status : Rebuilt
		 * @since Version 3.8.7
		 * @const STATUS_REBUILT
		 */
		
		const STATUS_REBUILT = 7;
		
		/**
		 * Status : Non-rail use
		 * @since Version 3.8.7
		 * @const STATUS_NONRAIL
		 */
		
		const STATUS_NONRAIL = 8;
		
		/**
		 * Status : Under restoration
		 * @since Version 3.8.7
		 * @const STATUS_RESTORATION
		 */
		
		const STATUS_RESTORATION = 9;
		
		/**
		 * Status : Under overhaul
		 * @since Version 3.8.7
		 * @const STATUS_OVERHAUL
		 */
		
		const STATUS_OVERHAUL = 10;
		
		/**
		 * Status : Re-numbered
		 * @since Version 3.8.7
		 * @const STATUS_RENUMBERED
		 */
		
		const STATUS_RENUMBERED = 11;
		
		/**
		 * Status : Sold overseas
		 * @since Version 3.8.7
		 * @const STATUS_OVERSEAS
		 */
		
		const STATUS_OVERSEAS = 12;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 */
		
		public function __construct() {
			parent::__construct(); 
			
			$this->Module = new Module("locos");
			$this->namespace = $this->Module->namespace;
			
			$this->Template = new stdClass;
			$this->Template->index = "index";
		}
		
		/**
		 * Find data for managers to fix
		 * @since Version 3.2
		 * @version 3.5
		 * @param string $search_type
		 * @param string $args
		 * @return array
		 */
		
		public function find($search_type = false, $args = false) {
			if (!$search_type) {
				throw new Exception("Cannot find data - no search type given");
				return false;
			}
			
			
			/**
			 * Find loco classes without a cover photo
			 */
			
			if ($search_type == LOCOS_FIND_CLASS_NOPHOTO || $search_type == self::FIND_CLASS_NOPHOTO) {
				
				$return = array(); 
				
				$classes = $this->listClasses();
				
				foreach ($classes['class'] as $row) {
					
					$LocoClass = new LocoClass($row['class_id']);
					
					if (!$LocoClass->hasCoverImage()) {
						$return[$LocoClass->id] = array(
							"id" => $LocoClass->id,
							"name" => $LocoClass->name,
							"flickr_tag" => $LocoClass->flickr_tag,
							"url" => $LocoClass->url->getURLs()
						);
					}
				}
				
				return $return;
				
				/*
				
				// Find classes without a photo
				
				$query = "SELECT id, name, flickr_tag FROM loco_class WHERE flickr_image_id = '' ORDER BY name ASC";
				
				if ($this->db instanceof \sql_db) {
					if ($rs = $this->db->query($query)) {
						$return = array(); 
						
						while ($row = $rs->fetch_assoc()) {
							$return[$row['id']] = $row;
						}
						
						return $return;
					} else {
						throw new Exception($this->db->error."\n".$query);
						return false;
					}
				} else {
					$return = array(); 
					
					foreach ($this->db->fetchAll($query) as $row) {
						$return[$row['id']] = $row;
					}
					
					return $return;
				}
				*/
			}
			
			/**
			 * Find locomotives without a cover photo
			 */
			
			if ($search_type == LOCOS_FIND_LOCO_NOPHOTO || $search_type == self::FIND_LOCO_NOPHOTO) {
				// Locos without a photo
				
				$query = "SELECT l.loco_id, l.loco_num, l.loco_status_id, s.name AS loco_status, c.id AS class_id, c.name AS class_name 
							FROM loco_unit AS l
							LEFT JOIN loco_class AS c ON l.class_id = c.id
							LEFT JOIN loco_status AS s ON s.id = l.loco_status_id
							WHERE l.photo_id = 0
							ORDER BY c.name, l.loco_num";
				
				if ($this->db instanceof \sql_db) {
					if ($rs = $this->db->query($query)) {
						$return = array(); 
						
						while ($row = $rs->fetch_assoc()) {
							$return[$row['loco_id']] = $row;
						}
						
						return $return;
					} else {
						throw new Exception($this->db->error."\n".$query);
						return false;
					}
				} else {
					$return = array(); 
					
					foreach ($this->db->fetchAll($query) as $row) {
						$return[$row['loco_id']] = $row; 
					}
					
					return $return;
				}
			}
			
			/**
			 * Find locomotives from a comma-separated list of numbers
			 */
			
			if ($search_type == LOCOS_FIND_FROM_NUMBERS && $args) {
				// Find locomotives from a comma-separated list of numbers
				
				$numbers = explode(",", $args); 
				foreach ($numbers as $id => $num) {
					$numbers[$id] = trim($num); 
				}
				
				$query = "SELECT l.loco_id, l.loco_num, l.loco_status_id, s.name AS loco_status, l.loco_gauge_id, CONCAT(g.gauge_name, ' (', g.gauge_metric, ')') AS loco_gauge, c.loco_type_id, t.title AS loco_type, c.id AS class_id, c.name AS class_name 
							FROM loco_unit AS l
							LEFT JOIN loco_class AS c ON l.class_id = c.id
							LEFT JOIN loco_status AS s ON s.id = l.loco_status_id
							LEFT JOIN loco_gauge AS g ON g.gauge_id = l.loco_gauge_id
							LEFT JOIN loco_type AS t ON c.loco_type_id = t.id
							WHERE l.loco_num IN ('".implode("','", $numbers)."')
							ORDER BY l.loco_num, c.name";
				
				$return = array(); 
				
				foreach ($this->db->fetchAll($query) as $row) {
					$return[$row['loco_id']] = $row; 
				}
				
				return $return;
			}
		}
		
		/**
		 * List classes
		 * @since Version 3.2
		 * @version 3.5
		 * @return array
		 * @param array $types
		 */
		
		public function listClasses($types = false) {
			$params = array(); 
			$return = array(); 
			
			if ($types === false) {
				$mckey = "railpage:loco.class.bytype=all";
			} else {
				if (is_array($types)) {
					$suff = implode(",", $types); 
				} else {
					$suff = $types;
				}
				
				$mckey = "railpage:loco.class.bytype=" . $suff;
			}
			
			#deleteMemcacheObject($mckey);
			
			if ($return = $this->getCache($mckey)) {
				
				$update = false;
				
				foreach ($return['class'] as $id => $row) {
					if (!isset($row['class_url'])) {
						$return['class'][$id]['class_url'] = $this->makeClassURL($row['slug']);
						$update = true;
					}
				}
				
				if ($update) {
					$this->setCache($mckey, $return, strtotime("+1 week"));
				}
				
				return $return;
			} else {
				if ($types === false) {
					$motive_power_types = ""; 
				} elseif (is_array($types)) {
					$motive_power_types = " WHERE c.loco_type_id IN (".implode(",", $types).")";
				} elseif (is_int($types)) {
					if ($this->db instanceof \sql_db) {
						$motive_power_types = " WHERE c.loco_type_id IN (".$this->db->real_escape_string($types).")";
					} else {
						$motive_power_types = " WHERE c.loco_type_id IN (?)";
						$params[] = $types;
					}
				}
				
				$query = "SELECT c.parent AS parent_class_id, c.source_id AS source, c.id AS class_id, c.flickr_tag, c.slug, c.flickr_image_id, c.introduced AS class_introduced, c.name AS class_name, c.desc AS class_desc, c.manufacturer_id AS class_manufacturer_id, m.manufacturer_name AS class_manufacturer, w.arrangement AS wheel_arrangement, w.id AS wheel_arrangement_id, t.title AS loco_type, c.loco_type_id AS loco_type_id, t.slug AS loco_type_slug
							FROM loco_class AS c
							LEFT JOIN loco_type AS t ON c.loco_type_id = t.id
							LEFT JOIN wheel_arrangements AS w ON c.wheel_arrangement_id = w.id
							LEFT JOIN loco_manufacturer AS m ON m.manufacturer_id = c.manufacturer_id
							".$motive_power_types."
							ORDER BY c.name";
				
				$return['stat'] = "ok";
				$return['count'] = 0;
				
				foreach ($this->db->fetchAll($query, $params) as $row) {
					$return['count']++; 
					$row['class_url'] = $this->makeClassUrl($row['slug']);
					$return['class'][$row['class_id']] = $row;
				}
				
				$this->setCache($mckey, $return, strtotime("+1 week"));
				
				return $return;
			}
		}
		
		/**
		 * List wheel arrangements
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function listWheelArrangements() {
			$query = "SELECT * FROM wheel_arrangements ORDER BY arrangement";
			$return = array();
			
			$mckey = "railpage:loco.wheelarrangements"; 
			
			if ($return = $this->getCache($mckey)) {
				return $return;
			} else {
				$return['stat'] = "ok"; 
				$return['count'] = 0;
				
				foreach ($this->db->fetchAll($query) as $row) {
					$return['count']++;
					$return['wheels'][$row['id']] = $row;
				}
				
				$this->setCache($mckey, $return, strtotime("+1 month")); 
				
				return $return;
			}
		}
		
		/**
		 * List manufacturers
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function listManufacturers() {
			$query = "SELECT * FROM loco_manufacturer ORDER BY manufacturer_name";
			$return = array();
			
			$mckey = "railpage:loco.manufacturers"; 
			
			if ($return = getMemcacheObject($mckey)) {
				return $return;
			} else {
				$return['stat'] = "ok"; 
				$return['count'] = 0;
				
				foreach ($this->db->fetchAll($query) as $row) {
					$return['count']++;
					$return['manufacturers'][$row['manufacturer_id']] = $row;
				}
				
				$this->setCache($mckey, $return, strtotime("+1 month")); 
				
				return $return;
			}
		}
		
		/**
		 * Loco classes by builder
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function classByManufacturer() {
			$classes = $this->listClasses();
			
			$return = array();
			
			if ($classes['stat'] == "ok") {
				$return['stat'] = "ok";
				
				foreach ($classes['class'] as $id => $data) {
					$return['manufacturer'][$data['class_manufacturer_id']][$id] = $data;
				}
				
				ksort($return['manufacturer']);
			} else {
				$return = $classes;
			}
			
			return $return;
		}
		
		/**
		 * Loco classes by builder
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function classByWheelset() {
			$classes = $this->listClasses();
			$return = array();
			
			if ($classes['stat'] == "ok") {
				$return['stat'] = "ok";
				
				foreach ($classes['class'] as $id => $data) {
					$return['wheels'][$data['wheel_arrangement_id']][$id] = $data;
				}
				
				ksort($return['wheels']);
			} else {
				$return = $classes;
			}
			
			return $return;
		}
		
		/**
		 * Loco classes by traction type
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function classByType() {
			$classes = $this->listClasses();
			$return = array();
			
			if ($classes['stat'] == "ok") {
				$return['stat'] = "ok";
				
				foreach ($classes['class'] as $id => $data) {
					$return['type'][$data['loco_type_id']][$id] = $data;
				}
				
				ksort($return['type']);
			} else {
				$return = $classes;
			}
			
			return $return;
		}
		
		/**
		 * Locos by operator
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $operator_id
		 * @return array
		 */
		
		public function locosByOperator($operator_id = false) {
			if (!$operator_id) {
				return false;
			}
			
			$query = "SELECT l.loco_id, l.loco_num, l.loco_gauge, l.loco_status_id AS status_id, s.name AS status_name, l.class_id AS class_id, c.slug AS class_slug, c.name AS class_name, c.flickr_tag AS class_flickr_tag, l.owner_id, o.operator_name AS owner_name, c.wheel_arrangement_id, w.arrangement AS wheel_arrangement, c.loco_type_id, t.title AS loco_type
						FROM loco_unit AS l 
						LEFT JOIN loco_org_link AS lo ON lo.loco_id = l.loco_id
						LEFT JOIN loco_status AS s ON s.id = l.loco_status_id
						LEFT JOIN loco_class AS c ON l.class_id = c.id
						LEFT JOIN operators AS o ON o.operator_id = lo.operator_id
						LEFT JOIN wheel_arrangements AS w ON c.wheel_arrangement_id = w.id
						LEFT JOIN loco_type AS t ON c.loco_type_id = t.id
						WHERE lo.operator_id = ?
						AND lo.link_type = 2
						ORDER BY c.name, l.loco_num";
			
			$return = array("stat" => "ok", "count" => 0, "locos" => array()); 
			
			foreach ($this->db->fetchAll($query, $operator_id) as $row) {
				if (!empty($row['class_flickr_tag'])) {
					$row['flickr_tag'] = $row['class_flickr_tag']."-".$row['loco_num'];
				}
				
				$row['loco_url'] = $this->makeLocoURL($row['class_slug'], $row['loco_num']);
				
				$return['locos'][$row['loco_id']] = $row;
				$return['count']++; 
			}
			
			return $return;
		}
		
		/**
		 * Locos by owner
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $owner_id
		 * @return array
		 */
		
		public function locosByOwner($owner_id = false) {
			if (!$owner_id) {
				return false;
			}
			
			$query = "SELECT l.loco_id, l.loco_num, l.loco_gauge, l.loco_status_id AS status_id, s.name AS status_name, l.class_id AS class_id, c.slug AS class_slug, c.name AS class_name, c.flickr_tag AS class_flickr_tag, l.operator_id, o.operator_name AS operator_name, c.wheel_arrangement_id, w.arrangement AS wheel_arrangement, c.loco_type_id, t.title AS loco_type
						FROM loco_unit AS l 
						LEFT JOIN loco_org_link AS lo ON lo.loco_id = l.loco_id
						LEFT JOIN loco_status AS s ON s.id = l.loco_status_id
						LEFT JOIN loco_class AS c ON l.class_id = c.id
						LEFT JOIN operators AS o ON o.operator_id = lo.operator_id
						LEFT JOIN wheel_arrangements AS w ON c.wheel_arrangement_id = w.id
						LEFT JOIN loco_type AS t ON c.loco_type_id = t.id
						WHERE lo.operator_id = ?
						AND lo.link_type = 1
						ORDER BY c.name, l.loco_num";
			
			$return = array("stat" => "ok", "count" => 0, "locos" => array()); 
			
			foreach ($this->db->fetchAll($query, $owner_id) as $row) {
				if (!empty($row['class_flickr_tag'])) {
					$row['flickr_tag'] = $row['class_flickr_tag']."-".$row['loco_num'];
				
				}
				
				$row['loco_url'] = $this->makeLocoURL($row['class_slug'], $row['loco_num']);
				
				$return['locos'][$row['loco_id']] = $row;
				$return['count']++; 
			}
			
			return $return;
		}
		
		/**
		 * List loco types
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function listTypes() {
			$query = "SELECT * FROM loco_type ORDER BY title";
			$return = array(); 
			
			$return['stat'] = "ok"; 
			$return['count'] = 0; 
			
			foreach ($this->db->fetchAll($query) as $row) {
				$return['types'][$row['id']] = $row; 
				$return['count']++; 
			}
			
			return $return;
		}
		
		/**
		 * List loco status types
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function listStatus() {
			$query = "SELECT * FROM loco_status ORDER BY name";
			$return = array(); 
			
			$return['stat'] = "ok"; 
			$return['count'] = 0; 
			
			foreach ($this->db->fetchAll($query) as $row) {
				$return['status'][$row['id']] = $row; 
				$return['count']++; 
			}
			
			return $return;
		}
		
		/**
		 * List years and the classes in each year
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function listyears() {
			$classes = $this->listClasses();
			$return = array(); 
			
			if ($classes['stat'] == "ok") {
				$return['stat'] = "ok";
				
				foreach ($classes['class'] as $id => $data) {
					$data['loco_type_url'] = sprintf("%s/type/%s", $this->Module->url, $data['loco_type_slug']);

					$return['years'][$data['class_introduced']][$id] = $data;
				}
				
				ksort($return['years']);
			} else {
				$return = $classes;
			}
			
			return $return;
		}
		
		/**
		 * List operators
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function listOperators() {
			$query = "SELECT * FROM operators ORDER BY operator_name";
			$return = array(); 
			
			$return['stat'] = "ok"; 
			$return['count'] = 0; 
			
			foreach ($this->db->fetchAll($query) as $row) {
				$return['operators'][$row['operator_id']] = $row;
				$return['count']++; 
			}
			
			return $return;
		}
		
		/**
		 * Add a builder
		 * @since Version 3.2
		 * @version 3.2
		 * @param string $builder_name
		 * @param string $builder_desc
		 * @return boolean
		 */
		
		public function addBuilder($builder_name = false, $builder_desc = false) {
			if (!$builder_name) {
				throw new Exception("Cannot add builder - name required");
				return false;
			}
			
			deleteMemcacheObject("railpage:loco.manufacturers"); 
			
			$data = array(
				"manufacturer_name" => $builder_name
			);
			
			if ($builder_desc) {
				$data['manufacturer_desc'] = $builder_desc;
			}
			
			$this->db->insert("loco_manufacturer", $data); 
			return true;
		}
		
		/**
		 * Add an operator
		 * @since Version 3.2
		 * @version 3.2
		 * @param string $operator_name
		 * @param string $operator_desc
		 * @return boolean
		 */
		
		public function addOperator($operator_name = false, $operator_desc = false) {
			if (!$operator_name) {
				throw new Exception("Cannot add operator - name required");
				return false;
			} 
			
			$data = array(
				"operator_name" => $operator_name
			);
			
			if (!empty($operator_desc)) {
				$data['operator_desc'] = $operator_desc;
			}
			
			$this->db->insert("operators", $data); 
			return true;
		}
		
		/**
		 * Edit note
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $note_id
		 * @param string $note_text
		 * @return boolean
		 */
		
		public function editNote($note_id = false, $note_text = false) {
			if (!$note_id || empty($note_id) || !$note_text || empty($note_text)) {
				return false;
			} 
			
			$data = array(
				"note_text" => $note_text
			); 
			
			$where = array(
				"note_id = ?" => $note_id
			);
			
			$this->db->update("loco_notes", $data, $where); 
			return true;
		}
		
		/**
		 * Delete note
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $note_id
		 * @return boolean
		 */
		
		public function deleteNote($note_id = false) {
			if (!$note_id || empty($note_id)) {
				return false;
			} 
			
			$where = array(
				"note_id = ?" => $note_id
			);
			
			$this->db->delete("loco_notes", $where); 
			return true;
		}
		
		/**
		 * Latest changes
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 * @param int $cutoff
		 */
		
		public function latestChanges($cutoff = 25) {
			throw new Exception(__CLASS__ . "::" . __METHOD__ . " is deprecated");
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT ll.*, u.username FROM log_locos AS ll LEFT JOIN nuke_users AS u ON u.user_id = ll.user_id ORDER BY ll.timestamp DESC LIMIT 0, ".$this->db->real_escape_string($cutoff);
				
				if ($rs = $this->db->query($query)) {
					$changes 	= array(); 
					$locos 		= array(); 
					$classes 	= array();
					
					while ($row = $rs->fetch_assoc()) {
						$row['timestamp'] = \DateTime::createFromFormat("Y-m-d H:i:s", $row['timestamp']); 
						$row['args'] = json_decode($row['args'], true);
						
						if (!empty($row['loco_id'])) {
							$locos[$row['loco_id']] = array(); 
						}
						
						if (!empty($row['class_id'])) {
							$classes[$row['class_id']] = array(); 
						}
						
						$changes[] = $row;
					}
					
					if (count($locos)) {
						$query = "SELECT l.loco_id, l.loco_num, l.class_id, c.name FROM loco_unit AS l LEFT JOIN loco_class AS c ON l.class_id = c.id WHERE l.loco_id IN (".implode(",", array_keys($locos)).")"; 
						
						if ($rs = $this->db->query($query)) {
							while ($row = $rs->fetch_assoc()) {
								foreach ($changes as $id => $data) {
									if ($data['loco_id'] == $row['loco_id']) {
										$changes[$id]['loco_data'] = $row; 
									}
								}
							}
						} else {
							throw new Exception($this->db->error); 
						}
					}
					
					if (count($classes)) {
						$query = "SELECT id AS class_id, name FROM loco_class WHERE id IN (".implode(",", array_keys($classes)).")"; 
						
						if ($rs = $this->db->query($query)) {
							while ($row = $rs->fetch_assoc()) {
								foreach ($changes as $id => $data) {
									if ($data['class_id'] == $row['class_id']) {
										$changes[$id]['class_data'] = $row; 
									}
								}
							}
						} else {
							throw new Exception($this->db->error); 
						}
					}
					
					return $changes;
				}
				
				
				$changes = array(); 
				
				// Loco notes
				$query = "SELECT n.*, l.loco_num, lc.name, l.class_id, n.user_id, u.username 
							FROM loco_notes AS n
							LEFT JOIN loco_unit AS l ON n.loco_id = l.loco_id
							LEFT JOIN loco_class AS lc ON l.class_id = lc.id
							LEFT JOIN nuke_users AS u ON n.user_id = u.user_id
							ORDER BY n.note_date DESC";
							
				if ($rs = $this->db->query($query)) {
					while ($row = $rs->fetch_assoc()) {
						$i = count($changes[$row['note_date']]);
						$changes[$row['note_date']][$i]['date_nice'] = date("F j, Y, g:i a", $row['note_date']); 
						$changes[$row['note_date']][$i]['plain'] = "A note was added to ".$row['loco_num']." of class ".$row['name']." by ".$row['username']; 
						$changes[$row['note_date']][$i]['html']	 = "A note was added to <a href='/locos/class/".$row['class_id']."/".$row['loco_num']."/'>".$row['loco_num']."</a> of class <a href='/locos/class/".$row['class_id']."/'>".$row['name']."</a> by <a href='/user/".$row['user_id']."/'>".$row['username']."</a>"; 
					}
				} else {
					throw new Exception($this->db->error); 
				}
				
				// Loco unit additions
				$query = "SELECT l.date_added, l.loco_num, lc.name, l.class_id 
							FROM loco_unit AS l 
							LEFT JOIN loco_class AS lc ON l.class_id = lc.id
							WHERE l.date_added != 0
							ORDER BY l.date_added DESC";
							
				if ($rs = $this->db->query($query)) {
					while ($row = $rs->fetch_assoc()) {
						$i = count($changes[$row['date_added']]);
						$changes[$row['date_added']][$i]['date_nice'] = date("F j, Y, g:i a", $row['date_added']); 
						$changes[$row['date_added']][$i]['plain'] = $row['loco_num']." was added to class ".$row['name']; 
						$changes[$row['date_added']][$i]['html'] = "<a href='/locos/class/".$row['class_id']."/".$row['loco_num']."/'>".$row['loco_num']."</a> was added to class <a href='/locos/class/".$row['class_id']."/'>".$row['name']."</a>"; 
					}
				} else {
					throw new Exception($this->db->error); 
				}
				
				// Loco unit changes
				$query = "SELECT l.date_modified, l.loco_num, lc.name, l.class_id 
							FROM loco_unit AS l 
							LEFT JOIN loco_class AS lc ON l.class_id = lc.id
							WHERE l.date_modified != 0
							ORDER BY l.date_modified DESC";
							
				if ($rs = $this->db->query($query)) {
					while ($row = $rs->fetch_assoc()) {
						$i = count($changes[$row['date_modified']]);
						$changes[$row['date_modified']][$i]['date_nice'] = date("F j, Y, g:i a", $row['date_modified']); 
						$changes[$row['date_modified']][$i]['plain'] = "The data for ".$row['loco_num']." in class ".$row['name']." was modified"; 
						$changes[$row['date_modified']][$i]['html'] = "The data for <a href='/locos/class/".$row['class_id']."/".$row['loco_num']."/'>".$row['loco_num']."</a> in class <a href='/locos/class/".$row['class_id']."/'>".$row['name']."</a> was modified"; 
					}
				} else {
					throw new Exception($this->db->error); 
				}
				
				// Class additions
				$query = "SELECT name, date_added, id AS class_id
							FROM loco_class
							WHERE date_added != 0 
							ORDER BY date_added DESC";
							
				if ($rs = $this->db->query($query)) {
					while ($row = $rs->fetch_assoc()) {
						$i = count($changes[$row['date_added']]);
						$changes[$row['date_added']][$i]['date_nice'] = date("F j, Y, g:i a", $row['date_added']); 
						$changes[$row['date_added']][$i]['plain'] = "Class ".$row['name']." has been created"; 
						$changes[$row['date_added']][$i]['html'] = "Class <a href='/locos/class/".$row['class_id']."/'>".$row['name']."</a> has been created";
					}
				} else {
					throw new Exception($this->db->error); 
				}
				
				// Class changes
				$query = "SELECT name, date_modified, id AS class_id
							FROM loco_class
							WHERE date_modified != 0 
							ORDER BY date_modified DESC";
							
				if ($rs = $this->db->query($query)) {
					while ($row = $rs->fetch_assoc()) {
						$i = count($changes[$row['date_modified']]);
						$changes[$row['date_modified']][$i]['date_nice'] = date("F j, Y, g:i a", $row['date_modified']); 
						$changes[$row['date_modified']][$i]['plain'] = "Class ".$row['name']." has been modified"; 
						$changes[$row['date_modified']][$i]['html'] = "Class <a href='/locos/class/".$row['class_id']."/'>".$row['name']."</a> has been modified";
					}
				} else {
					throw new Exception($this->db->error); 
				}
				
				krsort($changes);
				
				if ($cutoff) {
					$changes = array_slice($changes, 0, $cutoff); 
				}
				
				return $changes;
			} else {
				// Is this still required...?
			}
		}
		
		/** 
		 * List all locos
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function listAllLocos() {
			$query = "SELECT * FROM loco_unit ORDER BY loco_id DESC";
			
			$return = array(); 
			
			$return['stat'] = "ok";
			
			foreach ($this->db->fetchAll($query) as $row) {
				$return['locos'][$row['loco_id']] = $row; 
			}
			
			return $return;
		}
		
		/**
		 * Get the type of dates
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function dateTypes() {
			$query = "SELECT * FROM loco_date_type ORDER BY loco_date_text ASC";
			$return = array();
			
			foreach ($this->db->fetchAll($query) as $row) {
				$return[$row['loco_date_id']] = $row['loco_date_text']; 
			}
			
			return $return;
		}
		
		/**
		 * Load a single date entry
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $date_id
		 * @return mixed
		 */
		
		public function loadDate($date_id = false) {
			if (!$date_id) {
				throw new Exception("Cannot load date - no date ID provided"); 
				return false;
			}
			
			$query = "SELECT * FROM loco_unit_date WHERE date_id = ?"; 
			
			return $this->db->fetchRow($query, $date_id);
		}
		
		/**
		 * Edit a date
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $date_id
		 * @param int $date_type_id
		 * @param int $date
		 * @param string $text
		 * @return boolean
		 */
		
		public function editDate($date_id = false, $date_type_id = false, $date = false, $text) {
			if (!$date_id || !$date_type_id || !$date) {
				throw new Exception("Cannot edit date - missing required fields"); 
				return false;
			}
			
			$data = array(
				"loco_date_id" => $date_type_id,
				"date" => $date
			);
			
			if (!empty($text)) {
				$data['text'] = $text;
			}
			
			$where = array(
				"date_id = ?" => $date_id
			);
			
			$this->db->update("loco_unit_date", $data, $where); 
			
			return true;
		}
		
		/**
		 * Delete a date
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $date_id
		 * @return boolean
		 */
		
		public function deleteDate($date_id = false) {
			if (!$date_id || empty($date_id)) {
				throw new Exception("Cannot delete date - no date ID provided"); 
				return false;
			}
			
			$where = array(
				"date_id = ?" => $date_id
			);
			
			$this->db->delete("loco_unit_date", $where);
			return true;
		}
		
		/**
		 * Get a random photo 
		 * @since Version 3.2
		 * @return string
		 */
		
		public function randomPhoto() {
			$query = "SELECT photo_id FROM loco_unit WHERE photo_id > 0";
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query) as $row) {
				$return[] = $row['photo_id']; 
			}
			
			return $return;
		}
		
		/**
		 * Find locos, classes from a tag or array of tags
		 * @since Version 3.2
		 * @param string|array $tags
		 * @return array
		 */
		
		public function findFromTag($tags) {
			if (!is_array($tags)) {
				$tags = array($tags);
			}
			
			$craptags = array("rp3");
			
			foreach ($tags as $id => $tag) {
				if (in_array($tag, $craptags)) {
					unset($tags[$id]); 
				}
				
				if (preg_match("@railpage\:class\=([0-9]+)@", $tag, $matches)) {
					$classes[] = $matches[1];
				}
				
				if (preg_match("@railpage\:loco\=([a-zA-Z0-9\s\-]+)@", $tag, $matches)) {
					$locos[] = $matches[1];
				}
			}
			
			$return = array(); 
			$liveries = array(); 
			
			// Load class tags from the database
			$query = "SELECT REPLACE(flickr_tag, '-', '') AS flickr_tag, name, id FROM loco_class WHERE flickr_tag != ''";

			$liveries = $this->db->fetchAll($query);
			
			foreach ($liveries as $row) {
				if (@in_array($row['flickr_tag'], $tags) || @in_array($row['id'], $classes)) {
					// Back to lowercase, flickr is silly
					$row['flickr_tag'] = strtolower($row['flickr_tag']);
					foreach ($tags as $tag) {
						if (isset($locos) && is_array($locos) && count($locos) > 0) {
							if ($tag != $row['flickr_tag'] && stristr($tag, $row['flickr_tag']) && !@in_array($tag, $row['locos'])) {
								$row['locos'][] = strtoupper(str_replace($row['flickr_tag'], "", $tag));
							}
						}
					}
					
					if (isset($row['locos']) && is_array($row['locos'])) {
						natsort($row['locos']);
					}
					
					$return[$row['id']] = $row; 
				}
			}
			
			return $return;
		}
		
		/**
		 * List all liveries
		 * @since Version 3.2
		 * @author Michael Greenhill
		 * @return array
		 */
		
		public function listLiveries() {
			$query = "SELECT * FROM loco_livery ORDER BY livery";
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query) as $row) {
				$return[$row['livery_id']] = $row['livery']; 
			}
			
			return $return;
		}
		
		/**
		 * Get the ID from the livery name
		 * @since Version 3.2
		 * @author Michael Greenhill
		 * @param string $livery
		 */
		
		public function liveryID($livery = false) {
			if (!$livery || empty($livery)) {
				return false;
			}
			
			$query = "SELECT livery_id FROM loco_livery WHERE livery = ?";
			
			$result = $this->db->fetchOne($query, $livery); 
			
			if (count($result) === 0) {
				$data = array(
					"livery" => $livery
				);
				
				$this->db->insert("loco_livery", $data); 
				return $this->db->lastInsertId(); 
			} else {
				return $result['livery_id']; 
			}
		}
		
		/**
		 * Link loco A to loco B using relationship C
		 * @since Version 3.2
		 * @param \Railpage\Locos\Locomotive $LocoA
		 * @param \Railpage\Locos\Locomotive $LocoB
		 * @param int $link_type_id
		 * @return boolean
		 * @throws \Exception if $LocoA is not an instance of \Railpage\Locos\Locomotive
		 * @throws \Exception if $LocoB is not an instance of \Railpage\Locos\Locomotive
		 * @throws \Exception if $link_type_id is missing
		 */
		
		public function link(Locomotive $LocoA, Locomotive $LocoB, $link_type_id = false) {
			if (!$LocoA instanceof Locomotive) {
				throw new Exception("Cannot establish link between locomotives - Paramter 1 (Loco A) missing");
			}
			if (!$LocoB instanceof Locomotive) {
				throw new Exception("Cannot establish link between locomotives - Paramter 2 (Loco B) missing");
			}
			
			if ($link_type_id === false) {
				throw new Exception("Cannot establish link between locomotives - Parameter 3 (link type) missing");
			}
			
			$data = array(
				"loco_id_a" => $LocoA->id,
				"loco_id_b" => $LocoB->id,
				"link_type_id" => $link_type_id
			);
			
			$this->db->insert("loco_link", $data);
			return true;
		}
		
		/**
		 * Delete loco 
		 * @since Version 3.2
		 * @param int $id
		 * @return boolean
		 */
		
		public function deleteLink($id = false) {
			if (!$id) {
				throw new Exception("Cannot delete loco  - no ID given"); 
				return false;
			} 

			$where = array(
				"link_id = ?" => $id
			);
			
			$this->db->delete("loco_link", $where); 
			
			return true;
		}
		
		/**
		 * Get loco 
		 * @since Version 3.5
		 * @param int $id
		 * @return array
		 */
		
		public function getLink($id = false) {
			if (!$id) {
				throw new Exception("Cannot fetch loco  - no ID given"); 
				return false;
			}
			
			$query = "SELECT * FROM loco_link WHERE link_id = ?";
			
			return $this->db->fetchRow($query, $id); 
		}
		
		/**
		 * Get suggested corrections
		 * @since Version 3.2
		 * @return array
		 * @param boolean $active
		 */
		
		public function corrections($active = true) {
			if ($active) {
				$active_sql = " WHERE c.status = 0 ";
			} else {
				$active_sql = " WHERE c.status != 0 ";
			}
			
			$query = "SELECT c.*, l.loco_num, lc.name AS class_name, lc.id AS class_id, u.username, u.user_avatar
						FROM loco_unit_corrections AS c
						LEFT JOIN loco_unit AS l ON c.loco_id = l.loco_id
						LEFT JOIN loco_class AS lc ON lc.id = l.class_id
						LEFT JOIN nuke_users AS u ON c.user_id = u.user_id
						".$active_sql."
						ORDER BY c.date DESC";
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query) as $row) {
				$return[$row['loco_id']][] = $row; 
			}
			
			return $return;
		}
		
		/**
		 * Close a suggested correction
		 * @since Version 3.3
		 * @param int $id
		 * @param int $user_id
		 * @return boolean
		 */
		
		public function closeCorrection($id = false, $user_id = false) {
			if (!$id) {
				throw new Exception("Cannot close correction - no ID given"); 
				return false;
			} 
			
			if (!$user_id) {
				throw new Exception("Cannot close correction - no user ID given"); 
				return false;
			}
			
			$data = array(
				"status" => 1,
				"resolved_by" => $user_id,
				"resolved_date" => new Zend_Db_Expr('NOW()')
			);
			
			$where = array(
				"correction_id = ?" => $id
			);
			
			$this->db->update("loco_unit_corrections", $data, $where);
			return true;
		}
		
		/**
		 * Ignore a suggested correction
		 * @since Version 3.3
		 * @param int $id
		 * @param int $user_id
		 * @return boolean
		 */
		
		public function ignoreCorrection($id = false, $user_id = false) {
			if (!$id) {
				throw new Exception("Cannot ignore correction - no ID given"); 
				return false;
			} 
			
			if (!$user_id) {
				throw new Exception("Cannot ignore correction - no user ID given"); 
				return false;
			}
			
			$data = array(
				"status" => 2,
				"resolved_by" => $user_id,
				"resolved_date" => new Zend_Db_Expr('NOW()')
			);
			
			$where = array(
				"correction_id = ?" => $id
			);
			
			$this->db->update("loco_unit_corrections", $data, $where);
			return true;
		}
		
		/**
		 * Get loco gauges
		 * @since Version 3.4
		 * @return array
		 */
		
		public function listGauges() {
			$query = "SELECT * FROM loco_gauge ORDER BY gauge_name, gauge_imperial";
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query) as $row) {
				$return[$row['gauge_id']] = $row; 
			}
			
			return $return;
		}
		
		/**
		 * Add wheel arrangement
		 * @since Version 3.4
		 * @return boolean
		 * @param string $title
		 * @param string $arrangement
		 */
		
		public function addWheelArrangement($title = "", $arrangement = false) {
			deleteMemcacheObject("railpage:loco.wheelarrangements");

			$data = array(
				"title" => $title, 
				"arrangement" => $arrangement
			);
			
			$this->db->insert("wheel_arrangements", $data);
			return true;
		}
		
		/**
		 * List all organisation  types
		 * @since Version 3.4
		 * @return array
		 */
		
		public function listOrgLinkTypes() {
			$query = "SELECT * FROM loco_org_link_type ORDER BY name";
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query) as $row) {
				$return[$row['id']] = $row; 
			}
			
			return $return;
		}
		
		/**
		 * Get latest owner of a locomotive
		 * @since Version 3.4
		 * @param int $loco_id
		 * @return array
		 */
		
		public function getLastOwner($loco_id = false) {
			if (!$loco_id) {
				throw new Exception("Could not get latest owner - no loco ID given"); 
				return false;
			}
			
			$query = "SELECT l.*, o.operator_name FROM loco_org_link AS l LEFT JOIN operators AS o ON l.operator_id = o.operator_id WHERE l.loco_id = ? AND l.link_type = 1 ORDER BY l.link_weight DESC LIMIT 1";
			
			return $this->db->fetchRow($query, $loco_id); 
		}
		
		/**
		 * Get latest operator of a locomotive
		 * @since Version 3.4
		 * @param int $loco_id
		 * @return array
		 */
		
		public function getLastOperator($loco_id = false) {
			if (!$loco_id) {
				throw new Exception("Could not get latest operator - no loco ID given"); 
				return false;
			}
			
			$query = "SELECT l.*, o.operator_name FROM loco_org_link AS l LEFT JOIN operators AS o ON l.operator_id = o.operator_id WHERE l.loco_id = ? AND l.link_type = 2 ORDER BY l.link_weight DESC LIMIT 1";
			
			return $this->db->fetchRow($query, $loco_id); 
		}
		
		/**
		 * List production models
		 * @since Version 3.4
		 * @return array
		 */
		
		public function listModels() {
			$query = "SELECT DISTINCT Model from loco_class ORDER BY Model";
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query) as $row) {
				if (trim($row['Model']) != "") {
					$return[] = $row['Model'];
				}
			}
			
			return $return;
		}
		
		/**
		 * Get classes by production model
		 * @since Version 3.4
		 * @param string $model
		 * @return array
		 */
		
		public function getClassesByModel($model = false) {
			if (!$model) {
				throw new Exception("Could not fetch list of classes - no production model given"); 
				return false;
			}
			
			$query = "SELECT id, name, slug FROM loco_class WHERE Model = ? ORDER BY name";
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query, $model) as $row) {
				$row['class_url'] = $this->makeClassUrl($row['slug']);
				$return[$row['id']] = $row; 
			}
			
			return $return;
		}
		
		/**
		 * List locomotive groupings
		 * @since Version 3.5
		 * @return array
		 */
		
		public function listGroupings() {
			$query = "SELECT * FROM loco_groups ORDER BY group_name"; 
			
			$return = array("stat" => "ok"); 
			
			foreach ($this->db->fetchAll($query) as $row) {
				$return['groups'][$row['group_id']] = $row; 
			}
			
			return $return;
		}
		
		/**
		 * Add a new locomotive group
		 * @since Version 3.5
		 * @param string $name
		 * @param string $date_start
		 * @return int
		 */
		
		public function addGrouping($name = false, $date_start = false) {
			if (!$name) {
				throw new Exception("Cannot add group - group name cannot be empty"); 
				return false;
			}
			
			$data = array(
				"group_name" => $name
			);
			
			$this->db->insert("loco_groups", $data); 
			return $this->db->lastInsertId();
		}
		
		/**
		 * Get group membership for this loco
		 * @since Version 3.5
		 * @param int $loco_unit_id
		 * @param boolean $includeInactive
		 * @return array
		 */
		
		public function getGroupsMembership($loco_unit_id = false, $includeInactive = false) {
			if (!$loco_unit_id) {
				throw new Exception("Could not fetch group membership - no loco ID given"); 
				return false;
			}

			$query = "SELECT lg.* FROM loco_groups AS lg LEFT JOIN loco_groups_members AS lgm ON lgm.group_id = lg.group_id WHERE lgm.loco_unit_id = ?"; 
			
			if ($includeInactive == false) {
				$query .= " AND lg.active = 1"; 
			}
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query, $loco_unit_id) as $row) {
				$return[$row['group_id']] = $row; 
			}
			
			return $return;
		}
		
		/**
		 * Get members of a group
		 * @since Version 3.5
		 * @param int $group_id
		 */
		 
		public function getGroupMembers($group_id) {
			if (!$group_id) {
				throw new Exception("Cannot fetch group members - no group ID given"); 
				return false;
			}
			
			$query = "SELECT loco_unit_id FROM loco_groups_members WHERE group_id = ?";
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query, $group_id) as $row) {
				$return[] = $row['loco_unit_id']; 
			}
			
			return $return;
		}
		
		/** 
		 * Log an event 
		 * @since Version 3.5
		 * @param int $user_id
		 * @param string $title
		 * @param array $args
		 */
		
		public function logEvent($user_id = false, $title = false, $args = false) {
			if (!$user_id) {
				throw new Exception("Cannot log event, no User ID given"); 
				return false;
			}
			
			if (!$title) {
				throw new Exception("Cannot log event, no title given"); 
				return false;
			}
			
			if (is_array($args)) {
				$args = json_encode($args); 
			}
			
			$dataArray = array(); 
			$dataArray['user_id'] = $user_id; 
			$dataArray['timestamp'] = "NOW()";
			$dataArray['title'] = $title; 
			$dataArray['args'] = $args; 
			
			$this->db->insert("log_locos", $dataArray); 
			return true;
		}
		
		/**
		 * Make a class URL
		 * @since Version 3.8.7
		 * @param string $slug
		 * @return string
		 */
		
		public function makeClassURL($slug) {
			return sprintf("%s/%s", $this->Module->url, $slug);
		}
		
		/**
		 * Make a loco URL
		 * @since Version 3.8.7
		 * @param string $class_slug
		 * @param string $loco_number
		 * @return string
		 */
		
		public function makeLocoURL($class_slug, $loco_number) {
			$loco_number = str_replace(" ", "_", $loco_number);
			return sprintf("%s/%s/%s", $this->Module->url, $class_slug, $loco_number);
		}
		
		/**
		 * Get a random class
		 * @since Version 3.8.7
		 * @return \Railpage\Locos\LocoClass;
		 */
		
		public function getRandomClass() {
			$query = "SELECT `id`, `desc`, flickr_image_id FROM loco_class WHERE `desc` != '' AND flickr_image_id > 0";
			
			$ids = array();
			
			foreach ($this->db->fetchAll($query) as $row) {
				if (strlen(trim($row['desc'])) > 5 && $row['flickr_image_id'] > 0) {
					$ids[] = $row['id'];
				}
			}
			
			shuffle($ids);
			
			foreach ($ids as $id) {
				$LocoClass = new LocoClass($id);
				
				if (!empty($LocoClass->desc) && $LocoClass->getCoverImage()) {
					return $LocoClass;
				}
			}
		}
		
		/**
		 * Year introduced URL
		 * @since Version 3.8.7
		 * @param int $year
		 * @return string
		 */
		
		public function makeYearURL($year = NULL) {
			return sprintf("/locos/year/%d", $year);
		}
		
		/**
		 * Get statistics from the database
		 * @since Version 3.8.7
		 * @param string $statistic
		 * @return mixed
		 */
		
		public function getStatistic($statistic, $param1 = false, $param2 = false) {
			
			if (!$statistic) {
				throw new Exception("Cannot get module statistics - no statistic requested");
			}
			
			switch ($statistic) {
				
				case "railpage.locos.class.count" :
					
					$query = "SELECT count(id) AS count FROM loco_class";
					return $this->db->fetchOne($query);
					
					break;
				
				case "railpage.locos.loco.count" : 
					
					$query = "SELECT count(loco_id) AS count FROM loco_unit";
					return $this->db->fetchOne($query);
					
					break;
				
				case "railpage.locos.status.count" : 
					
					$query = "SELECT count(loco_id) AS count FROM loco_unit WHERE loco_status_id = ? OR loco_status_id = ?";
					return $this->db->fetchOne($query, array($param1, $param2));
					
					break;
			}
		}
		
		/**
		 * Get locomotive dates from a given date range
		 * @since Version 3.9
		 * @param \DateTime $DateFrom
		 * @param \DateTime $DateTo
		 * @return \Railpage\Locos\Date
		 * @yield \Railpage\Locos\Date
		 */
		
		public function yieldDatesWithinRange(DateTime $From, DateTime $To) {
			$query = "SELECT date_id FROM loco_unit_date WHERE timestamp >= ? AND timestamp <= ? ORDER BY timestamp";
			
			foreach ($this->db->fetchAll($query, array($From->format("Y-m-d"), $To->format("Y-m-d"))) as $row) {
				yield new Date($row['date_id']);
			}
		}
	}
?>