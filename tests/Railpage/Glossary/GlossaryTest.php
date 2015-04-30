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
		
		public function testAddEntry() {
			
			$User = new User;
			$User->username = self::AUTHOR;
			$User->setPassword('zasdfasdfadfag');
			$User->contact_email = "phpunit+glossary@railpage.com.au";
			$User->commit(); 
			
			$Type = new Type(self::TYPE);
			$Entry = new Entry;
			$Entry->name = self::NAME;
			$Entry->text = self::TEXT;
			$Entry->example = self::EXAMPLE;
			$Entry->Type = $Type;
			$Entry->setAuthor($User); 
			
			$Entry->commit(); 
			
			$this->assertFalse(!filter_var($Entry->id, FILTER_VALIDATE_INT));
			
		}
		
		public function testCompareEntry() {
			
			$Entry = new Entry(1); 
			
			$this->assertEquals(self::TYPE, $Entry->Type->name); 
			$this->assertEquals(self::NAME, $Entry->name);
			$this->assertEquals(self::TEXT, $Entry->text); 
			$this->assertEquals(self::EXAMPLE, $Entry->example); 
			$this->assertEquals(self::AUTHOR, $Entry->Author->username); 
			
		}
		
		public function testGetEntry() {
			
			$Type = new Type(self::TYPE); 
			
			foreach ($Type->getEntries() as $Entry) {
				$this->assertEquals(self::NAME, $Entry->name); 
			}
			
		}
	}