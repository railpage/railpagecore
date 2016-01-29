<?php
    /**
     * Send a push notifications
     * @since Version 3.10.0
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Images\Utility;
    
    use Railpage\Config\Base as Config;
    use Railpage\SiteMessages\SiteMessages;
    use Railpage\SiteMessages\SiteMessage;
    use Railpage\AppCore;
    use Railpage\Module;
    use Railpage\Users\User;
    use Railpage\Users\Factory as UserFactory;
    use Exception;
    use DateTime;
    use DateInterval;
    use DatePeriod;
    use stdClass;
    use Railpage\ContentUtility;
    use Railpage\Images\Image;
    use Railpage\Images\Competition;
    use Railpage\Images\Competitions;
    
    use Railpage\Notifications\Notifications;
    use Railpage\Notifications\Notification;
    
    
    class PushNotify {
        
        /**
         * Send a push notification to all image screeners when a photo has been submitted to a competition
         * @since Version 3.10.0
         * @param \Railpage\Images\Competition $Competition
         * @param \Railpage\Images\Image $Image
         * @param \Railpage\Users\User $User
         * @return void
         * @todo finish it!
         */
        
        public static function photoAwaitingApproval(Competition $Competition, Image $Image, User $User) {
            
            $Push = new Notification;
            $Push->subject = sprintf("%s photo comp - new submission", $Competition->title);
            $Push->body = sprintf("A photo has been submitted to the %s photo competition by %s. Please review this photo!", $Competition->title, $User->username); 
            $Push->setActionUrl($Competition->url->pending); 
            $Push->transport = Notifications::TRANSPORT_PUSH;
            
            return;
            
            // add screeners in here. somehow
            
            $Push->commit()->dispatch(); 
            
        }
        
        
        
    }