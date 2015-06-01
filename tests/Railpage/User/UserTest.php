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
			
			return $User;
			
		}
		
		public function test_isUsernameAvailable() {
			
			$User = new User;
			$User->username = "phpunit3";
			$User->contact_email = "michael+phpunit3@railpage.com.au";
			$User->setPassword("letmein1234");
			
			$this->assertTrue($User->isUsernameAvailable("phpunit3"));
			
			$User->commit(); 
			
			$this->assertFalse($User->isUsernameAvailable("phpunit3"));
			$this->assertFalse($User->isUsernameAvailable());
			
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
			
			$User = new User;
			$this->setExpectedException("Exception", "Cannot check if email address is available because no email address was provided");
			$this->assertFalse($User->isEmailAvailable());
			
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
			
			$id = $User->id;
			
			$User = new User;
			$User->Redis = new Railpage\NullCacheDriver; // Kept on erroring out with a cached username
			
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
			
			$this->assertTrue($User->loadWarnings()); 
			$this->assertTrue(!empty($User->warnings));
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_isHuman($User) {
			
			$this->assertFalse($User->validateHuman()); 
			
			$User->setValidatedHuman(); 
			
			$this->assertTrue($User->validateHuman());
			
			// Backdate the timestamp
			$User->meta['captchaTimestamp'] = strtotime("12 hours ago"); 
			$this->assertFalse($User->validateHuman()); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_getArray($User) {
			$array = $User->getArray(); 
			
			$this->assertTrue(is_array($array)); 
			$this->assertEquals($array['id'], $User->id) ;
			$this->assertEquals($array['username'], $User->username); 
			$this->assertEquals($array['contact_email'], $User->contact_email);
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_preferences($User) {
			$prefs = $User->getPreferences(); 
			
			$this->assertTrue(is_array($prefs)); 
			$this->assertTrue(count($prefs) > 1); 
			
			$prefs['test'] = "blah"; 
			$User->savePreferences($prefs); 
			
			$test = array("test" => true); 
			$User->savePreferences("testagain", $test); 
			
			$prefs = $User->getPreferences(); 
			$this->assertTrue(isset($prefs['testagain']));
			
			$test = $User->getPreferences("testagain"); 
			$this->assertEquals(true, $test['test']); 
			
			$User->savePreferences(); 
			
			$this->setExpectedException("Exception", "The requested preferences section \"zomgwhat\" does not exist"); 
			$User->getPreferences("zomgwhat"); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_getDuplicates($User) {
			
			$dups = $User->findDuplicates(); 
			
			$this->assertEquals(1, count($dups));
			$this->assertEquals($User->id, $dups[0]['user_id']);
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_logUserActivity($User) {
			
			$User->logUserActivity(1, "/blah", "Test page title", "8.8.8.8"); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_getIPs($User) {
			$ips = $User->getIPs(); 
			
			$this->assertEquals(0, count($ips)); 
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_validateEmail($User) {
			
			$User->validateEmail($User->contact_email); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_validateEmail_Empty($User) {
			
			$this->setExpectedException("Exception", "No email address was supplied."); 
			$User->validateEmail(""); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_validateEmail_Invalid($User) {
			
			$email = "asdffasdfsfasdfadfafsdfafd";
			
			$this->setExpectedException("Exception", sprintf("%s is not a valid email address", $email)); 
			$User->validateEmail($email); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_validateEmail_Existing($User) {
			
			$this->setExpectedException("Exception", sprintf("The requested email address %s is already in use by a different user.", $User->contact_email)); 
			
			$NewUser = new User; 
			$NewUser->username = "asdfadfaf";
			$NewUser->contact_email = $User->contact_email;
			$NewUser->setPassword("letmein1234");
			$NewUser->commit(); 
			
			$NewUser->validateEmail($NewUser->contact_email); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_safePassword($User) {
			
			$this->assertTrue($User->safePassword("asdfsdfaa234234")); 
			$this->assertFalse($User->safePassword($User->username)); 
			$this->assertFalse($User->safePassword("password")); 
			$this->assertFalse($User->safePassword("sadf23"));
			
			$this->setExpectedException("Exception", "You gotta supply a password..."); 
			$this->assertFalse($User->safePassword(""));
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_accountStatus($User) {
			
			$this->assertEquals(User::STATUS_UNACTIVATED, $User->getUserAccountStatus()); 
			
			$User->setUserAccountStatus(User::STATUS_ACTIVE);
			$this->assertEquals(User::STATUS_ACTIVE, $User->getUserAccountStatus()); 
			
			$this->assertTrue($User->isActive()); 
			
			$User->setUserAccountStatus(User::STATUS_UNACTIVATED);
			$this->assertFalse($User->isActive()); 
			$User->setUserAccountStatus(User::STATUS_ACTIVE);
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_rep($User) {
			
			$wheat = $User->wheat;
			$chaff = $User->chaff; 
			
			$User->wheat(10);
			$this->assertEquals($wheat + 10, $User->wheat); 
			
			$User->chaff(20); 
			$this->assertEquals($chaff + 20, $User->chaff); 
			
			$User->addChaff(20); 
			$this->assertEquals($chaff + 40, $User->chaff); 
			
			$wheat = $User->wheat; 
			$User->wheat();
			$this->assertEquals($wheat + 1, $User->wheat); 
			
			$chaff = $User->chaff;
			$User->chaff(); 
			$this->assertEquals($chaff + 1, $User->chaff); 
			
			$wheat = $User->wheat; 
			$User->wheat("asdfa");
			$this->assertEquals($wheat + 1, $User->wheat); 
			
			$chaff = $User->chaff;
			$User->chaff("asdfa"); 
			$this->assertEquals($chaff + 1, $User->chaff); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_getGroups($User) {
			
			$this->assertEquals(false, $User->getGroups()); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_newAPIKey($User) {
			
			$User->api_key = NULL;
			
			$User->newAPIKey(); 
			$this->assertTrue(!is_null($User->api_key));
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_recordLogins($User) {
			
			$User->recordLogin("8.8.8.8"); 
			
			$logins = $User->getLogins(); 
			
			$this->assertEquals(1, count($logins)); 
			$this->assertEquals("8.8.8.8", $logins[key($logins)]['login_ip']); 
			
			$User = new User;
			$this->assertFalse($User->recordLogin("8.8.8.8"));
			$this->assertFalse($User->getLogins());
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_autologin($User) {
			
			$this->assertFalse($User->tryAutoLogin()); 
						
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_notes($User) {
			
			$this->assertFalse($User->addNote());
			$this->assertFalse($User->addNote(NULL)); 
		
			$this->assertTrue($User->loadNotes());
			
			$this->assertTrue(!empty($User->notes)); 
			
			$User = new User; 
			$this->assertFalse($User->loadNotes()); 
			
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
	