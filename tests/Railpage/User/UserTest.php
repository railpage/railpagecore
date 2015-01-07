<?php
	
	use Railpage\Users\Users;
	use Railpage\Users\Admin;
	use Railpage\Users\Base;
	use Railpage\Users\User;
	use Railpage\Warnings;
	
	class UserTest extends PHPUnit_Framework_TestCase {
		
		public function test_newUser() {
			
			$User = new User;
			
			$this->assertInstanceOf("Railpage\\Users\\User", $User);
			
			$User->username = "phpunit2";
			$User->contact_email = "michael+phpunit@railpage.com.au";
			$User->setPassword("letmein1234");
			$User->commit(); 
			
			
			define("TEST_USER_ID", $User->id);
			
		}
		
		public function test_isUsernameAvailable() {
			
			$User = new User;
			$this->assertEquals(false, $User->isUsernameAvailable("phpunit2"));
			$this->assertEquals(true, $User->isUsernameAvailable("phpunit3"));
			
		}
		
		public function test_isEmailAvailable() {
			
			$User = new User;
			$this->assertEquals(false, $User->isEmailAvailable("michael+phpunit@railpage.com.au"));
			$this->assertEquals(true, $User->isEmailAvailable("notmyemailaddress@railpage.com.au"));
			
		}
		
		public function test_passwordStrength() {
			
			$User = new User(TEST_USER_ID); 
			
			$this->assertEquals(false, $User->safePassword("letmein"));
			$this->assertEquals(false, $User->safePassword("railpage"));
			$this->assertEquals(false, $User->safePassword($User->username));
			
		}
		
		public function test_loginUser() {
			
			$User = new User;
			
			$this->assertEquals(true, $User->validatePassword("letmein1234", "phpunit2"));
			$this->assertEquals("phpunit2", $User->username);
			
		}
	}
?>