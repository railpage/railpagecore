<?php
    /**
     * Notification transport interface
     * @since Version 3.9.1
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Notifications;
    
    /**
     * Transport Interface
     */
    
    interface TransportInterface {
        
        /**
         * Set the message data
         * @param array $data
         */
        
        public function setData($data);
        
        /**
         * Send the notification
         */
        
        public function send();
        
        /**
         * Validate the notification
         */
        
        public function validate(); 
        
    }