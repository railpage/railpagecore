<?php
	
	use Railpage\Ideas\Ideas;
	use Railpage\Ideas\Category;
	use Railpage\Ideas\Idea;
	use Railpage\Users\User;
	
	class IdeasTest extends PHPUnit_Framework_TestCase {
		
		const IDEA_TITLE = "Test idea";
		const IDEA_DESC = "Description blah blah test blah idea blah";
		const CAT_TITLE = "Test idea category";
		
		public $author_id = 0;
		
		public function testAddCategory() {
			
			$Category = new Category;
			$Category->name = self::CAT_TITLE; 
			$Category->commit(); 
			
			$this->assertFalse(!filter_var($Category->id, FILTER_VALIDATE_INT)); 
			
		}
		
		public function testAddIdea() {
			
			$User = new User;
			$User->username = "Test12123";
			$User->setPassword('safjasdfasdfasfd');
			$User->contact_email = "phpunit+ideas@railpage.com.au";
			$User->commit(); 
			$this->author_id = $User->id;
			
			$Idea = new Idea;
			$Idea->Category = new Category(1);
			$Idea->title = self::IDEA_TITLE;
			$Idea->description = self::IDEA_DESC;
			$Idea->setAuthor($User)->commit(); 
			
			$this->assertFalse(!filter_var($Idea->id, FILTER_VALIDATE_INT)); 
			
		}
		
		public function testCompareIdea() {
			
			$Idea = new Idea(1); 
			
			$this->assertEquals(self::IDEA_TITLE, $Idea->title); 
			$this->assertEquals(self::IDEA_DESC, $Idea->description);
			$this->assertEquals(self::CAT_TITLE, $Idea->Category->name);
			$this->assertEquals(0, $Idea->getVotes()); 
			$this->assertEquals(Ideas::STATUS_ACTIVE, $Idea->status);
			$this->assertInstanceOf("\\Railpage\\Users\\User", $Idea->Author); 
			$this->assertInstanceOf("\\DateTime", $Idea->Date);
			
		}
		
		public function testCanVote() {
			
			$User = new User($this->author_id);
			$Idea = new Idea(1);
			
			$this->assertFalse($Idea->canVote($User));
			
			unset($User);
			
			$User = new User;
			$User->username = __METHOD__;
			$User->contact_email = "phpunit+ideavote@railpage.com.au";
			$User->setPassword("asdfafasfafsdff23434");
			$User->commit(); 
			
			$this->assertTrue($Idea->canVote($User)); 
			
			$Idea->vote($User); 
			$this->assertEquals(1, $Idea->getVotes()); 
			$this->assertFalse($Idea->canVote($User)); 
			$this->assertEquals(1, count($Idea->getVoters()));
			
		}
	}