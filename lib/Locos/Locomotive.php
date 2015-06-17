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
	use Railpage\ContentUtility;
	use Railpage\Url;
	use Railpage\Debug;
	use DateTime;
	use Exception;
	use stdClass;
	use Railpage\Registry;
	use Railpage\Users\Utility\AvatarUtility;
	use Railpage\Users\Factory as UserFactory;
		
	/**
	 * Loco object
	 * @since Version 3.2
	 * @version 3.8.7
	 */
	
	class Locomotive extends Locos {
		
		/**
		 * Registry cache key
		 * @since Version 3.9.1
		 * @const string REGISTRY_KEY
		 */
		
		const REGISTRY_KEY = "railpage.locos.loco=%d";
		
		/**
		 * Memcached/Redis cache key
		 * @since Version 3.9.1
		 * @const string CACHE_KEY
		 */
		
		const CACHE_KEY = "railpage:locos.loco_id=%d";
		
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
			
			$timer = Debug::getTimer();
			
			/**
			 * Record this in the debug log
			 */
				
			Debug::RecordInstance(NULL, $id);
			
			$this->bootstrap(); 
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = filter_var($id, FILTER_VALIDATE_INT);
			} else {
				$this->id = Utility\LocomotiveUtility::getLocoId($class_id_or_slug, $number); 
			}
			
			// Load the loco object
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$this->fetch(); 
			}
			
			Debug::logEvent(sprintf("%s(%d)", __METHOD__, $this->id), $timer); 
		}
		
		/**
		 * Bootstrap this class
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function bootstrap() {
			$this->namespace = sprintf("%s.%s", $this->Module->namespace, "loco");
			
			/**
			 * List of templates
			 */
			
			$this->Templates = new stdClass;
			$this->Templates->view = "loco";
			$this->Templates->edit = "loco.edit";
			$this->Templates->sightings = "loco.sightings";
		}
		
		/**
		 * Populate this object with data returned from Memcached/Redis/DB
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function populate() {
			
			$timer = Debug::getTimer();
			
			$row = Utility\LocomotiveUtility::fetchLocomotive($this); 
			
			if (!is_array($row) || count($row) === 0) {
				throw new Exception("Data for this locomotive could not be retrieved") ;
			}
			
			$lookup = array(
				"loco_num" => "number",
				"loco_name" => "name",
				"loco_gauge_id" => "gauge_id",
				"loco_status_id" => "status_id",
				"loco_status" => "status",
				"class_id" => "class_id",
				"owner_id" => "owner_id",
				"owner_name" => "owner",
				"operator_id" => "operator_id",
				"operator_name" => "operator",
				"entered_service" => "entered_service",
				"withdrawn" => "withdrawal_date",
				"date_added" => "date_added",
				"date_modified" => "date_modified",
				"builders_number" => "builders_num",
				"photo_id" => "photo_id",
				"manufacturer_id" => "manufacturer_id"
			);
			
			foreach ($row as $key => $val) {
				if (isset($lookup[$key])) {
					$var = $lookup[$key];
					$this->$var = $val;
				}
			}
			
			$ints = [ "gauge_id", "status_id", "class_id", "owner_id", "operator_id", "photo_id", "manufacturer_id" ];
			
			foreach ($ints as $int) {
				$this->$int = filter_var($this->$int, FILTER_VALIDATE_INT); 
			}
			
			/**
			 * Attempt to load the locomotive from the registry, and fall back to a new instance
			 */
			
			$Registry = Registry::getInstance(); 
			$regkey = sprintf(LocoClass::REGISTRY_KEY, $this->class_id); 
			
			$this->Class = Factory::CreateLocoClass($this->class_id); 
			
			/**
			 * Alias the class
			 */
			
			$this->class = &$this->Class;
			$this->flickr_tag = trim(str_replace(" ", "", $this->Class->flickr_tag . "-" . $this->number));
			
			$this->gauge_formatted = format_gauge($this->gauge);
			
			$this->makeLinks();
			
			Debug::logEvent(__METHOD__, $timer); 
			
			return $row; 
		}
		
		/**
		 * Load the URL object
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function makeLinks() {
			
			if (!$this->Class instanceof LocoClass) {
				return;
			}
			
			$this->url = new Url(strtolower($this->makeLocoURL($this->Class->slug, $this->number)));
			$this->url->edit = sprintf("%s?mode=loco.edit&id=%d", $this->Module->url, $this->id);
			$this->url->sightings = sprintf("%s/sightings", $this->url->url);
			$this->url->photos = sprintf("%s/photos", $this->url->url);
			$this->fwlink = $this->url->short;
			
		}
		
		/**
		 * Load the locomotive object
		 * @since Version 3.2
		 * @version 3.2
		 * @return boolean
		 */
		
		public function fetch() {
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot load loco object - loco ID not provided");
				return false;
			}
			
			$timer = Debug::getTimer();
			
			$this->mckey = sprintf(self::CACHE_KEY, $this->id);
			
			$row = $this->populate(); 
				
			/**
			 * Set the meta data
			 */
			
			$this->meta = isset($row['meta']) ? json_decode($row['meta'], true) : array(); 
			
			/**
			 * Fetch a nicely formatted gauge
			 */
			
			$this->setGauge(new Gauge($row['loco_gauge_id'])); 
			
			/**
			 * If an asset ID exists and is greater than 0, create the asset object
			 */
			
			if (isset($row['asset_id']) && $row['asset_id'] > 0) {
				try {
					$this->Asset = new Asset($row['asset_id']);
				} catch (Exception $e) {
					// throw it away
				}
			}
			
			/**
			 * Do we need to update the database and Memcached records?
			 */
			
			$doUpdate = false;
			
			/**
			 * Get all owners of this locomotive
			 */
			
			$this->reloadOrganisations("owners"); 
			$this->reloadOrganisations("operators"); 
			
			/**
			 * Get the manufacturer
			 */
			
			$this->loadManufacturer(); 
			
			/**
			 * Set the StatsD namespaces
			 */
			
			$this->StatsD->target->view = sprintf("%s.%d.view", $this->namespace, $this->id);
			$this->StatsD->target->edit = sprintf("%s.%d.view", $this->namespace, $this->id);
			
			/**
			 * Update the database and Memcached records if required
			 */
			
			if ($doUpdate) {
				$this->commit(); 
			}
			
			Debug::logEvent(__METHOD__, $timer); 
		}
		
		/**
		 * Load the manufacturer for this locomotive
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function loadManufacturer() {
			
			if (empty($this->manufacturer_id)) {
				$this->manufacturer_id 	= $this->Class->manufacturer_id;
				$this->manufacturer 	= $this->Class->manufacturer;
				
				return;
			}
			
			try {
				$builders = $this->listManufacturers(); 
				
				if (count($builders['manufacturers'])) {
					$this->manufacturer = $builders['manufacturers'][$this->manufacturer_id]['manufacturer_name'];
				}
			} catch (Exception $e) {
				// throw it away
			}
			
			return;
			
		}
		
		/**
		 * Update the owners/operators
		 * @since Version 3.9.1
		 * @param string $type
		 * @return void
		 */
		
		private function reloadOrganisations($type) {
			
			if (substr($type, -1) !== "s") {
				$type .= "s";
			}
			
			$allowed = [ "owners", "operators" ];
			
			if (!in_array($type, $allowed)) {
				throw new InvalidArgumentException("Cannot update owners/operators/organisations: " . $type . " is an invalid organisation type"); 
			}
			
			$lookup = [
				"owners" => 1,
				"operators" => 2
			];
			
			$type_id = $lookup[$type];
			
			$var_name = substr($type, 0, -1);
			$var_name_id = substr($type, 0, -1) . "_id";
			
			if (!empty($this->$var_name_id) && empty($this->$type)) {
				$this->addOrganisation($this->$var_name_id, $type_id); 
			}
			
			$this->$type = $this->getOrganisations($type_id); 
				
			reset($this->$type);
			$array = $this->$type;
			
			if (isset($array[0]['organisation_id']) && isset($array[0]['organisation_name'])) {
				$this->$var_name_id = $array[0]['organisation_id']; 
				$this->$var_name 	= $array[0]['organisation_name']; 
				Debug::LogEvent(__METHOD__ . "() : Latest " . $var_name . " ID requires updating");
				
				return;
			}
			
			$this->$var_name_id = 0;
			$this->$var_name 	= "Unknown";
			
			return;

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
			
			if (!filter_var($this->gauge_id, FILTER_VALIDATE_INT)) {
				throw new Exception("No gauge has been set");
			}
			
			if (!filter_var($this->status_id, FILTER_VALIDATE_INT)) {
				throw new Exception("No status has been set");
			}
			
			/**
			 * Validate integers and set to zero if neccessary
			 */
			
			$ints = [ "owner_id", "operator_id", "photo_id", "manufacturer_id" ];
			
			foreach ($ints as $int) {
				if (!filter_var($this->$int, FILTER_VALIDATE_INT)) {
					$this->$int = 0;
				}
			}
			
			/**
			 * The database doesn't like NULLs so set them to an empty character
			 */
			
			$texts = [ "entered_service", "withdrawal_date", "builders_num", "name" ];
			
			foreach ($texts as $text) {
				if (is_null($this->$text)) {
					$this->$text = "";
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
			
			$timer = Debug::getTimer();
			
			$this->validate();
			
			$data = Utility\LocomotiveUtility::getSubmitData($this);
			
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				$rs = $this->db->insert("loco_unit", $data); 
				$this->id = $this->db->lastInsertId(); 
				
				$verb = "Insert";
			} else {
				$this->Memcached->delete($this->mckey);
				$this->Redis->delete($this->mckey); 
				
				$where = array(
					"loco_id = ?" => $this->id
				);
				
				$verb = "Update";
				
				$rs = $this->db->update("loco_unit", $data, $where); 
			}
			
			// Update the registry
			$Registry = Registry::getInstance(); 
			$regkey = sprintf(self::REGISTRY_KEY, $this->id); 
			$Registry->remove($regkey)->set($regkey, $this); 
			
			Debug::logEvent("Zend_DB: commit loco ID " . $this->id, $timer); 
			
			$this->makeLinks(); 
			
			return true;
		}
		
		/**
		 * Add note to this loco or edit an existing one
		 * @since Version 3.2
		 * @version 3.4
		 * @param string $note_text
		 * @param int $user_id
		 * @param int $note_id
		 */
		
		public function addNote($note_text = false, $user_id = false, $note_id = false) {
			if (!$note_text || empty($note_text)) {
				throw new Exception("No note text given"); 
			} 
			
			if (!$user_id instanceof User && !filter_var($user_id, FILTER_VALIDATE_INT)) {
				throw new Exception("No user provided"); 
			}
			
			if ($user_id instanceof User) {
				$user_id = $user_id->id;
			}
			
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
		
		/**
		 * Load notes
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function loadNotes() {
			$query = "SELECT n.*, u.username, user_avatar FROM loco_notes AS n LEFT JOIN nuke_users AS u ON n.user_id = u.user_id WHERE n.loco_id = ?";
			
			$notes = array(); 
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				if (!empty($row['user_avatar'])) {
					try {
						$User = UserFactory::CreateUser($row['user_id']);
						
						$row['user_avatar'] = AvatarUtility::Format($row['user_avatar'], 50, 50);
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
		
		/**
		 * Load dates
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function loadDates() {
			$query = "SELECT d.date_id, d.date, d.text, dt.loco_date_text AS title, dt.loco_date_id AS date_type_id
						FROM loco_unit_date AS d
						LEFT JOIN loco_date_type AS dt ON d.loco_date_id = dt.loco_date_id
						WHERE d.loco_unit_id = ?
						ORDER BY d.date DESC";
			
			return $this->db->fetchAll($query, $this->id);
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
			
			$Date = new Date;
			$Date->action = $text;
			$Date->action_id = $loco_date_id;
			$Date->Date = new DateTime($date); 
			
			$Date->commit(); 
			
			return true;
			
		}
		
		/**
		 * Get link(s) of this loco
		 * @since Version 3.2
		 * @return array
		 */
		
		public function links() {
			$query = "SELECT * FROM loco_link WHERE loco_id_a = ? OR loco_id_b = ?";
			$return = array();
			
			foreach ($this->db->fetchAll($query, array($this->id, $this->id)) as $row) {
				$article = $row['loco_id_a'] === $this->id ? "to" : "from";
				$key = $row['loco_id_a'] === $this->id ? "loco_id_b" : "loco_id_a";
				
				if ($row['link_type_id'] === RP_LOCO_RENUMBERED) {
					$return[$row['link_id']][$row[$key]] = "Renumbered " . $article;
				} elseif ($row['link_type_id'] === RP_LOCO_REBUILT) {
					$return[$row['link_id']][$row[$key]] = "Rebuilt to" . $article;
				}
			}
			
			return $return;
		}
		
		/**
		 * Save a correction for this loco
		 * @since Version 3.2
		 * @param string $correction_text
		 * @param int $user_id
		 */
		
		public function newCorrection($correction_text = false, $user_id = false) {
			
			$Correction = new Correction;
			$Correction->text = $correction_text;
			$Correction->setUser(UserFactory::CreateUser($user_id)); 
			$Correction->setObject($this);
			$Correction->commit(); 
			
			return true;
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
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot fetch rating - no loco ID given"); 
			}
			
			if ($detailed) {
				$query = "SELECT AVG(rating) as dec_avg, COUNT(rating) AS number_votes, SUM(rating) AS total_points FROM rating_loco WHERE loco_id = ?"; 
				
				$row = array(
					"dec_avg" => 0,
					"whole_avg" => 0,
					"total_points" => 0,
					"number_votes" => 0
				);
				
				$row = $this->db->fetchRow($query, $this->id); 
				
				$row['dec_avg'] = empty($row['dec_avg']) ? 0 : $row['dec_avg'];
				$row['total_points'] = empty($row['total_points']) ? 0 : $row['total_points'];
				$row['number_votes'] = empty($row['number_votes']) ? 0 : $row['number_votes'];
				$row['whole_avg'] = round($row['dec_avg']);
				
				return $row;
			}
			
			$query = "SELECT AVG(rating) as average_rating FROM rating_loco WHERE loco_id = ?"; 
			$row = $this->db->fetchRow($query, $this->id);
			
			return isset($row['average_rating']) ? $row['average_rating'] : floatval("2.5"); 
		}
		
		/**
		 * Get this user's rating for this loco
		 * @since Version 3.2
		 * @param int $user_id
		 * @return float|boolean
		 */
		
		public function userRating($user_id = false) {
			if (!$user_id instanceof User && !filter_var($user_id, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot fetch user rating for this loco - no user given"); 
			}
			
			if ($user_id instanceof User) {
				$user_id = $user_id->id;
			}
			
			$query = "SELECT rating FROM rating_loco WHERE user_id = ? AND loco_id = ? LIMIT 1"; 
			
			$rating = $this->db->fetchOne($query, array($user_id, $this->id)); 
			
			return $rating;
		}
		
		/**
		 * Set user rating for this loco
		 * @since Version 3.2
		 * @param int $user_id
		 * @param float $rating
		 * @return boolean
		 */
		 
		public function setRating($user_id = false, $rating = false) {
			if (!$user_id instanceof User && !filter_var($user_id, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot set user rating for this loco - no user given"); 
			}
			
			if ($user_id instanceof User) {
				$user_id = $user_id->id;
			}
			
			if (!filter_var($rating, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot set user rating for this loco - no rating given"); 
			}
			
			$rating = floatval($rating); 
			
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
		
		/**
		 * Get liveries carried by this loco
		 * Based on tagged Flickr photos
		 * @since Version 3.2
		 * @param object $f
		 * @return array|boolean
		 */
		
		public function getLiveries($f = false) {
			
			return Utility\LocomotiveUtility::getLiveriesForLocomotive($this->id); 
			
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
			
			$where = array("id = ?" => $org_link_id); 
			
			$this->db->delete("loco_org_link", $where);
			
			return true;
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
			
			if ($title === "Photo tagged") {
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
			$query = "SELECT ll.*, u.username FROM log_locos AS ll LEFT JOIN nuke_users AS u ON ll.user_id = u.user_id WHERE ll.loco_id = ? ORDER BY timestamp DESC"; 
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				$row['timestamp'] = DateTime::createFromFormat("Y-m-d H:i:s", $row['timestamp']); 
				$row['args'] = json_decode($row['args'], true);
				$return[] = $row; 
			}
			
			return $return;
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
			
			$query = "SELECT o.*, ot.name AS link_type_name, op.operator_name FROM loco_org_link AS o LEFT JOIN loco_org_link_type AS ot ON o.link_type = ot.id LEFT JOIN operators AS op ON op.operator_id = o.operator_id WHERE o.id = ?"; 
			
			return $this->db->fetchRow($query, $id);
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
			
			if (!$meta && json_last_error() === JSON_ERROR_UTF8) {
				$data['meta'] = ContentUtility::FixJSONEncode_UTF8($data['meta']); 
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
			
			if ($members['stat'] === "ok") {
				// Get the previous loco in this class
				
				$break = false;
				
				foreach ($members['locos'] as $row) {
					if ($break === true) {
						return new Locomotive($row['loco_id']);
					}
					
					if ($row['loco_id'] === $this->id) {
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
			if ($members['stat'] === "ok") {
				
				$break = false;
				
				$members['locos'] = array_reverse($members['locos']);
				foreach ($members['locos'] as $row) {
					if ($break === true) {
						return new Locomotive($row['loco_id']);
					}
					
					if ($row['loco_id'] === $this->id) {
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
			
			if ($Image instanceof Image && $Image->provider === "flickr") {
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
			
			if (isset($this->meta['coverimage'])) {
				$Image = new Image($this->meta['coverimage']['id']);
			} elseif ($this->Asset instanceof Asset) {
				$Image = $this->Asset;
			} elseif (filter_var($this->photo_id, FILTER_VALIDATE_INT) && $this->photo_id > 0) {
				$Image = (new Images)->findImage("flickr", $this->photo_id);
			}
			
			$return = array(
				"type" => "image",
				"provider" => $Image instanceof Image ? $Image->provider : "",
				"title" => $Image instanceof Image ? $Image->title : $Asset->meta['title'],
				"author" => array(
					"id" => "",
					"username" => "",
					"realname" => "",
					"url" => ""
				)
			);
			
			if ($Image instanceof Image) {
				$return = array_merge($return, array(
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
				));
			} elseif ($this->Asset instanceof Asset) {
				$return = array_merge($return, array(
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
				));
			}
			
			if (isset($Image)) {
				return $return;
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
			
			return Utility\CoverImageUtility::hasCoverImage($this); 
			
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
					"text" => strval(new Status($this->status_id))
				),
				"manufacturer" => array(
					"id" => $this->manufacturer_id,
					"text" => $this->manufacturer
				),
				"class" => $this->Class->getArray(),
				"url" => $this->url->getURLs()
			);
		}
		
		/**
		 * Set the locomotive class
		 * @since Version 3.9.1
		 * @param \Railpage\Locos\LocoClass $LocoClass
		 * @return \Railpage\Locos\Locomotive
		 */
		
		public function setLocoClass(LocoClass $LocoClass) {
			$this->Class = $LocoClass;
			$this->class = $LocoClass;
			$this->class_id = $LocoClass->id;
			
			return $this;
		}
		
		/**
		 * Set the locomotive gauge
		 * @since Version 3.9.1
		 * @param \Railpage\Locos\Gauge $Gauge
		 * @return \Railpage\Locos\Locomotive
		 */
		
		public function setGauge(Gauge $Gauge) {
			$this->gauge_id = $Gauge->id;
			$this->gauge = $Gauge->getArray(); 
			$this->gauge_formatted = (string) $Gauge;
			
			return $this;
		}
		
		/**
		 * Get the gauge
		 * @since Version 3.9.1
		 * @return \Railpage\Locos\Gauge
		 */
		
		public function getGauge() {
			return new Gauge($this->gauge_id);
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
		 * Get the loco manufacturer
		 * @since Version 3.9.1
		 * @return \Railpage\Locos\Manufacturer
		 */
		
		public function getManufacturer() {
			#if (filter_var($this->manufacturer_id, FILTER_VALIDATE_INT)) {
				return new Manufacturer($this->manufacturer_id); 
			#}
			
			#return $this->Class->getManufacturer(); 
		}
		
		/**
		 * Generate descriptive text
		 * @since Version 3.9.1
		 * @return string
		 */
		
		public function generateDescription() {
			
			$bits = array(); 
			
			/**
			 * Built as... by...
			 */
			
			$bits = Utility\LocomotiveUtility::getDescriptionBits_Manufacturer($this, $bits); 
			
			/**
			 * Process the dates
			 */
			
			$bits = Utility\LocomotiveUtility::getDescriptionBits_Dates($this, $bits); 
						
			/**
			 * The loco is currently...
			 */
			
			$bits = Utility\LocomotiveUtility::getDescriptionBits_Status($this, $bits); 
			
			/**
			 * Join it all together
			 */
						
			$str = trim(implode("", $bits)); 
			
			if (preg_match("/([a-zA-Z0-9]+)/", substr($str, -1))) {
				$str .= ".";
			}
			
			if (substr($str, -1) === ",") {
				$str = substr($str, 0, -1) . ".";
			}
			
			return $str;
		}
		
		/**
		 * Echo this locomotive as a string
		 * @since Version 3.9.1
		 * @return string
		 */
		
		public function __toString() {
			return $this->number;
		}
	}