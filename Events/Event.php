<?php
	/**
	 * Railpage Events module
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Events;
	
	use Railpage\AppCore;
	use Railpage\Organisations\Organisation;
	use Railpage\Place;
	use Railpage\Module;
	use Railpage\Url;
	use DateTime;
	use Exception;
	use stdClass;
	
	/**
	 * Event class
	 * @since Version 3.8.7
	 */
	
	class Event extends AppCore {
		
		/**
		 * Event ID
		 * @var int $id
		 * @since Version 3.8.7
		 */
		 
		public $id;
		
		/**
		 * Event title
		 * @var string $title
		 * @since Version 3.8.7
		 */
		
		public $title;
		
		/**
		 * Descriptive text
		 * @var string $desc
		 * @since Version 3.8.7
		 */
		
		public $desc;
		
		/**
		 * Meta data
		 * @var array $meta An array of extra data for this event, such as ticket purchasing websites
		 * @since Version 3.8.7
		 */
		
		public $meta;
		
		/**
		 * Event category
		 * @var \Railpage\Events\EventCategory $Category The event category (tours, open days, etc) that this event is filed under
		 * @since Version 3.8.7
		 */
		
		public $Category;
		
		/**
		 * Place
		 * @var \Railpage\Place $Place The geographic place (latitude & longitude) of this event
		 * @since Version 3.8.7
		 */
		
		public $Place;
		
		/**
		 * Organisation associated with this event
		 * @var \Railpage\Organisations\Organisation $Organisation The organisation hosting/running/assocated with this event
		 * @since Version 3.8.7
		 */
		
		public $Organisation;
		
		/**
		 * Event url
		 * @var string $url The URL relative to the site root of this event - eg
		 * @since Version 3.8.7
		 */
		
		public $url;
		
		/**
		 * Flickr tag
		 * @since Version 3.8.7
		 * @var string $flickr_tag A tag which when applied to Flickr photos will become associated with this event
		 */
		
		public $flickr_tag;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param int $id The ID of the event
		 * @uses \Railpage\Events\EventCategory The category which this event is filed under
		 * @uses \Railpage\Organisations\Organisation The organisation associated with this event
		 * @uses \Railpage\Place The geographic place (latitude & longitude) of this event
		 */
		
		public function __construct($id = false) {
			parent::__construct();
			
			/**
			 * Record this in the debug log
			 */
			
			if (function_exists("debug_recordInstance")) {
				debug_recordInstance(__CLASS__);
			}
			
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			$this->Module = new Module("events");
			$this->namespace = $this->Module->namespace;
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$query = "SELECT * FROM event WHERE id = ?";
				$this->mckey = sprintf("railpage:events.event=%d", $id);
			} elseif (is_string($id) && strlen($id) > 2) {
				$query = "SELECT * FROM event WHERE slug = ?";
				$this->mckey = getMemcacheObject(sprintf("railpage:events.event=%s", $id));
			}
			
			if (!$row = getMemcacheObject($this->mckey)) {
				if (isset($query)) {	
					$row = $this->db->fetchRow($query, $id);
					$this->mckey = sprintf("railpage:events.event=%d", $row['id']);
					setMemcacheObject($this->mckey, $row);
					setMemcacheObject(sprintf("railpage:events.event=%s", $row['slug']), $row['id']);
				}
			}
			
			if (isset($row) && is_array($row)) {
				$this->id = $row['id'];
				$this->title = $row['title'];
				$this->desc = $row['description']; 
				$this->meta = json_decode($row['meta'], true);
				$this->slug = $row['slug'];
				
				$this->flickr_tag = "railpage:event=" . $this->id;
				
				if (filter_var($row['category_id'], FILTER_VALIDATE_INT)) {
					$this->Category = new EventCategory($row['category_id']);
				}
				
				if (filter_var($row['organisation_id'], FILTER_VALIDATE_INT)) {
					$this->Organisation = new Organisation($row['organisation_id']);
				}
				
				if (round($row['lat'], 3) != "0.000" && round($row['lon'], 3) != "0.000") {
					$this->Place = new Place($row['lat'], $row['lon']);
				}
				
				$this->createUrls();
				
				$this->Templates = new stdClass();
				$this->Templates->view = sprintf("%s/event.tpl", $this->Module->Paths->html);
			}
			
			if (RP_DEBUG) {
				$site_debug[] = __CLASS__ . "::" . __METHOD__ . " completed in " . round(microtime(true) - $debug_timer_start, 5) . "s";
			}
		}
		
		/**
		 * Validate this event
		 * @since Version 3.8.7
		 * @return boolean
		 * @throws Exception if $this->title is empty
		 * @throws Exception if $this->desc is empty
		 * @throws Exception if $this->Category is not an instance of Railpage\Events\EventCategory
		 */
		
		private function validate() {
			if (empty($this->title)) {
				throw new Exception("Validation failed for event. Title cannot be empty");
				return false;
			}
			
			if (empty($this->desc)) {
				throw new Exception("Validation failed for event. Description cannot be empty");
				return false;
			}
			
			if (!$this->Category instanceof EventCategory) {
				throw new Exception("Validation failed for event. Event must have a category!");
				return false;
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this event
		 * @since Version 3.8.7
		 * @return boolean ZendFramework\Db will throw an exception if there's a fault
		 */
		
		public function commit() {
			$this->validate(); 
			
			deleteMemcacheObject($this->mckey);
			
			$data = array(
				"title" => $this->title,
				"description" => $this->desc,
				"meta" => json_encode($this->meta),
				"category_id" => $this->Category->id,
				"slug" => $this->slug
			);
			
			if ($this->Organisation instanceof Organisation) {
				$data['organisation_id'] = $this->Organisation->id;
			} else {
				$data['organisation_id'] = 0;
			}
			
			if ($this->Place instanceof Place) {
				$data['lat'] = $this->Place->lat;
				$data['lon'] = $this->Place->lon;
			} else {
				$data['lat'] = NULL;
				$data['lon'] = NULL;
			}
			
			if (filter_var($this->id)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("event", $data, $where); 
			} else {
				$this->db->insert("event", $data);
				$this->id = $this->db->lastInsertId(); 
				
				$this->createUrls();
			}
			
			return true;
		}
		
		/**
		 * Get event dates (instances)
		 * @return array
		 * @param DateTime $Start DateTime instance representing the date to limit searches from 
		 * @param DateTime $End DateTme instance representing the date to limit searches to
		 */
		
		public function getDates(DateTime $Start, DateTime $End = NULL) {
			
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			$query = "SELECT id FROM event_dates WHERE event_id = ?";
			
			$params = array(
				$this->id
			);
			
			if ($Start instanceof DateTime) {
				$query .= " AND date >= ?";
				$params[] = $Start->format("Y-m-d");
			}
			
			if ($End instanceof DateTime) {
				$query .= " AND date <= ?";
				$params[] = $End->format("Y-m-d");
			}
			
			$rs = $this->db->fetchAll($query, $params);
			
			if (RP_DEBUG) {
				$site_debug[] = __CLASS__ . "::" . __METHOD__ . " completed in " . round(microtime(true) - $debug_timer_start, 5) . "s";
			}
			
			return $rs;
		}
		
		/**
		 * Find an instance of this event on a given date
		 * @return mixed
		 * @param DateTime $Date DateTime instance to search for
		 * @throws Exception if $Date is not a valid instance of DateTime
		 */
		
		public function findDate(DateTime $Date) {
			
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			$query = "SELECT id FROM event_dates WHERE event_id = ? AND date = ?";
			
			if (!$Date instanceof DateTime) {
				throw new Exception("Cannot find dates for this event - no DateTime object given");
			}
			
			if (RP_DEBUG) {
				$site_debug[] = __CLASS__ . "::" . __METHOD__ . " completed in " . round(microtime(true) - $debug_timer_start, 5) . "s";
			}
			
			return $this->db->fetchAll($query, array($this->id, $Date->format("Y-m-d")));
		}
		
		/**
		 * Reject this event
		 * @since Version 3.8.7
		 * @return boolean
		 */
		
		public function reject() {
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot reject event - no event ID specified");
			}
			
			$where = array(
				"event_id = ?" => $this->id
			);
			
			if ($this->db->delete("event_dates", $where)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->delete("event", $where);
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Create a URL slug
		 * @since Version 3.8.7
		 */
		
		private function createSlug() {
			
			$proposal = substr(create_slug($this->title), 0, 60);
			
			$result = $this->db->fetchAll("SELECT id FROM event WHERE slug = ?", $proposal); 
			
			if (count($result)) {
				$proposal .= count($result);
			}
			
			$this->slug = $proposal;
			$this->commit();
			
		}
		
		/**
		 * Create URLs
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		private function createUrls() {
			if (empty($this->slug)) {
				$this->createSlug(); 
			}
			
			$this->url = new Url(sprintf("%s/%s", $this->Module->url, $this->slug));
			$this->url->edit = sprintf("%s?mode=event.edit&event_id=%d", $this->Module->url, $this->id);
			$this->url->delete = sprintf("%s?mode=event.reject&event_id=%d", $this->Module->url, $this->id);
		}
	}
?>