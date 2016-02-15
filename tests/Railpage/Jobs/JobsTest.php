<?php

use Railpage\Jobs\Classification;
use Railpage\Jobs\Classifications;
use Railpage\Jobs\Job;
use Railpage\Jobs\Jobs;
use Railpage\Jobs\Location;
use Railpage\Jobs\Locations;
use Railpage\Jobs\Scraper;
use Railpage\Organisations\Organisation;

class JobsTest extends PHPUnit_Framework_Testcase {
    
    public function testCreateOrg() {
        
        $Org = new Organisation;
        $Org->name = "Test org";
        $Org->desc = "test org descccc";
        $Org->commit(); 
        
        return $Org;
        
    }
    
    /**
     * @depends testCreateOrg
     */
    
    public function testScrape($Org) {
        
        $Scraper = new Scraper("http://careers.pageuppeople.com/587/railp/en/rss/", "pageuppeople", $Org);
	    $Scraper->fetch()->store();
        
        return "balls";
        
    }
    
    /**
     * @depends testCreateOrg
     * @depends testScrape
     */
    
    public function testGetJobs($Org, $blah) {
        
        $Jobs = new Jobs;
        
        foreach ($Jobs->yieldProviders() as $Provider) {
            // nahh
        }
        
        $Jobs->getRandomJob(); 
        
        foreach ($Jobs->yieldNewJobs() as $Job) {
            $this->assertFalse(!filter_var($Job->id, FILTER_VALIDATE_INT)); 
        }
        
        $Jobs->getNumNewJobs(); 
        
        $Jobs->getJobsFromEmployer($Org); 
        
    }
    
}