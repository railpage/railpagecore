<?php
	
	use Railpage\Breadcrumb;
	
	class BreadcrumbTest extends PHPUnit_Framework_TestCase {
		
		public function testAdd() {
			
			$Breadcrumb = new Breadcrumb;
			
			$this->assertInstanceOf("Railpage\\Breadcrumb", $Breadcrumb);
			
			$Breadcrumb->Add("Test item", "test url");
			
			$this->assertEquals("Test item", $Breadcrumb->menu[0]['title']);
			$this->assertEquals("test url", $Breadcrumb->menu[0]['url']);
			
		}
		
	}
	
?>