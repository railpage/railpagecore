<?php
	
	use Railpage\NullCacheDriver;
	
	class NullCacheDriverTest extends PHPUnit_Framework_TestCase {
		
		public function testFetch() {
			
			$Driver = new NullCacheDriver;
			$this->assertFalse($Driver->fetch("test")); 
			
		}
		
		public function testContains() {
			
			$Driver = new NullCacheDriver;
			$this->assertFalse($Driver->contains("test")); 
			
		}
		
		public function testDelete() {
			
			$Driver = new NullCacheDriver;
			$this->assertFalse($Driver->delete("test")); 
			
		}
		
		public function testSave() {
			
			$Driver = new NullCacheDriver;
			$this->assertFalse($Driver->save("test", "somedata", strtotime("+1 hour"))); 
			
		}


	}