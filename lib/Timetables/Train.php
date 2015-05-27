<?php
	/**
	 * Timetabled train
	 * @since Version 3.9
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Timetables;
	
	use Exception;
	use DateTime;
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
	 * Timetabled train
	 */
	
	class Train extends Timetables {
		
		/**
		 * Commodity: Unknown
		 * @since Version 3.9
		 * @const string COMMODITY_UNKNOWN
		 */
		
		const COMMODITY_UNKNOWN = 0;
		
		/**
		 * Commodity: Light engine
		 * @since Version 3.9
		 * @const string COMMODITY_LIGHTENGINE
		 */
		
		const COMMODITY_LIGHTENGINE = 1;
		
		/**
		 * Commodity: Intermodal freight
		 * @since Version 3.9
		 * @const string COMMODITY_INTERMODAL
		 */
		
		const COMMODITY_INTERMODAL = 2;
		
		/**
		 * Commodity: Steel
		 * @since Version 3.9
		 * @const string COMMODITY_STEEL
		 */
		
		const COMMODITY_STEEL = 3;
		
		/**
		 * Commodity: Passengers
		 * @since Version 3.9
		 * @const string COMMODITY_PASS
		 */
		
		const COMMODITY_PASS = 4;
		
		/**
		 * Commodity: General freight
		 * @since Version 3.9
		 * @const string COMMODITY_GENFREIGHT
		 */
		
		const COMMODITY_GENFREIGHT = 5;
		
		/**
		 * Commodity: Grain
		 * @since Version 3.9
		 * @const string COMMODITY_GRAIN
		 */
		
		const COMMODITY_GRAIN = 6;
		
		/**
		 * Database ID for this train
		 * @since Version 3.9
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Train name/number
		 * @since Verison 3.9
		 * @var string $number
		 */
		
		public $number;
		
		/**
		 * Metadata
		 * @since Version 3.9
		 * @var array $meta
		 */
		
		public $meta;
		
		/**
		 * Timetable provider
		 * @since Version 3.9
		 * @var string $provider
		 */
		
		public $provider;
		
		/**
		 * Commodity
		 * @since Version 3.9
		 * @var string $commodity
		 */
		
		public $commodity;
		
		/**
		 * Organisation
		 * @since Version 3.9
		 * @param \Railpage\Organisations\Organisation
		 */
		
		public $Organisation;
		
		/**
		 * Constructor
		 * @since Version 3.9
		 * @param int $id The database ID of this train
		 * @param string $provider The source of this timetable (default is "artc")
		 */
		
		public function __construct($id = false, $provider = "artc") {
			parent::__construct(); 
			
			if ($id != false && $provider == "artc") {
				$query = "SELECT id FROM timetable_trains WHERE train_number LIKE ? AND provider = ?";
				$id = $this->db->fetchOne($query, array("%" . $id . "%", $provider)); 
			}
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->mckey = sprintf("railpage:timetable.train=%d", $id);
				if (!$row = getMemcacheObject($this->mckey)) {
					$query = "SELECT * FROM timetable_trains WHERE id = ?";
					$row = $this->db->fetchRow($query, $id);
					
					setMemcacheObject($this->mckey, $row);
				}
			}
			
			if (isset($row)) {
				$this->id = $row['id'];
				$this->number = $row['train_number'];
				$this->meta = json_decode($row['meta'], true);
				$this->provider = $row['provider'];
				$this->commodity = $row['commodity'];
				
				if ($row['operator_id'] > 0) {
					$this->Organisation = new Organisation($row['operator_id']);
				}
				
				$this->url = new Url(sprintf("%s/t/%s/%s", $this->Module->url, $this->provider, strtolower($this->number)));
				$this->url->edit = sprintf("%s?mode=edit", $this->url->url);
			}
		}
		
		/**
		 * Validate changes to this train
		 * @since Version 3.9
		 * @return boolean
		 * @throws \Exception if $this->number is empty
		 * @throws \Exception if $this->provider is empty
		 */
		
		private function validate() {
			if (empty($this->number)) {
				throw new Exception("Train number cannot be empty");
			}
			
			if (empty($this->provider)) {
				throw new Exception("Train timetable provider cannot be empty");
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this timetabled train
		 * @since Version 3.9
		 * @return \Railpage\Timetables\Train
		 */
		
		public function commit() {
			$this->validate(); 
			
			if (isset($this->mckey)) {
				deleteMemcacheObject($this->mckey);
			}
			
			$data = array(
				"provider" => $this->provider,
				"train_number" => $this->number,
				"train_name" => "",
				"train_desc" => "",
				"gauge_id" => 0,
				"meta" => json_encode($this->meta),
				"commodity" => $this->commodity
			);
			
			if ($this->Organisation instanceof Organisation) {
				$data['operator_id'] = $this->Organisation->id;
			}
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("timetable_trains", $data, $where);
			} else {
				$this->db->insert("timetable_trains", $data);
				$this->id = $this->db->lastInsertId(); 
			}
			
			return $this;
		}
		
		/**
		 * Add a timing for this train
		 * @since Version 3.9
		 * @return \Railpage\Timetables\Train
		 * @param int|string $day Day of the week, in either integer (1) or string (Monday) format
		 * @param string $time Time of day this timing occurs
		 * @param string $going Direction of travel: "arr" or "dep"
		 * @param \DateTime $commencing DateTime instance that this timing is valid from
		 * @param \DateTime $expiring DateTime instance that this timing is valid until
		 */
		
		public function addTiming($day = false, $time = false, $going = "arr", $commencing = false, $expiring = false) {
			if (!$this->Point instanceof Point) {
				throw new Exception("Cannot add a timing because no valid timetable point has been set");
			}
			
			if (!$day || empty($day)) {
				throw new Exception("Cannot add a timing because no valid day of week has been set");
			}
			
			if (!$time || empty($time)) {
				throw new Exception("Cannot add a timing because no valid time of day has been set");
			}
			
			$going = strtolower($going);
			
			if ($going == "arriving") {
				$going = "arr";
			}
			
			if ($going == "departing") {
				$going = "dep";
			}
			
			if (!is_int($day)) {
				$day = date("N", strtotime($day));
			}
			
			$data = array(
				"train_id" => $this->id,
				"point_id" => $this->Point->id,
				"day" => $day,
				"time" => $time,
				"going" => $going,
			);
			
			$where = array_values($data);
			
			if ($commencing instanceof DateTime) {
				$data['starts'] = $commencing->format("Y-m-d H:i:s");
			}
			
			if ($expiring instanceof DateTime) {
				$data['expires'] = $expiring->format("Y-m-d H:i:s");
			}
			
			$query = "SELECT id FROM timetable_entries WHERE train_id = ? AND point_id = ? AND day = ? AND time = ? AND going = ?";
			
			if (!$id = $this->db->fetchOne($query, $where)) {
				$this->db->insert("timetable_entries", $data);
			}
			
			return $this;
		}
		
		/**
		 * Get timings for this train
		 * @since Version 3.9
		 * @return array
		 */
		
		public function getTimings() {
			$query = "SELECT e.day, e.time, e.going, e.point_id, p.name AS point_name, p.lat AS point_lat, p.lon AS point_lon 
						FROM timetable_entries AS e
						LEFT JOIN timetable_points AS p ON e.point_id = p.id
						WHERE e.train_id = ?
						ORDER BY e.day, e.time";
			
			$timings = array(); 
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				$dow = $row['day'];
				$day = date('l', strtotime("Sunday +{$dow} days"));
				
				if (!isset($timings[$day])) {
					$timings[$day] = array(); 
				}
				
				$Point = new Point($row['point_id']);
				
				$row['point'] = array(
					"id" => $Point->id,
					"name" => $Point->name,
					"url" => $Point->url->getURLs()
				);
				
				$timings[$day][] = $row;
			}
			
			return $timings;
		}
		
		/**
		 * Set the commodity that this train hauls
		 * @since Version 3.9
		 * @param string $commodity Commodity (eg intermodal, pass, train) that this train carries
		 * @return \Railpage\Timetables\Train
		 */
		
		public function setCommodity($commodity) {
			switch (strtolower($commodity)) {
				case "intermodal" :
					$this->commodity = self::COMMODITY_INTERMODAL;
					break;
					
				case "intermoda" :
					$this->commodity = self::COMMODITY_INTERMODAL;
					break;
					
				case "lighteng" :
					$this->commodity = self::COMMODITY_LIGHTENGINE;
					break;
					
				case "cnypass" :
					$this->commodity = self::COMMODITY_PASS;
					break;
					
				case "vlppass" :
					$this->commodity = self::COMMODITY_PASS;
					break;
					
				case "gsr-pass" :
					$this->commodity = self::COMMODITY_PASS;
					break;
					
				case "steel" :
					$this->commodity = self::COMMODITY_STEEL;
					break;
					
				case "genfrgt" :
					$this->commodity = self::COMMODITY_GENFREIGHT;
					break;
					
				case "grainmt" :
					$this->commodity = self::COMMODITY_GRAIN;
					break;
					
				case "grainld" :
					$this->commodity = self::COMMODITY_GRAIN;
					break;
					
				default :
					$this->commodity = self::COMMODITY_UNKNOWN;
					break;
					
			}
			
			return $this;
		}
	}
	