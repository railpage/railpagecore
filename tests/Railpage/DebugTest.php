<?php
    
    use Railpage\Debug;
    
    class DebugTest extends PHPUnit_Framework_TestCase {
        
        public function testAdd() {
            
            if (!defined("RP_DEBUG")) {
                define("RP_DEBUG", true); 
            }
            
            $timer = Debug::getTimer(); 
            Debug::logEvent("testing", $timer); 
            
            Debug::getLog(); 
            
            Debug::printPretty();
            
            //Debug::SaveError(new Exception("zomg this is shit"));  
            
            Debug::printArray("asdafd"); 
            
        }
        
    }
    