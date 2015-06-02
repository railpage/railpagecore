<?php
	
	use Railpage\fwlink;
	
	class fwlinkTest extends PHPUnit_Framework_TestCase {
		
		public function test_fwlink() {
			
			$fwlink = new fwlink;
			$fwlink->url = "/blah";
			$fwlink->title = "A test link";
			$fwlink->commit(); 
			
			$new = new fwlink($fwlink->id);
			
			return $fwlink;
			
		}
		
		/**
		 * @depends test_fwlink
		 */
		
		public function test_update($fwlink) {
			
			$fwlink->title = "testzomg";
			$fwlink->commit(); 
			
			$link = (string) $fwlink;
			
			$this->assertTrue(!empty($fwlink));
			
			return $fwlink;
			
		}
		
		/**
		 * @depends test_update
		 */
		
		public function test_load($fwlink) {
			
			$new = new fwlink($fwlink->url); 
			
			$this->assertEquals($new->title, $fwlink->title); 
			$this->assertEquals($new->url, $fwlink->url); 
			
		}
		
		public function test_break_url() {
			
			$fwlink = new fwlink;
			$this->setExpectedException("Exception", "Cannot validate new link - \$url is empty or not a string"); 
			$fwlink->commit(); 
			
		}
		
		public function test_break_title() {
			
			$fwlink = new fwlink;
			$fwlink->url = "/test123";
			$this->setExpectedException("Exception", "Cannot validate new link - \$title is empty or not a string"); 
			$fwlink->commit(); 
			
		}
		
	}
