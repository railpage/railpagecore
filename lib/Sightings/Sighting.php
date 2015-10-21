<?php
	/**
	 * Sightings module
	 * @since Version 3.2
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Sightings;
	
	use Railpage\AppCore;
	use Railpage\Debug;
	use Railpage\Url;
	use Railpage\Place;
	use Exception;
	use InvalidArgumentException;
	use DateTime;
	use DateTimeZone;
	use Railpage\ContentUtility;
	
	/**
	 * Sightings class
	 * @since Version 3.2
	 */
	
	class Sighting extends AppCore {
		
		/**
		 * ID
		 * @since Version 3.2
		 * @var int $id
		 */
		
		public $id;
		
		/** 
		 * Latitude
		 * @since Version 3.2
		 * @var float $lat
		 */
		
		public $lat;
		
		/**
		 * Longitude
		 * @since Version 3.2
		 * @var float $lon
		 */
		
		public $lon;
		
		/**
		 * User ID
		 * @since Version 3.2
		 * @var int $user_id
		 */
		
		public $user_id;
		
		/**
		 * Date of sighting
		 * @since Version 3.2
		 * @var object $date
		 */
		
		public $date;
		
		/**
		 * Date added
		 * @since Version 3.2
		 * @var object $date_added
		 */
		
		public $date_added;
		
		/**
		 * Extra descriptive text
		 * @since Version 3.2
		 * @var string $text
		 */
		
		public $text;
		
		/**
		 * Train number
		 * @since Version 3.5
		 * @var string $train_number
		 */
		
		public $train_number;
		
		/**
		 * Loco IDs
		 * @since Version 3.5
		 * @var array $loco_ids
		 */
		
		public $loco_ids;
		
		/**
		 * Timezone
		 * @since Version 3.5
		 * @var string $timezone
		 */
		
		public $timezone;
		
		/**
		 * Constructor
		 * @since Version 3.2
		 * @param object $db
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			
			parent::__construct(); 
			
			if ($id) {
				$this->id = $id;
				
				$this->fetch(); 
			}
		}
		
		/** 
		 * Populate this object with pre-existing data
		 * @since Version 3.2
		 * @return boolean
		 */
		
		public function fetch() {
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot fetch sighting - no ID given"); 
			}
			
			$mckey = sprintf("railpage:sighting=%d", $this->id); 
			
			if (!$row = $this->Memcached->fetch($mckey)) {
			
				$query = "SELECT s.id, s.date, s.date_added, s.lat, s.lon, s.text, s.user_id, s.timezone, u.username, s.loco_ids, s.meta
					FROM sighting AS s
					LEFT JOIN nuke_users AS u ON s.user_id = u.user_id
					WHERE s.id = ?";
				
				$row = $this->db->fetchRow($query, $this->id); 
				
				$this->Memcached->save($mckey, $row, 0);
			}
			
			if (!isset($row) || !is_array($row)) {
				return false;
			}
			
			$this->lat			= $row['lat'];
			$this->lon			= $row['lon'];
			$this->text			= $row['text'];
			$this->user_id		= $row['user_id'];
			$this->username		= $row['username'];
			$this->timezone		= $row['timezone'];
			$this->meta = json_decode($row['meta'], true);
			$this->loco_ids = json_decode($row['loco_ids'], true);
			 
			$this->date 		= new DateTime($row['date'], new DateTimeZone($this->timezone));
			$this->date_added	= new DateTime($row['date_added'], new DateTimeZone($this->timezone));
			
			$this->url = new Url("/sightings/view/" . $this->id); 
			
			$this->Place = Place::Factory($this->lat, $this->lon);
			
			return true;
		}
		
		/**
		 * Get this sighting as an associative array
		 * @since Version 3.10.0
		 * @return array
		 */
		
		public function getArray() {
			
			$array = array(
				"id" => $this->id,
				"lat" => $this->lat,
				"lon" => $this->lon,
				"text" => $this->text,
				"author" => array(
					"id" => $this->user_id,
					"username" => $this->username
				),
				"timezone" => $this->timezone,
				"date" => array(
					"added" => array(
						"absolute" => $this->date_added->format("Y-m-d H:i:s"),
						"relative" => ContentUtility::relativeTime($this->date_added),
					),
					"seen" => array(
						"absolute" => $this->date->format("Y-m-d H:i:s"),
						"relative" => ContentUtility::relativeTime($this->date),
					),
				),
				"loco_ids" => $this->loco_ids,
				"meta" => $this->meta,
				"url" => $this->url->getURLs(),
				"place" => $this->Place->getArray()
			);
			
			return $array;
			
		}
		
		/**
		 * Validate changes to a sighting
		 * @since Version 3.5
		 * @return boolean
		 */
		
		public function validate() {
			if (empty($this->lat)) {
				throw new Exception("Cannot validate sighting - latitude cannot be empty"); 
				return false;
			}
			
			if (empty($this->lon)) {
				throw new Exception("Cannot validate sighting - longitude cannot be empty"); 
				return false;
			}
			
			if (empty($this->user_id)) {
				throw new Exception("Cannot validate sighting - user_id cannot be empty"); 
				return false;
			}
			
			if (empty($this->loco_ids)) {
				throw new Exception("Cannot validate sighting - loco_ids cannot be empty"); 
				return false;
			}
			
			if (is_null($this->text)) {
				$this->text = "";
			}
			
			if (is_null($this->train_number)) {
				$this->train_number = "";
			}
			
			return true;
		}
		
		/**
		 * Commit changes to a sighting
		 * @since Version 3.5
		 */
		
		public function commit() {
			
			$this->validate(); 
			
			
			$data = array(
				"date" => $this->date->format("Y-m-d H:i:s"),
				"date_added" => $this->date_added->format("Y-m-d H:i:s"),
				"lat" => $this->lat,
				"lon" => $this->lon,
				"text" => $this->text,
				"traincode" => $this->train_number,
				"user_id" => $this->user_id,
				"timezone" => $this->timezone,
				"loco_ids" => implode(",", array_map('intval', $this->loco_ids))
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("sighting", $data, $where); 
			} else {
				$this->db->insert("sighting", $data);
				$this->id = $this->db->lastInsertId(); 
			}
			
			/*
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"sighting_id = ?" => $this->id
				);
				
				$this->db->delete("sighting_locos", $where); 
				
				foreach ($this->loco_ids as $loco_id) {
					$data = array(
						"sighting_id" => $this->id,
						"loco_id" => $loco_id
					);
					
					$this->db->insert("sighting_locos", $data); 
				}
			}
			*/
			
			return true;
		}
		
		/**
		 * Get locos tagged in this sighting
		 * @since Version 3.5
		 * @return array
		 */
		
		public function locos() {
			$query = "SELECT l.loco_num, l.loco_id, l.photo_id AS loco_photo_id, l.loco_gauge_id, c.name AS class_name, c.id AS class_id, c.slug AS class_slug,
							CONCAT(g.gauge_name, ' ', g.gauge_imperial, ' (', g.gauge_metric, ')') AS loco_gauge,
							t.title AS class_type, t.id AS class_type_id,
							s.name AS loco_status, l.loco_status_id
						FROM loco_unit AS l 
						LEFT JOIN loco_class AS c ON l.class_id = c.id
						LEFT JOIN loco_gauge AS g ON l.loco_gauge_id = g.gauge_id
						LEFT JOIN loco_type AS t ON t.id = c.loco_type_id
						LEFT JOIN loco_status AS s ON s.id = l.loco_status_id
						WHERE l.loco_id IN (" . implode(", ", $this->loco_ids) . ")
						ORDER BY l.loco_num";
				
			$return = array(); 
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				$row['loco_url'] = sprintf("/locos/%s/%s", $row['class_slug'], $row['loco_num']);
				
				$return[$row['loco_id']] = $row; 
			}
			
			return $return;
		}
		
		/**
		 * Delete this sighting
		 * @since Version 3.5
		 * @return boolean
		 */
		
		public function delete() {
			if (!$this->id) {
				throw new Exception("Cannot delete sighting - sighting has no ID (is it a new sighting?)");
				return false;
			}
			
			$where = array(
				"sighting_id = ?" => $this->id
			);
			
			$this->db->delete("sighting_locos", $where); 
			
			$where = array(
				"id = ?" => $this->id
			);
			
			$this->db->delete("sighting", $where);
			
			return true;
		}
	}


