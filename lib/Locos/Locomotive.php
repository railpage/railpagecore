<?php
	/** 
	 * Loco database
	 * @since Version 3.2
	 * @version 3.8.7
	 * @author Michael Greenhill
	 * @package Railpage
	 */
	
	namespace Railpage\Locos;
	
	use Railpage\Locos\Liveries\Livery;
	use Railpage\Users\User;
	use Railpage\Images\Images;
	use Railpage\Images\Image;
	use Railpage\Assets\Asset;
	use Railpage\Url;
	use DateTime;
	use Exception;
	use stdClass;
	use Railpage\Registry;
		
	/**
	 * Loco object
	 * @since Version 3.2
	 * @version 3.8.7
	 */
	
	class Locomotive extends Locos {
		
		/**
		 * Loco ID
		 * @since Version 3.2
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Loco number
		 * @since Version 3.2
		 * @var string $number Locomotive fleet number, eg R761 or NR101
		 */
		
		public $number;
		
		/**
		 * Flickr tag
		 * @since Version 3.2
		 * @var string $flickr_tag
		 */
		
		public $flickr_tag;
		
		/**
		 * Gauge
		 * @since Version 3.2
		 * @var string $gauge
		 */
		
		public $gauge;
		
		/**
		 * Gauge ID
		 * @since Version 3.4
		 * @var int $gauge_id
		 */
		
		public $gauge_id;
		
		/**
		 * Gauge - formatted to look nicer
		 * @since Version 3.2
		 * @var string $gauge_formatted
		 */
		
		public $gauge_formatted;
		
		/**
		 * Status ID
		 * @since Version 3.2
		 * @var int $status_id
		 */
		
		public $status_id;
		
		/**
		 * Loco status
		 * @since Version 3.2
		 * @var string $status
		 */
		
		public $status;
		
		/**
		 * Class ID
		 * @since Version 3.2
		 * @var int $class_id
		 */
		
		public $class_id;
		
		/**
		 * Class object
		 * @since Version 3.8.7
		 * @var \Railpage\Locos\LocoClass $class Instance of \Railpage\Locos\LocoClass that this locomotive belongs to
		 */
		
		public $Class;
		
		/**
		 * Alias of $this->Class
		 * @since Version 3.2
		 * @var \Railpage\Locos\LocoClass $class Instance of \Railpage\Locos\LocoClass that this locomotive belongs to
		 */
		
		public $class;
				
		/**
		 * All owners
		 * @since Version 3.4
		 * @var array $owners An array of owners of this locomotive
		 */
		
		public $owners; 
		
		/**
		 * Owner ID
		 * @since Version 3.2
		 * @var int $owner_id The ID of the current/newest owner
		 */
		
		public $owner_id;
		
		/**
		 * Owner name
		 * @since Version 3.2
		 * @var string $owner The formatted name of the current/newest owner
		 */
		
		public $owner;
		
		/**
		 * All operators
		 * @since Version 3.4
		 * @var array $operators An array of operators of this locomotive
		 */
		
		public $operators; 
		
		/**
		 * Operator ID
		 * @since Version 3.2
		 * @var int $operator_id The ID of the current/newest operator
		 */
		
		public $operator_id;
		
		/**
		 * Operator name
		 * @since Version 3.2
		 * @var string $operator The formatted name of the current/newest operator
		 */
		
		public $operator;
		
		/**
		 * Entered service date
		 * @deprecated Deprecated since Version 3.8.7 - replaced by \Railpage\LocoClass\Date objects
		 * @since Version 3.2
		 * @var int $entered_service
		 */
		
		public $entered_service;
		
		/**
		 * Withdrawal date
		 * @deprecated Deprecated since Version 3.8.7 - replaced by \Railpage\LocoClass\Date objects
		 * @since Version 3.2
		 * @var int $withdrawal_date
		 */
		
		public $withdrawal_date;
		
		/**
		 * Builders number
		 * @since Version 3.2
		 * @var string $builders_num The builders number
		 */
		
		public $builders_num;
		
		/**
		 * Loco photo ID
		 * @since Version 3.2
		 * @var int $photo_id ID of a Flickr photo to show as the cover photo of this locomotive
		 */
		
		public $photo_id;
		
		/**
		 * Loco builder ID
		 * @since Version 3.2
		 * @var int $manufacturer_id
		 */
		
		public $manufacturer_id;
		
		/**
		 * Loco builder name
		 * @since Version 3.2
		 * @var int $manufacturer
		 */
		
		public $manufacturer;
		
		/**
		 * Date added
		 * @since Version 3.2
		 * @var int $date_added When this locomotive was added to the database
		 */
		
		public $date_added;
		
		/**
		 * Date modified
		 * @since Version 3.2
		 * @var int $date_modified When this locomotive was last modified in the database
		 */
		
		public $date_modified;
		
		/**
		 * Locomotive name
		 * @since Version 3.2
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Locomotive data rating
		 * @since Version 3.2
		 * @var float $rating
		 */
		
		public $rating;
		
		/**
		 * Memcache key
		 * @since Version 3.7.5
		 * @var string $mckey The unique Memcached identifier of this locomotive
		 */
		
		public $mckey; 
		
		/**
		 * Loco URL
		 * @since Version 3.8
		 * @var string $url The link to this locomotive's page, relative to the site root of Railpage
		 */
		
		public $url;
		
		/**
		 * Asset ID for non-Flickr cover photo
		 * @since Version 3.8.7
		 * @var \Railpage\Assets\Asset $Asset An instance of \Railpage\Assets\Asset identified as the "primary" asset for this locomotive. Could be featuring the cover photo.
		 */
		
		public $Asset;
		
		/**
		 * Array of liveries worn by this locomotive
		 * @since Version 3.8.7
		 * @var array $liveries
		 */
		
		public $liveries;
		
		/**
		 * Loco meta data
		 * @since Version 3.8.7
		 * @var array $meta
		 */
		
		public $meta;
		
		/**
		 * Constructor
		 * @since Version 3.2
		 * @param int $id
		 * @param int|string $class_id_or_slug
		 * @param string $number
		 */
		
		public function __construct($id = NULL, $class_id_or_slug = NULL, $number = NULL) {
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
			
			$this->namespace = sprintf("%s.%s", $this->Module->namespace, "loco");
			
			/**
			 * List of templates
			 */
			
			$this->Templates = new stdClass;
			$this->Templates->view = "loco";
			$this->Templates->edit = "loco.edit";
			$this->Templates->sightings = "loco.sightings";
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id; 
			}
			
			if ((is_null($id) || $id == false) && !is_null($number)) {
				if (!filter_var($class_id_or_slug, FILTER_VALIDATE_INT) && is_string($class_id_or_slug)) {
					// Assume Zend_DB
					$slug_mckey = sprintf("railpage:loco.id;fromslug=%s;v2", $class_id_or_slug);
					
					if ($mcresult = $this->Memcached->fetch($slug_mckey)) {
						$class_id_or_slug = $mcresult;
					} else {
						$class_id_or_slug = $this->db->fetchOne("SELECT id FROM loco_class WHERE slug = ?", $class_id_or_slug); 
						
						$this->Memcached->save($slug_mckey, $class_id_or_slug, strtotime("+48 hours"));
					}
				}
				
				// We are searching by loco number - we need to find it first
				if ($this->db instanceof \sql_db) {
					$query = "SELECT loco_id FROM loco_unit WHERE class_id = ".$this->db->real_escape_string($class_id_or_slug)." AND loco_num = '".$this->db->real_escape_string($number)."'"; 
				
					if ($rs = $this->db->query($query)) {
						$row = $rs->fetch_assoc(); 
						
						$this->id = $row['loco_id'];
					}
				} else {
					if (!$this->id = $this->Memcached->fetch(sprintf("railpage:loco.id;fromclass=%s;fromnumber=%s", $class_id_or_slug, $number))) {
						
						$params = array(
							$class_id_or_slug,
							$number
						);
						
						$query = "SELECT loco_id FROM loco_unit WHERE class_id = ? AND loco_num = ?";
						
						if (preg_match("/_/", $number)) {
							$params[1] = str_replace("_", " ", $number);
						} else {
							if (strlen($number) == 5 && preg_match("/([a-zA-Z]{1})([0-9]{4})/", $number)) {
								$params[] = sprintf("%s %s", substr($number, 0, 2), substr($number, 2, 3));
								$query = "SELECT loco_id FROM loco_unit WHERE class_id = ? AND (loco_num = ? OR loco_num = ?)";
							}
						}
						
						$this->id = $this->db->fetchOne($query, $params);
						
						$this->Memcached->save(sprintf("railpage:loco.id;fromclass=%s;fromnumber=%s", $class_id_or_slug, $number), $this->id, strtotime("+1 month"));
					}
				}
			} else {
				$this->id = $id;
			}
			
			// Load the loco object
			if (!empty($this->id)) {
				$this->fetch(); 
			}
			
			if (RP_DEBUG) {
				$site_debug[] = "Railpage: " . __CLASS__ . "(" . $this->id . ") instantiated in " . round(microtime(true) - $debug_timer_start, 5) . "s";
			}
		}
		
		/**
		 * Load the locomotive object
		 * @since Version 3.2
		 * @version 3.2
		 * @return boolean
		 */
		
		public function fetch() {
			if (!$this->id) {
				throw new Exception("Cannot load loco object - loco ID not provided");
				return false;
			}
			
			$this->mckey = sprintf("railpage:locos.loco_id=%d", $this->id);
			#deleteMemcacheObject($this->mckey);
			
			if ($row = $this->Memcached->fetch($this->mckey)) {
				// Do nothing
			} elseif ($this->db instanceof \sql_db) {
				$query = "SELECT l.*, s.name AS loco_status, ow.operator_name AS owner_name, op.operator_name AS operator_name
							FROM loco_unit AS l
							LEFT JOIN loco_status AS s ON l.loco_status_id = s.id
							LEFT JOIN operators AS ow ON ow.operator_id = l.owner_id
							LEFT JOIN operators AS op ON op.operator_id = l.operator_id
							WHERE l.loco_id = ".$this->id;
				
				if ($rs = $this->db->query($query)) {
					$row = $rs->fetch_assoc(); 
					
					$this->Memcached->save($this->mckey, $row, strtotime("+1 week")); 
				}
			} else {
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
				
				$row = $this->db->fetchRow($query, $this->id);
				
				if (RP_DEBUG) {
					if ($row === false) {
						$site_debug[] = "Zend_DB: FAILED select loco ID " . $this->id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
					} else {
						$site_debug[] = "Zend_DB: SUCCESS select loco ID " . $this->id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
					}
				}
					
				$this->Memcached->save($this->mckey, $row, strtotime("+1 month")); 
			}
			
			if (isset($row) && is_array($row)) {
				$this->number 		= stripslashes($row['loco_num']); 
				$this->name			= stripslashes($row['loco_name']);
				$this->gauge 		= stripslashes($row['loco_gauge']);
				$this->gauge_id		= $row['loco_gauge_id'];
				$this->status_id 	= $row['loco_status_id']; 
				$this->status		= $row['loco_status'];
				$this->class_id 	= $row['class_id']; 
				$this->owner_id 	= $row['owner_id']; 
				$this->owner		= $row['owner_name'];
				$this->operator_id 	= $row['operator_id']; 
				$this->operator		= $row['operator_name'];
				$this->entered_service	= $row['entered_service'];
				$this->withdrawal_date	= $row['withdrawn'];
				
				$this->date_added		= $row['date_added']; 
				$this->date_modified	= $row['date_modified'];
				
				$this->builders_num		= $row['builders_number'];
				$this->photo_id			= intval($row['photo_id']);
				$this->manufacturer_id	= $row['manufacturer_id'];
				
				$this->Class 		= new LocoClass($this->class_id);
				$this->class = &$this->Class;
				$this->flickr_tag	= trim(str_replace(" ", "", $this->Class->flickr_tag."-".$this->number));
				
				$this->gauge_formatted = format_gauge($this->gauge);
				
				$this->url = new Url(strtolower($this->makeLocoURL($this->Class->slug, $this->number)));
				$this->url->edit = sprintf("%s?mode=loco.edit&id=%d", $this->Module->url, $this->id);
				$this->url->sightings = sprintf("%s/sightings", $this->url->url);
				$this->url->photos = sprintf("%s/photos", $this->url->url);
				$this->fwlink = $this->url->short;
				
				/**
				 * Set the meta data
				 */
				
				if (isset($row['meta'])) {
					$this->meta = json_decode($row['meta'], true); 
				} else {
					$this->meta = array(); 
				}
				
				// Fetch the gauge data
				if ($this->gauge = $this->Memcached->fetch(sprintf("railpage:locos.gauge_id=%d", $row['loco_gauge_id']))) {
					// Do nothing
				} elseif ($this->db instanceof \sql_db) {
					$query = "SELECT * FROM loco_gauge WHERE gauge_id = '".$this->db->real_escape_string($row['loco_gauge_id'])."'";
					
					if ($rs = $this->db->query($query)) {
						$this->gauge = $rs->fetch_assoc(); 
						
						$this->Memcached->save("rp-locos-gauge-" . $row['loco_gauge_id'], $this->gauge);
					}
				} else {
					$query = "SELECT * FROM loco_gauge WHERE gauge_id = ?";
					
					$this->gauge = $this->db->fetchRow($query, $row['loco_gauge_id']);
					
					$this->Memcached->save("railpage:locos.gauge_id=" . $row['loco_gauge_id'], $this->gauge, strtotime("+2 months"));
				}
				
				/**
				 * If an asset ID exists and is greater than 0, create the asset object
				 */
				
				if (isset($row['asset_id']) && $row['asset_id'] > 0) {
					try {
						$this->Asset = new Asset($row['asset_id']);
					} catch (Exception $e) {
						global $Error; 
						$Error->save($e); 
					}
				}
				
				/**
				 * Get all owners of this locomotive
				 */
				
				try {
					$this->owners = $this->getOrganisations(1); 
					
					if (!empty($this->owner_id) && empty($this->owners)) {
						$this->addOrganisation($this->owner_id, 1); 
						
						// Re-fetch the owners
						$this->owners = $this->getOrganisations(1); 
					}
						
					reset($this->owners);
					
					if (isset($this->owners[0]['organisation_id']) && isset($this->owners[0]['organisation_name'])) {
						$this->owner_id = $this->owners[0]['organisation_id']; 
						$this->owner 	= $this->owners[0]['organisation_name']; 
					} else {
						$this->owner_id = 0;
						$this->owner 	= "Unknown";
					}
				} catch (Exception $e) {
					global $Error; 
					$Error->save($e); 
				}
				
				/**
				 * Get all operators of this locomotive
				 */
				
				try {
					$this->operators = $this->getOrganisations(2);
					
					if (!empty($this->operator_id) && empty($this->operators)) {
						$this->addOrganisation($this->operator_id, 2); 
						
						// Re-fetch the operators
						$this->operators = $this->getOrganisations(2);
					} 
						
					reset($this->operators);
					
					if (isset($this->operators[0]['organisation_id']) && isset($this->operators[0]['organisation_name'])) {
						$this->operator_id 	= $this->operators[0]['organisation_id']; 
						$this->operator 	= $this->operators[0]['organisation_name']; 
					} else {
						$this->operator_id 	= 0;
						$this->operator 	= "Unknown";
					}
				} catch (Exception $e) {
					global $Error; 
					$Error->save($e); 
				}
				
				/**
				 * Get the manufacturer
				 */
				
				if (empty($this->manufacturer_id)) {
					$this->manufacturer_id 	= $this->Class->manufacturer_id;
					$this->manufacturer 	= $this->Class->manufacturer;
				} else {
					try {
						$builders = $this->listManufacturers(); 
						
						if (count($builders['manufacturers'])) {
							$this->manufacturer = $builders['manufacturers'][$this->manufacturer_id]['manufacturer_name'];
						}
					} catch (Exception $e) {
						// I hate globals, but I don't want to throw an exception here...
						global $Error; 
						
						$Error->save($e);
					}
				}
				
				/**
				 * Update the latest owner/operator stored in this row
				 */
				
				$owners 	= $this->getOrganisations(1, 1); 
				$operators 	= $this->getOrganisations(2, 1); 
				
				if (count($owners) && intval(trim($this->owner_id)) != intval(trim($owners[0]['operator_id']))) {
					if (RP_DEBUG) {
						global $site_debug; 
						$site_debug[] = __CLASS__ . "::" . __FUNCTION__ . "() : committing changes to owner for loco ID " . $this->id;
						$site_debug[] = __CLASS__ . "::" . __FUNCTION__ . "() : Current owner_id: " . $this->owner_id . ", Proposed owner_id: " . $owners[0]['operator_id']; 
					}
					
					$this->owner = $owners[0]['organisation_name']; 
					$this->owner_id = $owners[0]['operator_id']; 
					
					$this->commit(); 
				}
				
				if (count($operators) && intval(trim($this->operator_id)) != intval(trim($operators[0]['operator_id']))) {
					if (RP_DEBUG) {
						global $site_debug; 
						$site_debug[] = __CLASS__ . "::" . __FUNCTION__ . "() : committing changes to operator for loco ID " . $this->id;
						$site_debug[] = __CLASS__ . "::" . __FUNCTION__ . "() : Current operator_id: " . $this->operator_id . ", Proposed operator_id: " . $owners[0]['operator_id']; 
					}
					
					$this->operator = $operators[0]['organisation_name']; 
					$this->operator_id = $operators[0]['operator_id']; 
					
					$this->commit();
				}
				
				/**
				 * Populate the list of liveries
				 */
				
				/*
				foreach ($this->db->fetchAll("SELECT lu.livery_id FROM loco_unit_livery AS lu LEFT JOIN loco_livery AS li ON lu.livery_id = li.livery_id WHERE lu.loco_id = ? ORDER BY li.livery", $this->id) as $row) {
					$Livery = new Livery($row['livery_id']);
					
					$livery = array(
						"id" => $Livery->id,
						"name" => $Livery->name,
						"tag" => $Livery->tag,
						"country" => array(
							"code" => $Livery->country,
						),
						"region" => array(
							"code" => $Livery->region
						)
					);
					
					if ($Livery->Image instanceof \Railpage\Images\Image) {
						$livery['image'] = array(
							"id" => $Livery->Image->id,
							"title" => $Livery->Image->title,
							"description" => $Livery->Image->description,
							"provider" => $Livery->Image->provider,
							"photo_id" => $Livery->Image->photo_id,
							"sizes" => $Livery->Image->sizes,
						);
					}
					
					$this->liveries[] = $livery;
				}
				*/
				
				/**
				 * Set the StatsD namespaces
				 */
				
				$this->StatsD->target->view = sprintf("%s.%d.view", $this->namespace, $this->id);
				$this->StatsD->target->edit = sprintf("%s.%d.view", $this->namespace, $this->id);
			} else {
				throw new Exception("No data found for Loco ID " . $this->id);
				return false;
			}
		}
		
		/**
		 * Validate
		 * @since Version 3.2
		 * @version 3.2
		 * @return boolean
		 */
		
		public function validate() {
			
			if ($this->class instanceof LocoClass && !$this->Class instanceof LocoClass) {
				$this->Class = &$this->class;
			}
			
			if (empty($this->number)) {
				throw new Exception("No locomotive number specified");
			}
			
			if (!filter_var($this->class_id, FILTER_VALIDATE_INT) || $this->class_id === 0) {
				if ($this->Class instanceof LocoClass) {
					$this->class_id = $this->Class->id;
				} else {
					throw new Exception("Cannot add locomotive because we don't know which class to add it into");
				}
			}
			
			return true;
		}
		
		/**
		 * Commit changes to database
		 * @since Version 3.2
		 * @version 3.8.7
		 * @return boolean
		 */
		
		public function commit() {
			
			$this->validate();
			
			// Drop whitespace from loco numbers of all types except steam
			if (in_array($this->class_id, array(2, 3, 4, 5, 6)) || in_array($this->Class->type_id, array(2, 3, 4, 5, 6))) {
				$this->number = str_replace(" ", "", $this->number);
			}
			
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			if ($this->db instanceof \sql_db) {
				$dataArray = array();
				$dataArray['loco_num'] 			= $this->db->real_escape_string($this->number);
				$dataArray['loco_gauge_id'] 	= $this->db->real_escape_string($this->gauge_id);
				$dataArray['loco_status_id'] 	= $this->db->real_escape_string($this->status_id);
				$dataArray['class_id'] 			= $this->db->real_escape_string($this->class_id);
				$dataArray['owner_id'] 			= $this->db->real_escape_string($this->owner_id); 
				$dataArray['operator_id'] 		= $this->db->real_escape_string($this->operator_id); 
				$dataArray['entered_service'] 	= $this->entered_service;
				$dataArray['withdrawn'] 		= $this->withdrawal_date;
				$dataArray['builders_number']	= $this->builders_num; 
				$dataArray['photo_id']			= $this->db->real_escape_string($this->photo_id);
				$dataArray['manufacturer_id']	= $this->db->real_escape_string($this->manufacturer_id);
				$dataArray['loco_name']			= $this->db->real_escape_string($this->name);
				$dataArray['meta'] = $this->db->real_escape_string(json_encode($this->meta));
				
				if ($this->Asset instanceof Asset) {
					$dataArray['asset_id'] = $this->db->real_escape_string($this->Asset->id);
				} else {
					$dataArray['asset_id'] = 0;
				}
				
				if (empty($this->date_added)) {
					$dataArray['date_added'] = time(); 
				} else {
					$dataArray['date_modified'] = time(); 
				}
				
				if (!empty($this->id)) {
					$where = array(); 
					$where['loco_id'] = $this->id;
					
					$query = $this->db->buildQuery($dataArray, "loco_unit", $where); 
				} else {
					$query = $this->db->buildQuery($dataArray, "loco_unit");
				}
				
				if ($rs = $this->db->query($query)) {
					if (!$this->id) {
						$this->id = $this->db->insert_id;
					}
					
					return $this->id;
				} else {
					throw new Exception("Could create / edit loco number ".$this->number."\n".$this->db->error."\nQuery: ".$query);
					return false;
				}
			} else {
				$data = array(
					"loco_num" => $this->number,
					"loco_gauge_id" => $this->gauge_id,
					"loco_status_id" => $this->status_id,
					"class_id" => $this->class_id,
					"owner_id" => empty($this->owner_id) ? 0 : $this->owner_id,
					"operator_id" => empty($this->operator_id) ? 0 : $this->operator_id,
					"entered_service" => empty($this->entered_service) ? "" : $this->entered_service,
					"withdrawn" => empty($this->withdrawal_date) ? "" : $this->withdrawal_date,
					"builders_number" => empty($this->builders_num) ? "" : $this->builders_num,
					"photo_id" => empty($this->photo_id) ? 0 : $this->photo_id,
					"manufacturer_id" => empty($this->manufacturer_id) ? 0 : $this->manufacturer_id,
					"loco_name" => empty($this->name) ? "" : $this->name,
					"meta" => json_encode($this->meta)
				);
				
				if (empty($this->date_added)) {
					$data['date_added'] = time(); 
				} else {
					$data['date_modified'] = time(); 
				}
				
				if ($this->Asset instanceof \Railpage\Assets\Asset) {
					$data['asset_id'] = $this->Asset->id;
				} else {
					$data['asset_id'] = 0;
				}
				
				if (empty($this->id)) {
					$rs = $this->db->insert("loco_unit", $data); 
					$this->id = $this->db->lastInsertId(); 
					
					$verb = "Insert";
				} else {
					$this->deleteCache($this->mckey);
					$where = array(
						"loco_id = ?" => $this->id
					);
					
					$verb = "Update";
					
					$rs = $this->db->update("loco_unit", $data, $where); 
				}
				
				if (RP_DEBUG) {
					if ($rs === false) {
						$site_debug[] = "Zend_DB: FAILED " . $verb . " loco ID " . $this->id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
					} else {
						$site_debug[] = "Zend_DB: SUCCESS " . $verb . " loco ID " . $this->id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
					}
				}
				
				return true;
			}
		}
		
		/**
		 * Add note to this loco or edit an existing one
		 * @since Version 3.2
		 * @version 3.4
		 * @param string $note_text
		 * @param int $user_id
		 * @param int $note_id
		 */
		
		public function addNote($note_text = false, $user_id, $note_id = false) {
			if (!$note_text || empty($note_text)) {
				throw new Exception("No note text given"); 
				return false;
			} 
			
			if ($this->db instanceof \sql_db) {
				$dataArray = array(); 
				$dataArray['loco_id'] = $this->id; 
				
				if (!empty($user_id)) {
					$dataArray['user_id'] = $this->db->real_escape_string($user_id); 
				} 
				
				$dataArray['note_date'] = time(); 
				$dataArray['note_text'] = $this->db->real_escape_string($note_text); 
				
				if ($note_id) {
					$where = array(); 
					$where['note_id'] = $this->db->real_escape_string($note_id); 
					
					$query = $this->db->buildQuery($dataArray, "loco_notes", $note_id); 
				} else {
					$query = $this->db->buildQuery($dataArray, "loco_notes"); 
				}
				
				if ($rs = $this->db->query($query)) {
					return $this->db->insert_id;
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$data = array(
					"loco_id" => $this->id,
					"note_date" => time(),
					"note_text" => $note_text
				);
				
				if (!empty($user_id)) {
					$data['user_id'] = $user_id;
				}
				
				if ($note_id) {
					$where = array(
						"note_id = ?" => $note_id
					);
					
					$this->db->update("loco_notes", $data, $where);
					return true;
				} else {
					$this->db->insert("loco_notes", $data);
					return $this->db->lastInsertId(); 
				}
			}
		}
		
		/**
		 * Load notes
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function loadNotes() {
			if ($this->db instanceof \sql_db) {
				$query = "SELECT n.*, u.username, user_avatar FROM loco_notes AS n LEFT JOIN nuke_users AS u ON n.user_id = u.user_id WHERE n.loco_id = ".$this->id; 
				
				if ($rs = $this->db->query($query)) {
					$notes = array(); 
					
					while ($row = $rs->fetch_assoc()) {
						if (!empty($row['user_avatar'])) {
							try {
								$row['user_avatar'] = format_avatar($row['user_avatar'], 50);
							} catch (Exception $e) {
								global $Error; 
								$Error->save($e); 
							}
						}
						
						$notes[$row['note_id']] = $row; 
					}
					
					return $notes; 
				} else {
					throw new Exception($this->db->error."\n".$query); 
					return false;
				}
			} else {
				$query = "SELECT n.*, u.username, user_avatar FROM loco_notes AS n LEFT JOIN nuke_users AS u ON n.user_id = u.user_id WHERE n.loco_id = ?";
				
				$notes = array(); 
				
				foreach ($this->db->fetchAll($query, $this->id) as $row) {
					if (!empty($row['user_avatar'])) {
						try {
							$User = new User($row['user_id']);
							
							$row['user_avatar'] = format_avatar($row['user_avatar'], 50);
							$row['user_url'] = $User->url;
						} catch (Exception $e) {
							global $Error; 
							$Error->save($e); 
						}
					}
					
					$notes[$row['note_id']] = $row; 
				}
				
				return $notes;
			}
		}
		
		/**
		 * Load dates
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function loadDates() {
			if ($this->db instanceof \sql_db) {
				$query = "SELECT d.date_id, d.date, d.text, dt.loco_date_text AS title, dt.loco_date_id AS date_type_id
							FROM loco_unit_date AS d
							LEFT JOIN loco_date_type AS dt ON d.loco_date_id = dt.loco_date_id
							WHERE d.loco_unit_id = ".$this->id."
							ORDER BY d.date DESC";
							
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows > 0) {
						while ($row = $rs->fetch_assoc()) {
							$return[] = $row;
						}
						
						return $return;
					} else {
						return false;
					}
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$query = "SELECT d.date_id, d.date, d.text, dt.loco_date_text AS title, dt.loco_date_id AS date_type_id
							FROM loco_unit_date AS d
							LEFT JOIN loco_date_type AS dt ON d.loco_date_id = dt.loco_date_id
							WHERE d.loco_unit_id = ?
							ORDER BY d.date DESC";
				
				return $this->db->fetchAll($query, $this->id);
			}
		}
		
		/**
		 * Add a date to this loco
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $loco_date_id
		 * @param int $date
		 * @param string $text
		 * @return boolean
		 */
		
		public function addDate($loco_date_id = false, $date = false, $text = false) {
			if (!$loco_date_id) {
				throw new Exception("Cannot add date - no date type given");
				return false;
			}
			
			if (!$date) {
				throw new Exception("Cannot add date - no date given");
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$dataArray['loco_unit_id']	= $this->id;
				$dataArray['loco_date_id'] 	= $this->db->real_escape_string($loco_date_id); 
				$dataArray['date']			= $this->db->real_escape_string($date); 
				
				if (!empty($text) && $text != false) {
					$dataArray['text'] = $this->db->real_escape_string($_POST['loco_date_text']);
				}
				
				$query = $this->db->buildQuery($dataArray, "loco_unit_date"); 
				
				if ($rs = $this->db->query($query)) {
					return true; 
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$data = array(
					"loco_unit_id" => $this->id,
					"loco_date_id" => $loco_date_id,
					"date" => $date
				);
				
				if (!empty($text) && $text != false) {
					$data['text'] = $_POST['loco_date_text'];
				}
				
				$this->db->insert("loco_unit_date", $data);
				return true;
			}
		}
		
		/**
		 * Register a hit against this loco
		 * @since Version 3.2
		 * @return boolean
		 */
		
		public function hit() {
			return false;
			
			if ($this->db instanceof \sql_db) {
				$dataArray['loco_id']	= $this->id;
				$dataArray['class_id'] 	= $this->Class->id;
				$dataArray['time']		= time(); 
				$dataArray['ip']		= $_SERVER['REMOTE_ADDR'];
				$dataArray['user_id']	= $_SESSION['user_id']; 
				
				$query = $this->db->buildQuery($dataArray, "loco_hits"); 
				
				if ($this->db->query($query)) {
					return true;
				} else {
					throw new Exception($this->db->error);
					return false;
				}
			} else {
				$data = array(
					"loco_id" => $this->id,
					"class_id" => $this->Class->id,
					"time" => time(),
					"ip" => $_SERVER['REMOTE_ADDR'],
					"user_id" => $_SESSION['user_id']
				);
				
				$this->db->insert("loco_hits", $data); 
				
				return true;
			}
		}
		
		/**
		 * Get link(s) of this loco
		 * @since Version 3.2
		 * @return array
		 */
		
		public function links() {
			if ($this->db instanceof \sql_db) {
				$query = "SELECT * FROM loco_link WHERE loco_id_a = '".$this->id."' OR loco_id_b = '".$this->id."'";
				
				if ($rs = $this->db->query($query)) {
					$return = array(); 
					
					while ($row = $rs->fetch_assoc()) {
						if ($row['loco_id_a'] == $this->id) {
							if ($row['link_type_id'] == RP_LOCO_RENUMBERED) {
								$return[$row['link_id']][$row['loco_id_b']] = "Renumbered to";
								#$return[$row['loco_id_b']] = "Renumbered to";
							} elseif ($row['link_type_id'] == RP_LOCO_REBUILT) {
								$return[$row['link_id']][$row['loco_id_b']] = "Rebuilt to";
								#$return[$row['loco_id_b']] = "Rebuilt to";
							}
						} else {
							if ($row['link_type_id'] == RP_LOCO_RENUMBERED) {
								$return[$row['link_id']][$row['loco_id_a']] = "Renumbered from";
							} elseif ($row['link_type_id'] == RP_LOCO_REBUILT) {
								$return[$row['link_id']][$row['loco_id_a']] = "Rebuilt from";
							}
						}
					}
					
					return $return;
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$query = "SELECT * FROM loco_link WHERE loco_id_a = ? OR loco_id_b = ?";
				$return = array();
				
				foreach ($this->db->fetchAll($query, array($this->id, $this->id)) as $row) {
					if ($row['loco_id_a'] == $this->id) {
						if ($row['link_type_id'] == RP_LOCO_RENUMBERED) {
							$return[$row['link_id']][$row['loco_id_b']] = "Renumbered to";
						} elseif ($row['link_type_id'] == RP_LOCO_REBUILT) {
							$return[$row['link_id']][$row['loco_id_b']] = "Rebuilt to";
						}
					} else {
						if ($row['link_type_id'] == RP_LOCO_RENUMBERED) {
							$return[$row['link_id']][$row['loco_id_a']] = "Renumbered from";
						} elseif ($row['link_type_id'] == RP_LOCO_REBUILT) {
							$return[$row['link_id']][$row['loco_id_a']] = "Rebuilt from";
						}
					}
				}
				
				return $return;
			}
		}
		
		/**
		 * Save a correction for this loco
		 * @since Version 3.2
		 * @param string $correction_text
		 * @param int $user_id
		 */
		
		public function newCorrection($correction_text = false, $user_id = false) {
			if (!$correction_text || !$user_id) {
				throw new Exception("Cannot save correction - both \$correction_text and \$user_id must be provided"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$dataArray = array(); 
				$dataArray['text'] 		= $this->db->real_escape_string($correction_text); 
				$dataArray['loco_id']	= $this->db->real_escape_string($this->id); 
				$dataArray['user_id']	= $this->db->real_escape_string($user_id);
				$dataArray['date']		= "NOW()"; 
				
				$query = $this->db->buildQuery($dataArray, "loco_unit_corrections"); 
				
				if ($this->db->query($query)) {
					return true;
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$data = array(
					"text" => $correction_text,
					"loco_id" => $this->id,
					"user_id" => $user_id,
					"date" => new \Zend_Db_Expr('NOW()')
				);
				
				$this->db->insert("loco_unit_corrections", $data); 
				return true;
			}
		}
		
		/**
		 * Get corrections for this loco
		 * @since Version 3.2
		 * @param boolean $active
		 * @return array
		 */
		
		public function corrections($active = true) {
			if ($active) {
				$active_sql = " AND c.status = 0 ";
			} else {
				$active_sql = "";
			}
			
			$query = "SELECT c.correction_id, c.user_id, UNIX_TIMESTAMP(c.date) as date, c.status, c.text , u.username
				FROM loco_unit_corrections AS c
				LEFT JOIN nuke_users AS u ON c.user_id = u.user_id
				WHERE c.loco_id = ? " . $active_sql;
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				$return[$row['correction_id']] = $row; 
			}
			
			return $return;
		}
		
		/**
		 * Get ratings for this loco
		 * @since Version 3.2
		 * @param boolean $detailed
		 * @return float
		 */
		
		public function getRating($detailed = false) {
			if (empty($this->id)) {
				throw new Exception("Cannot fetch rating - no loco ID given"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				if ($detailed) {
					$query = "SELECT AVG(rating) as dec_avg, COUNT(rating) AS number_votes, SUM(rating) AS total_points FROM rating_loco WHERE loco_id = ".$this->db->real_escape_string($this->id); 
					
					$row = array();
					
					$row['dec_avg'] = 0;
					$row['whole_avg'] = 0;
					$row['total_points'] = 0;
					$row['number_votes'] = 0;
					
					if ($rs = $this->db->query($query)) {
						if ($rs->num_rows == 1) {
							$row = $rs->fetch_assoc(); 	
					
							$row['dec_avg'] = empty($row['dec_avg']) ? 0 : $row['dec_avg'];
							$row['total_points'] = empty($row['total_points']) ? 0 : $row['total_points'];
							$row['number_votes'] = empty($row['number_votes']) ? 0 : $row['number_votes'];
							
							$row['whole_avg'] = round($row['dec_avg']);
						}
						
						return $row;
					} else {
						throw new Exception($this->db->error); 
						return false;
					}
				} else {
					$query = "SELECT AVG(rating) as average_rating FROM rating_loco WHERE loco_id = ".$this->db->real_escape_string($this->id); 
					
					if ($rs = $this->db->query($query)) {
						if ($rs->num_rows == 1) {
							$row = $rs->fetch_assoc(); 
							
							if ($row['average_rating']) {
								return $row['average_rating'];
							} else {
								return floatval("2.5");
							}
						} else {
							return floatval("2.5"); // Unrated; give it a 2.5 out of 5
						}
					} else {
						throw new Exception($this->db->error); 
						return false;
					}
				}
			} else {
				if ($detailed) {
					$query = "SELECT AVG(rating) as dec_avg, COUNT(rating) AS number_votes, SUM(rating) AS total_points FROM rating_loco WHERE loco_id = ?"; 
					
					$row = array();
					
					$row['dec_avg'] = 0;
					$row['whole_avg'] = 0;
					$row['total_points'] = 0;
					$row['number_votes'] = 0;
					
					$row = $this->db->fetchRow($query, $this->id); 
					
					$row['dec_avg'] = empty($row['dec_avg']) ? 0 : $row['dec_avg'];
					$row['total_points'] = empty($row['total_points']) ? 0 : $row['total_points'];
					$row['number_votes'] = empty($row['number_votes']) ? 0 : $row['number_votes'];
					$row['whole_avg'] = round($row['dec_avg']);
					
					return $row;
				} else {
					$query = "SELECT AVG(rating) as average_rating FROM rating_loco WHERE loco_id = ?"; 
					$row = $this->db->fetchRow($query, $this->id);
					
					if ($row['average_rating']) {
						return $row['average_rating'];
					} else {
						return floatval("2.5");
					}
				}
			}
		}
		
		/**
		 * Get this user's rating for this loco
		 * @since Version 3.2
		 * @param int $user_id
		 * @return float|boolean
		 */
		
		public function userRating($user_id = false) {
			if (!$user_id || empty($user_id)) {
				throw new Exception("Cannot fetch user rating for this loco - no user given"); 
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT rating FROM rating_loco WHERE user_id = ".$this->db->real_escape_string($user_id)." AND loco_id = ".$this->db->real_escape_string($this->id)." LIMIT 1"; 
				
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows > 0) {
						return true;
					} else {
						return false;
					}
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$query = "SELECT rating FROM rating_loco WHERE user_id = ? AND loco_id = ? LIMIT 1"; 
				
				$row = $this->db->fetchAll($query, array($user_id, $this->id)); 
				
				if (count($row)) {
					return true;
				} else {
					return false;
				}
			}
		}
		
		/**
		 * Set user rating for this loco
		 * @since Version 3.2
		 * @param int $user_id
		 * @param float $rating
		 * @return boolean
		 */
		 
		public function setRating($user_id = false, $rating = false) {
			if (!$user_id || empty($user_id)) {
				throw new Exception("Cannot set user rating for this loco - no user given"); 
			}
			
			if (!$rating || empty($rating)) {
				throw new Exception("Cannot set user rating for this loco - no rating given"); 
			}
			
			$rating = floatval($rating); 
			
			if ($this->db instanceof \sql_db) {
				$dataArray = array();
				$dataArray['loco_id']	= $this->db->real_escape_string($this->id);
				$dataArray['user_id']	= $this->db->real_escape_string($user_id);
				$dataArray['rating']	= $this->db->real_escape_string($rating);
				$dataArray['date']		= "NOW()";
				
				$query = $this->db->buildQuery($dataArray, "rating_loco");
				
				if ($this->userRating($user_id)) {
					$where = array();
					$where['user_id'] = $this->db->real_escape_string($user_id);
					$where['loco_id'] = $this->db->real_escape_string($this->id);
					
					$query = $this->db->buildQuery($dataArray, "rating_loco", $where);
				}
				
				if ($this->db->query($query)) {
					return true;
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$data = array(
					"loco_id" => $this->id,
					"user_id" => $user_id,
					"rating" => $rating,
					"date" => new \Zend_Db_Expr('NOW()')
				);
				
				if ($this->userRating($user_id)) {
					$where = array(
						"user_id = ?" => $user_id,
						"loco_id = ?" => $this->id
					);
					
					$this->db->update("rating_loco", $data, $where);
				} else {
					$this->db->insert("rating_loco", $data);
				}
				
				return true;
			}
		}
		
		/**
		 * Get liveries carried by this loco
		 * Based on tagged Flickr photos
		 * @since Version 3.2
		 * @param object $f
		 * @return array|boolean
		 */
		
		public function getLiveries($f = false) {
			if (is_object($f)) {
				$mckey = "railpage:locos.liveries.loco_id=" . $this->id; 
				
				if ($result = $this->Memcached->fetch($mckey)) {
					return $result;
				} else {
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
					
					if ($memcache) {
						$memcache->delete($mckey);
						$memcache->set($mckey, $tags, strtotime("+1 day")); // Expire in one day
					}
				}
				
				if (count($tags)) {
					return $tags;
				} else {
					return false;
				}
			} else {
				$query = "SELECT l.livery_id AS id, l.livery AS name, l.photo_id FROM loco_livery AS l LEFT JOIN loco_unit_livery AS ul ON l.livery_id = ul.livery_id WHERE ul.ignored = ? AND ul.loco_id = ? GROUP BY l.livery_id ORDER BY l.livery";
				$return = array();
				
				foreach ($this->db->fetchAll($query, array("0", $this->id)) as $row) {
					$Livery = new Livery($row['id']);
					
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
		 * Get organisation links by the given type
		 * @since Version 3.4
		 * @param int $org_type
		 * @param int $limit
		 * @return array
		 */
		
		public function getOrganisations($org_type = false, $limit = false) {
			if ($limit) {
				$limit_sql = "LIMIT 0, 1"; 
			} else {
				$limit_sql = NULL;
			}
			
			if ($this->db instanceof \sql_db) {
				$org_sql = $org_type !== false ? " AND ot.id = '".$this->db->real_escape_string($org_type)."'" : NULL;
				
				$query = "SELECT o.*, op.operator_id AS organisation_id, op.operator_name AS organisation_name FROM loco_org_link AS o LEFT JOIN loco_org_link_type AS ot ON ot.id = o.link_type LEFT JOIN operators AS op ON op.operator_id = o.operator_id WHERE o.loco_id = '".$this->db->real_escape_string($this->id)."' " . $org_sql . " ORDER BY ot.id, o.link_weight DESC ".$limit_sql.""; 
				
				if ($rs = $this->db->query($query)) {
					$return = array(); 
					
					while ($row = $rs->fetch_assoc()) {
						$return[] = $row; 
					}
					
					return $return;
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$params = array($this->id); 
				
				if ($org_type !== false) {
					$org_sql = " AND ot.id = ?";
					$params[] = $org_type;
				} else {
					$org_sql = "";
				}
				
				$query = "SELECT o.*, op.operator_id AS organisation_id, op.operator_name AS organisation_name FROM loco_org_link AS o LEFT JOIN loco_org_link_type AS ot ON ot.id = o.link_type LEFT JOIN operators AS op ON op.operator_id = o.operator_id WHERE o.loco_id = ? " . $org_sql . " ORDER BY ot.id, o.link_weight DESC ".$limit_sql.""; 
				
				$return = $this->db->fetchAll($query, $params);
				return $return;
			}
		}
		
		/**
		 * Add an organisation link
		 * @since Version 3.4
		 * @param int $org_id
		 * @param int $org_type
		 * @param int $date
		 * @param int $weight
		 */
		
		public function addOrganisation($org_id = false, $org_type = false, $date = false, $weight = 0) {
			if (!$org_id) {
				throw new Exception("Could not add new organisation link - no org_id given"); 
				return false;
			}
			
			if (!$org_type) {
				throw new Exception("Could not add new organisation link - no org_type_id given"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$dataArray = array(); 
				$dataArray['loco_id'] = $this->id; 
				$dataArray['operator_id'] = $this->db->real_escape_string($org_id); 
				$dataArray['link_type'] = $this->db->real_escape_string($org_type); 
				$dataArray['link_weight'] = $this->db->real_escape_string($weight); 
				
				if ($date && !empty($date)) {
					$timestamp = strtotime($date); 
					
					$dataArray['link_date'] = date("Y-m-d H:i:s", $timestamp);
				}
				
				$query = $this->db->buildQuery($dataArray, "loco_org_link"); 
				
				if ($this->db->query($query)) {
					return true; 
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$data = array(
					"loco_id" => $this->id,
					"operator_id" => $org_id,
					"link_type" => $org_type,
					"link_weight" => $weight
				);
				
				if ($date && !empty($date)) {
					$timestamp = strtotime($date); 
					
					$data['link_date'] = date("Y-m-d H:i:s", $timestamp);
				}
				
				return $this->db->insert("loco_org_link", $data);
			}
		}
		
		/**
		 * Delete an organisation link
		 * @since Version 3.4
		 * @param int $org_link_id
		 * @return boolean
		 */
		
		public function deleteOrgLink($org_link_id = false) {
			if (!$org_link_id) {
				throw new Exception("Could not delete org link - no org_link_id specified"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "DELETE FROM loco_org_link WHERE id = '".$this->db->real_escape_string($org_link_id)."'"; 
				
				if ($this->db->query($query)) {
					return true; 
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$where = array("id = ?" => $org_link_id); 
				
				$this->db->delete("loco_org_link", $where);
				
				return true;
			}
		}
		
		/**
		 * Change the order of an org link
		 * @since Version 3.4
		 * @param int $org_link_id
		 * @param string $direction
		 * @return boolean
		 */
		
		public function changeOrgLinkWeight($org_link_id = false, $direction = false) {
			throw new Exception(__CLASS__ . "::" . __METHOD__ . " is deprecated");
			
			if (!$org_link_id) {
				throw new Exception("Could not set org link weight - no org link ID given"); 
				return false;
			} 
			
			if (!$direction) {
				throw new Exception("Could not set org link weight - no direction given"); 
				return false;
			}
			
			$query = "SELECT * FROM loco_org_link WHERE id = '".$this->db->real_escape_string($org_link_id)."'"; 
			
			if ($rs = $this->db->query($query)) {
				if ($rs->num_rows == 1) {
					$row = $rs->fetch_assoc(); 
					$current_weight = $row['link_weight']; 
					
					$loco_id = $row['loco_id']; 
					$link_type = $row['link_type']; 
					
					// Get the other links that match the above criteria
					$query = "SELECT * FROM loco_org_link WHERE loco_id = '".$this->db->real_escape_string($loco_id)."' AND link_type = '".$this->db->real_escape_string($link_type)."' ORDER BY link_weight DESC"; 
					
					if ($rs = $this->db->query($query)) {
						$links = array(); 
						
						$noweight = true;
						
						while ($row = $rs->fetch_assoc()) {
							$links[] = $row; 
						}
						
						foreach ($links as $id => $row) {
							if ($row['link_weight'] > 0) {
								$noweight = false;
								break;
							}
						}
						
						if ($noweight) {
							$weight = 1; 
						} else {
							$prevweight = 0; 
							
							foreach ($links as $id => $row) {
								if ($row['id'] != $org_link_id) {
									$prevweight = $row['link_weight']; 
								}
								
								if ($row['id'] == $org_link_id) {
									break;
								}
							}
							
							if ($direction == "up" || $direction == "UP") {
								$weight = $prevweight + 1; 
							} else {
								$weight = $current_weight - 1;
							}
						}
						
						$dataArray = array(); 
						$dataArray['link_weight'] = $weight; 
						
						$where = array(); 
						$where['id'] = $org_link_id; 
						
						$query = $this->db->buildQuery($dataArray, "loco_org_link", $where);
						
						if ($direction == "down") {
							#printArray($query);die;
						}
						
						#printArray($query);die;
						
						if ($this->db->query($query)) {
							return true;
						} else {
							throw new Exception($this->db->query); 
							return false;
						}
					} else {
						throw new Exception($this->db->error); 
						return false;
					}
				} else {
					throw new Exception("Could not set org link weight - no match found for ID ".$org_link_id); 
				}
			} else {
				throw new Exception($this->db->error); 
				return false;
			}
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
			
			$Event = new \Railpage\SiteEvent; 
			$Event->user_id = $user_id; 
			$Event->title = $title;
			$Event->args = $args; 
			$Event->key = "loco_id";
			$Event->value = $this->id;
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
				$query = "SELECT ll.*, u.username FROM log_locos AS ll LEFT JOIN nuke_users AS u ON ll.user_id = u.user_id WHERE ll.loco_id = '".$this->db->real_escape_string($this->id)."' ORDER BY timestamp DESC"; 
				
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
				$query = "SELECT ll.*, u.username FROM log_locos AS ll LEFT JOIN nuke_users AS u ON ll.user_id = u.user_id WHERE ll.loco_id = ? ORDER BY timestamp DESC"; 
				
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
		 * Get a locomotive org link
		 * @since Version 3.5
		 * @param int $id
		 * @return array
		 */
		
		public function getOrgLink($id = false) {
			if (!$id) {
				throw new Exception("Cannot fetch organisation link - no org link ID given"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT o.*, ot.name AS link_type_name, op.operator_name FROM loco_org_link AS o LEFT JOIN loco_org_link_type AS ot ON o.link_type = ot.id LEFT JOIN operators AS op ON op.operator_id = o.operator_id WHERE o.id = '".$this->db->real_escape_string($id)."'"; 
				
				if ($rs = $this->db->query($query)) {
					return $rs->fetch_assoc(); 
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				$query = "SELECT o.*, ot.name AS link_type_name, op.operator_name FROM loco_org_link AS o LEFT JOIN loco_org_link_type AS ot ON o.link_type = ot.id LEFT JOIN operators AS op ON op.operator_id = o.operator_id WHERE o.id = ?"; 
				
				return $this->db->fetchRow($query, $id);
			}
		}
		
		/**
		 * Loco sightings
		 * @since Version 3.5
		 * @return array
		 */
		
		public function sightings() {
			$Sightings = new \Railpage\Sightings\Base;
			
			return $Sightings->findLoco($this->id); 
		}
		
		/**
		 * Get contributors of this locomotive
		 * @since Version 3.7.5
		 * @return array
		 */
		
		public function getContributors() {
			$return = array(); 
			
			$query = "SELECT DISTINCT l.user_id, u.username FROM log_general AS l LEFT JOIN nuke_users AS u ON u.user_id = l.user_id WHERE l.module = ? AND l.key = ? AND l.value = ?";
			
			foreach ($this->db->fetchAll($query, array("locos", "loco_id", $this->id)) as $row) {
				$return[$row['user_id']] = $row['username']; 
			}
			
			return $return;
		}
		
		/**
		 * Return an array of tags appliccable to this loco
		 * @since Version 3.7.5
		 * @return array
		 */
		
		public function getTags() {
			$tags = $this->Class->getTags(); 
			$tags[] = "railpage:loco=" . $this->number;
			$tags[] = $this->flickr_tag;
			$tags[] = $this->number;
			
			asort($tags);
			
			return $tags;
		}
		
		/**
		 * Add an asset to this locomotive
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
			$data['namespace'] = "railpage.locos.loco";
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
		 * Get next locomotive
		 * @since Version 3.8.7
		 * @return \Railpage\Locos\Locomotive
		 */
		
		public function next() {
			$members = $this->Class->members(); 
			
			if ($members['stat'] == "ok") {
				// Get the previous loco in this class
				
				$break = false;
				
				foreach ($members['locos'] as $row) {
					if ($break == true) {
						return new Locomotive($row['loco_id']);
					}
					
					if ($row['loco_id'] == $this->id) {
						$break = true;
					}
				}
			}
		}
		
		/**
		 * Get previous locomotive
		 * @since Version 3.8.7
		 * @return \Railpage\Locos\Locomotive
		 */
		
		public function previous() {
			$members = $this->Class->members(); 
			
			// Get the next loco in this class
			if ($members['stat'] == "ok") {
				
				$break = false;
				
				$members['locos'] = array_reverse($members['locos']);
				foreach ($members['locos'] as $row) {
					if ($break == true) {
						return new Locomotive($row['loco_id']);
					}
					
					if ($row['loco_id'] == $this->id) {
						$break = true;
					}
				}
			}
		}
		
		/**
		 * Set the cover photo for this locomotive
		 * @since Version 3.8.7
		 * @param $Image Either an instance of \Railpage\Images\Image or \Railpage\Assets\Asset
		 * @return $this
		 */
		
		public function setCoverImage($Image) {
			
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
				$this->photo_id = $Image->photo_id;
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
				"url" => $Image->url instanceof Url ? $Image->url->getURLs() : $Image->url
			);
			
			$this->commit(); 
			
			return $this;
		}
		
		/**
		 * Get the cover photo for this locomotive
		 * @since Version 3.8.7
		 * @return array
		 * @todo Set the AssetProvider (requires creating AssetProvider)
		 */
		
		public function getCoverImage() {
			
			/**
			 * Image stored in meta data
			 */
			
			if (isset($this->meta['coverimage'])) {
				$Image = new Image($this->meta['coverimage']['id']);
				return array(
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
			}
			
			/**
			 * Asset
			 */
			
			if ($this->Asset instanceof Asset) {
				return array(
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
			}
			
			/**
			 * Ordinary Flickr image
			 */
			
			if (filter_var($this->photo_id, FILTER_VALIDATE_INT) && $this->photo_id > 0) {
				$Images = new Images;
				$Image = $Images->findImage("flickr", $this->photo_id);
				
				return array(
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
			}
			
			/**
			 * No cover image!
			 */
			
			return false;
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
			
			if (filter_var($this->photo_id, FILTER_VALIDATE_INT) && $this->photo_id > 0) {
				return true;
			}
			
			/**
			 * No cover image!
			 */
			
			return false;
		}
		
		/**
		 * Get locomotive data as an associative array
		 * @since Version 3.9
		 * @return array
		 */
		
		public function getArray() {
			return array(
				"id" => $this->id,
				"number" => $this->number,
				"name" => $this->name,
				"gauge" => $this->gauge,
				"status" => array(
					"id" => $this->status_id,
					"text" => $this->status
				),
				"manufacturer" => array(
					"id" => $this->manufacturer_id,
					"text" => $this->manufacturer
				),
				"class" => $this->Class->getArray(),
				"url" => $this->url->getURLs()
			);
		}
	}
?>