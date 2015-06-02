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
	use Railpage\Locos\Liveries\Base as Liveries;
	use Railpage\Locos\Liveries\Livery;
	
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
			return $last['id'];
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
			
			$this->assertFalse(!filter_var($Class->id, FILTER_VALIDATE_INT)); 
			
			return $Class->id;
		}
		
		/**
		 * @depends testAddLocoClass
		 * @depends testAddGauge
		 * @depends testAddStatus
		 */
		
		public function testAddLoco($class_id, $gauge_id, $status_id) {
			$Class = new LocoClass($class_id);
			$Gauge = new Gauge($gauge_id);
			
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
	}
	