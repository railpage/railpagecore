<?php
    
    use Railpage\Breadcrumb;
    
    class BreadcrumbTest extends PHPUnit_Framework_TestCase {
        
        public function testAdd() {
            
            $Breadcrumb = new Breadcrumb;
            
            $this->assertInstanceOf("Railpage\\Breadcrumb", $Breadcrumb);
            
            $Breadcrumb->Add("Test item", "test url");
            
            $this->assertEquals("Test item", $Breadcrumb->menu[0]['title']);
            $this->assertEquals("test url", $Breadcrumb->menu[0]['url']);
            
            $Breadcrumb->Add("test non link");
            
            $Breadcrumb->Add();
            
            $this->assertTrue(!empty(strval($Breadcrumb))); 
            
            $Breadcrumb->Remove("test non link"); 
            $Breadcrumb->Remove(); 
            
            $Breadcrumb->ReplaceItem(); 
            $Breadcrumb->ReplaceItem("Test item", "/testurl2");
            
        }
        
    }
    