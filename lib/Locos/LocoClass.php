<?php
	/** 
	 * Loco database
	 * @since Version 3.2
	 * @version 3.8.7
	 * @author Michael Greenhill
	 * @package Railpage
	 */
	
	namespace Railpage\Locos;
	
	use Exception;
	use DateTime;
	use stdClass;
	use Railpage\Url;
	use Railpage\Images\Images;
	use Railpage\Images\Image;
	use Railpage\Assets\Asset;
	use Railpage\Locos\Liveries\Livery;
	use Railpage\Users\User;
		
	/**
	 * Locomotive class (eg X class or 92 class) class
	 * @since Version 3.2
	 * @version 3.8.7
	 */
	
	class LocoClass extends Locos {
		
		/**
		 * Loco class ID
		 * @since Version 3.2
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Name
		 * @since Version 3.2
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Description
		 * @since Version 3.2
		 * @var string $desc
		 */
		
		public $desc;
		
		/**
		 * Year introduced
		 * @since Version 3.2
		 * @var string $introduced
		 */
		
		public $introduced;
		
		/**
		 * Type
		 * @since Version 3.2
		 * @var string $type
		 */
		
		public $type;
		
		/**
		 * Type ID
		 * @since Version 3.2
		 * @var int $type_id
		 */
		
		public $type_id;
		
		/**
		 * Manufacturer
		 * @since Version 3.2
		 * @var string $manufacturer
		 */
		
		public $manufacturer;
		
		/**
		 * Manufacturer ID
		 * @since Version 3.2
		 * @var int $manufacturer_id
		 */
		
		public $manufacturer_id;
		
		/**
		 * Wheel arrangement text
		 * @since Version 3.2
		 * @var string $wheel_arrangement
		 */
		
		public $wheel_arrangement;
		
		/**
		 * Wheel arrangement ID
		 * @since Version 3.2
		 * @var int $wheel_arrangement_id
		 */
		
		public $wheel_arrangement_id;
		
		/**
		 * Flickr photo tag
		 * @since Version 3.2
		 * @var string $flickr_tag
		 */
		
		public $flickr_tag;
		
		/**
		 * Flickr photo ID
		 * @since Version 3.2
		 * @var int $flickr_image_id
		 */
		
		public $flickr_image_id;
		
		/**
		 * Parent object
		 * @since Version 3.2
		 * @var object $parent
		 */
		
		public $parent;
		
		/**
		 * Child objects
		 * @since Version 3.2
		 * @var object $children
		 */
		
		public $children;
		
		/**
		 * Data source ID
		 * @since Version 3.2
		 * @var object $source
		 */
		
		public $source;
		
		/**
		 * Axle load
		 * @since Version 3.2
		 * @var string $axle_load
		 */
		
		public $axle_load;
		
		/**
		 * Weight
		 * @since Version 3.2
		 * @var string $weight
		 */
		
		public $weight;
		
		/**
		 * Length
		 * @since Version 3.2
		 * @var string $length
		 */
		
		public $length;
		
		/**
		 * Tractive effort
		 * @since Version 3.2
		 * @var string $tractive_effort
		 */
		
		public $tractive_effort;
		
		/**
		 * Model number
		 * @since Version 3.2
		 * @var string $model
		 */
		
		public $model;
		
		/**
		 * Date added
		 * @since Version 3.2
		 * @var int $date_added
		 */
		
		public $date_added;
		
		/**
		 * Date modified
		 * @since Version 3.2
		 * @var int $date_modified
		 */
		
		public $date_modified;
		
		/**
		 * Download ID
		 * @since Version 3.5
		 * @var int $download_id
		 */
		
		public $download_id;
		
		/**
		 * URL Slug
		 * @since Version 3.7.5
		 * @var string $slug
		 */
		
		public $slug;
		
		/**
		 * URL
		 * @since Version 3.8
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Asset ID for non-Flickr cover photo
		 * @since Version 3.8.7
		 * @param object $Asset
		 */
		
		public $Asset;
		
		/**
		 * Constructor
		 * @since Version 3.2
		 * @param int|string $id_or_slug
		 * @param boolean $recurse
		 */
		
		public function __construct($id_or_slug = false, $recurse = true) {
			
			parent::__construct(); 
			
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			/**
			 * Record this in the debug log
			 */
				
			if (function_exists("debug_recordInstance")) {
				debug_recordInstance(__CLASS__);
			}
			
			$this->Templates = new stdClass;
			$this->Templates->view = "class";
			$this->Templates->sightings = "loco.sightings";
			$this->Templates->bulkedit = "class.bulkedit";
			$this->Templates->bulkedit_operators = "class.bulkedit.operators";
			$this->Templates->bulkedit_buildersnumbers = "class.bulkedit.buildersnumbers";
			$this->Templates->bulkedit_status = "class.bulkedit.status";
			$this->Templates->bulkedit_gauge = "class.bulkedit.gauge";
			
			$this->namespace = sprintf("%s.%s", $this->Module->namespace, "class");
			
			// Set the ID
			if (filter_var($id_or_slug, FILTER_VALIDATE_INT) || is_string($id_or_slug)) {
				$this->id = $id_or_slug;
				$this->fetch($recurse);
			}
			
			if (RP_DEBUG) {
				$site_debug[] = "Railpage: " . __CLASS__ . "(" . $this->id . ") instantiated in " . round(microtime(true) - $debug_timer_start, 5) . "s";
			}
		}
		
		/**
		 * Load / fetch a class
		 * @since Version 3.2
		 * @param boolean $recurse
		 */
		
		public function fetch($recurse) {
			if (!$this->id) {
				throw new Exception("Cannot fetch loco class - no class ID given");
				return false;
			}
			
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				$slugkey = sprintf("railpage:locos.class.id;fromslug=%s", $this->id);
				
				if ($id = getMemcacheObject($slugkey)) {
					$this->id = $id;
				} else {
					$this->id = $this->db->fetchOne("SELECT id FROM loco_class WHERE slug = ?", $this->id);
					
					setMemcacheObject($slugkey, $this->id, strtotime("+1 week"));
				}
			}
			
			$this->mckey = sprintf("railpage:locos.class_id=%d", $this->id); 
			$key = "id";
			
			if (!$row = $this->Memcached->fetch($this->mckey)) {
				if ($this->db instanceof \sql_db) {
					$query = "SELECT c.id, c.asset_id, c.slug, c.download_id, c.date_added, c.date_modified, c.model, c.axle_load, c.tractive_effort, c.weight, c.length, c.parent AS parent_class_id, c.source_id AS source, c.id AS class_id, c.flickr_tag, c.flickr_image_id, c.introduced AS class_introduced, c.name AS class_name, c.loco_type_id AS loco_type_id, c.desc AS class_desc, c.manufacturer_id AS class_manufacturer_id, m.manufacturer_name AS class_manufacturer, w.arrangement AS wheel_arrangement, w.id AS wheel_arrangement_id, t.title AS loco_type
								FROM loco_class AS c
								LEFT JOIN loco_type AS t ON c.loco_type_id = t.id
								LEFT JOIN wheel_arrangements AS w ON c.wheel_arrangement_id = w.id
								LEFT JOIN loco_manufacturer AS m ON m.manufacturer_id = c.manufacturer_id
								WHERE c.".$key." = ".$this->db->real_escape_string($this->id);
					
					if ($rs = $this->db->query($query)) {
						$row = $rs->fetch_assoc();
						
						$this->Memcached->save($this->mckey, $row, strtotime("+1 year")); 
					}
				} else {
					if (RP_DEBUG) {
						global $site_debug;
						$debug_timer_start = microtime(true);
					}
					
					$query = "SELECT c.id, c.meta, c.asset_id, c.slug, c.download_id, c.date_added, c.date_modified, c.model, c.axle_load, c.tractive_effort, c.weight, c.length, c.parent AS parent_class_id, c.source_id AS source, c.id AS class_id, c.flickr_tag, c.flickr_image_id, c.introduced AS class_introduced, c.name AS class_name, c.loco_type_id AS loco_type_id, c.desc AS class_desc, c.manufacturer_id AS class_manufacturer_id, m.manufacturer_name AS class_manufacturer, w.arrangement AS wheel_arrangement, w.id AS wheel_arrangement_id, t.title AS loco_type
								FROM loco_class AS c
								LEFT JOIN loco_type AS t ON c.loco_type_id = t.id
								LEFT JOIN wheel_arrangements AS w ON c.wheel_arrangement_id = w.id
								LEFT JOIN loco_manufacturer AS m ON m.manufacturer_id = c.manufacturer_id
								WHERE c.".$key." = ?";
					
					$row = $this->db->fetchRow($query, $this->id);
					
					if (RP_DEBUG) {
						if ($row === false) {
							$site_debug[] = "Zend_DB: FAILED select loco class ID/slug " . $this->id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
						} else {
							$site_debug[] = "Zend_DB: SUCCESS select loco class ID/slug " . $this->id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
						}
					}
					
					/** 
					 * Normalise some items
					 */
					
					if (function_exists("convert_to_utf8")) {
						foreach ($row as $key => $val) {
							$row[$key] = convert_to_utf8($val);
						}
					}
					
					$this->Memcached->save($this->mckey, $row, strtotime("+1 year")); 
				}
			}
			
			if (isset($row) && is_array($row)) {
				
				if (!isset($row['id'])) {
					deleteMemcacheObject($this->mckey);
				} else {
					$this->id = $row['id'];
				}
				
				// Populate the class objects
				$this->slug 	= $row['slug']; 
				$this->name 	= $row['class_name']; 
				$this->desc		= $row['class_desc'];
				$this->type		= $row['loco_type'];
				$this->type_id	= $row['loco_type_id'];
				
				$this->introduced 	= $row['class_introduced'];
				
				$this->manufacturer		= $row['class_manufacturer'];
				$this->manufacturer_id	= $row['class_manufacturer_id'];
				
				$this->wheel_arrangement	= $row['wheel_arrangement'];
				$this->wheel_arrangement_id	= $row['wheel_arrangement_id'];
				
				$this->flickr_tag 		= $row['flickr_tag'];
				$this->flickr_image_id 	= $row['flickr_image_id'];
				
				$this->axle_load = $row['axle_load'];
				$this->tractive_effort = $row['tractive_effort'];
				$this->weight 	= $row['weight'];
				$this->length 	= $row['length'];
				$this->model	= $row['model'];
				
				$this->date_added		= $row['date_added'];
				$this->date_modified	= $row['date_modified'];
				
				$this->download_id		= $row['download_id']; 
				
				if (empty($this->slug) || $this->slug === "1") {
					$this->createSlug();
					
					if (RP_DEBUG) {
						global $site_debug; 
						$site_debug[] = __CLASS__ . "::" . __FUNCTION__ . "() : Creating url slug for loco class ID " . $this->id;
					}
					
					$this->commit();  
				}
				
				$this->url = new Url($this->makeClassURL($this->slug));
				$this->url->view = $this->url->url;
				$this->url->edit = sprintf("%s?mode=class.edit&id=%d", $this->Module->url, $this->id);
				$this->url->addLoco = sprintf("%s?mode=loco.edit&class_id=%d", $this->Module->url, $this->id);
				$this->url->sightings = sprintf("%s/sightings", $this->url->url);
				$this->url->bulkadd = sprintf("%s?mode=loco.bulkadd&class_id=%d", $this->Module->url, $this->id);
				$this->url->bulkedit = sprintf("%s?mode=class.bulkedit&id=%d", $this->Module->url, $this->id);
				$this->url->bulkedit_operators = sprintf("%s?mode=class.bulkedit.operators&id=%d", $this->Module->url, $this->id);
				$this->url->bulkedit_buildersnumbers = sprintf("%s?mode=class.bulkedit.buildersnumbers&id=%d", $this->Module->url, $this->id);
				$this->url->bulkedit_status = sprintf("%s?mode=class.bulkedit.status&id=%d", $this->Module->url, $this->id);
				$this->url->bulkedit_gauge = sprintf("%s?mode=class.bulkedit.gauge&id=%d", $this->Module->url, $this->id);
				
				/**
				 * Set the meta data
				 */
				
				if (isset($row['meta'])) {
					$this->meta = json_decode($row['meta'], true); 
				} else {
					$this->meta = array(); 
				}
				
				/**
				 * If an asset ID exists and is greater than 0, create the asset object
				 */
				 
				if (isset($row['asset_id']) && $row['asset_id'] > 0) {
					try {
						$this->Asset = new \Railpage\Assets\Asset($row['asset_id']);
					} catch (Exception $e) {
						global $Error; 
						$Error->save($e); 
					}
				}
				
				/** 
				 * Create the fwlink object
				 */
				
				try {
					#var_dump($this->url);die;
					$this->fwlink = new \Railpage\fwlink($this->url);
					
					if (empty($this->fwlink->url) && !empty(trim($this->name))) {
						$this->fwlink->url = $this->url;
						$this->fwlink->title = $this->name;
						$this->fwlink->commit();
					}
				} catch (Exception $e) {
					// Do nothing
				}
				
				// Parent object
				if ($row['parent_class_id'] > 0) {
					try {
						$this->parent = new LocoClass($row['parent_class_id'], false);
					} catch (Exception $e) {
						// Re-throw the error
						throw new Exception($e->getMessage()); 
					}
				}
				
				// Data source object
				if ($row['source'] > 0 && class_exists("Source")) {
					try {
						$this->source = new \Source($row['source']);
					} catch (Exception $e) {
						// Re-throw the error
						throw new Exception($e->getMessage());
					}
				}
				
				/**
				 * Set the StatsD namespaces
				 */
				
				$this->StatsD->target->view = sprintf("%s.%d.view", $this->namespace, $this->id);
				$this->StatsD->target->edit = sprintf("%s.%d.view", $this->namespace, $this->id);
				
				#printArray(round(microtime(true) - RP_START_TIME, 4) . "s");
				
				/*
				// Child classes
				if ($this->db instanceof \sql_db) {	
					$query = "SELECT c.id AS child_class_id, c.name AS child_class_name FROM loco_class AS c WHERE c.parent = ".$this->db->real_escape_string($this->id);
					
					if ($rs = $this->db->query($query)) {
						while ($row = $rs->fetch_assoc()) {
							$this->children[$row['child_class_id']] = $row['child_class_name'];
						}
					} else {
						throw new Exception($this->db->error);
					}
				} else {
					$query = "SELECT c.id AS child_class_id, c.name AS child_class_name FROM loco_class AS c WHERE c.parent = ?";
					
					foreach ($this->db->fetchAll($query, $this->id) as $row) {
						$this->children[$row['child_class_id']] = $row['child_class_name'];
					}
				}
				*/
				
				/*
				if (RP_PLATFORM != "API" && !filter_var($this->download_id, FILTER_VALIDATE_INT)) {
					// Create a new download for this class' datasheet
					if (RP_DEBUG) {
						global $site_debug; 
						$site_debug[] = __CLASS__ . "::" . __FUNCTION__ . "() : No download ID for class ID " . $this->id;
					}
					
					try {
						$Download = new \Railpage\Downloads\Download(); 
						
						if (!empty($this->name) && strlen($this->name) > 1) {
							$Download->name			= $this->name." data sheet"; 
							$Download->url			= "http://www.railpage.com.au/modules.php?name=Locos&mode=exportclass&id=".$this->id."&format=xlsx";
							$Download->desc			= "Data sheet for the ".$this->name." class, formatted as a Microsoft Excel spreadsheet";
							$Download->date			= time(); 
							$Download->mime			= "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
							$Download->active		= 1;
							$Download->approved		= 1;
							$Download->Category	 	= new \Railpage\Downloads\Category("23");
							$Download->cat_id		= "23";
							$Download->extra_data	= array("Class name" => $this->name, "Class ID" => $this->id);
							$Download->filename		= 'Railpage-Locodata-'.$this->name.'.xlsx';
							$Download->user_id		= 45;
							$Download->filepath		= "/";
							
							$Download->commit(); 
							
							$this->download_id = $Download->id; 
							
							if (RP_DEBUG) {
								global $site_debug; 
								$site_debug[] = __CLASS__ . "::" . __FUNCTION__ . "() : committing changes to download ID for loco ID " . $this->id;
							}
							
							$this->commit(); 
						}
					} catch (Exception $e) {
						// Discard the error
					}
				}
				*/
			}
		}
		
		/**
		 * Class members
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function members() {
			$query = "SELECT l.*, s.name AS loco_status, o.operator_name, ow.operator_name AS owner_name, g.*
						FROM loco_unit AS l 
						LEFT JOIN loco_status AS s ON l.loco_status_id = s.id 
						LEFT JOIN operators AS ow ON l.owner_id = ow.operator_id 
						LEFT JOIN operators AS o ON l.operator_id = o.operator_id 
						LEFT JOIN loco_gauge AS g ON g.gauge_id = l.loco_gauge_id
						WHERE l.class_id = ? 
						ORDER BY l.loco_num ASC";
						
			// Get the loco gauges
			$gaugeq = "SELECT * FROM loco_gauge"; 
			$gauge = array(); 
			
			foreach ($this->db->fetchAll($gaugeq) as $row) {
				$gauge[$row['gauge_id']] = $row; 
			}
			
			$return = array(
				"stat" => "ok",
				"count" => 0
			);
			
			$builders = $this->listManufacturers();
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				if (empty($row['manufacturer_id'])) {
					$row['manufacturer_id'] = $this->manufacturer_id; 
				}
				
				$return['count']++;
				
				$row['flickr_tag'] = $this->flickr_tag."-".$row['loco_num'];
				
				$row['loco_gauge'] = array();
				
				$row['manufacturer'] 				= $builders['manufacturers'][$row['manufacturer_id']]['manufacturer_name'];
				$row['loco_gauge']['gauge_name']	= $row['gauge_name']."<span style='display:block;margin-top:-8px;margin-bottom:-4px;' class='gensmall'>".$row['gauge_metric']."</span>";
				$row['loco_gauge_formatted'] 		= $row['gauge_name']." ".$row['gauge_imperial']." (".$row['gauge_metric'].")";
				
				try {
					if ($owner = $this->getLastOwner($row['loco_id'])) {
						$row['owner_id'] = $owner['operator_id']; 
						$row['owner_name'] = $owner['operator_name']; 
					}
				} catch (Exception $e) {
					global $Error; 
					$Error->save($e); 
				}
				
				try {
					if ($operator = $this->getLastOperator($row['loco_id'])) {
						$row['operator_id'] = $operator['operator_id']; 
						$row['operator_name'] = $operator['operator_name']; 
					}
				} catch (Exception $e) {
					global $Error; 
					$Error->save($e); 
				}
					
				$row['url'] = strtolower($this->url . "/" . $row['loco_num']);
				$row['url_edit'] = sprintf("%s?mode=loco.edit&id=%d", $this->Module->url, $row['loco_id']);
				
				$return['locos'][$row['loco_id']] = $row;
			}
				
			// Sort by loco number
			if (isset($return['locos']) && count($return['locos'])) {
				uasort($return['locos'], function($a, $b) {
					return strnatcmp($a['loco_num'], $b['loco_num']); 
				});
			}
			
			return $return;
		}
		
		/**
		 * Validate changes
		 * @since Version 3.2
		 * @version 3.8.7
		 * @return boolean
		 */
		
		public function validate() {
			if (empty($this->name)) {
				throw new Exception("Locomotive class name cannot be empty");
			}
			
			if (empty($this->introduced)) {
				throw new Exception("Year introduced cannot be empty");
			}
			
			if (empty($this->manufacturer_id) || !filter_var($this->manufacturer_id, FILTER_VALIDATE_INT)) {
				throw new Exception("Manufacturer ID cannot be empty");
			}
			
			if (empty($this->wheel_arrangement_id) || !filter_var($this->wheel_arrangement_id, FILTER_VALIDATE_INT)) {
				throw new Exception("Wheel arrangement ID cannot be empty");
			}
			
			if (empty($this->type_id) || !filter_var($this->type_id, FILTER_VALIDATE_INT)) {
				throw new Exception("Locomotive type ID cannot be empty");
			}
			
			return true;
		}
		
		/**
		 * Commit changes to the database
		 * @since Version 3.2
		 * @version 3.8.7
		 * @return boolean
		 */
		
		public function commit() {
			$this->validate();
			
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			$this->flushMemcached();
			
			$data = array(
				"name" => $this->name, 
				"desc" => $this->desc,
				"introduced" => $this->introduced,
				"wheel_arrangement_id" => $this->wheel_arrangement_id,
				"loco_type_id" => $this->type_id,
				"manufacturer_id" => $this->manufacturer_id,
				"flickr_tag" => $this->flickr_tag,
				"flickr_image_id" => $this->flickr_image_id,
				"length" => $this->length,
				"weight" => $this->weight,
				"axle_load" => $this->axle_load,
				"tractive_effort" => $this->tractive_effort,
				"model" => $this->model,
				"download_id" => empty($this->download_id) ? 0 : $this->download_id,
				"slug" => empty($this->slug) ? "" : $this->slug,
				"meta" => json_encode(isset($this->meta) && is_array($this->meta) ? $this->meta : array())
			);
			
			if (empty($this->date_added)) {
				$data['date_added'] = time(); 
			} else {
				$data['date_modified'] = time(); 
			}
			
			if ($this->Asset instanceof \Railpage\Assets\Asset) {
				$data['asset_id'] = $this->Asset->id;
			}
			
			foreach ($data as $key => $val) {
				if (is_null($val)) {
					$data[$key] = "";
				}
			}
			
			if ($this->id) {
				// Update
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("loco_class", $data, $where); 
				$verb = "Update";
			} else {
				$this->db->insert("loco_class", $data); 
				$this->id = $this->db->lastInsertId(); 
				
				$this->createSlug();
				$this->commit();
				
				$this->url = new Url($this->makeClassURL($this->slug));
				$this->url->edit = sprintf("%s?mode=class.edit&id=%d", $this->Module->url, $this->id);
				$this->url->addLoco = sprintf("%s?mode=loco.edit&class_id=%d", $this->Module->url, $this->id);
				
				$verb = "Insert";
			}
			
			if (RP_DEBUG) {
				$site_debug[] = "Zend_DB: SUCCESS " . $verb . " loco class ID " . $this->id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
			}
			
			deleteMemcacheObject("railpage:loco.class.bytype=all");
			
			return true;
		}
		
		/**
		 * Register a hit against this class
		 * @since Version 3.2
		 * @return boolean
		 */
		
		public function hit() {
			return false;
			
			$dataArray = array(); 
			
			$dataArray['class_id'] 	= $this->id;
			$dataArray['time']		= time(); 
			$dataArray['ip']		= $_SERVER['REMOTE_ADDR'];
			$dataArray['user_id']	= $_SESSION['user_id']; 
			
			if ($this->db instanceof \sql_db) {
				$query = $this->db->buildQuery($dataArray, "loco_hits"); 
				
				if ($this->db->query($query)) {
					return true;
				} else {
					throw new Exception($this->db->error);
					return false;
				}
			} else {
				$this->db->insert("loco_hits", $dataArray); 
				return true;
			}
		}
		
		/**
		 * Get liveries carried by this loco class
		 * Based on tagged Flickr photos
		 * @since Version 3.2
		 * @param object $f
		 * @return array|boolean
		 */
		
		public function getLiveries($f = false) {
			if (is_object($f)) {
				// Get photos of this loco, including tags
				$result = $f->groups_pools_getPhotos(RP_FLICKR_GROUPID, $this->flickr_tag, NULL, NULL, "tags");
				$tags = array();
				
				if (isset($result['photos']['photo'])) {
					foreach ($result['photos']['photo'] as $photo) {
						$rawtags = explode(" ", $photo['tags']); 
						
						foreach ($rawtags as $tag) {
							if (preg_match("@railpage:livery=([0-9]+)@", $tag, $matches)) {
								if (!in_array($matches[1], $tags)) {
									$tags[] = $matches[1];
								}
							}
						}
					}
				}
				
				if (count($tags)) {
					return $tags;
				} else {
					return false;
				}
			} else {
				$query = "SELECT l.livery_id AS id, l.livery AS name, l.photo_id FROM loco_livery AS l LEFT JOIN loco_unit_livery AS ul ON l.livery_id = ul.livery_id WHERE ul.ignored = ? AND ul.loco_id IN (SELECT loco_id FROM loco_unit WHERE class_id = ?) GROUP BY l.livery_id ORDER BY l.livery";
				$return = array();
				
				foreach ($this->db->fetchAll($query, array("0", $this->id)) as $row) {
					$Livery = new Liveries\Livery($row['id']);
					
					$row = array(
						"id" => $Livery->id,
						"name" => $Livery->name,
						"photo" => array(
							"id" => $Livery->photo_id,
							"provider" => "flickr"
						)
					);
					
					$return[] = $row;
				}
				
				return $return;
			}
		}
		
		/** 
		 * Log an event 
		 * @since Version 3.5
		 * @param int $user_id
		 * @param string $title
		 * @param array $args
		 * @param int $class_id
		 */
		
		public function logEvent($user_id = false, $title = false, $args = false, $class_id = false) {
			if (!$user_id) {
				throw new Exception("Cannot log event, no User ID given"); 
				return false;
			}
			
			if (!$title) {
				throw new Exception("Cannot log event, no title given"); 
				return false;
			}
			
			if (!$class_id) {
				$class_id = $this->id; 
			}
			
			$Event = new \Railpage\SiteEvent; 
			$Event->user_id = $user_id; 
			$Event->title = $title;
			$Event->args = $args; 
			$Event->key = "class_id";
			$Event->value = $class_id;
			$Event->module_name = "locos";
			
			if ($title == "Photo tagged") {
				$Event->module_name = "flickr"; 
			}
			
			$Event->commit();
			
			return true;
		}
		
		/**
		 * Get events recorded against this class
		 * @since Version 3.5
		 * @return array
		 */
		
		public function getEvents() {
			if ($this->db instanceof \sql_db) {
				$query = "SELECT ll.*, u.username FROM log_locos AS ll LEFT JOIN nuke_users AS u ON ll.user_id = u.user_id WHERE ll.class_id = '".$this->db->real_escape_string($this->id)."' ORDER BY timestamp DESC"; 
				
				if ($rs = $this->db->query($query)) {
					$return = array(); 
					
					while ($row = $rs->fetch_assoc()) {
						$row['timestamp'] = \DateTime::createFromFormat("Y-m-d H:i:s", $row['timestamp']); 
						$row['args'] = json_decode($row['args'], true);
						$return[] = $row; 
					}
					
					return $return;
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$query = "SELECT ll.*, u.username FROM log_locos AS ll LEFT JOIN nuke_users AS u ON ll.user_id = u.user_id WHERE ll.class_id = ? ORDER BY timestamp DESC"; 
				
				$return = array(); 
				
				foreach ($this->db->fetchAll($query, $this->id) as $row) {
					$row['timestamp'] = \DateTime::createFromFormat("Y-m-d H:i:s", $row['timestamp']); 
					$row['args'] = json_decode($row['args'], true);
					$return[] = $row; 
				}
				
				return $return;
			}
		}
		
		/**
		 * Get contributors of this locomotive
		 * @since Version 3.7.5
		 * @return array
		 */
		
		public function getContributors() {
			$return = array(); 
			
			$query = "SELECT DISTINCT l.user_id, u.username FROM log_general AS l LEFT JOIN nuke_users AS u ON u.user_id = l.user_id WHERE l.module = ? AND l.key = ? AND l.value = ?";
			
			foreach ($this->db->fetchAll($query, array("locos", "class_id", $this->id)) as $row) {
				$return[$row['user_id']] = $row['username']; 
			}
			
			return $return;
		}
		
		/**
		 * Create a URL slug
		 * @since Version 3.7.5
		 */
		
		private function createSlug() {
			// Assume ZendDB
			$proposal = create_slug($this->name);
			
			$result = $this->db->fetchAll("SELECT id FROM loco_class WHERE slug = ?", $proposal); 
			
			if (count($result)) {
				$proposal .= count($result);
			}
			
			$this->slug = $proposal;
		}
		
		/**
		 * Return an array of tags appliccable to this loco
		 * @since Version 3.7.5
		 * @return array
		 */
		
		public function getTags() {
			return array(
				"railpage:class=" . $this->id,
				$this->flickr_tag
			);
		}
		
		/**
		 * Add an asset to this loco class
		 * @since Version 3.8
		 * @param array $data
		 * @return boolean
		 */
		
		public function addAsset($data = false) {
			if (!is_array($data)) {
				throw new Exception("Cannot add asset - \$data must be an array"); 
				return false;
			}
			
			$data['date'] = new \Zend_Db_Expr("NOW()");
			$data['namespace'] = "railpage.locos.class";
			$data['namespace_key'] = $this->id;
			
			$meta = json_encode($data['meta']);
			
			/**
			 * Handle UTF8 errors
			 */
			
			if (!$meta && json_last_error() == JSON_ERROR_UTF8) {
				// Loop through meta and re-encode
				
				foreach ($data['meta'] as $key => $val) {
					if (!is_array($val)) {
						$data['meta'][$key] = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($val));
					} else {
						foreach ($data['meta'][$key][$val] as $k => $v) {
							if (!is_array($v)) {
								$data['meta'][$key][$val][$k] = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($v));
							}
						}
					}
				}
				
				$data['meta'] = json_encode($data['meta']);
			} else {
				$data['meta'] = $meta;
			}
			
			$this->db->insert("asset", $data);
			return true;
		}
		
		/**
		 * Get the status of the class members, including number in database, scrapped quantity, stored quantity, etc
		 * @since Version 3.8.7
		 * @return array
		 */
		
		public function getFleetStatus() {
			$query = "SELECT u.loco_id AS id, u.loco_num AS number, u.loco_name AS name, u.loco_status_id AS status_id, s.name AS status, u.photo_id, g.* FROM loco_unit AS u LEFT JOIN loco_status AS s ON u.loco_status_id = s.id LEFT JOIN loco_gauge AS g ON g.gauge_id = u.loco_gauge_id WHERE u.class_id = ? ORDER BY s.name";
			
			$return = array(
				"num" => 0,
				"status" => array()
			); 
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				$return['num']++;
				
				if (!isset($return['status'][$row['status_id']])) {
					$return['status'][$row['status_id']] = array(
						"id" => $row['status_id'],
						"name" => $row['status'],
						"num" => 0,
						"units" => array()
					);
				}
				
				$Loco = new Locomotive($row['id']);
				$row['url'] = $Loco->url;
				
				$return['status'][$row['status_id']]['num']++;
				$return['status'][$row['status_id']]['units'][] = $row;
			}
			
			foreach ($return['status'] as $id => $row) {
				usort($return['status'][$id]['units'], function($a, $b) {
					return strnatcmp($a['number'], $b['number']);
				});
			}
			
			return $return;
		}
		
		/**
		 * Get locomotive class timeline
		 * @since Version 3.8.7
		 * @return array
		 */
		
		public function getTimeline() {
			$query = "SELECT d.*, lu.loco_num, ld.loco_date_text FROM loco_unit_date AS d INNER JOIN loco_date_type AS ld ON ld.loco_date_id = d.loco_date_id INNER JOIN loco_unit AS lu ON lu.loco_id = d.loco_unit_id WHERE lu.class_id = ? ORDER BY timestamp ASC";
			
			$return = array(
				"timeline" => array(
					"headline" => $this->name . " timeline",
					"type" => "default", 
					"text" => NULL,
					"asset" => array(
						"media" => NULL,
						"credit" => NULL,
						"caption" => NULL
					),
					"date" => array()
				)
			);
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				if ($row['timestamp'] == "0000-00-00") {
					$row['timestamp'] = date("Y-m-d", $row['date']);
				}
				
				$row['meta'] = json_decode($row['meta'], true);
				
				$data = array(
					"startDate" => str_replace("-", ",", $row['timestamp']),
					"endDate" => str_replace("-", ",", $row['timestamp']),
					"headline" => $row['loco_num'] . " - " . $row['loco_date_text'],
					"text" => $row['text'],
					"asset" => array(
						"media" => NULL,
						"thumbnail" => NULL,
						"credit" => NULL,
						"caption" => NULL
					),
					"meta" => array(
						"date_id" => $row['date_id']
					)
				);
				
				/**
				 * Location
				 */
				
				if (isset($row['meta']['position']['lat']) && isset($row['meta']['position']['lon'])) {
					try {
						$Image = new \Railpage\Images\MapImage($row['meta']['position']['lat'], $row['meta']['position']['lon']);
						$data['asset']['media'] = $Image->sizes['thumb']['source'];
						$data['asset']['thumbnail'] = $Image->sizes['thumb']['source'];
						$data['asset']['caption'] = "<a href='/place?lat=" . $Image->Place->lat . "&lon=" . $Image->Place->lon . "'>" . $Image->Place->name . ", " . $Image->Place->Country->name . "</a>";
						
					} catch (Exception $e) {
						// Throw it away. Throw. It. Away. NOW!
					}
				}
				
				/**
				 * Liveries
				 */
				
				if (isset($row['meta']['livery']['id'])) {
					try {
						$Images = new \Railpage\Images\Images;
						$Image = $Images->findLocoImage($row['loco_unit_id'], $row['meta']['livery']['id']);
						
						if ($Image instanceof \Railpage\Images\Image) {
							$data['asset']['media'] = $Image->sizes['thumb']['source'];
							$data['asset']['thumbnail'] = $Image->sizes['thumb']['source'];
							$data['asset']['caption'] = "<a href='/image?id=" . $Image->id . "'>" . $Image->title . "</a>";
							$data['asset']['credit'] = $Image->author->username;
						}
					} catch (Exception $e) {
						// Throw it away. Throw. It. Away. NOW!
					}
				}
				
				$return['timeline']['date'][] = $data;
			}
			
			return $return;
		}
		
		/**
		 * Bulk add locomotives to this class
		 * @since Version 3.8.7
		 * @param int|string $first_loco
		 * @param int|string $last_loco
		 * @param int $gauge_id
		 * @param int $status_id
		 * @param int $manufacturer_id
		 */
		
		public function bulkAddLocos($first_loco = false, $last_loco = false, $gauge_id = false, $status_id = false, $manufacturer_id = false, $prefix = "") {
			if ($first_loco === false) {
				throw new Exception("Cannot add locomotives to class - first loco number was not provided");
			}
			
			if (preg_match("@([a-zA-Z]+)@", $first_loco)) {
				throw new Exception("The first locomotive number provided has letters in it - the bulk add loco code doesn't support this yet");
			}
			
			if ($last_loco === false) {
				throw new Exception("Cannot add locomotives to class - last loco number was not provided");
			}
			
			if (preg_match("@([a-zA-Z]+)@", $last_loco)) {
				throw new Exception("The last locomotive number provided has letters in it - the bulk add loco code doesn't support this yet");
			}
			
			if ($gauge_id === false || !filter_var($gauge_id, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot add locomotives to class - no gauge ID provided");
			}
			
			if ($status_id === false || !filter_var($status_id, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot add locomotives to class - no status ID provided");
			}
			
			if ($manufacturer_id === false || !filter_var($manufacturer_id, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot add locomotives to class - no manufacturer ID was provided");
			}
			
			$first_loco = trim($first_loco);
			$last_loco = trim($last_loco);
			$gauge_id = trim($gauge_id);
			$status_id = trim($status_id);
			$manufacturer_id = trim($manufacturer_id);
			$prefix = trim($prefix);
			
			$this->db->query("CALL PopulateLocoClass(?, ?, ?, ?, ?, ?, ?)", array($first_loco, $last_loco, $this->id, $gauge_id, $status_id, $manufacturer_id, $prefix));
			
			$this->flushMemcached();
			
			return $this;
		}
		
		/**
		 * Add an organisation to the class members
		 * @since Version 3.8.7
		 * @param int $organisation_id
		 * @param int $link_type
		 * @param int $link_weight
		 */
		
		public function addOrganisation($organisation_id = false, $link_type = false, $link_weight = false) {
			if (!$organisation_id) {
				throw new Exception("Cannot add organisation to class members because no organisation ID was specified");
			}
			
			if (!$link_type) {
				throw new Exception("Cannot add organisation to class members because no link type ID was specified");
			}
			
			if (!$link_weight) {
				throw new Exception("Cannot add organisation to class members because no link weight was specified");
			}
			
			$organisation_id = trim($organisation_id);
			$link_type = trim($link_type);
			$link_weight = trim($link_weight);
			
			$this->db->query("CALL PopulateLocoOrgs(?, ?, ?, ?)", array($this->id, $organisation_id, $link_weight, $link_type));
			
			$this->flushMemcached();
			
			return $this;
		}
		
		/**
		 * Flush any cached data from Memcached
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		public function flushMemcached() {
			if (!empty($this->mckey)) {
				removeMemcacheObject("railpage:locos.class_id=" . $this->id);
				removeMemcacheObject("railpage:locos.class_id=" . $this->slug);
			}
			
			return $this;
		}
		
		/**
		 * Loco sightings
		 * @since Version 3.8.7
		 * @return array
		 */
		
		public function sightings() {
			$Sightings = new \Railpage\Sightings\Base;
			
			return $Sightings->findLocoClass($this->id); 
		}
		
		/**
		 * Check if this loco class has a cover image
		 * @since Version 3.9
		 * @return boolean
		 */
		
		public function hasCoverImage() {
			
			/**
			 * Image stored in meta data
			 */
			
			if (isset($this->meta['coverimage']) && isset($this->meta['coverimage']['id']) && !empty($this->meta['coverimage']['id'])) {
				return true;
			}
			
			/**
			 * Asset
			 */
			
			if ($this->Asset instanceof Asset) {
				return true;
			}
			
			/**
			 * Ordinary Flickr image
			 */
			
			if (filter_var($this->flickr_image_id, FILTER_VALIDATE_INT) && $this->flickr_image_id > 0) {
				return true;
			}
			
			/**
			 * No cover image!
			 */
			
			return false;
		}
		
		/**
		 * Get the cover photo for this locomotive class
		 * @since Version 3.9
		 * @return array
		 * @todo Set the AssetProvider (requires creating AssetProvider)
		 */
		
		public function getCoverImage() {
			
			$mckey = sprintf("railpage:locos.class.coverimage;id=%d", $this->id);
			
			if ($result = getMemcacheObject($mckey)) {
				return $result;
			}
			
			/**
			 * Image stored in meta data
			 */
			
			if (isset($this->meta['coverimage'])) {
				$Image = new Image($this->meta['coverimage']['id']);
				
				$return = array(
					"type" => "image",
					"provider" => $Image->provider,
					"title" => $Image->title,
					"author" => array(
						"id" => $Image->author->id,
						"username" => $Image->author->username,
						"realname" => isset($Image->author->realname) ? $Image->author->realname : $Image->author->username,
						"url" => $Image->author->url
					),
					"image" => array(
						"id" => $Image->id,
					),
					"sizes" => $Image->sizes,
					"url" => $Image->url->getURLs()
				);
				
				setMemcacheObject($mckey, $return, strtotime("+1 month"));
				
				return $return;
			}
			
			/**
			 * Asset
			 */
			
			if ($this->Asset instanceof Asset) {
				$return = array(
					"type" => "asset",
					"provider" => "", // Set this to AssetProvider soon
					"title" => $Asset->meta['title'],
					"author" => array(
						"id" => "",
						"username" => "",
						"realname" => "",
						"url" => ""
					),
					"sizes" => array(
						"large" => array(
							"source" => $Asset->meta['image'],
						),
						"original" => array(
							"source" => $Asset->meta['original'],
						)
					),
					"url" => array(
						"url" => $Asset['meta']['image'],
					)
				);
				
				setMemcacheObject($mckey, $return, strtotime("+1 month"));
				
				return $return;
			}
			
			/**
			 * Ordinary Flickr image
			 */
			
			if (filter_var($this->flickr_image_id, FILTER_VALIDATE_INT) && $this->flickr_image_id > 0) {
				$Images = new Images;
				$Image = $Images->findImage("flickr", $this->flickr_image_id);
				
				$return = array(
					"type" => "image",
					"provider" => $Image->provider,
					"title" => $Image->title,
					"author" => array(
						"id" => $Image->author->id,
						"username" => $Image->author->username,
						"realname" => isset($Image->author->realname) ? $Image->author->realname : $Image->author->username,
						"url" => $Image->author->url
					),
					"image" => array(
						"id" => $Image->id,
					),
					"sizes" => $Image->sizes,
					"url" => $Image->url->getURLs()
				);
				
				setMemcacheObject($mckey, $return, strtotime("+1 month"));
				
				return $return;
			}
			
			/**
			 * No cover image!
			 */
			
			return false;
		}
		
		/**
		 * Set the cover photo for this locomotive class
		 * @since Version 3.9
		 * @param $Image Either an instance of \Railpage\Images\Image or \Railpage\Assets\Asset
		 * @return $this
		 */
		
		public function setCoverImage($Image) {
			
			$mckey = sprintf("railpage:locos.class.coverimage;id=%d", $this->id);
			
			deleteMemcacheObject($mckey);
			
			/**
			 * Zero out any existing images
			 */
			
			$this->photo_id = NULL;
			$this->Asset = NULL;
			
			if (isset($this->meta['coverimage'])) {
				unset($this->meta['coverimage']);
			}
			
			/**
			 * $Image is a Flickr image
			 */
			
			if ($Image instanceof Image && $Image->provider == "flickr") {
				$this->flickr_image_id = $Image->photo_id;
				$this->commit(); 
				
				return $this;
			}
			
			/**
			 * Image is a site asset
			 */
			
			if ($Image instanceof Asset) {
				$this->Asset = clone $Image;
				$this->commit(); 
				
				return $this;
			}
			
			/**
			 * Image is a generic image, so we'll just store the Image ID and fetch it later with $this->getCoverImage()
			 */
			
			$this->meta['coverimage'] = array(
				"id" => $Image->id,
				"title" => $Image->title,
				"sizes" => $Image->sizes,
				"url" => $Image->url->getURLs()
			);
			
			$this->commit(); 
			
			return $this;
		}
		
		/**
		 * Get this locomotive class data as an associative array
		 * @since Version 3.9
		 * @return array
		 */
		
		public function getArray() {
			return array(
				"id" => $this->id,
				"name" => $this->name,
				"desc" => $this->desc,
				"type" => array(
					"id" => $this->type_id,
					"text" => $this->type,
				),
				"introduced" => $this->introduced,
				"weight" => $this->weight,
				"axle_load" => $this->axle_load,
				"tractive_effort" => $this->tractive_effort,
				"wheel_arrangement" => array(
					"id" => $this->wheel_arrangement_id,
					"text" => $this->wheel_arrangement
				)
			);
		}
		
		/**
		 * Set the manufacturer
		 * @since Version 3.9.1
		 * @param \Railpage\Locos\Manufacturer $Manufacturer
		 * @return \Railpage\Locos\LocoClass
		 */
		
		public function setManufacturer(Manufacturer $Manufacturer) {
			$this->manufacturer_id = $Manufacturer->id;
			$this->manufacturer = $Manufacturer->name;
			
			return $this;
		}
		
		/**
		 * Set the wheel arrrangement
		 * @since Version 3.9.1
		 * @param \Railpage\Locos\WheelArrangement $WheelArrangement
		 * @return \Railpage\Locos\LocoClass
		 */
		
		public function setWheelArrangement(WheelArrangement $WheelArrangement) {
			$this->wheel_arrangement_id = $WheelArrangement->id;
			$this->wheel_arrangement = $WheelArrangement->arrangement;
			
			return $this;
		}
		
		/**
		 * Set the type
		 * @since Version 3.9.1
		 * @param \Railpage\Locos\Type $Type
		 * @return \Railpage\Locos\LocoClass
		 */
		
		public function setType(Type $Type) {
			$this->type_id = $Type->id;
			$this->type = $Type->name;
			
			return $this;
		}
	}