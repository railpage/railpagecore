<?php
    
    use Railpage\Events\EventCategory;
    
    class EventCategoryTest extends PHPUnit_Framework_TestCase {
        
        public function test_break_name() {
            
            $this->setExpectedException("Exception", "Event name cannot be empty");
            
            $Category = new EventCategory;
            $Category->commit(); 
            
        }
        
        public function test_validate_desc() {
            
            $Category = new EventCategory;
            $Category->name = "adsfasdf";
            $Category->commit(); 
            
        }
        
    }