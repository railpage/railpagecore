<?php
	use Railpage\Locos\Operator;
	
	class OperatorTest extends PHPUnit_Framework_TestCase {
		
		public function testAdd() {
			$Operator = new Operator;
			
			$this->assertInstanceOf("Railpage\\Locos\\Operator", $Operator);
			
			$Operator->name = "Test Operator";
			$Operator->commit(); 
		}
		
		public function testGet() {
			$Operator = new Operator(1);
			
			$this->assertEquals(1, $Operator->id);
			$this->assertEquals("Test Operator", $Operator->name);
		}
		
		public function testUpdate() {
			$Operator = new Operator(1);
			
			$Operator->name = "Test Operator Updated";
			$Operator->organisation_id = 200;
			
			$updated_name = $Operator->name;
			$updated_org_id = $Operator->organisation_id;
			
			$Operator->commit(); 
			
			// Reload the operator
			$Operator = new Operator(1);
			
			$this->assertEquals($updated_name, $Operator->name);
			$this->assertEquals($updated_org_id, $Operator->organisation_id);
		}
	}
?>