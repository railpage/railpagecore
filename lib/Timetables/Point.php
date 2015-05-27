<?php
	/**
	 * Timetabling point
	 * @since Version 3.9
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Timetables;
	
	use Exception;
	use DateTime;
	use DateInterval;
	use Railpage\AppCore;
	use Railpage\Module;
	use Railpage\Url;
	use Railpage\Place;
	use Railpage\Locations\Locations;
	use Railpage\Locations\Location;
	use Railpage\Locos\LocoClass;
	use Railpage\Locos\Locomotive;
	use Railpage\Organisations\Organisation;
	
	/**
	 * Point
	 */
	
	class Point extends Timetables {
		
		/**
		 * Timetable point ID
		 * @since Version 3.9
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Latitude
		 * @since Version 3.9
		 * @var float $lat
		 */
		
		public $lat;
		
		/**
		 * Longitude
		 * @since Version 3.9
		 * @var float $lon
		 */
		
		public $lon;
		
		/**
		 * Name of this point
		 * @since Version 3.9
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * URL Slug
		 * @since Version 3.9
		 * @var string $slug
		 */
		
		private $slug;
		
		/**
		 * Constructor
		 * @since Version 3.9
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			parent::__construct(); 
			
			if (is_string($id)) {
				$query = "SELECT id FROM timetable_points WHERE name = ? OR slug = ?"; 
				
				if ($tmpid = $this->db->fetchOne($query, array($id, $id))) {
					$id = $tmpid;
				} else {
					$this->name = $id;
					$this->commit();
				}
			}
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->mckey = sprintf("railpage:timetable.point=%d", $id);
				
				if (!$row = getMemcacheObject($this->mckey)) {
					$query = "SELECT * FROM timetable_points WHERE id = ?";
					
					$row = $this->db->fetchRow($query, $id);
					
					setMemcacheObject($this->mckey, $row);
				}
			}
			
			if (isset($row) && count($row)) {
				$this->id = $row['id'];
				$this->lat = $row['lat'];
				$this->lon = $row['lon'];
				$this->name = $row['name'];
				$this->slug = isset($row['slug']) ? $row['slug'] : "";
				
				if (!isset($row['slug']) || empty($row['slug']) || substr($row['slug'], -1, 1) == "1") {
					$this->createSlug(); 
					$this->commit();
				}
				
				$this->url = new Url(sprintf("%s/p/%s", $this->Module->url, $this->slug));
				$this->url->edit = sprintf("%s?mode=point.edit", $this->url->url);
			}
		}
		
		/**
		 * Validate changes to this timetabling point
		 * @since Version 3.9
		 * @return boolean
		 * @throws \Exception if $this->name is empty
		 */
		
		private function validate() {
			if (empty($this->name)) {
				throw new Exception("Timetabling point must have a name");
			}
			
			if (empty($this->lat)) {
				$this->lat = 0;
			}
			
			if (empty($this->lon)) {
				$this->lon = 0;
			}
			
			if (empty($this->slug)) {
				$this->createSlug(); 
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this timetabling point
		 * @since Version 3.9
		 * @return $this
		 */
		
		public function commit() {
			$this->validate();
			
			if (isset($this->mckey)) {
				deleteMemcacheObject($this->mckey);
			}
			
			$data = array(
				"name" => $this->name,
				"lat" => $this->lat,
				"lon" => $this->lon,
				"slug" => $this->slug
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("timetable_points", $data, $where);
			} else {
				$this->db->insert("timetable_points", $data);
				$this->id = $this->db->lastInsertId(); 
			}
			
			return $this;
		}
		
		/**
		 * Create a URL slug
		 * @since Version 3.9
		 */
		
		private function createSlug() {
			$proposal = create_slug($this->name);
			
			$result = $this->db->fetchAll("SELECT id FROM timetable_points WHERE slug = ?", $proposal); 
			
			if (count($result)) {
				$proposal .= count($result);
			}
			
			$this->slug = $proposal;
		}
		
		/**
		 * Get trains at this location
		 * @since Version 3.9
		 * @param \DateTime $from
		 * @param \DateTime $to
		 * @return array
		 */
		
		public function getTrains($from = false, $to = false) {
			if (!$from) {
				$from = new DateTime;
			} 
			
			if (!$to) {
				$to = new DateTime; 
				$to->add(new DateInterval("PT120M"));
			}
			
			$query = "SELECT t.id, e.day, e.time, e.going FROM timetable_trains AS t
						LEFT JOIN timetable_entries AS e ON e.train_id = t.id
						WHERE e.day = ? AND e.time >= ? 
						AND e.day <= ? AND e.time <= ?
						AND e.point_id = ?
						ORDER BY e.day, e.time";
			
			$where = array(
				$from->format("N"), 
				$from->format("H:i:s"),
				$to->format("N"),
				$to->format("H:i:s"),
				$this->id
			);
			
			$return = array();
			
			foreach ($this->db->fetchAll($query, $where) as $row) {
				$Train = new Train($row['id']);
				$return[] = array(
					"time" => array(
						"day" => $row['day'],
						"time" => $row['time'],
						"going" => $row['going']
					), 
					"train" => array(
						"id" => $Train->id,
						"provider" => $Train->provider,
						"number" => $Train->number,
						"url" => $Train->url->getURLs(),
						"meta" => $Train->meta
					)
				);
			}
			
			return $return;
		}
	}
	