<?php
    
    use Railpage\Locos\LocoClass;
    
    class LocoClassTest extends PHPUnit_Framework_TestCase {
        
        public function test_break_name() {
            
            $this->setExpectedException("Exception", "Locomotive class name cannot be empty");
            
            $LocoClass = new LocoClass;
            $LocoClass->commit(); 
            
        }
        
        public function test_break_introduced() {
            
            $this->setExpectedException("Exception", "Year introduced cannot be empty");
            
            $LocoClass = new LocoClass;
            $LocoClass->name = "Test class";
            $LocoClass->commit(); 
            
        }
        
        public function test_break_manufacturer() {
            
            $this->setExpectedException("Exception", "Manufacturer ID cannot be empty");
            
            $LocoClass = new LocoClass;
            $LocoClass->name = "Test class";
            $LocoClass->introduced = "2015";
            $LocoClass->commit(); 
            
        }
        
        public function test_break_arrangement() {
            
            $this->setExpectedException("Exception", "Wheel arrangement ID cannot be empty");
            
            $LocoClass = new LocoClass;
            $LocoClass->name = "Test class";
            $LocoClass->introduced = "2015";
            $LocoClass->manufacturer_id = 1;
            $LocoClass->commit(); 
            
        }
        
        public function test_break_type() {
            
            $this->setExpectedException("Exception", "Locomotive type ID cannot be empty");
            
            $LocoClass = new LocoClass;
            $LocoClass->name = "Test class";
            $LocoClass->introduced = "2015";
            $LocoClass->manufacturer_id = 1;
            $LocoClass->wheel_arrangement_id = 1;
            $LocoClass->commit(); 
            
        }
        
        
    }