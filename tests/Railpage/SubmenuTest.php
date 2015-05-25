<?php
	
	use Railpage\Submenu;
	
	class SubmenuTest extends PHPUnit_Framework_TestCase {
		
		public function testAddSection() {
			$Submenu = new Submenu;
			$Submenu->AddGrouping("Test 1"); 
			$Submenu->Section("Test 2");
			$Submenu->AddSection("Test 3"); 
			
			$this->assertTrue(in_array("Test 1", array_keys($Submenu->menu))); 
			$this->assertTrue(in_array("Test 2", array_keys($Submenu->menu))); 
			$this->assertTrue(in_array("Test 3", array_keys($Submenu->menu))); 
			
			$Submenu->AddGrouping("Test 4");
			$Submenu->Section("Test 4"); 
			
			$this->assertEquals("Test 4", $Submenu->section);
			$this->setExpectedException('InvalidArgumentException');
			
			$Submenu = new Submenu;
			$Submenu->Section();
		}
		
		public function testSetGroupingSubtitle() {
			$Submenu = new Submenu;
			$Submenu->AddGrouping("Test 1"); 
			
			$Submenu->SetGroupingSubtitle("Test 1", "A test subtitle"); 
			$this->assertEquals($Submenu->menu['Test 1']['subtitle'], "A test subtitle"); 
		}
		
		public function testAddItem() {
			$Submenu = new Submenu;
			$Submenu->Add("No grouping", "/nogrouping"); 
			$Submenu->AddGrouping("Test 1"); 
			$Submenu->Add("Test", "/test", "Test 1"); 
			$Submenu->Section("Test 2")->Add("Test2", "/test2");
			$Submenu->Section("Test 3")->Add("Test3", "/test3", false, array("class" => "testclass")); 
			
			$this->assertEquals($Submenu->menu[0]['title'], "No grouping");
			$this->assertEquals($Submenu->menu[0]['url'], "/nogrouping");
			
			$this->assertEquals($Submenu->menu['Test 1']['menu'][0]['title'], "Test");
			$this->assertEquals($Submenu->menu['Test 1']['menu'][0]['url'], "/test");
			$this->assertFalse(isset($Submenu->menu['Test 1']['menu'][0]['meta'])); 
			
			$this->assertEquals($Submenu->menu['Test 2']['menu'][0]['title'], "Test2");
			$this->assertEquals($Submenu->menu['Test 2']['menu'][0]['url'], "/test2");
			$this->assertFalse(isset($Submenu->menu['Test 2']['menu'][0]['meta'])); 
			
			$this->assertEquals($Submenu->menu['Test 3']['menu'][0]['title'], "Test3");
			$this->assertEquals($Submenu->menu['Test 3']['menu'][0]['url'], "/test3");
			$this->assertTrue(isset($Submenu->menu['Test 3']['menu'][0]['meta'])); 
			
			$this->assertTrue($Submenu->HasItems());
			
			$this->setExpectedException('InvalidArgumentException');
			
			$Submenu = new Submenu;
			$Submenu->Section("Test")->Add("", "/blah");
		}
		
		public function testGetURLExpectedException() {
			$this->setExpectedException('InvalidArgumentException');
			
			$Submenu = new Submenu;
			$Submenu->Section("Test")->Add("asdfaf", "/blah");
			
			$this->assertFalse(is_null(filter_var($Submenu->GetURL("/blah"), FILTER_SANITIZE_STRING)));
			$this->assertFalse($Submenu->GetURL("/sadfafsdfafdf"));
			
			$Submenu->GetURL();
		}
	}