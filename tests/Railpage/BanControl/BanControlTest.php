<?php
    
    use Railpage\BanControl\BanControl;
    use Railpage\Users\User;
    use Railpage\Users\Users;
    use Railpage\AppCore;
    use Railpage\Registry;
    
    class BanControlTest extends PHPUnit_Framework_TestCase {
        
        public function test_loadBanControl() {
            
            $BanControl = new BanControl;
            $BanControl->loadAll(); 
            $BanControl->loadUsers(); 
            $BanControl->loadIPs();
            $BanControl->loadDomains(); 
            $BanControl->loadAll();
            
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
            
            $Registry = Registry::getInstance(); 
            $Registry->remove("redis");
            
            $BanControl->unBanUser($User);
            
            $this->assertFalse($BanControl->isClientBanned($User->id, "8.8.8.8"));
            
            $BanControl->banUser($User->id, "test ban", strtotime("+1 hour"), $User->id); 
            
            $this->assertFalse($BanControl->isUserBanned());
            
            $BanControl->isUserBanned($User->id); 
            $BanControl->users = NULL;
            $BanControl->isUserBanned($User); 
            $BanControl->lookupUser($User->id);
            
            $BanControl->banUser($User->id, "test ban", 0, $User->id); 
            $BanControl->lookupUser($User->id);
            
            
        }
        
        /**
         * @depends test_loadBanControl
         * @depends test_newUser
         */
        
        public function test_banIP($BanControl, $User) {
            
            $BanControl->banIP("8.8.8.8", "test ban", strtotime("+1 hour"), $User->id); 
            
            $BanControl->unBanUser($User);
            
            $BanControl->ip_addresses = NULL;
            $BanControl->isIPBanned("8.8.8.8"); 
            $BanControl->isClientBanned($User->id, "8.8.8.8");
            $BanControl->lookupIP("8.8.8.8");
            $BanControl->unBanIP("8.8.8.8");
            
            $BanControl->banIP("8.8.8.8", "test ban", 0, $User->id); 
            $BanControl->lookupIP("8.8.8.8");
            $BanControl->unBanIP("8.8.8.8");
            
            $this->setExpectedException("Exception", "Cannot check for banned IP address because no or an invaild IP address was given");
            $BanControl->isIPBanned(); 
         
        }
        
        /**
         * @depends test_loadBanControl
         */
        
        public function test_lookupUser_break($BanControl) {
            
            $this->setExpectedException("Exception", "Cannot peform user ban lookup - no user ID given"); 
            $BanControl->lookupUser(); 
            
        }
        
        /**
         * @depends test_loadBanControl
         */
        
        public function test_lookupIP_break($BanControl) {
            
            $this->setExpectedException("Exception", "Cannot peform IP ban lookup - no IP address given"); 
            $BanControl->lookupIP(); 
            
        }
        
    }
