<?php
    /**
     * Push notifications utility class
     * @since Version 3.10.0
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Notifications\Utility;
    
    use Railpage\Notifications\Notifications;
    use Railpage\AppCore;
    use Railpage\Debug;
    use Railpage\Users\User;
    use Railpage\Users\UserFactory;
    use DateTime;
    use Exception;
    use InvalidArgumentException;
    
    class PushUtility {
        
        /**
         * Find a user ID from a given push registration ID
         * @since Version 3.10.0
         * @param string $registration_id
         * @return int
         */
        
        public static function getUserIdFromRegistrationId($registration_id) {
            
            $Database = AppCore::GetDatabase();
            $Memcached = AppCore::GetMemcached(); 
            
            $key = sprintf("railpage:gcm.subscription.key=%s", $registration_id); 
    
            if (!$user_id = $Memcached->fetch($key)) {
                $query = "SELECT user_id FROM nuke_user_push WHERE registration_id = ?";
                
                $user_id = $Database->fetchOne($query, $registration_id); 
                $Memcached->save($key, $user_id, 0); 
            }
            
            return $user_id; 
            
        }
        
        /**
         * Get most recent push notification for a given user
         * @since Version 3.10.0
         * @param \Railpage\Users\User|int $User
         * @return array
         */
        
        public static function getCurrentNotification($User) {
            if ($User instanceof User) {
                $User = $User->id;
            }
            
            $query = "SELECT n.* FROM notifications AS n LEFT JOIN notifications_recipients AS nr ON n.id = nr.notification_id WHERE nr.user_id = ? AND n.transport = ? ORDER BY n.date_sent DESC LIMIT 1";
            
            $params = [ $User, Notifications::TRANSPORT_PUSH ];
            
            $result = AppCore::GetDatabase()->FetchRow($query, $params); 
            
            $result['meta'] = json_decode($result['meta'], true); 
            
            return $result;
            
            
        }
        
    }