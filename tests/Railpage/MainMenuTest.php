<?php
    
    use Railpage\MainMenu;
    
    class MainMenuTest extends PHPUnit_Framework_TestCase {
        
        public function testAdd() {
            
            $Menu = new MainMenu;
            
            $Menu->Section("test")->Section("Balls")->Section("testing");
            
            $Menu->Section("test"); 
            $Menu->Add("test");
            $Menu->Get(); 
            
        }
        
    }
    