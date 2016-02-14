<?php

use Railpage\Images\Collection;

class CollectionTest extends PHPUnit_Framework_TestCase {
    
    public function test_break_name() {
        
        $this->setExpectedException("Exception", "Title cannot be empty"); 
        
        $Collection = new Collection;
        $Collection->commit(); 
        
    }
    
    public function test_break_description() {
        
        $this->setExpectedException("Exception", "Description cannot be empty"); 
        
        $Collection = new Collection;
        $Collection->name = "asdff";
        $Collection->commit(); 
        
    }
}