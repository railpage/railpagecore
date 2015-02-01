<?php
	/**
	 * Instance of an event - an event date
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Events;
	
	use Railpage\Events\Event;
	use DateTime;
	use DateTimeZone;
	use Exception;
	use Railpage\Place;
	use Railpage\AppCore;
	use Railpage\Module;
	use Railpage\Url;
	
	/**
	 * EventDate
	 *
	 * Describes an instance of an event. For example, Puffing Billy operate regular Dinner & Dance trains so instead of having individual events for each night it operates we have one event with multiple EventDate instances.
	 * @since Version 3.8.7
	 */
	
	class EventDate extends AppCore {
		
		/**
		 * ID
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Event object
		 * @var \Railpage\Events\Event $Event The Event object that this EventDate instance belongs to
		 */
		
		public $Event;
		
		/**
		 * Event date
		 * @var \DateTime $Date A DateTime object representing the calendar date that this EventDate is on
		 */
		
		public $Date;
		
		/**
		 * Event start time
		 * @var \DateTime $Start An optional DateTime object describing the start time of the event
		 */
		
		public $Start;
		
		/**
		 * Event end time
		 * @var \DateTime $End An optional DateTime object describing the end time of the event
		 */
		
		public $End;
		
		/**
		 * Place
		 * @var \Railpage\Place $Place The geographic location this EventDate instance is taking place at
		 */
		
		public $Place;
		
		/**
		 * Meta data
		 * @var array $meta An array of extra data
		 */
		
		public $meta;
		
		/**
		 * EventDate url
		 * @var string $url The URL to this EventDate, relative to the site root
		 */
		
		public $url;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param int $id
		 */
		
		public function __construct($id = NULL) {
			parent::__construct(); 
			
			$this->Module = new Module("events");
			$this->namespace = $this->Module->namespace;
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				if ($row = $this->db->fetchRow("SELECT * FROM event_dates WHERE id = ?", $id)) {
					$this->id = $id;
					$this->Event = new Event($row['event_id']);
					$this->Date = new DateTime($row['date']);
					$this->meta = json_decode($row['meta'], true);
					$this->url = new Url("/events?mode=event.date&event_id=" . $this->Event->id . "&date_id=" . $this->id);
					
					if ($row['start'] != "00:00:00") {
						$this->Start = new DateTime($row['date'] . " " . $row['start']);
					}
					
					if ($row['end'] != "00:00:00") {
						$this->End = new DateTime($row['date'] . " " . $row['end']);
					}
					
					if (isset($this->meta['lat']) && empty($this->meta['lat'])) {
						unset($this->meta['lat']);
					}
					
					if (isset($this->meta['lon']) && empty($this->meta['lon'])) {
						unset($this->meta['lon']);
					}
					
					if (isset($this->meta['lat']) && isset($this->meta['lon'])) {
						$this->Place = new Place($this->meta['lat'], $this->meta['lon']);
					}
					
					try {
						if ($this->Event->Place instanceof Place && !empty($this->Event->Place->Region->timezone)) {
							$this->Date->setTimezone(new DateTimeZone($this->Event->Place->Region->timezone));
							
							if ($this->Start instanceof DateTime) {
								$this->Start->setTimezone(new DateTimeZone($this->Event->Place->Region->timezone));
							}
							
							if ($this->End instanceof DateTime) {
								$this->End->setTimezone(new DateTimeZone($this->Event->Place->Region->timezone));
							}
						}
					} catch (Exception $e) {
						printArray($e->getMessage());
						printArray($this->Event->Place->Region->timezone);
					}
				}
			}
		}
		
		/**
		 * Validate changes to this event
		 * @return boolean
		 * @throws \Exception if $this->Date is not an instance of \DateTime
		 * @throws \Exception if $this->Event is not an instance of \Railpage\Events\Event
		 */
		
		private function validate() {
			if (!$this->Date instanceof DateTime) {
				throw new Exception("Cannot validate changes to this event instance - date cannot be empty");
			}
			
			if (!$this->Event instanceof Event) {
				throw new Exception("Cannot validate changes to this event instance - no event given");
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this event, or create a new event as required
		 */
		
		public function commit() {
			$this->validate(); 
			
			$data = array(
				"event_id" => $this->Event->id,
				"date" => $this->Date->format("Y-m-d")
			);
			
			if ($this->Place instanceof Place) {
				$this->meta['lat'] = $this->Place->lat;
				$this->meta['lon'] = $this->Place->lon;	
			}
			
			if (!empty($this->meta)) {
				$data['meta'] = json_encode($this->meta);
			}
			
			if ($this->Start instanceof DateTime) {
				$data['start'] = $this->Start->format("H:i:s");
			}
			
			if ($this->End instanceof DateTime) {
				$data['end'] = $this->End->format("H:i:s");
			}
			
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("event_dates", $data, $where);
			} else {
				$this->db->insert("event_dates", $data);
				$this->id = $this->db->lastInsertId(); 
			}
			
			return true;
		}
	}
?>