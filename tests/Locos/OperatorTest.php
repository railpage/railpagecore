<?php
	use Railpage\Locos\Operator
	
	class OperatorTest extends PHPUnit_Framework_TestCase {
		
		public function testAdd() {
			$Operator = new Operator;
			
			$this->assertInstanceOf("Railpage\\Operator", $Operator);
			
			$Operator->name = "Test Operator";
			$Operator->commit(); 
		} 
		
		public function testGet() {
			$Operator = new Operator(1);
			
			$this->assertEquals(1, $Operator->id);
			$this->assertEquals("Test operator", $Operator->name);
		}
	}
?>