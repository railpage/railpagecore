<?php
    
    use Railpage\PageControls;
    
    class PageControlsTest extends PHPUnit_Framework_TestCase {
        
        public function testAdd() {
            
            $Controls = new PageControls;
            $Controls->addControl(['href' => "/", 'text' => 'test']); 
            $Controls->addControl(['href' => "/df", 'text' => 'tessdft']); 
            
            $Controls->removeControl("/"); 
            $Controls->removeControl("notalink"); 
            $Controls->addControl(['href' => "/", 'text' => 'test']); 
            $Controls->removeControl("/", "test"); 
            
            $Controls->addControl();
            $Controls->removeControl(); 
            
            $Controls->addControl([
                "href" => "/asdf",
                "text" => "asdasfdaff",
                "title" => "z title",
                "rel" => "sadfadf",
                "id" => "222234sdsfs",
                "glyphicon" => "zzzzzzzzzz",
                "other" => [
                    "zsfdszzzz" => "4234ssfsdf"
                ],
                "data" => [
                    "wahtever-thing" => "asdf"
                ]
            ]);
            
            $Controls->__toString();
            
            $Controls = new PageControls(PAGECONTROL_TYPE_BUTTON);
            $Controls->__toString();
            
            $Controls->addControl(['href' => "/", 'text' => 'test']); 
            $Controls->addControl(['href' => "/df", 'text' => 'tessdft']); 
            $Controls->__toString();
            
        }
        
    }
    