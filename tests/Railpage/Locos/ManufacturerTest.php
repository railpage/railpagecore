<?php
	use Railpage\Locos\Manufacturer;
	
	class ManufacturerTest extends PHPUnit_Framework_TestCase {
		
		public function testAdd() {
			$Manufacturer = new Manufacturer; 
			
			$this->assertInstanceOf("Railpage\\Locos\\Manufacturer", $Manufacturer);
			
			$Manufacturer->name = "Test Manufacturer";
			$Manufacturer->desc = "Test description";
			$Manufacturer->commit(); 
		}
		
		public function testGet() {
			$Manufacturer = new Manufacturer(1);
			
			$this->assertEquals(1, $Manufacturer->id);
			$this->assertEquals("Test Manufacturer", $Manufacturer->name);
			$this->assertEquals("Test description", $Manufacturer->desc);
		}
		
		public function testUpdate() {
			$Manufacturer = new Manufacturer(1);
			
			$Manufacturer->name = "Test Manufacturer Updated";
			$Manufacturer->desc = "Test description updated";
			
			$updated_name = $Manufacturer->name;
			$updated_desc = $Manufacturer->desc;
			
			$Manufacturer->commit(); 
			
			// Reload the operator
			$Manufacturer = new Manufacturer(1);
			
			$this->assertEquals($updated_name, $Manufacturer->name);
			$this->assertEquals($updated_desc, $Manufacturer->desc);
			
		}
	}
?>