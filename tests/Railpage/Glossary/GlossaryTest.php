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
			
			return $User->id;
			
		}
		
		/**
		 * @depends testAddUser
		 */
		
		public function testAddEntry($user_id) {
			
			$User = new User($user_id);
			
			$Type = new Type(self::TYPE);
			$Entry = new Entry;
			$Entry->name = self::NAME;
			$Entry->text = self::TEXT;
			$Entry->example = self::EXAMPLE;
			$Entry->Type = $Type;
			$Entry->setAuthor($User); 
			
			$Entry->commit(); 
			
			$this->assertFalse(!filter_var($Entry->id, FILTER_VALIDATE_INT));
			
			return $Entry;
			
		}
		
		/**
		 * @depends testAddEntry
		 * @depends testAddUser
		 */
		
		public function testCompareEntry($Entry, $user_id) {
			
			$entry_id = $Entry->id;
			$User = new User($user_id);
			unset($Entry);
			
			$Entry = new Entry($entry_id); 
			
			$this->assertEquals(self::TYPE, $Entry->Type->name); 
			$this->assertEquals(self::NAME, $Entry->name);
			$this->assertEquals(self::TEXT, $Entry->text); 
			$this->assertEquals(self::EXAMPLE, $Entry->example); 
			$this->assertEquals($User->username, $Entry->Author->username); 
			
		}
		
		public function testGetEntry() {
			
			$Type = new Type(self::TYPE); 
			
			foreach ($Type->getEntries() as $Entry) {
				$this->assertEquals(self::NAME, $Entry->name); 
			}
			
		}
	}
	