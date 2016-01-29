<?php
    
    use Railpage\Locos\Correction;
    use Railpage\Locos\LocoClass;
    
    class CorrectionTest extends PHPUnit_Framework_TestCase {
        
        public function test_break_text() {
            
            $this->setExpectedException("Exception", "Cannot validate changes to this correction: no text provided");
            
            $Correction = new Correction;
            $Correction->commit(); 
            
        }
        
        public function test_break_object() {
            
            $this->setExpectedException("Exception", "Cannot validate changes to this correction: no locomotive or locomotive class provided");
            
            $Correction = new Correction;
            $Correction->text = "adfaf";
            $Correction->commit(); 
            
        }
        
        public function test_break_user() {
            
            $this->setExpectedException("Exception", "Cannot validate changes to this correction: no valid user provided");
            
            $Correction = new Correction;
            $Correction->text = "adfaf";
            $Correction->setObject(new LocoClass);
            $Correction->commit(); 
            
        }
    }