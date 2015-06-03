<?php
	
	use Railpage\BanControl\BanControl;
	use Railpage\Users\User;
	use Railpage\Users\Users;
	
	class BanControlTest extends PHPUnit_Framework_TestCase {
		
		public function test_loadBanControl() {
			
			$BanControl = new BanControl;
			$BanControl->loadAll(); 
			$BanControl->loadUsers(); 
			$BanControl->loadIPs();
			$BanControl->loadDomains(); 
			
			return $BanControl;
			
		}
		
		public function test_newUser() {
			
			$User = new User;
			$User->username = "BanControl tester";
			$User->contact_email = "michael+phpunitbantest@railpage.com.au";
			$User->setPassword("BanControl"); 
			$User->commit(); 
			
			return $User;
			
		}
		
		/**
		 * @depends test_loadBanControl
		 * @depends test_newUser
		 */
		
		public function test_banUser($BanControl, $User) {
			
			$BanControl->banUser($User->id, "test ban", strtotime("+1 hour"), $User->id); 
			
		}
		 
		
	}
