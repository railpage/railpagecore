<?php
    
    use Railpage\Notifications\Notifications;
    use Railpage\Notifications\Notification;
    use Railpage\Users\User; 
    
    
    class NotificationsTest extends PHPUnit_Framework_TestCase {
        
        public function test_createUser() {
            
            $User = new User;
            
            $User->username = "phpunit_notifications";
            $User->contact_email = "michael+phpunit_notifications@railpage.com.au";
            
            $User->setPassword("letmein1234");
            $User->commit(); 
            
            return $User;
            
        }
        
        /**
         * @depends test_createUser
         */
        
        public function testCreateNotification($User) {
            
            $Notification = new Notification;
            $Notification->subject = "test";
            $Notification->body = "such test, many wow";
            $Notification->Author = $User;
            $Notification->setActionUrl("https://www.google.com.au"); 
            $Notification->addRecipient($User->id, $User->username, $User->contact_email); 
            $Notification->addHeader("blah", "thing"); 
            $Notification->commit();
            $Notification->dispatch(); 
            
            $Notification->getRecipients(); 
            
            return $Notification;
            
        }
        
        /**
         * @depends testCreateNotification
         * @depends test_createUser
         */
        
        public function testGetNotifications($Notification, $User) {
            
            $Notifications = new Notifications;
            $Notifications->getNotificationsWithStatus(); 
            $Notifications->getNotificationsByTransport(); 
            
            $Notifications->Recipient = $User; 
            $Notifications->getNotificationsForUser(); 
            
            $Notifications->getAllNotifications(); 
            
            $Notifications->getPastNotificationsForUser($User);
            
            $Notifications->setPushSubscription($User, "21312323132131321");
            $Notifications->getPushSubscriptions($User);
            $Notifications->setPushSubscription($User, "21312323132131321", false);
            
        }

    }
    