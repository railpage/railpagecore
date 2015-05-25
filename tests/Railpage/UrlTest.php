<?php
	
	use Railpage\Url;
	
	class UrlTest extends PHPUnit_Framework_TestCase {
		
		public function testAdd() {
			
			$Url = new Url("/test/blah");
			
			$this->assertInstanceOf("Railpage\\Url", $Url);
			
			$this->assertEquals("/test/blah", $Url->url);
			$this->assertEquals("http://www.railpage.com.au/test/blah", $Url->canonical);
			
		}
		
	}
	