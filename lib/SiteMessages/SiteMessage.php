<?php
    /**
     * Site Message
     * @since Version 3.9
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\SiteMessages;
    
    use Railpage\Users\User;
    use Railpage\AppCore;
    use Railpage\Url;
    use Exception;
    use DateTime;
    
    class SiteMessage extends AppCore {
        
        /**
         * Status: active
         * @since Version 3.9
         * @const STATUS_ACTIVE
         */
        
        const STATUS_ACTIVE = 1;
        
        /**
         * Status: inactive
         * @since Version 3.9
         * @const STATUS_INACTIVE
         */
        
        const STATUS_INACTIVE = 0;
        
        /**
         * Message ID
         * @since Version 3.9
         * @var int $id
         */
        
        public $id;
        
        /**
         * Message title
         * @since Version 3.9
         * @var string $title
         */
        
        public $title;
        
        /**
         * Message text
         * @since Version 3.9
         * @var string $text
         */
        
        public $text;
        
        /**
         * Message status
         * @since Version 3.9
         * @var int $status
         */
        
        public $status;
        
        /**
         * An object that this site message is linked to
         * @since Version 3.9.1
         * @var object $Object
         */
        
        public $Object;
        
        /**
         * Date this message will start from
         * @since Version 3.9
         * @var \DateTime $Start
         */
        
        public $Start;
        
        /**
         * Date this message will expire
         * @since Version 3.9
         * @var \DateTime $End
         */
        
        public $End;
        
        /**
         * Constructor
         * @since Version 3.9
         * @param int $id
         */
        
        public function __construct($id = false) {
            parent::__construct(); 
            
            if ($id && filter_var($id, FILTER_VALIDATE_INT)) {
                $this->id = $id;
                
                $query = "SELECT * FROM messages WHERE message_id = ?";
                
                $row = $this->db->fetchRow($query, $this->id);
                
                $this->title = isset($row['message_title']) ? $row['message_title'] : "";
                $this->text = $row['message_text'];
                $this->status = $row['message_active'];
                
                if ($row['date_start'] != "0000-00-00") {
                    $this->Start = new DateTime($row['date_start']);
                }
                
                if ($row['date_end'] != "0000-00-00") {
                    $this->End = new DateTime($row['date_end']);
                }
                
                $this->makeURLs();
            }
        }
        
        /**
         * Validate this message
         * @since Version 3.9
         * @return boolean
         */
        
        private function validate() {
            if (empty($this->text)) {
                throw new Exception("SiteMessage text cannot be empty");
            }
            
            if (empty($this->status) || !filter_var($this->status, FILTER_VALIDATE_INT)) {
                $this->status = self::STATUS_ACTIVE;
            }
            
            if (empty($this->title)) {
                $this->title = "";
            }
            
            return true;
        }
        
        /**
         * Subimt changes to this site message
         * @since Version 3.9
         * @return \Railpage\SiteMessages\SiteMessage
         */
        
        public function commit() {
            $this->validate(); 
            
            $data = array(
                "message_active" => $this->status,
                "message_text" => $this->text,
                "message_title" => $this->title,
                "date_start" => $this->Start instanceof DateTime ? $this->Start->format("Y-m-d") : "0000-00-00",
                "date_end" => $this->End instanceof DateTime ? $this->End->format("Y-m-d") : "0000-00-00"
            );
            
            if (is_object($this->Object)) {
                $data['object_ns'] = isset($this->Object->namespace) ? $this->Object->namespace : $this->Object->Module->namespace;
                $data['object_id'] = $this->Object->id;
            }
            
            $data['target_user'] = isset($this->User) && $this->User instanceof User ? $this->User->id : 0;
            
            if (filter_var($this->id, FILTER_VALIDATE_INT)) {
                $where = array(
                    "message_id = ?" => $this->id
                );
                
                $this->db->update("messages", $data, $where);
            } else {
                $this->db->insert("messages", $data);
                $this->id = $this->db->lastInsertId();
                
                $this->makeURLs();
            }
            
            return $this;
        }
        
        /**
         * Make URLs for this object
         * @since Version 3.9
         * @return \Railpage\SiteMessages\SiteMessage
         */
        
        public function makeURLs() {
            $this->url = new Url("");
            $this->url->edit = sprintf("/administrators?mode=messages.edit&id=%d", $this->id);
            $this->url->dismiss = sprintf("/messages/dismiss/%d", $this->id);
            
            return $this;
        }
        
        /**
         * Target this site message to a specific user
         * @since Version 3.9.1
         * @return \Railpage\SiteMessages\SiteMessage
         * @param \Railpage\Users\User $User
         */
        
        public function targetUser(User $User) {
            $this->User = $User;
            
            return $this;
        }
    }
    
    