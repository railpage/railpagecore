<?php
	
	use Railpage\Locations\Locations;
	use Railpage\Locations\Location;
	use Railpage\Locations\Country;
	use Railpage\Locations\Region;
	use Railpage\Locations\Date;
	use Railpage\Locations\DateType;
	use Railpage\Locations\Correction;
	use Railpage\Users\User;
	
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
			
			$NewLocation = new Location($Location->slug); 
			$this->assertEquals($Location->id, $NewLocation->id); 
			
			$Database = $NewLocation->getDatabaseConnection(); 
			$data = [ "slug" => "" ];
			$where = [ "id = ?" => $NewLocation->id ];
			$Database->update("location", $data, $where); 
			
			$NewLocation = new Location($Location->id);
			
			return $id;
			
		}
		
		/**
		 * @depends testAddLocation
		 */
		
		public function test_duplicate($location_id) {
			
			$Location = new Location($location_id); 
			$Location = clone $Location; 
			
			$Location->id = NULL;
			$Location->slug = NULL; 
			$Location->commit(); 
			
			return $Location;
			
		}
		
		public function test_addUser() {
			
			$User = new User;
			$User->username = "locations";
			$User->contact_email = "michael+phpunit+locations@railpage.com.au";
			$User->setPassword("asdfafasfasdf");
			$User->commit(); 
			
			return $User;
			
		}
		
		/**
		 * @depends test_duplicate
		 * @depends test_addUser
		 */
		
		public function test_like($Location, $User) {
			
			$this->assertFalse($Location->doesUserLike($User->id));
			$this->assertFalse($Location->doesUserLike($User));
			
			$this->assertTrue($Location->recommend($User->id)); 
			
			$this->assertTrue($Location->doesUserLike($User->id));
			
			$this->assertFalse($Location->recommend($User)); 
			
		}
		
		/**
		 * @depends test_duplicate
		 * @depends test_addUser
		 */
		
		public function test_like_break_lookup($Location, $User) {
			
			//$this->setExpectedException("InvalidArgumentException", "No user ID provided");
			$this->assertFalse($Location->doesUserLike());
			
		}
		
		/**
		 * @depends test_duplicate
		 * @depends test_addUser
		 */
		
		public function test_like_break_recommend($Location, $User) {
			
			$this->setExpectedException("InvalidArgumentException", "No user ID provided"); 
			$Location->recommend(); 
			
		}
		
		/**
		 * @depends test_duplicate
		 */
		
		public function test_getArray($Location) {
			
			$this->assertTrue(is_array($Location->getArray()));
			
		}
		
		/**
		 * @depends test_duplicate
		 */
		
		public function test_getDates($Location) {
			
			$Location->getDates();
			
		}
		
		/**
		 * @depends test_duplicate
		 * @depends test_addUser
		 */
		
		public function test_addCorrection($Location, $User) {
			
			$Correction = new Correction;
			$Correction->comments = "asdfsadf";
			$Correction->setLocation($Location)->setAuthor($User)->commit(); 
			
			$this->assertFalse(!filter_var($Correction->id, FILTER_VALIDATE_INT));
			
			$Correction = new Correction($Correction->id); 
			
			$New = new Correction(99999); 
			$this->assertTrue(empty($New->comments)); 
			
			return $Correction;
			
		}
		
		/**
		 * @depends test_addCorrection
		 */
		
		public function test_updateCorrection($Correction) {
			
			$Correction->comments = "asfasdfasdfafdasdfasdfadf";
			$Correction->commit(); 
			
		}
		
		/**
		 * @depends test_addCorrection
		 */
		
		public function test_break_correction_author($Correction) {
			
			$this->setExpectedException("Exception", "No valid user has been set"); 
			
			$Correction = clone $Correction; 
			$Correction->Author = NULL;
			$Correction->commit(); 
			
		}
		
		/**
		 * @depends test_addCorrection
		 */
		
		public function test_break_correction_location($Correction) {
			
			$this->setExpectedException("Exception", "No valid location has been set"); 
			
			$Correction = clone $Correction; 
			$Correction->Location = NULL;
			$Correction->commit(); 
			
		}
		
		/**
		 * @depends test_addCorrection
		 */
		
		public function test_break_correction_comments($Correction) {
			
			$this->setExpectedException("Exception", "No comments were added"); 
			
			$Correction = clone $Correction; 
			$Correction->comments = NULL;
			$Correction->commit(); 
			
		}
		
		/**
		 * @depends test_addCorrection
		 * @depends test_addUser
		 */
		
		public function test_rejectCorrection($Correction, $User) {
			
			$Correction->reject($User, "because I can"); 
			
		}
		
		/**
		 * @depends test_addCorrection
		 * @depends test_addUser
		 */
		
		public function test_approveCorrection($Correction, $User) {
			
			$Correction->resolve($User, "because I can also approve it"); 
			
			$Correction = new Correction($Correction->id);
			
		}
		
		/**
		 * @depends test_duplicate
		 * @depends test_addUser
		 * @depends test_addCorrection
		 */
		
		public function test_getContributors($Location, $User, $Correction) {
			$Location->getContributors(); 
		}
		
		/**
		 * @depends test_duplicate
		 */
		
		public function test_break_validate_lat($Location) {
			
			$this->setExpectedException("Exception", "Cannot validate location - no latitude value"); 
			$Location = clone $Location;
			$Location->lat = NULL;
			$Location->commit();
			
		}
		
		/**
		 * @depends test_duplicate
		 */
		
		public function test_break_validate_lon($Location) {
			
			$this->setExpectedException("Exception", "Cannot validate location - no longitude value"); 
			$Location = clone $Location;
			$Location->lon = NULL;
			$Location->commit();
			
		}
		
		/**
		 * @depends test_duplicate
		 */
		
		public function test_break_validate_name($Location) {
			
			$this->setExpectedException("Exception", "Cannot validate location - no name specified"); 
			$Location = clone $Location;
			$Location->name = NULL;
			$Location->commit();
			
		}
		
		/**
		 * @depends test_duplicate
		 */
		
		public function test_break_validate_description($Location) {
			
			$this->setExpectedException("Exception", "Cannot validate location - no description specified"); 
			$Location = clone $Location;
			$Location->desc = NULL;
			$Location->commit();
			
		}
			
		
		public function test_break_loadLocation() {
			
			$this->setExpectedException("Exception", "Unable to fetch data for location ID " . 9999); 
			$Location = new Location(9999); 
			
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
			
			$this->setExpectedException("Exception", "Cannot approve location - no location created yet"); 
			$Location->id = NULL; 
			$Location->approve(); 
			
		}
		
		/**
		 * @depends testAddLocation
		 */
		
		public function testRejectLocation($id) {
			
			$Location = new Location($id); 
			$Location = clone $Location;
			$Location->id = NULL; 
			$Location->desc = "asdfasdfaf";
			$Location->commit();
			$Location->reject(); 
			
			$this->setExpectedException("Exception", "Cannot reject location - no location created yet"); 
			
			$Location = new Location($id); 
			$Location = clone $Location;
			$Location->id = NULL; 
			$Location->reject();
			
		}
		
		/**
		 * @depends test_duplicate
		 */
		
		public function test_getPhotos($Location) {
			
			$Location->getPhotosForSite(); 
			
		}
		
		/**
		 * @depends test_duplicate
		 */
		
		public function test_newCountry($Location) {
			
			$Country = clone $Location->Region->Country;
			
			$Country->getRegions(); 
			$Country->getLocations(); 
			
			$Region = clone $Location->Region;
			
			$Region->getLocations(); 
			
		}
		
		public function test_break_region() {
			
			$this->setExpectedException("InvalidArgumentException", "No country was specified"); 
			
			$Region = new Region;
			
		}
		
		public function test_locations() {
			
			$Locations = new Locations;
			$Locations->getCountries(); 
			$Locations->getRegions();
			$Locations->getLocations(); 
			$Locations->getPending(); 
			$Locations->getDateTypes(); 
			$Locations->getRandomLocation(); 
			$Locations->getOpenCorrections();
			
		}
		
	}