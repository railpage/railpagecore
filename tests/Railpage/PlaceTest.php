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
			$this->assertInstanceOf("Railpage\\Locations\\Region", $Place->Region);
			$this->assertInternalType("float", $Place->boundingBox->southWest->lat);
		}
		
		public function testGetAddress() {
			$lat = "-37.13009600";
			$lon = "145.07711000";
			
			$Place = new Place($lat, $lon);
			
			$this->assertInternalType("array", $Place->getAddress());
		}
		
		public function testGetWeather() {
			$lat = "-37.13009600";
			$lon = "145.07711000";
			
			$Place = new Place($lat, $lon);
			
			$weather = $Place->getWeatherForecast();
			$this->assertInternalType("array", $weather);
			$this->assertCount(14, $weather);
		}
	}
?>