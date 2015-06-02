<?php
	
	use Railpage\Users\Users;
	use Railpage\Users\Admin;
	use Railpage\Users\Base;
	use Railpage\Users\User;
	use Railpage\Warnings\Warning;
	use Railpage\Forums\Forums;
	use Railpage\Forums\Post;
	use Railpage\Forums\Thread;
	use Railpage\Forums\Forum;
	use Railpage\Forums\Category;
	use Railpage\AppCore;
	use Railpage\Registry;
	
	
	class UserBaseTest extends PHPUnit_Framework_TestCase {
	
		public function test_getRanks() {
			
			$Base = new Base;
			
			$this->assertEquals(0, count($Base->getRanks())); 
			
			$Base->addCustomRank("Test rank");
			
			$Admin = new Admin;
			$ranks = $Admin->ranks(); 
			$this->assertEquals("ok", $ranks['stat']); 
			
			
			$this->assertEquals(1, count($Base->getRanks()));
			
			$this->setExpectedException("Exception", "No rank text given"); 
			$Base->addCustomRank();
			
		}
		
		public function test_getOnlineUsers() {
			
			$Base = new Base;
			
			$this->assertEquals(0, count($Base->onlineUsers())); 
			
		}
		
		public function test_getUserFromEmail() {
			
			$Base = new Base;
			
			$this->setExpectedException("Exception", "Can't find user - no email address provided");
			$Base->getUserFromEmail();
			
		}
	}