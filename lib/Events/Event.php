<?php
	/**
	 * Railpage Events module
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Events;
	
	use Railpage\Users\User;
	use Railpage\Users\Factory as UserFactory;
	use Railpage\AppCore;
	use Railpage\Organisations\Factory as OrganisationsFactory;
	use Railpage\Organisations\Organisation;
	use Railpage\Place;
	use Railpage\Module;
	use Railpage\Debug;
	use Railpage\Url;
	use DateTime;
	use Exception;
	use stdClass;
	use Railpage\Images\Images;
	
	
	/**
	 * Event class
	 * @since Version 3.8.7
	 */
	
	class Event extends AppCore {
		
		/**
		 * Registry key
		 * @since Version 3.9.1
		 * @const string REGISTRY_KEY
		 */
		
		const REGISTRY_KEY = "railpage:events.event=%d";
		
		/**
		 * Memcached/Redis cache key
		 * @since Version 3.9.1
		 * @const string CACHE_KEY
		 */
		
		const CACHE_KEY = "railpage:events.event=%d";
		
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
		 * Event status
		 * @since Version 3.9.1
		 * @var int $status
		 */
		
		public $status;
		
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
			
			Debug::RecordInstance(); 
			
			$timer = Debug::getTimer(); 
			
			$this->Module = new Module("events");
			$this->namespace = $this->Module->namespace;
			
			if ($id !== false) {
				$this->populate($id); 
			}
			
			Debug::logEvent(__METHOD__, $timer); 
			
		}
		
		/**
		 * Load this object
		 * @since Version 3.9.1
		 * @return array
		 * @param int|string $id
		 */
		
		private function load($id) {
			
			if (!filter_var($id, FILTER_VALIDATE_INT)) {
				$id = $this->db->fetchOne("SELECT id FROM event WHERE slug = ?", $id); 
			}
			
			if (!$id = filter_var($id, FILTER_VALIDATE_INT)) {
				return;
			}
			
			$this->id = $id;
			$this->mckey = sprintf("railpage:events.event=%d", $this->id);
			$query = "SELECT * FROM event WHERE id = ?";
			
			if (!$row = $this->Memcached->fetch($this->mckey)) {
				$row = $this->db->fetchRow($query, $this->id);
				Debug::logEvent(__METHOD__ . " - fetched from SQL"); 
				
				$this->Memcached->save($this->mckey, $row);
			}
			
			return $row;

		}
		
		/**
		 * Populate this object
		 * @since Version 3.9.1
		 * @return void
		 * @param int|string $id
		 */
		
		private function populate($id) {
			
			$row = $this->load($id); 
			
			if (!isset($row) || !is_array($row)) {
				return;
			}
			
			$this->title = $row['title'];
			$this->desc = $row['description']; 
			$this->meta = json_decode($row['meta'], true);
			$this->slug = $row['slug'];
			$this->status = isset($row['status']) ? $row['status'] : Events::STATUS_APPROVED;
			
			if (!isset($row['user_id'])) {
				$row['user_id'] = 45;
			}
			
			$this->setAuthor(UserFactory::CreateUser($row['user_id']));
			
			$this->flickr_tag = "railpage:event=" . $this->id;
			
			if (filter_var($row['category_id'], FILTER_VALIDATE_INT)) {
				$this->Category = Factory::CreateEventCategory($row['category_id']);
			}
			
			if (filter_var($row['organisation_id'], FILTER_VALIDATE_INT)) {
				$this->Organisation = OrganisationsFactory::CreateOrganisation($row['organisation_id']);
			}
			
			if (!empty($row['lat']) && round($row['lat'], 3) != "0.000" && !empty($row['lon']) && round($row['lon'], 3) != "0.000") {
				$this->Place = Place::Factory($row['lat'], $row['lon']);
			}
			
			$this->createUrls();
			$this->Templates = new stdClass();
			$this->Templates->view = sprintf("%s/event.tpl", $this->Module->Paths->html);
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
			}
			
			if (empty($this->desc)) {
				throw new Exception("Validation failed for event. Description cannot be empty");
			}
			
			if (!$this->Category instanceof EventCategory) {
				throw new Exception("Validation failed for event. Event must have a category!");
			}
			
			if (!isset($this->slug) || empty($this->slug)) {
				$this->createSlug();
			}
			
			if (!filter_var($this->status)) {
				$this->status = Events::STATUS_UNAPPROVED;
			}
			
			if (!$this->Author instanceof User) {
				throw new Exception("A valid user object must be set (hint: Event::setAuthor()");
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
			
			$data = array(
				"title" => $this->title,
				"description" => $this->desc,
				"meta" => json_encode($this->meta),
				"category_id" => $this->Category->id,
				"slug" => $this->slug,
				"status" => $this->status,
				"user_id" => $this->Author->id,
				"lat" => "",
				"lon" => "",
				"organisation_id" => 0
			);
			
			if ($this->Organisation instanceof Organisation) {
				$data['organisation_id'] = $this->Organisation->id;
			}
			
			if ($this->Place instanceof Place) {
				$data['lat'] = $this->Place->lat;
				$data['lon'] = $this->Place->lon;
			}
			
			if (filter_var($this->id)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("event", $data, $where); 
			
				$this->Redis->delete(sprintf(self::CACHE_KEY, $this->id)); 
				
				if (isset($this->mckey)) {
					$this->Memcached->delete($this->mckey);
				}

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
		 * @param $status Only show dates with this status flag
		 */
		
		public function getDates(DateTime $Start, DateTime $End = NULL, $status = Events::STATUS_APPROVED) {
			
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			$query = "SELECT id FROM event_dates WHERE event_id = ? AND status = ?";
			
			$params = array(
				$this->id,
				$status
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
			
			$query = "SELECT id FROM event_dates WHERE event_id = ? AND date = ? AND status = ?";
			
			if (!$Date instanceof DateTime) {
				throw new Exception("Cannot find dates for this event - no DateTime object given");
			}
			
			if (RP_DEBUG) {
				$site_debug[] = __CLASS__ . "::" . __METHOD__ . " completed in " . round(microtime(true) - $debug_timer_start, 5) . "s";
			}
			
			$where = array(
				$this->id, 
				$Date->format("Y-m-d"),
				Events::STATUS_APPROVED
			);
			
			return $this->db->fetchAll($query, $where);
		}
		
		/**
		 * Approve this event
		 * @since Version 3.9.1
		 * @return \Railpage\Events\Event
		 */
		
		public function approve() {
			$this->status = Events::STATUS_APPROVED;
			$this->commit(); 
			
			return $this;
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
			
			$this->db->delete("event_dates", $where);
			$where = array(
				"id = ?" => $this->id
			);
			
			$this->db->delete("event", $where);
			
			return true;
				
		}
		
		/**
		 * Create a URL slug
		 * @since Version 3.8.7
		 */
		
		private function createSlug() {
			
			$proposal = substr(create_slug($this->title), 0, 32);
			
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
			$this->url->reject = $this->url->delete;
			$this->url->approve = sprintf("%s?mode=event.approve&event_id=%d", $this->Module->url, $this->id);
			$this->url->publish = $this->url->approve; 
			
			if (isset($this->meta['website']) && !empty($this->meta['website'])) {
				$this->url->website = $this->meta['website'];
			}
			
			if (isset($this->meta['tickets']) && !empty($this->meta['tickets'])) {
				$this->url->tickets = $this->meta['tickets'];
			}
		}
		
		/**
		 * Create an associative array representing this object
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getArray() {
			$array = array(
				"id" => $this->id,
				"title" => $this->title,
				"description" => $this->desc,
				"status" => array(
					"id" => $this->status,
					"name" => $this->status == Events::STATUS_APPROVED ? "Approved" : "Unapproved"
				),
				"url" => $this->url->getURLs(),
				"category" => array(
					"id" => $this->Category->id,
					"name" => $this->Category->name,
					"url" => $this->Category->url->getURLs()
				),
				"place" => array(
					"lat" => 0,
					"lon" => 0
				),
				"author" => array(
					"id" => $this->Author->id,
					"username" => $this->Author->username,
					"url" => $this->Author->url->getURLs()
				)
			);
			
			if ($this->Organisation instanceof Organisation) {
				$array['organisation'] = $this->Organisation->getArray();
			}
			
			if ($this->Place instanceof Place) {
				$array['place'] = array(
					"lat" => $this->Place->lat,
					"lon" => $this->Place->lon
				);
			}
			
			return $array;
		}
		
		/**
		 * Get the street address of this event if applicable
		 * @since Version 3.10.0
		 * @return string
		 */
		
		public function getAddress() {
			
			if (!empty($this->meta['address'])) {
				return $this->meta['address'];
			}
			
			if (!$this->Place instanceof Place) {
				return;
			}
			
			if ($this->Place instanceof Place) {
				
				$this->meta['address'] = $this->Place->getAddress(); 
				$this->commit(); 
				
				return $this->meta['address'];
				
			}
			
		}
		
		/**
		 * Get the cover photo for this event
		 * @since Version 3.10.0
		 * @return false|array
		 */
		
		public function getCoverPhoto() {
			
			if (!isset($this->meta['coverphoto']) || empty($this->meta['coverphoto'])) {
				return false;
			}
			
			if ($CoverPhoto = (new Images)->getImageFromUrl($this->meta['coverphoto'], Images::OPT_NOPLACE)) {
				return $CoverPhoto->getArray();
			}
			
			return false;
			
		}
	}
	