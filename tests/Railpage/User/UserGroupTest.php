<?php
	
	use Railpage\Users\Users;
	use Railpage\Users\Admin;
	use Railpage\Users\Base;
	use Railpage\Users\User;
	use Railpage\Users\Group;
	use Railpage\Users\Groups;
	use Railpage\AppCore;
	use Railpage\Registry;
	use Railpage\Organisations\Organisation;
	
	
	class UserGroupTest extends PHPUnit_Framework_TestCase {
		
		public function test_newUser() {
			
			$User = new User;
			$User->username = "GroupTester";
			$User->contact_email = "michael+phpunit+groups@railpage.com.au";
			$User->setPassword("asdfadfa1111");
			$User->commit(); 
			$User->setUserAccountStatus(User::STATUS_ACTIVE);
			
			return $User;
			
		}
		
		/**
		 * @depends test_newUser
		 */
	
		public function test_newGroup($User) {
			
			$Group = new Group;
			$Group->name = "Test group 1";
			$Group->desc = "Description blah";
			$Group->type = Group::TYPE_OPEN;
			$Group->setOwner($User)->commit(); 
			
			return $Group;
			
		}
		
		/**
		 * @depends test_newGroup
		 * @depends test_newUser
		 */
		
		public function test_addMember($Group, $User) {
			
			$Group->addMember($User->username);
			$Group->approveUser($User->id); 
			$Group->userInGroup();
			$Group->removeUser();
			$Group->removeUser($User); 
			
			$Group->addMember("8796734");
			$Group->addMember($User->username, false, "test", "blah", 1);
			
		}
		
		public function test_exception_members() {
			
			$this->setExpectedException("Exception", "Cannot fetch group - group ID cannot be empty"); 
			$Group = new Group;
			$Group->members(); 
			
		}
		
		public function test_exception_name() {
			
			$this->setExpectedException("Exception", "Cannot validate group - group name cannot be empty"); 
			
			$Group = new Group;
			$Group->commit(); 
			
		}
		
		public function test_exception_desc() {
			
			$this->setExpectedException("Exception", "Cannot validate group - group description cannot be empty"); 
			
			$Group = new Group;
			$Group->name = "asdfafasdfa";
			$Group->commit(); 
			
		}
		
		public function test_exception_owner() {
			
			$this->setExpectedException("Exception", "Cannot validate group - group owner user ID cannot be empty"); 
			
			$Group = new Group;
			$Group->name = "asdfafasdfa";
			$Group->desc = "blah";
			$Group->commit(); 
			
		}
		
		
		/**
		 * @depends test_newUser
		 */
		
		public function test_newGroupOrg($User) {
			
			$Org = new Organisation;
			$Org->name = "Test org";
			$Org->desc = "User group org";
			$Org->commit(); 
			$this->assertFalse(!filter_var($Org->id, FILTER_VALIDATE_INT)); 
			
			$Group = new Group;
			$Group->name = "Test org group 2";
			$Group->desc = "Description blah";
			$Group->type = Group::TYPE_OPEN;
			$Group->setOwner($User)->setOrganisation($Org);
			
			$this->assertEquals($Org->id, $Group->organisation_id);
			
			$Group->commit(); 
			
			$NewGroup = new Group($Group->id);
			
			$this->assertEquals($Org->id, $Group->organisation_id);
			
		}
		
		/**
		 * @depends test_newGroup
		 */
		
		public function test_getGroups($Group) {
			
			$Groups = new Groups; 
			
		}
		
		/**
		 * @depends test_newGroup
		 */
		
		public function test_findWithAttribute($Group) {
			
			$Group->attributes['test'] = 1;
			$Group->commit(); 
			
			$Groups = new Groups;
			
			$Groups->findWithAttribute("test"); 
			$Groups->findWithAttribute("test", 1); 
			$Groups->findWithAttribute("test", "asdfdfa"); 
			
			$this->setExpectedException("Exception", "Cannot filter groups by attribute - no attribute given!"); 
			$Groups->findWithAttribute(); 
			
		}
	}