<?php
	use Railpage\Locos\Type;
	
	class TypeTest extends PHPUnit_Framework_TestCase {
		
		public function testAdd() {
			$Type = new Type; 
			
			$this->assertInstanceOf("Railpage\\Locos\\Type", $Type);
			
			$Type->name = "Test Type";
			$Type->commit(); 
		}
		
		public function testGet() {
			$Type = new Type(1);
			
			$this->assertEquals(1, $Type->id);
			$this->assertEquals("Test Type", $Type->name);
		}
		
		public function testUpdate() {
			$Type = new Type(1);
			
			$Type->name = "Test Type Updated";
			
			$updated_name = $Type->name;
			
			$Type->commit(); 
			
			// Reload the operator
			$Type = new Type(1);
			
			$this->assertEquals($updated_name, $Type->name);
			
		}
	}
?>