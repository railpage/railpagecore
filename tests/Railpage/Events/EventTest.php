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
		}
		
		public function testCompareCategory() {
			$Category = new EventCategory(1); 
			
			$this->assertEquals(self::CAT_NAME, $Category->name);
			$this->assertEquals(self::CAT_DESC, $Category->desc);
		}
		
		public function testAddEvent() {
			$Category = new EventCategory(1); 
			$User = new User(1);
			
			$Event = new Event; 
			$Event->title = self::EVENT_NAME;
			$Event->desc = self::EVENT_DESC;
			$Event->Category = $Category;
			$Event->setAuthor($User); 
			
			$Event->commit(); 
			
			$this->assertFalse(!filter_var($Event->id, FILTER_VALIDATE_INT));
		}
		
		public function testCompareEvent() {
			$Event = new Event(1); 
			
			$this->assertEquals(self::EVENT_NAME, $Event->title); 
			$this->assertEquals(self::EVENT_DESC, $Event->desc); 
			$this->assertInstanceOf("\\Railpage\\Events\\EventCategory", $Event->Category);
			
			$this->assertFalse(!filter_var($Event->id, FILTER_VALIDATE_INT));
			$this->assertFalse(!filter_var($Event->Category->id, FILTER_VALIDATE_INT));
		}
		
		public function testAddPlace() {
			$Event = new Event(1); 
			$Place = new Place(self::LAT, self::LON); 
			$Event->Place = $Place;
			$Event->commit(); 
		}
		
		public function testComparePlace() {
			$Event = new Event(1); 
			
			$this->assertEquals(self::LAT, $Event->Place->lat); 
			$this->assertEquals(self::LON, $Event->Place->lon);
		}
		
		public function testAddDate() {
			$Event = new Event(1); 
			
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
			$EventDate->setAuthor(new User(1));
			$EventDate->commit(); 
			
			$this->assertFalse(!filter_var($EventDate->id, FILTER_VALIDATE_INT));
			$this->assertEquals(Events::STATUS_UNAPPROVED, $EventDate->status); 
		}
		
		public function testCompareDate() {
			$EventDate = new EventDate(1);
			
			$this->assertEquals(self::EVENT_DATE, $EventDate->Date->format("Y-m-d")); 
			$this->assertEquals(self::EVENT_START, $EventDate->Start->format("ga"));
			$this->assertEquals(self::EVENT_END, $EventDate->End->format("ga"));
		}
		
		public function testApproveDate() {
			$EventDate = new EventDate(1); 
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
	