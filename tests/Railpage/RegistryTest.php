<?php
	
	use Railpage\Registry;
	
	class RegistryTest extends PHPUnit_Framework_TestCase {
		
		const KEY = "Blah";
		const VAL = "sadfadsfadsfasdfsafasf";
		
		public function test_add() {
			
			$Registry = Registry::getInstance(); 
			$Registry->set(self::KEY, self::VAL);
			
			$Registry = Registry::getInstance(); 
			$this->assertEquals(self::VAL, $Registry->get(self::KEY));
			
		}
		
		public function test_break_add() {
			
			$this->setExpectedException("Exception", sprintf("There is already an entry for %s in the registry", self::KEY));
			
			$Registry = Registry::getInstance(); 
			$Registry->set(self::KEY, self::VAL); 
			
		}
		
		public function test_remove() {
			
			$Registry = Registry::getInstance(); 
			$Registry->remove(self::KEY); 
			
		}
		
	}