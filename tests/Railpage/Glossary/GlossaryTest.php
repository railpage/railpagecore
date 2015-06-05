<?php
	
	use Railpage\Glossary\Entry;
	use Railpage\Glossary\Type;
	use Railpage\Glossary\Glossary;
	use Railpage\Users\User;
	
	class GlossaryTest extends PHPUnit_Framework_TestCase {
		
		const TYPE = "Slang";
		const NAME = "Testing";
		const TEXT = "Evaluating a situation to see if the expected outcome matches the actual outcome";
		const EXAMPLE = "I'm testing Railpage's code by running it through PHPUnit";
		const AUTHOR = "Glossary tester";
		
		public function testAddUser() {
			
			$User = new User;
			$User->username = self::AUTHOR;
			$User->setPassword('zasdfasdfadfag');
			$User->contact_email = "phpunit+glossary@railpage.com.au";
			$User->commit(); 
			
			$this->assertFalse(!filter_var($User->id, FILTER_VALIDATE_INT));
			
			return $User;
			
		}
		
		/**
		 * @depends testAddUser
		 */
		
		public function testAddEntry($User) {
			
			$Type = new Type(self::TYPE);
			$Entry = new Entry;
			$Entry->name = self::NAME;
			$Entry->text = self::TEXT;
			$Entry->example = self::EXAMPLE;
			$Entry->Type = $Type;
			$Entry->setAuthor($User); 
			
			$Entry->commit(); 
			
			$Entry->name = "test"; 
			$Entry->commit(); 
			$Entry->name = self::NAME;
			$Entry->commit(); 
			
			$Entry = new Entry($Entry->id);
			
			$this->assertFalse(!filter_var($Entry->id, FILTER_VALIDATE_INT));
			
			$this->assertEquals($User->id, $Entry->Author->id);
			
			$Entry->approve(); 
			
			return $Entry;
			
		}
		
		/**
		 * @depends testAddEntry
		 */
		
		public function testCompareEntry($Entry) {
			
			$NewEntry = new Entry($Entry->id); 
			
			$this->assertEquals($Entry->name, $NewEntry->name);
			
			$this->assertEquals(self::TYPE, $NewEntry->Type->name); 
			$this->assertEquals(self::NAME, $NewEntry->name);
			$this->assertEquals(self::TEXT, $NewEntry->text); 
			$this->assertEquals(self::EXAMPLE, $NewEntry->example); 
			$this->assertEquals($Entry->Author->id, $NewEntry->Author->id); 
			$this->assertEquals($Entry->Author->username, $NewEntry->Author->username); 
			
		}
		
		/**
		 * @depends testAddEntry
		 */
		
		public function testGetEntry() {
			
			$Type = new Type(self::TYPE); 
			
			foreach ($Type->getEntries() as $Entry) {
				$this->assertEquals(self::NAME, $Entry->name); 
			}
			
		}
		
		public function test_loadTypes() {
			
			$types = [ "code", "acronym", "station", "slang", "general", "term" ];
			
			foreach ($types as $type) {
				
				$GlossaryType = new Type($type); 
				
			}
		}
		
		/**
		 * @depends testAddUser
		 */
		
		public function test_delete($User) {
			
			$Type = new Type(self::TYPE);
			$Entry = new Entry;
			$Entry->name = self::NAME;
			$Entry->text = self::TEXT;
			$Entry->example = self::EXAMPLE;
			$Entry->Type = $Type;
			$Entry->setAuthor($User); 
			
			$Entry->commit(); 
			
			$Entry->reject(); 
			
		}
		
		public function test_break_name() {
			
			$this->setExpectedException("Exception", "Entry name cannot be empty");
			
			$Entry = new Entry;
			$Entry->commit(); 
			
		}
		
		public function test_break_text() {
			
			$this->setExpectedException("Exception", "Entry text cannot be empty");
			
			$Entry = new Entry;
			$Entry->name = "asdf";
			$Entry->commit(); 
			
		}
		
		public function test_break_type() {
			
			$this->setExpectedException("Exception", "Entry type is invalid");
			
			$Entry = new Entry;
			$Entry->name = "asdf";
			$Entry->text = "asfdfafadfsaf";
			$Entry->commit(); 
			
		}
		
		public function test_break_user() {
			
			$this->setExpectedException("Exception", "No author given for glossary entry");
			
			$Entry = new Entry;
			$Entry->Type = new Type(self::TYPE);
			$Entry->example = NULL;
			$Entry->name = "asdf";
			$Entry->text = "asfdfafadfsaf";
			$Entry->commit(); 
			
		}
		
		/**
		 * @depends testAddEntry
		 */
		
		public function test_nukeDate($Entry) {
			
			$Database = $Entry->getDatabaseConnection(); 
			
			$data = [ "date" => "0000-00-00 00:00:00" ];
			$where = [ "id = ?" => $Entry->id ];
			$Database->update("glossary", $data, $where); 
			
			$Entry = new Entry($Entry->id); 
			
		}
	}
	