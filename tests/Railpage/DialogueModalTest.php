<?php
    
    use Railpage\DialogueModal;
    
    class DialogueModalTest extends PHPUnit_Framework_TestCase {
        
        public function testAdd() {
            
            $Modal = new DialogueModal("A testing modal", "testing body", [ "element" => "button", "class" => "btn-large" ]);
            $Modal->setHeader("testing"); 
            $Modal->setBody("this is a test body"); 
            $Modal->addLinkAction("linkylinky", "/asdafd", "classssss"); 
            $Modal->addButtonAction("linkylinky", "classssss", "submit", [ "sdadsdfa" => "sadf" ]); 
            #$Modal->__toString(); 
            
            
        }
        
    }
    