<?php
    /**
     * Dispatch a notification via push messaging
     * @since Version 3.10.0
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Notifications\Transport;
    
    use Railpage\AppCore;
    use Railpage\Notifications\Notifications;
    use Railpage\Notifications\TransportInterface;
    use Railpage\Users\Factory as UserFactory;
    use Exception;
    use InvalidArgumentException;
    use DateTime;
    
    use GuzzleHttp\Client;
    use GuzzleHttp\Exception\RequestException;
    
    /**
     * Push
     */
    
    class Push extends AppCore implements TransportInterface {
        
        /**
         * Set the message data
         * @param array $data
         */
        
        public function setData($data) {
            
            if (!is_array($data)) {
                throw new InvalidArgumentException("No or invalid message data was sent");
            }
            
            $this->data = $data;
            
        }
        
        /**
         * Send the notification
         */
        
        public function send() {
            
            $Client = new Client;
            
            $failures = array();
            $result = array(); 
                    
            $data = [
                "registration_ids" => [ ],
                "data" => [
                    "title" => $this->data['subject'],
                    "message" => $this->data['body']
                ]
            ];
            
            
            foreach ($this->data['recipients'] as $user_id => $userdata) {
                $ThisUser = UserFactory::CreateUser($user_id); 
                
                $subscriptions = Notifications::getPushSubscriptions($ThisUser); 
                
                foreach ($subscriptions as $sub) {
                    $data['registration_ids'][] = $sub['registration_id'];
                }
            }
            
            if (empty($data['registration_ids'])) {
                return;
            }
                    
            try {
                $response = $Client->post($sub['endpoint'], [
                    "headers" => [ 
                        "Content-Type" => "application/json",
                        "Authorization" => "key=" . GOOGLE_SERVER_KEY
                    ],
                    "json" => $data
                ]);
            
                $body = $response->getBody(); 
                
                $result = json_decode($body, true);
                
                $this->removeStaleSubscriptions($result, $data['registration_ids']); 
            
                $return = array(
                    "stat" => true,
                    "failures" => $result
                );
                 
            } catch (RequestException $e) {
                
                $return = [ 
                    "stat" => false,
                    "failures" => [ 
                        "message" => $e->getMessage(),
                        "body" => $e->getRequest()->getBody()
                    ]
                ];
                
            }
            
            return $return;
            
        }
        
        /**
         * Remove stale subscriptions
         * @since Version 3.10.0
         * @param array $result
         * @param array $subscriptions
         * @return void
         */
        
        private function removeStaleSubscriptions($result, $subscriptions) {
            
            #printArray($subscriptions); 
            #printArray($result); 
            
            $unsub = [];
            
            foreach ($result['results'] as $key => $val) {
                if (!isset($val['error'])) {
                    continue;
                }
                
                $where = [ "registration_id = ?" => $subscriptions[$key] ];
                
                $this->db->delete("nuke_user_push", $where); 
                
            }
            
            return;
            
        }
        
        /**
         * Validate the notification
         */
        
        public function validate() {
            
            
            
        }
        
    }