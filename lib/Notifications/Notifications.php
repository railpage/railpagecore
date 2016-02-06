<?php
    /**
     * System notifications, eg email, gitter or something else
     * @since Version 3.9.1
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Notifications;
    
    use Railpage\Users\User;
    use Railpage\AppCore;
    use Exception;
    use DateTime;
    
    /**
     * Notifications
     * @since Version 3.9.1
     */
    
    class Notifications extends AppCore {
        
        /**
         * Transport: Email
         * @since Version 3.9.1
         * @const int TRANSPORT_EMAIL
         */
        
        const TRANSPORT_EMAIL = 1;
        
        /**
         * Transport: Push
         * @since Version 3.10.0
         * @const int TRANSPORT_PUSH
         */
        
        const TRANSPORT_PUSH = 2;
        
        /**
         * Status: Queued
         * @since Version 3.9.1
         * @const int STATUS_QUEUED
         */
        
        const STATUS_QUEUED = 0; 
        
        /**
         * Status: Sent
         * @since Version 3.9.1
         * @const int STATUS_SENT
         */
         
        const STATUS_SENT = 1;
        
        /**
         * Status: Error
         * @since Version 3.9.1
         * @const int STATUS_ERROR
         */
        
        const STATUS_ERROR = 2;
        
        /**
         * Fetch notifications in a specified state
         * @since Version 3.9.1
         * @param int $status
         * @return array
         */
        
        public function getNotificationsWithStatus($status = self::STATUS_QUEUED) {
            $query = "SELECT * FROM notifications WHERE status = ?";
            
            return $this->db->fetchAll($query, $status);
        }
        
        /**
         * Fetch notifications by transport type
         * @since Version 3.9.1
         * @param int $transport
         * @return array
         */
        
        public function getNotificationsByTransport($transport = self::TRANSPORT_EMAIL) {
            $query = "SELECT * FROM notifications WHERE transport = ?";
            
            return $this->db->fetchAll($query, $transport);
        }
        
        /**
         * Fetch notifications for a specified user
         * @since Version 3.9.1
         * @return array
         */
        
        public function getNotificationsForUser() {
            if (!isset($this->Recipient) || !$this->Recipient instanceof User) {
                throw new Exception("\$this->Recipient is not an instance of \\Railpage\\Users\\User");
            }
            
            $query = "SELECT * FROM notifications WHERE id IN (SELECT id FROM notifications_recipients WHERE user_id = ?)";
            
            return $this->db->fetchAll($query, $this->Recipient->id);
        }
        
        /**
         * Get all notifications
         * @since Version 3.9.1
         * @param int $page
         * @param int $items_per_page
         * @return array
         */
        
        public function getAllNotifications($page = 1, $items_per_page = 25) {
            $query = "SELECT SQL_CALC_FOUND_ROWS * FROM notifications ORDER BY date_queued DESC LIMIT ?, ?";
            
            $where = array(
                ($page - 1) * $items_per_page, 
                $items_per_page
            );
            
            $result = $this->db->fetchAll($query, $where); 
            
            $return = array(
                "page" => $page, 
                "limit" => $items_per_page,
                "total" => $this->db->fetchOne("SELECT FOUND_ROWS() AS total"),
                "notifications" => $result
            );
            
            return $return;
        }
        
        /**
         * Get past notifications sent to a given user
         * @since Version 3.10.0
         * @param \Railpage\Users\User $User
         * @return array
         */
        
        public function getPastNotificationsForUser(User $User) {
            
            $query = "SELECT n.id, n.author, n.transport, n.status, n.date_queued, n.date_sent, n.subject, r.destination, r.date_sent
                        FROM notifications AS n 
                        LEFT JOIN notifications_recipients AS r ON r.notification_id = n.id
                        WHERE r.user_id = ?
                        ORDER BY r.date_sent, n.date_queued DESC";
            
            return $this->db->fetchAll($query, $User->id); 
            
        }
        
        /**
         * Set push notification settings for a user
         * @since Version 3.10.0
         * @param \Railpage\Users\User $User
         * @param string $subscription
         * @return void
         */
        
        public static function setPushSubscription(User $User, $subscription, $enabled = null) {
            
            $endpoint = false;
            $registration_id = false;
            $provider = false;
            
            if (strpos($subscription, "https://android.googleapis.com/gcm/send/") !== false) {
                
                $endpoint = "https://android.googleapis.com/gcm/send/";
                $registration_id = str_replace("https://android.googleapis.com/gcm/send/", "", $subscription); 
                $provider = "google";
                
            }
            
            if (!$endpoint || !$registration_id || !$provider) {
                return;
            }
            
            /**
             * Subscribe
             */
            
            if ($enabled) {
                // Check for an existing subscription
                $query = "SELECT endpoint, registration_id FROM nuke_user_push WHERE user_id = ? AND provider = ?";
                $params = [ $User->id, $provider ];
                
                $Database = AppCore::GetDatabase(); 
                $result = $Database->fetchAll($query, $params); 
                
                foreach ($result as $row) {
                    
                    if ($row['endpoint'] == $endpoint && $row['registration_id'] == $registration_id) {
                        return;
                    }
                    
                }
                
                // No matching subscription on record - save it
                $data = [
                    "user_id" => $User->id,
                    "provider" => $provider,
                    "endpoint" => $endpoint,
                    "registration_id" => $registration_id
                ];
                
                $Database->insert("nuke_user_push", $data); 
                $id = $Database->lastInsertId(); 
                
                if ((!$result || count($result) === 0) && filter_var($id, FILTER_VALIDATE_INT)) {
                    try {
                        $Push = new Notification;
                        $Push->transport = Notifications::TRANSPORT_PUSH;
                        $Push->subject = "Welcome to push notifications!";
                        $Push->body = "You'll start to receive immediate notifications here when there are new posts in your subscribed threads, or a new photo competition to enter, or anything else we think you might be interested in. This feature is in early development, so please feel free to provide feedback";
                        $Push->setActionUrl("/account")->addRecipient($User->id, $User->username, $User->username); 
                        $Push->commit()->dispatch(); 
                    } catch (Exception $e) {
                        // Throw it away
                    }
                }
            
                return;
            }
            
            /**
             * Unsubscribe
             */
            
            if ($enabled == false) {
                $where = [ "registration_id = ?" => $subscription ];
                
                $Database->delete("nuke_user_push", $where); 
            }
            
        }
        
        /**
         * Get the push subscription(s) for a user
         * @since Version 3.10.0
         * @param \Railpage\Users\User $User
         * @return array
         */
        
        public static function getPushSubscriptions(User $User) {
            
            $Database = AppCore::GetDatabase(); 
            
            $rs = $Database->fetchAll("SELECT * FROM nuke_user_push WHERE user_id = ?", $User->id); 
            
            foreach ($rs as $key => $val) {
                if ($val['endpoint'] == "https://android.googleapis.com/gcm/send/") {
                    $rs[$key]['endpoint'] = "https://android.googleapis.com/gcm/send";
                }
            }
            
            return $rs;
            
        }
    }
        