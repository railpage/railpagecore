<?php
	
	use Railpage\Organisations\Base;
	use Railpage\Organisations\Organisation;
	use Railpage\Organisations\OrganisationFromSlug;
	
	class OrganisationsTest extends PHPUnit_Framework_TestCase {
		
		public function testAddOrg() {
			
			$Organisation = new Organisation;
			$Organisation->name = "Test Org";
			$Organisation->desc = "Description blah blah";
			$Organisation->commit(); 
			
			return $Organisation;
			
		}
		
		/**
		 * @depends testAddOrg
		 */
		
		public function testLoadOrg($Organisation) {
			
			$NewOrg = new Organisation;
			$NewOrg = new Organisation($Organisation->id); 
			$NewOrg = new Organisation($Organisation->slug); 
			$NewOrg = new OrganisationFromSlug($Organisation->slug); 
			
			$Organisation->name = "Blah testing again";
			$Organisation->commit(); 
			
		}
		
		/**
		 * @depends testAddOrg
		 */
		
		public function testFindOrgs($Organisation) {
			
			$Orgs = new Base;
			$Orgs->search($Organisation->name); 
			$Orgs->getOrganisations(); 
			
		}
		
		/**
		 * @depends testAddOrg
		 */
		
		public function test_yieldJobs($Organisation) {
			
			foreach ($Organisation->yieldJobs() as $Job) {
				
			}
			
		}
		
		/**
		 * @depends testAddOrg
		 */
		
		public function test_yieldEvents($Organisation) {
			
			foreach ($Organisation->yieldUpcomingEvents() as $Job) {
				
			}
			
		}
	}