<?php
    
    /**
     * Add a suggested correction to a location
     * @since Version 3.9.1
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Locations;
    
    use Railpage\Users\User; 
    use Railpage\Users\Factory as UserFactory;
    use Railpage\AppCore;
    use Exception;
    use InvalidArgumentException;
    use DateTime;
    use DateTimeInterval;
    use Railpage\PrivateMessages\Message;
    
    class Correction extends AppCore {
        
        /**
         * Status: New
         * @since Version 3.9.1
         * @const int STATUS_NEW
         */
        
        const STATUS_NEW = 0;
        
        /**
         * Status: closed
         * @since Version 3.9.1
         * @const int STATUS_CLOSED
         */
        
        const STATUS_CLOSED = 1;
        
        /**
         * Status: rejected
         * @since Version 3.9.1
         * @const int STATUS_REJECTED
         */
        
        const STATUS_REJECTED = 2;
        
        /**
         * Correction ID
         * @since Version 3.9.1
         * @var int $id
         */
         
        public $id;
        
        /**
         * Comments
         * @since Version 3.9.1
         * @var string $comments
         */
        
        public $comments;
        
        /**
         * Status
         * @since Version 3.9.1
         * @var int $status
         */
        
        public $status;
        
        /**
         * Date this correction was added
         * @since Version 3.9.1
         * @var \DateTime $DateAdded
         */
        
        public $DateAdded;
        
        /**
         * Date this correction was closed
         * @since Version 3.9.1
         * @var \DateTime $DateClosed
         */
        
        public $DateClosed;
        
        /**
         * Location that this correction applies to
         * @since Version 3.9.1
         * @var \Railpage\Locations\Location $Location
         */
        
        public $Location;
        
        /**
         * Constructor
         * @since Version 3.9.1
         * @param int $correction_id
         */
        
        public function __construct($correction_id = false) {
            
            parent::__construct(); 
            
            if ($this->id = filter_var($correction_id, FILTER_VALIDATE_INT)) {
                $this->populate(); 
            }
            
        }
        
        /**
         * Set the location
         * @since Version 3.9.1
         * @param \Railpage\Locations\Location $Location
         * @return \Railpage\Locations\Correction
         */
        
        public function setLocation(Location $Location) {
            
            $this->Location = $Location;
            
            return $this;
            
        }
        
        /**
         * Populate this object
         * @since Version 3.9.1
         * @return void
         */
        
        private function populate() {
            
            $query = "SELECT * FROM location_corrections WHERE id = ?";
            
            if (!$row = $this->db->fetchRow($query, $this->id)) {
                return;
            }
            
            $this->comments = $row['comments']; 
            $this->DateAdded = new DateTime($row['date_added']); 
            $this->Location = new Location($row['location_id']);
            
            $this->setAuthor(UserFactory::CreateUser($row['user_id'])); 
            
            if (!is_null($row['date_closed'])) {
                $this->DateClosed = new DateTime($row['date_closed']); 
            }
            
            return;
            
        }
        
        /**
         * Validate changes to this correction
         * @since Version 3.9.1
         * @throws \Exception if $this->Author is not an instance of \Railpage\Users\User
         * @throws \Exception if $this->Location is not an instance of \Railpage\Locations\Location
         * @thorws \Exception if $this->comments is empty
         */
        
        private function validate() {
            
            if (!$this->Author instanceof User) {
                throw new Exception("No valid user has been set");
            }
            
            if (!$this->Location instanceof Location) {
                throw new Exception("No valid location has been set"); 
            }
            
            if (!$this->DateAdded instanceof DateTime) {
                $this->DateAdded = new DateTime;
            }
            
            if (empty($this->comments)) {
                throw new Exception("No comments were added"); 
            }
            
            if (!filter_var($this->status, FILTER_VALIDATE_INT)) {
                $this->status = self::STATUS_NEW;
            }
            
            return true;
            
        }
        
        /**
         * Commit changes to this correction
         * @since Version 3.9.1
         * @return \Railpage\Locations\Correction
         */
        
        public function commit() {
            
            $this->validate(); 
            
            $data = [
                "location_id" => $this->Location->id,
                "user_id" => $this->Author->id,
                "comments" => trim($this->comments),
                "status" => $this->status,
                "date_added" => $this->DateAdded->format("Y-m-d H:i:s")
            ];
            
            if ($this->DateClosed instanceof DateTime) {
                $data['date_closed'] = $this->DateClosed->format("Y-m-d H:i:s");
            }
            
            if (filter_var($this->id, FILTER_VALIDATE_INT)) {
                $where = [ "id = ?" => $this->id ];
                $this->db->update("location_corrections", $data, $where); 
            } else {
                $this->db->insert("location_corrections", $data); 
                $this->id = filter_var($this->db->lastInsertId(), FILTER_VALIDATE_INT); 
            }
            
            return $this;
            
        }
        
        /**
         * Reject this correction
         * @since Version 3.9.1
         * @param \Railpage\Users\User $Staff
         * @param string $comments
         * @return \Railpage\Locations\Correction
         */
        
        public function reject(User $Staff, $comments = NULL) {
            
            $this->close("reject", $Staff, $comments);
            
            return $this;
            
        }
        
        /**
         * Resolve this correction
         * @since Version 3.9.1
         * @param \Railpage\Users\User $Staff
         * @param string $comments
         * @return \Railpage\Locations\Correction
         */
        
        public function resolve(User $Staff, $comments = NULL) {
            
            $this->close("resolve", $Staff, $comments);
            
            return $this;
            
        }
        
        /**
         * Close this correction
         * @since Version 3.9.1
         * @param string $action
         * @param \Railpage\Users\User $Staff
         * @param string $comments
         * @return void
         */
        
        private function close($action = false, User $Staff, $comments = NULL) {
            
            $this->status = $action == "reject" ? self::STATUS_REJECTED : self::STATUS_CLOSED;
            $this->DateClosed = new DateTime;
            $this->commit(); 
            
            $verb = $action == "reject" ? "not accepted" : "accepted";
            
            $Message = new Message;
            $Message->setAuthor($Staff);
            $Message->setRecipient($this->Author);
            $Message->subject = "Your Locations correction was " . $verb;
            $Message->body = "Your suggested correction for [url=" . $this->Location->url->url . "]" . strval($this->Location) . "[/url] in [url=" . $this->Location->Region->url->url . "]" . strval($this->Location->Region) . "[/url] was " . $verb . " by " . $Staff->username . ".";
            
            if (!empty($this->comments)) {
                $Message->body .= "\n\n[quote=Your suggestion]" . $this->comments . "[/quote]";
            }
            
            if (!is_null($comments)) {
                $Message->body .= "\n\n" . $comments;
            }
            
            $Message->body .= "\n\nThis is an automated message - there is no need to reply.";
            
            $Message->send();
            
            return;
            
        }
        
    }