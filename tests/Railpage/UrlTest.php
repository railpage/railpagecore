<?php
	
	use Railpage\Url;
	
	class UrlTest extends PHPUnit_Framework_TestCase {
		
		public function testAdd() {
			
			$Url = new Url("/test/blah");
			
			$this->assertInstanceOf("Railpage\\Url", $Url);
			
			$this->assertEquals("/test/blah", $Url->url);
			$this->assertEquals("http://www.railpage.com.au/test/blah", $Url->canonical);
			
			$_SERVER['debug'] = true;
			
			$Url = new Url("/test/blah2"); 
			$this->assertEquals("/test/blah2", $Url->url);
			$this->assertTrue(count($Url->getURLs()) >= 1); 
			
		}
		
	}
	