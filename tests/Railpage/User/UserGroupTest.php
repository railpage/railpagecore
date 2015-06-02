<?php
	
	use Railpage\Users\Users;
	use Railpage\Users\Admin;
	use Railpage\Users\Base;
	use Railpage\Users\User;
	use Railpage\Users\Group;
	use Railpage\Users\Groups;
	use Railpage\AppCore;
	use Railpage\Registry;
	
	
	class UserGroupTest extends PHPUnit_Framework_TestCase {
	
		public function test_newGroup() {
			
			$Group = new Group;
			
			return $Group;
			
		}
		
		/**
		 * @depends test_newGroup
		 */
		
		public function test_getGroups($Group) {
			
			$Groups = new Groups; 
			
		}
	}