<?php
	
	use Railpage\Events\Events;
	use Railpage\Events\Event;
	use Railpage\Events\EventDate;
	use Railpage\Events\EventCategory;
	use Railpage\Users\User;
	use Railpage\Place;
	
	class EventTest extends PHPUnit_Framework_TestCase {
		
		const CAT_NAME = "Test Category";
		const CAT_DESC = "This is a test description";
		const EVENT_NAME = "Test event";
		const EVENT_DESC = "Test event descriptive text";
		const LAT = "-37.806619";
		const LON = "144.932102";
		const EVENT_DATE = "2015-12-01";
		const EVENT_START = "7am"; 
		const EVENT_END = "4pm";
		const TIMEZONE = "Australia/Melbourne";
		
		public function testAddCategory() {
			$Category = new EventCategory;
			$Category->name = self::CAT_NAME;
			$Category->desc = self::CAT_DESC;
			$Category->commit(); 
			
			$this->assertFalse(!filter_var($Category->id, FILTER_VALIDATE_INT));
			
			return $Category->id;
		}
		
		/**
		 * @depends testAddCategory
		 */
		
		public function testCompareCategory($cat_id) {
			$Category = new EventCategory($cat_id); 
			
			$this->assertEquals(self::CAT_NAME, $Category->name);
			$this->assertEquals(self::CAT_DESC, $Category->desc);
		}
		
		public function testAddUser() {

			$User = new User;
			
			$User->username = "EventUser";
			$User->contact_email = "michael+phpunitevents@railpage.com.au";
			$User->setPassword("letmein1234");
			$User->commit(); 
			
			$this->assertFalse(!filter_var($User->id, FILTER_VALIDATE_INT));
			
			return $User->id;
		}
		
		/**
		 * @depends testAddCategory
		 * @depends testAddUser
		 */
		
		public function testAddEvent($cat_id, $user_id) {
			$Category = new EventCategory($cat_id); 
			$User = new User($user_id);
			
			$Event = new Event; 
			$Event->title = self::EVENT_NAME;
			$Event->desc = self::EVENT_DESC;
			$Event->Category = $Category;
			$Event->setAuthor($User); 
			
			$Event->commit(); 
			
			$this->assertFalse(!filter_var($Event->id, FILTER_VALIDATE_INT));
			
			return $Event->id;
		}
		
		/**
		 * @depends testAddEvent
		 */
		
		public function testCompareEvent($event_id) {
			$Event = new Event($event_id); 
			
			$this->assertEquals(self::EVENT_NAME, $Event->title); 
			$this->assertEquals(self::EVENT_DESC, $Event->desc); 
			$this->assertInstanceOf("\\Railpage\\Events\\EventCategory", $Event->Category);
			
			$this->assertFalse(!filter_var($Event->id, FILTER_VALIDATE_INT));
			$this->assertFalse(!filter_var($Event->Category->id, FILTER_VALIDATE_INT));
		}
		
		/**
		 * @depends testAddEvent
		 */
		
		public function testAddPlace($event_id) {
			$Event = new Event($event_id); 
			$Place = new Place(self::LAT, self::LON); 
			$Event->Place = $Place;
			$Event->commit(); 
			
			return $Event->id;
		}
		
		/**
		 * @depends testAddPlace
		 */
		
		public function testComparePlace($event_id) {
			$Event = new Event($event_id); 
			
			$this->assertEquals(self::LAT, $Event->Place->lat); 
			$this->assertEquals(self::LON, $Event->Place->lon);
		}
		
		/**
		 * @depends testAddEvent
		 */
		
		public function testAddDate($event_id) {
			$Event = new Event($event_id); 
			
			$EventDate = new EventDate;
			
			$Date = new DateTime(self::EVENT_DATE); 
			$Date->setTimezone(new DateTimeZone("Australia/Melbourne"));
			
			$Start = new DateTime(sprintf("%s %s", self::EVENT_DATE, self::EVENT_START));
			$Start->setTimezone(new DateTimeZone("Australia/Melbourne"));
			
			$End = new DateTime(sprintf("%s %s", self::EVENT_DATE, self::EVENT_END));
			$End->setTimezone(new DateTimeZone("Australia/Melbourne"));
			
			$EventDate->Date = $Date;
			$EventDate->Event = $Event;
			$EventDate->Start = $Start;
			$EventDate->End = $End;
			$EventDate->setAuthor(new User($Event->Author->id));
			$EventDate->commit(); 
			
			$this->assertFalse(!filter_var($EventDate->id, FILTER_VALIDATE_INT));
			$this->assertEquals(Events::STATUS_UNAPPROVED, $EventDate->status);
			
			return $EventDate->id; 
		}
		
		/**
		 * @depends testAddDate
		 */
		
		public function testCompareDate($event_date_id) {
			$EventDate = new EventDate($event_date_id);
			
			$this->assertEquals(self::EVENT_DATE, $EventDate->Date->format("Y-m-d")); 
			$this->assertEquals(self::EVENT_START, $EventDate->Start->format("ga"));
			$this->assertEquals(self::EVENT_END, $EventDate->End->format("ga"));
		}
		
		/**
		 * @depends testAddDate
		 */
		
		public function testApproveDate($event_date_id) {
			$EventDate = new EventDate($event_date_id); 
			$EventDate->approve(); 
			
			$this->assertEquals(Events::STATUS_APPROVED, $EventDate->status); 
		}
		
		public function testGetEventsForDate() {
			$Date = new DateTime(self::EVENT_DATE); 
			
			$result = (new Events)->getEventsForDate($Date);
			
			$this->assertEquals(1, count($result)); 
			
			$EventDate = new EventDate($result[0]['id']); 
			$this->assertEquals(self::EVENT_NAME, $EventDate->Event->title);
		}
	}
	