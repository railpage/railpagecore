<?php
	
	use Railpage\Users\Users;
	use Railpage\Users\Admin;
	use Railpage\Users\Base;
	use Railpage\Users\User;
	use Railpage\Warnings\Warning;
	
	class UserTest extends PHPUnit_Framework_TestCase {
		
		public function test_newUser() {
			
			$User = new User;
			
			$this->assertInstanceOf("Railpage\\Users\\User", $User);
			
			$User->username = "phpunit2";
			$User->contact_email = "michael+phpunit2@railpage.com.au";
			$User->setPassword("letmein1234");
			$User->commit(); 
			
			$this->assertFalse(!filter_var($User->id, FILTER_VALIDATE_INT));
			
		}
		
		public function test_isUsernameAvailable() {
			
			$User = new User;
			$User->username = "phpunit3";
			$User->contact_email = "michael+phpunit3@railpage.com.au";
			$User->setPassword("letmein1234");
			
			$this->assertTrue($User->isUsernameAvailable("phpunit3"));
			
			$User->commit(); 
			
			$this->assertFalse($User->isUsernameAvailable("phpunit3"));
			
		}
		
		public function test_isEmailAvailable() {
			
			$User = new User;
			$User->username = "phpunit4";
			$User->contact_email = "michael+phpunit4@railpage.com.au";
			$User->setPassword("letmein1234");
			
			$this->assertTrue($User->isEmailAvailable("michael+phpunit4@railpage.com.au"));
			
			$User->commit(); 
			
			$this->assertFalse($User->isEmailAvailable("michael+phpunit4@railpage.com.au"));
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_passwordStrength() {
			
			$User = new User;
			$User->username = "phpunit5";
			
			$this->assertFalse($User->safePassword("letmein"));
			$this->assertFalse($User->safePassword("railpage"));
			$this->assertFalse($User->safePassword($User->username));
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_loginUser() {
			
			$User = new User;
			$User->username = "phpunit6";
			$User->contact_email = "michael+phpunit6@railpage.com.au";
			$User->setPassword("letmein1234");
			$User->commit(); 
			
			$User = new User;
			
			$this->assertTrue($User->validatePassword("letmein1234", "phpunit6"));
			$this->assertEquals("phpunit6", $User->username);
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_issueWarning() {
			
			$User = new User;
			$User->username = "phpunit7";
			$User->contact_email = "michael+phpunit7@railpage.com.au";
			$User->setPassword("letmein1234");
			$User->commit(); 
			
			$Warning = new Warning;
			
			$this->assertInstanceOf("\\Railpage\\Warnings\\Warning", $Warning);
			
			$Warning->setRecipient($User);
			$Warning->setIssuer($User);
			$Warning->level = 54;
			$Warning->action = "Raised warning level to 54%";
			$Warning->reason = "phpUnit test";
			$Warning->comments = "Staff comment";
			
			$Warning->commit();
			
			$this->assertFalse(!filter_var($Warning->id, FILTER_VALIDATE_INT));
			
			$this->assertEquals(54, $User->warning_level);
		}
	}
?>