<?php
    /**
     * Instance of an event - an event date
     * @since Version 3.8.7
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Events;
    
    use Railpage\Users\User;
    use Railpage\Users\Factory as UserFactory;
    use Railpage\Events\Event;
    use DateTime;
    use DateTimeZone;
    use Exception;
    use Railpage\Place;
    use Railpage\AppCore;
    use Railpage\Module;
    use Railpage\Url;
    use Railpage\Images\Images;
    use Railpage\Images\Image;
    
    /**
     * EventDate
     *
     * Describes an instance of an event. For example, Puffing Billy operate regular Dinner & Dance trains so instead of having individual events for each night it operates we have one event with multiple EventDate instances.
     * @since Version 3.8.7
     */
    
    class EventDate extends AppCore {
        
        /**
         * Event date status: running
         * @since Version 3.9.1
         * @const int STATUS_RUNNING
         */
        
        const STATUS_RUNNING = 1;
        
        /**
         * Event date status: pending approval
         * @since Version 3.9.1
         * @const int STATUS_UNAPPROVED
         */
        
        const STATUS_UNAPPROVED = 0;
        
        /**
         * Event date status: cancelled
         * @since Version 3.9.1
         * @const int STATUS_CANCELLED
         */
        
        const STATUS_CANCELLED = 2;
        
        /**
         * Event date status: rejected
         * @since Version 3.9.1
         * @const int STATUS_REJECTED
         */
        
        const STATUS_REJECTED = 3;
        
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
         * Status
         * @since Version 3.9.1
         * @var int $status
         */
        
        public $status;
        
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
                    $this->Event = Factory::CreateEvent($row['event_id']);
                    $this->Date = new DateTime($row['date']);
                    $this->meta = json_decode($row['meta'], true);
                    $this->status = $row['status'];
                    
                    $this->url = new Url("/events?mode=event.date&event_id=" . $this->Event->id . "&date_id=" . $this->id);
                    $this->url->approve = sprintf("/events?mode=event.date.setstatus&date_id=%d&status=%d", $this->id, self::STATUS_RUNNING);
                    $this->url->reject = sprintf("/events?mode=event.date.setstatus&date_id=%d&status=%d", $this->id, self::STATUS_REJECTED);
                    $this->url->cancel = sprintf("/events?mode=event.date.setstatus&date_id=%d&status=%d", $this->id, self::STATUS_CANCELLED);
                    $this->url->export = sprintf("/events/export/date/%d.ics", $this->id); 
                    
                    $this->setAuthor(UserFactory::CreateUser($row['user_id']));
                    
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
                        $this->Place = Place::Factory($this->meta['lat'], $this->meta['lon']);
                    }
                    
                    #var_dump(get_class(Factory::CreateEvent($row['event_id'])));
                    #var_dump($this->id);
                    
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
            
            if (!filter_var($this->status, FILTER_VALIDATE_INT)) {
                $this->status = Events::STATUS_UNAPPROVED;
            }
            
            if (!$this->Author instanceof User) {
                throw new Exception("A valid user object must be set (hint: EventDate::setAuthor()");
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
                "date" => $this->Date->format("Y-m-d"),
                "status" => $this->status,
                "user_id" => $this->Author->id
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
        
        /**
         * Approve this event date
         * @since Version 3.9.1
         * @return \Railpage\Events\EventDate
         */
        
        public function approve() {
            $this->status = Events::STATUS_APPROVED;
            $this->commit(); 
            
            if ($this->Event->status == Events::STATUS_UNAPPROVED) {
                $this->Event->approve(); 
            }
            
            return $this;
        }
        
        /**
         * Reject this event date
         * @since Version 3.9.1
         * @return \Railpage\Events\EventDate
         */
        
        public function reject() {
            $where = array(
                "id = ?" => $this->id
            );
            
            $this->db->delete("event_categories", $where);
            
            return $this;
        }
        
        /**
         * Create an associative array representing this object
         * @since Version 3.9.1
         * @return array
         */
        
        public function getArray() {
            $array = array(
                "id" => $this->id,
                "date" => array(
                    "absolute" => $this->Date->format("Y-m-d H:i:s"),
                    "iso8601" => $this->Date->format(DateTime::ISO8601),
                    "ymd" => $this->Date->format("Y-m-d"),
                    "nice" => $this->Date->format("l F j, Y"),
                    "day" => $this->Date->format("d"),
                    "month" => $this->Date->format("M")
                ),
                "start" => $this->Start instanceof DateTime ? $this->Start->format("g:i a") : 0,
                "end" => $this->End instanceof DateTime ? $this->End->format("g:i a") : 0,
                "status" => array(
                    "id" => $this->status,
                    "name" => $this->status == Events::STATUS_APPROVED ? "Approved" : "Unapproved"
                ),
                "url" => $this->url->getURLs(),
                "event" => $this->Event->getArray(),
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
            
            if ($this->Place instanceof Place) {
                $array['place'] = array(
                    "lat" => $this->Place->lat,
                    "lon" => $this->Place->lon,
                    "address" => $this->Place->getAddress()
                );
            }
            
            if (isset($this->Event->meta['coverphoto']) && !empty($this->Event->meta['coverphoto'])) {
                if ($CoverPhoto = (new Images)->getImageFromUrl($this->Event->meta['coverphoto'], Images::OPT_NOPLACE)) {
                    $array['event']['coverphoto'] = $CoverPhoto->getArray();
                }
            }
            
            return $array;
        }
        
        /**
         * Set the status of this date
         * @since Version 3.9.1
         * @param int $status
         * @return \Railpage\Events\EventDate
         */
        
        public function setStatus($status = NULL) {
            if (is_null($status)) {
                throw new Exception("No status flag was specified");
            }
            
            switch ($status) {
                case self::STATUS_RUNNING :
                case self::STATUS_REJECTED :
                    $this->status = $status;
                    $this->commit(); 
                    
                    break;
                
                case self::STATUS_CANCELLED :
                    $this->status = $status;
                    $this->commit(); 
                    
                    // DO something else in the future
                    
                    break;
                    
            }
            
            return $this;
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
            
            if (!empty($this->Event->meta['address'])) {
                return $this->Event->meta['address'];
            }
            
            if (!$this->Place instanceof Place || $this->Event->Place instanceof Place) {
                return;
            }
            
            if ($this->Place instanceof Place) {
                
                $this->meta['address'] = $this->Place->getAddress(); 
                $this->commit(); 
                
                return $this->meta['address'];
                
            }
            
            if ($this->Event->Place instanceof Place) {
                
                $this->Event->meta['address'] = $this->Event->Place->getAddress(); 
                $this->Event->commit(); 
                
                return $this->Event->meta['address'];
                
            }
            
        }
    }
    