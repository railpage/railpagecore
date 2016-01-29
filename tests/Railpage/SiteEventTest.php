<?php
    
    use Railpage\Users\User;
    use Railpage\SiteEvent;
    
    class SiteEventTest extends PHPUnit_Framework_TestCase {
        
        public function test_addEvent() {
            
            $User = new User;
            $User->username = "helptesterzz";
            $User->contact_email = "michael+phpunit+helpzz@railpage.com.au";
            $User->setPassword('sadfasdfaf'); 
            $User->commit(); 
            
            $Event = new SiteEvent;
            $Event->user_id = $User->id; 
            $Event->module_name = "help";
            $Event->title = "Help item created";
            $Event->args = array();
            $Event->key = "help_id";
            $Event->value = 1;
            $Event->commit();
            
            $Event = new SiteEvent($Event->id); 
            $Event->title = "sdfasdfadf";
            $Event->commit(); 
            
        }
        
        public function test_break_title() {
            
            $this->setExpectedException("Exception", "Cannot validate site event - title cannot be empty!"); 
            
            $Event = new SiteEvent;
            $Event->commit(); 
            
        }
        
        public function test_break_user() {
            
            $this->setExpectedException("Exception", "Cannot validate site event - user ID cannot be empty!"); 
            
            $Event = new SiteEvent;
            $Event->title = "asdfaf";
            $Event->commit(); 
            
        }
        
        public function test_break_key() {
            
            $this->setExpectedException("Exception", "Cannot validate site event - key cannot be empty!"); 
            
            $Event = new SiteEvent;
            $Event->title = "asdfaf";
            $Event->user_id = 1;
            $Event->commit(); 
            
        }
        
        
    }