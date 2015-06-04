<?php
	use Railpage\Locos\Type;
	
	class TypeTest extends PHPUnit_Framework_TestCase {
		
		public function testAdd() {
			$Type = new Type; 
			
			$this->assertInstanceOf("Railpage\\Locos\\Type", $Type);
			
			$Type->name = "Test Type";
			$Type->commit(); 
			
			$NewType = new Type; 
			$NewType->name = "Test type"; 
			$NewType->commit(); 
			
			$Database = $Type->getDatabaseConnection(); 
			$data = [ "slug" => "" ];
			$where = [ "id = ?" => $Type->id ];
			$Database->update("loco_type", $data, $where);
			
			$Database = $Type->getDatabaseConnection(); 
			$data = [ "slug" => "" ];
			$where = [ "id = ?" => $NewType->id ];
			$Database->update("loco_type", $data, $where);
			
			
			$Type = new Type($Type->id); 
			$NewType = new Type($NewType->id); 
			
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
		
		public function test_break_validate() {
			
			$this->setExpectedException("Exception", "Cannot validate changes to this loco type: name cannot be empty");
			
			$Type = new Type;
			$Type->commit(); 
			
		}
		
	}
	