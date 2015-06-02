<?php
	
	use Railpage\Users\Users;
	use Railpage\Users\Admin;
	use Railpage\Users\Base;
	use Railpage\Users\User;
	use Railpage\Warnings\Warning;
	
	class NoUserTest extends PHPUnit_Framework_TestCase {
		
		public function test_isUsernameAvailable() {
			
			$User = new User;
			$User->username = "phpunit3";
			$User->contact_email = "michael+phpunit3@railpage.com.au";
			$User->setPassword("letmein1234");
			
			$this->assertTrue($User->isUsernameAvailable("phpunit3"));
			
			$User->commit(); 
			
			$this->assertFalse($User->isUsernameAvailable("phpunit3"));
			$this->assertFalse($User->isUsernameAvailable());
			
			$Base = new Base;
			$this->assertFalse($Base->username_available("phpunit3")); 
			$this->assertTrue($Base->username_available("blahsdfsfa")); 
			
			$Admin = new Admin;
			$this->assertFalse($Admin->username_available("phpunit3")); 
			
			$User = new User;
			$this->setExpectedException("Exception", "Cannot check if username is available because no username was provided");
			$this->assertFalse($User->isUsernameAvailable());
			
		}
		
		public function test_isEmailAvailable() {
			
			$User = new User;
			$User->username = "phpunit4";
			$User->contact_email = "michael+phpunit4@railpage.com.au";
			$User->setPassword("letmein1234");
			
			$this->assertTrue($User->isEmailAvailable("michael+phpunit4@railpage.com.au"));
			
			$User->commit(); 
			
			$this->assertFalse($User->isEmailAvailable("michael+phpunit4@railpage.com.au"));
			$this->assertFalse($User->isEmailAvailable());
			
			$Base = new Base;
			$this->assertFalse($Base->email_available("michael+phpunit4@railpage.com.au")); 
			$this->assertTrue($Base->email_available("michael+phpunit87654@railpage.com.au")); 
			$this->assertfalse($Base->email_available());
			
			$Admin = new Admin;
			$this->assertFalse($Admin->email_available("michael+phpunit4@railpage.com.au")); 
			
			$User = new User;
			$this->setExpectedException("Exception", "Cannot check if email address is available because no email address was provided");
			$this->assertFalse($User->isEmailAvailable());
			
		}
		
        public function test_validateNewUser_EmptyUsername() {
			
			$this->setExpectedException("Exception", "Username cannot be empty"); 
			
			$User = new User; 
			
			$User->validate(); 
			
		}
			
		public function test_validateNewUser_EmtpyEmail() {
			
			$this->setExpectedException("Exception", "User must have an email address"); 
			
			$User = new User; 
			
			$User->username = "testblah";
			$User->validate(); 
			
		}
		
		public function test_validateNewUser_InvalidEmail() {
			
			$email = "adfsdafaf";
			
			$this->setExpectedException("Exception", sprintf("%s is not a valid email address", $email)); 
			
			$User = new User; 
				
			$User->username = "testblah";
			$User->contact_email = $email;
			$User->validate(); 
			
		}
		
		public function test_validateNewUser_EmptyPassword() {
			
			$this->setExpectedException("Exception", "Password is empty"); 
			
			$User = new User; 
				
			$User->username = "testblah";
			$User->contact_email = "blah@railpage.com.au";
			$User->provider = "railpage";
			$User->validate(); 
			
		}
		
		public function test_validateNewUser_EmptyPasswordSuccess() {
			
			$User = new User; 
			
			$User->username = "testblah";
			$User->contact_email = "blah@railpage.com.au";
			$User->provider = "google";
			$User->validate(); 
			$this->assertEquals("", $User->password); 
			
		}
		
		public function test_validateNewUser_DefaultTheme() {
			
			$User = new User; 
			
			$User->username = "testblah";
			$User->contact_email = "blah@railpage.com.au";
			$User->provider = "google";
			$User->default_theme = NULL;
			
			$User->validate(); 
			
		}
		
	}
