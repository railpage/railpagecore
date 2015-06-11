<?php
	
	use Railpage\Ideas\Ideas;
	use Railpage\Ideas\Category;
	use Railpage\Ideas\Idea;
	use Railpage\Users\User;
	use Railpage\Forums\Forums;
	use Railpage\Forums\Forum;
	use Railpage\Forums\Category as ForumCategory;
	use Railpage\Forums\Thread;
	use Railpage\Forums\Post;
	
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
			
			$NewCat = new Category($Category->slug); 
			$this->assertEquals($Category->id, $NewCat->id);
			
			// Duplicate it
			$Clone = new Category;
			$Clone->name = self::CAT_TITLE; 
			$Clone->commit(); 
			
			$this->assertNotEquals($Category->slug, $Clone->slug); 
			
			return $Category->id;
			
		}
		
		/**
		 * @depends testAddCategory
		 */
		
		public function testAddIdea($category_id) {
			
			$User = new User;
			$User->username = "Test12123";
			$User->setPassword('safjasdfasdfasfd');
			$User->contact_email = "phpunit+ideas@railpage.com.au";
			$User->commit(); 
			$this->author_id = $User->id;
			
			$Idea = new Idea;
			$Idea->Category = new Category($category_id);
			$Idea->title = self::IDEA_TITLE;
			$Idea->description = self::IDEA_DESC;
			$Idea->setAuthor($User)->commit(); 
			
			$this->assertFalse(!filter_var($Idea->id, FILTER_VALIDATE_INT)); 
			
			$idea_id = $Idea->id;
			
			$Idea = new Idea($idea_id);
			$this->assertEquals($idea_id, $Idea->id); 
			$this->assertEquals(self::IDEA_TITLE, $Idea->title);
			
			$NewIdea = new Idea($Idea->slug); 
			$this->assertEquals($Idea->id, $NewIdea->id);
			
			$Clone = new Idea; 
			$Clone->Category = new Category($category_id);
			$Clone->title = self::IDEA_TITLE;
			$Clone->description = self::IDEA_DESC;
			$Clone->setAuthor($User)->commit(); 
			
			$this->assertNotEquals($Clone->slug, $Idea->slug); 
			
			return $idea_id;
			
		}
		
		/**
		 * @depends testAddIdea
		 */
		
		public function testCatGetIdeas($idea_id) {
			
			$Idea = new Idea($idea_id); 
			
			foreach ($Idea->Category->getIdeas() as $ThisIdea) {
				$this->assertInstanceOf("Railpage\\Ideas\\Idea", $ThisIdea); 
			}
			
		}
		
		/**
		 * @depends testAddIdea
		 */
		
		public function testCompareIdea($idea_id) {
			
			$Idea = new Idea($idea_id); 
			
			$this->assertEquals(self::IDEA_TITLE, $Idea->title); 
			$this->assertEquals(self::IDEA_DESC, $Idea->description);
			$this->assertEquals(self::CAT_TITLE, $Idea->Category->name);
			$this->assertEquals(0, $Idea->getVotes()); 
			$this->assertEquals(Ideas::STATUS_ACTIVE, $Idea->status);
			$this->assertInstanceOf("\\Railpage\\Users\\User", $Idea->Author); 
			$this->assertInstanceOf("\\DateTime", $Idea->Date);
			
			$Idea->commit(); 
			
			$NewIdea = new Idea($Idea->slug); 
			
			$this->assertEquals($Idea->id, $NewIdea->id); 
			
		}
		
		/**
		 * @depends testAddIdea
		 */
		
		public function testCanVote($idea_id) {
			
			$User = new User($this->author_id);
			$Idea = new Idea($idea_id);
			
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
		
		/**
		 * @depends testAddCategory
		 */
		
		public function testGetCategories() {
			
			$Ideas = new Ideas;
			
			foreach ($Ideas->getCategories() as $Category) {
				$this->assertEquals(self::CAT_TITLE, $Category->name);
				$this->assertFalse(!filter_var($Category->id, FILTER_VALIDATE_INT));
			}
		}
		
		/**
		 * @depends testAddIdea
		 */
		
		public function testGetNewIdeas() {
			
			$Ideas = new Ideas;
			
			foreach ($Ideas->getNewIdeas() as $Idea) {
				$this->assertFalse(!filter_var($Idea->id, FILTER_VALIDATE_INT)); 
				
			}
		}
		
		/**
		 * @depends testAddCategory
		 */
		
		public function testUpdateCategory($cat_id) {
			
			$Cat = new Category($cat_id); 
			$Cat->commit(); 
			
		}
		
		/**
		 * @depends testAddCategory
		 */
		
		public function test_break_category_name($cat_id) {
			
			$this->setExpectedException("Exception", "Idea category name cannot be empty");
			
			$Category = new Category($cat_id); 
			$Category->name = NULL; 
			$Category->commit(); 
			
		}
		
		/**
		 * @depends testAddIdea
		 */
		
		public function test_break_idea_title($idea_id) {
			
			$this->setExpectedException("Exception", "Title of the idea cannot be empty");
			
			$Idea = new Idea($idea_id); 
			$Idea->title = NULL;
			$Idea->commit(); 
			
		}
		
		/**
		 * @depends testAddIdea
		 */
		
		public function test_break_idea_title_long($idea_id) {
			
			$this->setExpectedException("Exception", "The title for this idea is too long");
			
			$Idea = new Idea($idea_id); 
			$Idea->title = "Bacon ipsum dolor amet t-bone kielbasa meatloaf, cow doner picanha filet mignon pig venison shank pork.";
			$Idea->commit(); 
			
		}
		
		/**
		 * @depends testAddIdea
		 */
		
		public function test_break_idea_description($idea_id) {
			
			$this->setExpectedException("Exception", "Description for the idea cannot be empty");
			
			$Idea = new Idea($idea_id); 
			$Idea->description = NULL;
			$Idea->commit(); 
			
		}
		
		/**
		 * @depends testAddIdea
		 */
		
		public function test_break_idea_category($idea_id) {
			
			$this->setExpectedException("Exception", "There must be a valid author specified for this idea");
			
			$Idea = new Idea($idea_id); 
			$Idea->Author = NULL;
			$Idea->commit(); 
			
		}
		
		/**
		 * @depends testAddIdea
		 */
		
		public function test_break_idea_author($idea_id) {
			
			$this->setExpectedException("Exception", "Each idea must belong to a valid category");
			
			$Idea = new Idea($idea_id); 
			$Idea->Category = NULL;
			$Idea->commit(); 
			
		}
		
		/**
		 * @depends testAddIdea
		 */
		
		public function test_idea_status($idea_id) {
			
			$Idea = new Idea($idea_id); 
			$Idea->status = NULL;
			$Idea->redmine_id = 1;
			$Idea->commit(); 
			
			$this->assertEquals(Ideas::STATUS_ACTIVE, $Idea->status); 
			
		}
		
		/**
		 * @depends testAddIdea
		 */
		
		public function test_canvote_status($idea_id) {
			
			$Idea = new Idea($idea_id); 
			
			$Idea->status = Ideas::STATUS_DELETED;
			$this->assertFalse($Idea->canVote($Idea->Author));
			
			$Idea->status = Ideas::STATUS_ACTIVE;
			$this->assertFalse($Idea->canVote($Idea->Author));
			
			$this->setExpectedException("Exception", "We couldn't add your vote to this idea. You must be logged in and not already voted for this idea");
			$Idea->vote($Idea->Author);
			
		}
		
		/**
		 * @depends testAddIdea
		 */
		
		public function test_getarray($idea_id) {
			
			$Idea = new Idea($idea_id); 
			$Idea->getArray(); 
			
		}
		
		/**
		 * @depends testAddIdea
		 */
		
		public function test_createThread($idea_id) {
			
			$Idea = new Idea($idea_id); 
			
			$Category = new ForumCategory;
			$Category->title = "Ideas";
			$Category->commit(); 
			
			$Forum = new Forum;
			$Forum->setCategory($Category);
			$Forum->name = "Ideas forum";
			$Forum->commit();
			
			$Thread = new Thread;
			$Thread->title = $Idea->title;
			$Thread->setForum($Forum)->setAuthor($Idea->Author)->commit(); 
			
			$Post = new Post;
			$Post->text = $Idea->description;
			$Post->ip = "8.8.8.8";
			$Post->setAuthor($Idea->Author)->setThread($Thread)->commit(); 
			
			$this->assertFalse(!filter_var($Post->id, FILTER_VALIDATE_INT)); 
			
			return $Thread->id;
			
		}
		
		/**
		 * @depends test_createThread
		 * @depends testAddIdea
		 */
		
		public function test_setIdeaThread($thread_id, $idea_id) {
			
			$Thread = new Thread($thread_id);
			$Idea = new Idea($idea_id); 
			
			$this->assertNull($Idea->getForumThread()); 
			$Idea->setForumThread($Thread);
			
			$this->assertInstanceOf("Railpage\\Forums\\Thread", $Idea->getForumThread()); 
			
		}
	}
	