<?php
	use Railpage\Place;
	
	class PlaceTest extends PHPUnit_Framework_TestCase {
		
		public function testGetPlace() {
			$lat = "-37.13009600";
			$lon = "145.07711000";
			
			$Place = new Place($lat, $lon);
			
			$this->assertEquals($lat, $Place->lat);
			$this->assertEquals($lon, $Place->lon);
			$this->assertInstanceOf("Railpage\\Locations\\Country", $Place->Country);
		}
	}
?>