<?php
	
	use Railpage\Users\Users;
	use Railpage\Users\Admin;
	use Railpage\Users\Base;
	use Railpage\Users\User;
	use Railpage\Users\Group;
	use Railpage\Users\Groups;
	use Railpage\Warnings\Warning;
	use Railpage\Forums\Forums;
	use Railpage\Forums\Post;
	use Railpage\Forums\Thread;
	use Railpage\Forums\Forum;
	use Railpage\Forums\Category;
	use Railpage\AppCore;
	use Railpage\Registry;
	
	
	class UserTest extends PHPUnit_Framework_TestCase {
		
		public function test_newUser() {
			
			$User = new User;
			
			$this->assertInstanceOf("Railpage\\Users\\User", $User);
			
			$User->username = "phpunit2";
			$User->contact_email = "michael+phpunit2@railpage.com.au";
			
			$User->setPassword("letmein1234");
			$User->commit(); 
			
			$this->assertFalse(!filter_var($User->id, FILTER_VALIDATE_INT));
			
			$NewUser = new User;
			$this->assertFalse($NewUser->load());
			
			$Base = new Base; 
			$regs = $Base->getUserRegistrationStats(); 
			$this->assertTrue(count($regs) > 0); 
			
			return $User;
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_find($User) {
			
			$Admin = new Admin;
			
			foreach ($Admin->find($User->username) as $ThisUser) {
				$this->assertInstanceOf("\\Railpage\\Users\\User", $ThisUser);
			}
			
			foreach ($Admin->find($User->username, true) as $ThisUser) {
				$this->assertInstanceOf("\\Railpage\\Users\\User", $ThisUser);
			}
			
			foreach ($Admin->find("adfasfasdfa") as $row) {
				$this->assertEquals(NULL, $row); 
			}
			
			$this->setExpectedException("Exception", "Cannot perform user lookup because no partial username was provided"); 
			
			foreach ($Admin->find() as $row) {
				
			}
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_multiUsers($User) {
			
			$Admin = new Admin; 
			
			$multi = $Admin->multiUsers(); 
			$this->assertTrue(is_array($multi)); 
			
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_getUserFromEmail($User) {
			
			$Base = new Base;
			
			$this->assertInstanceOf("Railpage\\Users\\User", $Base->getUserFromEmail($User->contact_email)); 
			
			$this->setExpectedException("Exception", sprintf("No user found with an email address of %s and logging in via %s", "blah@test.com", "railpage"));
			$Base->getUserFromEmail("blah@test.com", "railpage"); 
			
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
			
			$User->setPassword("letmein");
			
			$this->setExpectedException("Exception", "Cannot set password - no password was provided"); 
			$User->setPassword();
			
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
		
		public function test_warning_break_recipient() {
			
			$this->setExpectedException("Exception", "Cannot validate warning level adjustment - no or invalid recipient provided");
			
			$Warning = new Warning;
			$Warning->commit(); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_warning_break_issuer($User) {
			
			$this->setExpectedException("Exception", "Cannot validate warning level adjustment - no or invalid issuer provided");
			
			$Warning = new Warning;
			$Warning->setRecipient($User); 
			$Warning->commit(); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_warning_break_level($User) {
			
			$this->setExpectedException("Exception", "Cannot validate warning level adjustment - no new warning level provided");
			
			$Warning = new Warning;
			$Warning->setRecipient($User)->setIssuer($User);
			$Warning->commit(); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_warning_break_reason($User) {
			
			$this->setExpectedException("Exception", "Cannot validate warning level adjustment - reason cannot be empty");
			
			$Warning = new Warning;
			$Warning->setRecipient($User)->setIssuer($User);
			$Warning->level = 20;
			$Warning->commit(); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_warning_break_exempt($User) {
			
			$User->warning_exempt = 1;
			
			$this->setExpectedException("Exception", sprintf("Cannot add warning to this user (ID %d, Username %s). Disallowed by system policy.", $User->id, $User->username));
			
			$Warning = new Warning;
			$Warning->setRecipient($User)->setIssuer($User);
			$Warning->level = 20;
			$Warning->reason = "Testing";
			$Warning->commit(); 
			
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
			
			return $Warning;
			
		}
		
		/**
		 * @depends test_issueWarning
		 */
		
		public function test_updateWarning($Warning) {
			
			// reload it
			$Warning = new Warning($Warning->id); 
			
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
			
			$Base = new Base; 
			
			$dups = $Base->findDuplicateUsernames();
			
			$this->assertEquals(0, count($dups)); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_logUserActivity($User) {
			
			$User->logUserActivity(1, "/blah", "Test page title", "8.8.8.8"); 
			
			$this->setExpectedException("Exception", "Cannot log user activity because no module ID was provided"); 
			$User->logUserActivity("asdfaf"); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_logUserActivity_NoUrl($User) {
			
			$this->setExpectedException("Exception", "Cannot log user activity because no URL was provided"); 
			$User->logUserActivity(1); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_logUserActivity_NoPagetitle($User) {
			
			$this->setExpectedException("Exception", "Cannot log user activity because no pagetitle was provided"); 
			$User->logUserActivity(1, "/blah"); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_logUserActivity_NoIP($User) {
			
			$this->setExpectedException("Exception", "Cannot log user activity because no remote IP was provided"); 
			$User->logUserActivity(1, "/blah", "asdfaf"); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_getIPs($User) {
			
			$ips = $User->getIPs(); 
			
			$this->assertEquals(0, count($ips)); 
			
			$User->recordLogin("8.8.8.8");
			$ips = $User->getIPs(); 
			$this->assertEquals(1, count($ips)); 
			
			$Forums = new Forums;
			$Category = new Category;
			$Forum = new Forum;
			$Thread = new Thread;
			$Post = new Post;
			
			$Category->title = "Test category";
			$Category->commit(); 
			
			$Forum->name = "Test forum";
			$Forum->setCategory($Category)->commit(); 
			
			$Thread->title = "Test thread";
			$Thread->setForum($Forum)->setAuthor($User)->commit(); 
			
			$Post->ip = "8.8.8.8";
			$Post->text = "Test post text"; 
			$Post->setThread($Thread)->setAuthor($User)->commit();
			
			$ips = $User->getIPs(); 
			$this->assertEquals(2, count($ips)); 
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
			
			$User->setUserAccountStatus();
			
			$User->setUserAccountStatus(User::STATUS_ACTIVE);
			$this->assertEquals(User::STATUS_ACTIVE, $User->getUserAccountStatus()); 
			
			$this->assertTrue($User->isActive()); 
			
			$User->setUserAccountStatus(User::STATUS_UNACTIVATED);
			$this->assertFalse($User->isActive()); 
			$User->setUserAccountStatus(User::STATUS_ACTIVE);
			
			return $User;
			
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
			
			$this->assertEquals(2, count($logins)); 
			$this->assertEquals("8.8.8.8", $logins[key($logins)]['login_ip']); 
			
			$User = new User;
			$this->assertFalse($User->recordLogin("8.8.8.8"));
			$this->assertFalse($User->getLogins());
			
			return $User;
			
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
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_reset($User) {
			
			$NewUser = clone $User;
			
			$NewUser->refresh(); 
			
			$NewUser->reset(); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_load($User) {
			
			$User->password_new = "asdfaf";
			$User->wheat = 10;
			$User->commit(); 
			
			$User->load(); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_generateUserData($User) {
			
			$this->assertFalse(count($User->generateUserData()) === 0);
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_unlinkFlickr($User) {
			
			$User->unlinkFlickr(); 
			
			$this->assertEquals(NULL, $User->flickr_nsid); 
			$this->assertEquals(NULL, $User->flickr_username); 
			$this->assertEquals(NULL, $User->flickr_oauth_token); 
			$this->assertEquals(NULL, $User->flickr_oauth_secret); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_aclRole($User) {
			
			$this->assertEquals("user", $User->aclRole());
			$this->assertEquals("guest", (new User)->aclRole()); 
			
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_updateVisit($User) {
			
			$User->updateVisit(false, time()); 
			
			$User->updateVisit(); 
			
			unset($User->mckey); 
			
			$User->updateVisit();
			
			$NewUser = new User; 
			$NewUser->updateVisit();
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_validateAvatar($User) {
			
			$User->avatar = "http://doge2048.com/meta/doge-600.png";
			$User->validateAvatar(); 
			
			$Config = AppCore::getConfig(); 
			$Config->AvatarMaxWidth = 100;  
			$Config->AvatarMaxHeight = 100; 
			
			#$Registry = Registry::getInstance();
			#$Registry->set("config", $Config);
			
			$User->avatar = "http://doge2048.com/meta/doge-600.png";
			$User->validateAvatar(); 
			$User->validateAvatar(true);
			
			$Config->AvatarMaxWidth = 1000;  
			$Config->AvatarMaxHeight = 1000; 
			
			#$Registry = Registry::getInstance();
			#$Registry->set("config", $Config);
			
			$User->avatar = "http://doge2048.com/meta/doge-600.png";
			$User->validateAvatar(); 
			$User->validateAvatar(true);
			
			$User->avatar = "http://not-an-image.com/noimage.jpgzor";
			$User->validateAvatar(); 
			$User->validateAvatar(true);
			
		}
		
		/**
		 * @depends test_accountStatus
		 */
		
		public function test_updateSessionTime($User) {
			
			$NewUser = new User; 
			$this->assertFalse($NewUser->updateSessionTime()); 
			
			$User->updateSessionTime(); 
			
			unset($User->mckey); 
			$User->updateSessionTime(); 
			
			$_SERVER['REMOTE_ADDR'] = "8.8.8.8";
			$User->updateSessionTime(); 
			
			$this->assertTrue($User->session_time != 0); 
			
			return $User;
			
		}
		
		/**
		 * @depends test_updateSessionTime
		 */
		
		public function test_memberList($User) {
			
			$User->setUserAccountStatus(User::STATUS_ACTIVE);
			
			$Admin = new Admin;
			
			$members = $Admin->memberList();
			
			$this->assertTrue(is_array($members)); 
			$this->assertTrue(count($members) > 0); 
			$this->assertTrue(count($members['members']) == 1); 
			$this->assertEquals($User->id, $members['members'][key($members['members'])]['id']);
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_getTimeline($User) {
			
			$timeline = $User->timeline(25, 1); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_getNumRegistrationsByMonth($User) {
			
			$Base = new Base; 
			
			$From = new DateTime("1 month ago"); 
			$To = new DateTime; 
			
			$User->setUserAccountStatus(User::STATUS_ACTIVE);
			
			$this->assertEquals(1, count($Base->getNumRegistrationsByMonth($From, $To))); 
			
		}
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_group($User) {
			
			$User->Redis = new \Railpage\NullCacheDriver;
			
			$this->assertEquals(0, count($User->getGroups())); 
			
			$Group = new Group; 
			$Group->name = "Test user group";
			$Group->desc = "A test user group description";
			$Group->type = Group::TYPE_HIDDEN;
			$Group->owner_user_id = $User->id;
			$Group->commit(); 
			
			$this->assertFalse(!filter_var($Group->id, FILTER_VALIDATE_INT)); 
			
			$this->assertFalse($Group->userInGroup($User)); 
			
			$Group->addMember($User->username, $User->id); 
			$Group->approveUser($User);
			$this->assertTrue($Group->userInGroup($User)); 
			
			$Group->removeUser($User); 
			$this->assertFalse($Group->userInGroup($User)); 
			
			$Group->addMember($User->username, $User->id, "Manager", "03 9562 2222", "blah"); 
			$Group->approveUser($User);
			$this->assertTrue($Group->userInGroup($User)); 
			
			$members = $Group->getMembers(); 
			$this->assertTrue(is_array($members)); 
			$this->assertTrue(count($members['members']) > 0);
			
			$this->assertTrue($User->inGroup($Group->id)); 
			
			$this->assertTrue(count($User->getGroups()) > 0); 
			
			$NewGroup = new Group($Group->id); 
			
			$this->assertEquals($NewGroup->id, $Group->id); 
			$this->assertEquals($NewGroup->name, $Group->name); 
			$this->assertEquals($NewGroup->desc, $Group->desc); 
			$this->assertEquals($NewGroup->type, $Group->type);
			
			$Group->type = Group::TYPE_OPEN;
			$Group->commit(); 
			
			
			
			
			if (!defined("RP_GROUP_ADMINS")) {
				define("RP_GROUP_ADMINS", "michaelisawesome");
			}
			
			$this->assertFalse($User->inGroup()); 
			$this->assertFalse($User->inGroup(RP_GROUP_ADMINS));
			
			$User->level = 2;
			$this->assertTrue($User->inGroup(RP_GROUP_ADMINS)); 
			
			$User->level = 1;
			
			
			return $Group;
			
		}
		
		/**
		 * @depends test_group
		 */
		
		public function test_groups($Group) {
			
			$Groups = new Groups; 
			$allgroups = $Groups->getGroups(); 
			
			$this->assertTrue(is_array($allgroups)); 
			$this->assertTrue(count($allgroups) > 0); 
			
		}
	}
	