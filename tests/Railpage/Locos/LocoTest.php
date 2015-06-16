<?php
	
	use Railpage\Locos\Locomotive;
	use Railpage\Locos\Locos;
	use Railpage\Locos\LocoClass;
	use Railpage\Locos\Correction;
	use Railpage\Locos\Date;
	use Railpage\Locos\Manufacturer;
	use Railpage\Locos\Operator;
	use Railpage\Locos\Type;
	use Railpage\Locos\WheelArrangement;
	use Railpage\Locos\Gauge;
	use Railpage\Locos\Status;
	use Railpage\Locos\Liveries\Base as Liveries;
	use Railpage\Locos\Liveries\Livery;
	use Railpage\Locos\Factory as LocosFactory;
	use Railpage\Users\User;
	use Railpage\Images\Images;
	use Railpage\Images\Image;
	use Railpage\Assets\Asset;
	
	class LocoTest extends PHPUnit_Framework_TestCase {
		
		const MAN_NAME = "Test manufacturer";
		const MAN_DESC = "Test manufacturer description";
		const ARRANGEMENT = "0-6-0";
		const ARRANGEMENT_NAME = "Six coupled";
		const OPERATOR = "Test operator";
		const OWNER = "Test owner";
		const TYPE = "Steam";
		const CLASS_NAME = "Test loco class";
		const CLASS_DESC = "Test loco class description blah blah blah blah blah blah blah";
		const CLASS_INTRODUCED = "2015";
		const CLASS_FLICKR_TAG = "rp-au-test-testlococlass";
		const CLASS_AXLE_LOAD = "21 tons";
		const CLASS_WEIGHT = "123 tons";
		const CLASS_LENGTH = "23 metres";
		const CLASS_TRACTIVE_EFFORT = "4000 hp";
		const CLASS_MODEL = "AC1235blah";
		const LOCO_NUM = 1100;
		
		public function test_addUser() {
			
			$User = new User;
			$User->username = "Locos test";
			$User->setPassword('asfdasdf'); 
			$User->contact_email = "michael+phpunit+locos@railpage.com.au";
			$User->commit(); 
			
			return $User;
			
		}
		
		public function testAddManufacturer() {
			$Manufacturer = new Manufacturer;
			
			$Manufacturer->name = self::MAN_NAME;
			$Manufacturer->desc = self::MAN_DESC;
			$Manufacturer->commit(); 
			
			$this->assertFalse(!filter_var($Manufacturer->id, FILTER_VALIDATE_INT));
			
			return $Manufacturer->id; 
		}
		
		public function testAddWheelArrangement() {
			$Arrangement = new WheelArrangement;
			$Arrangement->arrangement = self::ARRANGEMENT;
			$Arrangement->name = self::ARRANGEMENT_NAME;
			
			$Arrangement->commit(); 
			
			$this->assertFalse(!filter_var($Arrangement->id, FILTER_VALIDATE_INT)); 
			
			return $Arrangement->id;
			
		}
		
		public function testAddOperator() {
			$Operator = new Operator;
			$Operator->name = self::OPERATOR; 
			$Operator->commit(); 
			
			$this->assertFalse(!filter_var($Operator->id, FILTER_VALIDATE_INT)); 
			
			$this->operator_id = $Operator->id;
			
			$Owner = new Operator;
			$Owner->name = self::OWNER;
			$Owner->commit(); 
			
			$this->assertFalse(!filter_var($Owner->id, FILTER_VALIDATE_INT)); 
			
			return $Owner->id;
		}
		
		public function testAddType() {
			$Type = new Type;
			$Type->name = self::TYPE;
			$Type->commit(); 
			
			$this->assertFalse(!filter_var($Type->id, FILTER_VALIDATE_INT)); 
			
			return $Type->id;
		}
		
		public function testAddGauge() {
			$Gauge = new Gauge;
			$Gauge->name = "Broad";
			$Gauge->width_metric = "1600";
			$Gauge->commit(); 
			
			$this->assertFalse(!filter_var($Gauge->id, FILTER_VALIDATE_INT)); 
			
			return $Gauge->id;
		}
		
		public function testAddStatus() {
			
			$Locos = new Locos;
			$Locos->addStatus("Operational");
			
			$statuses = $Locos->listStatus();
			
			$last = array_pop($statuses['status']); 
			
			$Status = new Status; 
			$Status->name = "Scrapped";
			$Status->commit(); 
			
			$Status = new Status($Status->id); 
			$Status->name = "Unscrapped";
			$Status->commit(); 
			
			return $last['id'];
			
		}
		
		public function test_break_status() {
			
			$this->setExpectedException("Exception", "No name was given for this status");
			
			$Locos = new Locos;
			$Locos->addStatus(); 
			
		}
		
		public function test_break_status_again() {
			
			$this->setExpectedException("Exception", "Name cannot be empty");
			
			$Status = new Status;
			$Status->commit(); 
			
		}
		
		public function test_measurements() {
			
			$Locos = new Locos;
			
			$cm = 160;
			$imp = $Locos->convert_to_inches($cm); 
			
			$this->assertEquals("5 foot 3 inches", sprintf("%d foot %d inches", $imp['ft'], $imp['in']));
			
			$this->assertEquals($cm, $Locos->convert_to_cm(5, 3));
			
		}
		
		/**
		 * @depends testAddManufacturer
		 * @depends testAddWheelArrangement
		 * @depends testAddType
		 */
		
		public function testAddLocoClass($manufacturer_id, $wheel_arrangement_id, $type_id) {
			$Class = new LocoClass;
			$Class->name = self::CLASS_NAME;
			$Class->desc = self::CLASS_DESC;
			$Class->introduced = self::CLASS_INTRODUCED;
			$Class->flickr_tag = self::CLASS_FLICKR_TAG;
			$Class->axle_load = self::CLASS_AXLE_LOAD;
			$Class->weight = self::CLASS_WEIGHT;
			$Class->length = self::CLASS_LENGTH;
			$Class->tractive_effort = self::CLASS_TRACTIVE_EFFORT;
			$Class->model = self::CLASS_MODEL;
			
			$Manufacturer = new Manufacturer($manufacturer_id);
			$WheelArrangement = new WheelArrangement($wheel_arrangement_id);
			$Type = new Type($type_id);
			
			$Class->setManufacturer($Manufacturer)
				  ->setWheelArrangement($WheelArrangement)
				  ->setType($Type)
				  ->commit(); 
			
			$this->assertEquals($Manufacturer->id, $Class->getManufacturer()->id); 
			
			$this->assertFalse(!filter_var($Class->id, FILTER_VALIDATE_INT)); 
			
			return $Class->id;
		}
		
		/**
		 * @depends testAddLocoClass
		 * @depends testAddGauge
		 * @depends testAddStatus
		 * @depends testAddManufacturer
		 */
		
		public function testAddLoco($class_id, $gauge_id, $status_id, $manufacturer_id) {
			$Class = new LocoClass($class_id);
			$Gauge = new Gauge($gauge_id);
			
			$TestClass = LocosFactory::CreateLocoClass($Class->slug);
			
			$Loco = new Locomotive;
			
			$Loco->number = self::LOCO_NUM;
			$Loco->status_id = $status_id;
			
			$Loco->setLocoClass($Class)
			     ->setGauge($Gauge)
			     ->commit(); 
			
			$this->assertFalse(!filter_var($Loco->id, FILTER_VALIDATE_INT));
			
			$id = $Loco->id;
			
			// Add another loco
			$Loco = new Locomotive;
			
			$Loco->number = (self::LOCO_NUM) + 1;
			$Loco->status_id = $status_id;
			
			$Loco->setLocoClass($Class)
			     ->setGauge($Gauge)
			     ->commit(); 
			
			$Loco->name = "Test blah";
			$Loco->commit(); 
			
			$NewLoco = new Locomotive(NULL, $class_id, $Loco->number);
			
			$tags = $Loco->getTags(); 
			$this->assertTrue(is_array($tags)); 
			
			$TestLoco = LocosFactory::CreateLocoClass($Loco->id);
			$TestLoco = LocosFactory::CreateLocoClass(false, $Class->slug, $Loco->number);
			
			return $id;
		}
		
		/**
		 * @depends testAddLoco
		 */
		 
		public function testFetchLoco($loco_id) {
			$Loco = new Locomotive($loco_id);
			
			$this->assertEquals(self::LOCO_NUM, $Loco->number);
			$this->assertEquals(self::CLASS_NAME, $Loco->Class->name);
			
			$NextLoco = $Loco->next(); 
			
			$this->assertEquals((self::LOCO_NUM) + 1, $NextLoco->number); 
			$this->assertEquals(self::CLASS_NAME, $NextLoco->Class->name);
			
			$Gauge = $Loco->getGauge(); 
			$Manufacturer = $Loco->getManufacturer(); 
			
			$Loco->setManufacturer(new Manufacturer($Manufacturer->id))
				 ->getManufacturer();
			
			$Loco->getArray();
			$Loco->builders_num = "1";
			$Loco->status_id = 4;
			$desc = $Loco->generateDescription();
			
			$Loco->status_id = 5;
			$desc = $Loco->generateDescription();
			
			$Loco->status_id = 9;
			$desc = $Loco->generateDescription();
			
			$NextLoco = $NextLoco->next();
			$this->assertNotInstanceOf("Railpage\\Locos\\Locomotive", $NextLoco); 
			
			$Previous = $Loco->next()->previous(); 
			$this->assertInstanceOf("Railpage\\Locos\\Locomotive", $Previous);
			
			$Previous = $Previous->previous(); 
			$this->assertNotInstanceOf("Railpage\\Locos\\Locomotive", $Previous);
			
		}
		
		/**
		 * @depends testAddLocoClass 
		 */
		
		public function testGetClassByWheelArrangement($class_id) {
			$Class = new LocoClass($class_id); 
			$Arrangement = new WheelArrangement($Class->wheel_arrangement_id); 
			
			foreach ($Arrangement->getClasses() as $row) {
				$this->assertEquals($row['wheel_arrangement']['id'], $Arrangement->id);
			}
		}
		
		/**
		 * @depends testAddLocoClass 
		 */
		
		public function testGetClassByType($class_id) {
			$Class = new LocoClass($class_id); 
			$Type = new Type($Class->type_id); 
			
			foreach ($Type->getClasses() as $row) {
				$this->assertEquals($row['type']['id'], $Type->id);
			}
		}
		
		/**
		 * @depends testAddLocoClass 
		 */
		
		public function testGetClassByManufacturer($class_id) {
			$Class = new LocoClass($class_id); 
			$Manufacturer = new Manufacturer($Class->type_id); 
			
			foreach ($Manufacturer->getClasses() as $row) {
				$this->assertEquals($row['manufacturer']['id'], $Manufacturer->id);
			}
		}
		
		public function test_break_loco_number() {
			
			$this->setExpectedException("Exception", "No locomotive number specified"); 
			
			$Loco = new Locomotive;
			$Loco->commit(); 
			
		}
				
		public function test_break_loco_class() {
			
			$this->setExpectedException("Exception", "Cannot add locomotive because we don't know which class to add it into"); 
			
			$Loco = new Locomotive; 
			$Loco->number = "1233";
			$Loco->commit(); 
			
		}
		
		/**
		 * @depends testAddLocoClass
		 */
				
		public function test_break_loco_gauge($class_id) {
			
			$this->setExpectedException("Exception", "No gauge has been set"); 
			
			$Loco = new Locomotive; 
			$Loco->setLocoClass(new LocoClass($class_id));
			$Loco->number = "1233";
			$Loco->commit(); 
			
		}
		
		/**
		 * @depends testAddLocoClass
		 * @depends testAddGauge
		 */
				
		public function test_break_loco_status($class_id, $gauge_id) {
			
			$this->setExpectedException("Exception", "No status has been set"); 
			
			$Loco = new Locomotive; 
			$Loco->setLocoClass(new LocoClass($class_id));
			$Loco->setGauge(new Gauge($gauge_id));
			$Loco->number = "1233";
			$Loco->commit(); 
			
		}
		
		/**
		 * @depends testAddLocoClass
		 * @depends testAddGauge
		 * @depends testAddStatus
		 */
				
		public function test_add_loco_class_a($class_id, $gauge_id, $status_id) {
			
			$Loco = new Locomotive; 
			$Loco->class = new LocoClass($class_id);
			$Loco->setGauge(new Gauge($gauge_id));
			$Loco->number = "1233";
			$Loco->status_id = $status_id;
			$Loco->commit(); 
			
			$Loco = new Locomotive; 
			$Loco->class_id = $class_id;
			$Loco->setGauge(new Gauge($gauge_id));
			$Loco->number = "1234";
			$Loco->status_id = $status_id;
			$Loco->commit(); 
			
		}
		
		/**
		 * @depends testAddLoco
		 * @depends test_addUser
		 */
		
		public function test_addNote($loco_id, $User) {
			
			$Loco = new Locomotive($loco_id); 
			$Loco->addNote("test note text", $User);
			$note_id = $Loco->addNote("test note text1111", $User->id);
			
			$notes = $Loco->loadNotes();
			
			// edit it
			$Loco->addNote("test note text 121231333", $User->id, $note_id);
			
			$notes = $Loco->loadNotes();
			
			// get contributors
			$list = $Loco->getContributors(); 
			
			$this->setExpectedException("Exception", "No note text given"); 
			$Loco->addNote(); 
			
		}
		
		/**
		 * @depends testAddLoco
		 * @depends test_addUser
		 */
		
		public function test_addNote_break_user($loco_id, $User) {
			
			$this->setExpectedException("Exception", "No user provided");
			
			$Loco = new Locomotive($loco_id); 
			$Loco->addNote("asdfadf"); 
			
		}
		
		/**
		 * @depends testAddLoco
		 */
		
		public function test_hasCoverImage($loco_id) {
			
			$Loco = new Locomotive($loco_id); 
			$this->assertFalse($Loco->hasCoverImage()); 
			
			return $Loco;
			
		}
		
		/**
		 * @depends test_hasCoverImage
		 */
		
		public function test_setCoverImage($Loco) {
			
			$Image = (new Images)->findImage("flickr", "18230769058"); 
			
			$Loco->setCoverImage($Image); 
			$this->assertTrue($Loco->hasCoverImage()); 
			
			$Loco->getCoverImage(); 
			
			return $Image;
			
		}
		
		/**
		 * @depends test_setCoverImage
		 */
		
		public function test_getRandomClass() {
			
			$Locos = new Locos;
			
			$Locos->getRandomClass();
			
		}
		
		/**
		 * @depends testAddLoco
		 * @depends test_addUser
		 */
		
		public function test_addLocoCorrection($loco_id, $User) {
			
			$Loco = new Locomotive($loco_id); 
			$Loco->newCorrection("test blah", $User->id); 
			
			$Correction = new Correction;
			$Correction->text = "test 123132";
			$Correction->setUser($User); 
			$Correction->setObject($Loco);
			$Correction->commit(); 
			
			$Correction = new Correction($Correction->id); 
			$Correction->text = "asdfafaf";
			$Correction->status = Correction::STATUS_CLOSED;
			$Correction->commit(); 
			
			$opencorrections = $Loco->corrections();
			$allcorrections = $Loco->corrections(false);
			
			$this->assertTrue(count($allcorrections) > 1);
			
			$this->assertTrue(count($opencorrections) == 1);
			
			$Correction->status = Correction::STATUS_OPEN;
			return $Correction;
			
		}
		
		/**
		 * @depends testAddLocoClass
		 * @depends testAddLoco
		 * @depends test_addUser
		 */
		
		public function test_addClassCorrection($class_id, $loco_id, $User) {
			
			$Class = new LocoClass($class_id); 
			$Loco = new Locomotive($loco_id);
			
			$Correction = new Correction;
			$Correction->text = "test class 123132";
			$Correction->setUser($User); 
			$Correction->setObject($Class);
			$Correction->commit()->setMaintainer($User)->approve("Accepted");
			
			$Correction = new Correction;
			$Correction->text = "test class 1zzz";
			$Correction->setUser($User); 
			$Correction->setObject($Class);
			$Correction->commit()->setMaintainer($User)->reject("blah test");
			
			$Correction = new Correction($Correction->id);
			
			$Correction = new Correction;
			$Correction->text = "test loco 123132";
			$Correction->setUser($User); 
			$Correction->setObject($Loco);
			$Correction->commit()->setMaintainer($User)->approve("Accepted");
			
			$Correction = new Correction;
			$Correction->text = "test loco 1zzz";
			$Correction->setUser($User); 
			$Correction->setObject($Loco);
			$Correction->commit()->setMaintainer($User)->reject("blah test");
			
			// break some stuff
			$Database = $Correction->getDatabaseConnection(); 
			$data = [ "loco_id" => NULL, "class_id" => NULL ];
			$where = [ "correction_id = ?" => $Correction->id ];
			$Database->update("loco_unit_corrections", $data, $where); 
			
			$this->setExpectedException("Exception", "Unable to determine if this correction belongs to a locomotive or a locomotive class"); 
			$Correction = new Correction($Correction->id); 
			
		}
		
		/**
		 * @depends test_addLocoCorrection
		 */
		
		public function test_break_approveCorrection($Correction) {
			
			$this->setExpectedException("Exception", "Cannot close correction - User resolving this correction not specified");
			
			$Correction->close(); 
			
		}
		
		/**
		 * @depends test_addLocoCorrection
		 */
		
		public function test_break_rejectCorrection($Correction) {
			
			$this->setExpectedException("Exception", "Cannot ignore correction - User resolving this correction not specified");
			
			$Correction->ignore(); 
			
		}
		
		public function test_addLivery() {
			
			$Livery = new Livery;
			$Livery->name = "Test livery";
			$Livery->country = "AU";
			$Livery->region = "VIC";
			
			$Livery->commit(); 
			
			$Livery = new Livery($Livery->id); 
			$Livery->name = "Test livery 2 blah";
			$Livery->commit(); 
			
			return $Livery;
			
		}
		
		/**
		 * @depends testAddLoco
		 */
		
		public function test_listAllTheThings($loco_id) {
			
			$Loco = new Locomotive($loco_id);
			$Locos = new Locos;
			
			$Locos->listModels(); 
			$Locos->listGroupings(); 
			$Locos->listGauges(); 
			$Locos->listLiveries(); 
			$Locos->listOperators(); 
			$Locos->listLiveries(); 
			$Locos->listAllLocos(); 
			$Locos->listYears(); 
			$Locos->listStatus(); 
			$Locos->listTypes(); 
			$Locos->listManufacturers(); 
			$Locos->listWheelArrangements(); 
			$Locos->listOrgLinkTypes();
			
			$Locos->classByManufacturer(); 
			$Locos->classByWheelset(); 
			$Locos->classByType(); 
			
			$Locos->listClasses(); 
			$Locos->listClasses($Loco->Class->getType()->id);
			$Locos->listClasses(array($Loco->Class->getType()->id));
			
		}
		
		/**
		 * @depends testAddLoco
		 * @depends test_addUser
		 */
		
		public function test_rateLoco($loco_id, $User) {
			
			$Loco = new Locomotive($loco_id); 
			
			$this->assertEquals(2.5, $Loco->getRating()); 
			
			$Loco->setRating($User, 5); 
			$Loco->userRating($User);
			$this->assertEquals(5, $Loco->userRating($User->id)); 
			
			$Loco->setRating($User->id, 4);
			
			$this->assertTrue(is_array($Loco->getRating(true)));
			
		}
		
		public function test_break_rate_getloco() {
			
			$this->setExpectedException("Exception", "Cannot fetch rating - no loco ID given"); 
			
			$Loco = new Locomotive;
			$Loco->getRating(); 
			
		}
		
		public function test_break_rate_getuser() {
			
			$this->setExpectedException("Exception", "Cannot fetch user rating for this loco - no user given"); 
			
			$Loco = new Locomotive;
			$Loco->userRating(); 
			
		}
		
		public function test_break_rate_set_nouser() {
			
			$this->setExpectedException("Exception", "Cannot set user rating for this loco - no user given"); 
			
			$Loco = new Locomotive;
			$Loco->setRating(); 
			
		}
		
		/**
		 * @depends test_addUser
		 */
		
		public function test_break_rate_set_norating($User) {
			
			$this->setExpectedException("Exception", "Cannot set user rating for this loco - no rating given");
			
			$Loco = new Locomotive;
			$Loco->setRating($User); 
			
		}
			
	}
	