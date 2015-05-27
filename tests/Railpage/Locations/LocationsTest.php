<?php
	
	use Railpage\Locations\Locations;
	use Railpage\Locations\Location;
	use Railpage\Locations\Country;
	use Railpage\Locations\Region;
	use Railpage\Locations\Date;
	use Railpage\Locations\DateType;
	
	class LocationsTest extends PHPUnit_Framework_TestCase {
		
		const LOCATION_NAME = "Test location";
		const LOCATION_DESC = "Test location description test blah blah";
		const LOCATION_LAT = "-37.914964";
		const LOCATION_LON = "145.370425";
		const COUNTRY_CODE = "AU";
		const COUNTRY_NAME = "Australia";
		const REGION_CODE = "VIC";
		const REGION_NAME = "Victoria";
		
		public function testAddLocation() {
			
			$Location = new Location;
			$Location->lat = self::LOCATION_LAT;
			$Location->lon = self::LOCATION_LON;
			$Location->name = self::LOCATION_NAME;
			$Location->desc = self::LOCATION_DESC;
			
			$Location->commit(); 
			
			$this->assertFalse(!filter_var($Location->id, FILTER_VALIDATE_INT)); 
			$id = $Location->id;
			
			$Location = new Location($id); 
			$this->assertEquals(self::LOCATION_NAME, $Location->name); 
			$this->assertEquals(self::LOCATION_DESC, $Location->desc); 
			$this->assertEquals(self::LOCATION_LAT, $Location->lat); 
			$this->assertEquals(self::LOCATION_LON, $Location->lon); 
			$this->assertEquals(Location::STATUS_INACTIVE, $Location->active); 
			
			return $id;
			
		}
		
		/**
		 * @depends testAddLocation
		 */
		
		public function testUpdateLocation($id) {
			
			$Location = new Location($id);
			$Location->name = "asdfadfaadfadf";
			$Location->commit(); 
			
			$Location = new Location($id); 
			$this->assertEquals("asdfadfaadfadf", $Location->name); 
			
		}
		
		/**
		 * @depends testAddLocation
		 */
		
		public function testApproveLocation($id) {
			
			$Location = new Location($id); 
			$Location->approve(); 
			
			$Location = new Location($id); 
			$this->assertEquals(Location::STATUS_ACTIVE, $Location->active); 
			
		}
		
	}