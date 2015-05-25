<?php
	use Railpage\Locos\Type;
	
	class TypeTest extends PHPUnit_Framework_TestCase {
		
		public function testAdd() {
			$Type = new Type; 
			
			$this->assertInstanceOf("Railpage\\Locos\\Type", $Type);
			
			$Type->name = "Test Type";
			$Type->commit(); 
			
			return $Type->id;
		}
		
		/**
		 * @depends testAdd
		 */
		
		public function testGet($id) {
			$Type = new Type($id);
			
			$this->assertEquals($id, $Type->id);
			$this->assertEquals("Test Type", $Type->name);
		}
		
		/**
		 * @depends testAdd
		 */
		
		public function testUpdate($id) {
			$Type = new Type($id);
			
			$Type->name = "Test Type Updated";
			
			$updated_name = $Type->name;
			
			$Type->commit(); 
			
			// Reload the operator
			$Type = new Type($id);
			
			$this->assertEquals($updated_name, $Type->name);
			
		}
	}
	