<?php
    use Railpage\Chronicle\Chronicle;
    use Railpage\Chronicle\Entry;
    use Railpage\Chronicle\EntryType;
    use Railpage\Chronicle\Decade;
    use Railpage\Chronicle\Year;
    use Railpage\Users\User;
    
    class ChronicleTest extends PHPUnit_Framework_TestCase {
        
        /**
         * Create a demo user
         */
        
        public function testCreateDemoUser() {
            
            $User = new User;
            $User->username = "phpunit";
            $User->contact_email = "phpunit@website.com";
            $User->provider = "railpage";
            $User->setPassword("thisisnotmypassword");
            $User->commit(); 
            
            return $User; 
            
        }
        
        /**
         * EntryType
         */
        
        public function testAddEntryType() {
            $EntryType = new EntryType; 
            
            $this->assertInstanceOf("Railpage\\Chronicle\\EntryType", $EntryType);
            
            $EntryType->text = "Test entry type";
            $EntryType->group = EntryType::GROUPING_LOCOS;
            $EntryType->commit(); 
            
            return $EntryType;
            
        }
        
        /**
         * @depends testAddEntryType
         */
        
        public function testGetEntryType($TestType) {
            $EntryType = new EntryType($TestType->id);
            
            $this->assertEquals($TestType->id, $EntryType->id);
            $this->assertEquals("Test entry type", $EntryType->text);
            $this->assertEquals(EntryType::GROUPING_LOCOS, $EntryType->group);
        }
        
        /**
         * @depends testAddEntryType
         */
        
        public function testUpdateEntryType($TestType) {
            $EntryType = new EntryType($TestType->id);
            
            $EntryType->text = "Test entry type updated to Locations";
            $EntryType->group = EntryType::GROUPING_LOCATIONS;
            
            $updated_name = $EntryType->text;
            $updated_desc = $EntryType->group;
            
            $EntryType->commit(); 
            
            // Reload the operator
            $EntryType = new EntryType($TestType->id);
            
            $this->assertEquals($updated_name, $EntryType->text);
            $this->assertEquals($updated_desc, $EntryType->group);
        }
        
        /**
         * Entry
         * @depends testCreateDemoUser
         * @depends testAddEntryType
         */
        
        public function testAddEntry($User, $TestType) {
            
            $Entry = new Entry; 
            
            $this->assertInstanceOf("Railpage\\Chronicle\\Entry", $Entry);
            
            $Entry->setAuthor($User);
            $Entry->Date = new DateTime("1988-02-18");
            $Entry->blurb = "A test chronicle entry";
            $Entry->text = "A test chronicle entry descriptive text";
            $Entry->EntryType = new EntryType($TestType->id);
            $Entry->commit(); 
            
            return $Entry->id;
            
        }
        
        /**
         * @depends testAddEntry
         */
        
        public function testGetEntry($entry_id) {
            
            $Entry = new Entry($entry_id);
            
            $this->assertEquals($entry_id, $Entry->id);
            $this->assertEquals("A test chronicle entry", $Entry->blurb);
            $this->assertEquals("A test chronicle entry descriptive text", $Entry->text);
            $this->assertEquals("18th February 1988", $Entry->Date->format("jS F Y"));
            
        }
        
        /**
         * @depends testAddEntry
         */
        
        public function testUpdateEntry($entry_id) {
            
            $Entry = new Entry($entry_id);
            
            $Entry->blurb = "blurb";
            $Entry->text = "text";
            $Entry->Date = new DateTime("1989-04-28");
            
            $updated_blurb = $Entry->blurb;
            $updated_text = $Entry->text;
            
            $Entry->commit(); 
            
            // Reload the operator
            $Entry = new Entry($entry_id);
            
            $this->assertEquals($updated_blurb, $Entry->blurb);
            $this->assertEquals($updated_text, $Entry->text);
            $this->assertEquals("28th April 1989", $Entry->Date->format("jS F Y"));
            
            return $Entry->id;
            
        }
        
        /**
         * @depends testCreateDemoUser
         */
        
        public function test_getEntriesForDate($User) {
            
            $Chronicle = new Chronicle;
            
            $Entry = new Entry;
            $NewUser = new User($User->id); 
            
            $Entry->setAuthor($NewUser);
            $Entry->Date = new DateTime("1970-02-18");
            $Entry->blurb = "A test chronicle entry";
            $Entry->text = "A test chronicle entry descriptive text";
            $Entry->EntryType = new EntryType(1);
            $Entry->commit(); 
            
            $id = $Entry->id;
            
            $Date = new DateTime("28th April 1989");
            
            foreach ($Chronicle->getEntriesForDate($Date) as $Entry) {
                $this->assertEquals($id, $Entry->id);
                $this->assertEquals($Date->format("Y-m-d"), $Entry->Date->format("Y-m-d"));
            }
            
            $now = new DateTime;
            $Entry = new Entry;
            $Entry->setAuthor($NewUser);
            $Entry->Date = $now; 
            $Entry->blurb = "A test entry for today";
            $Entry->text = "Blah don't care";
            $Entry->EntryType = new EntryType(1); 
            $Entry->commit(); 
            $entry_id = $Entry->id;
            
            foreach ($Chronicle->getEntriesForDate() as $Entry) {
                $this->assertEquals($entry_id, $Entry->id); 
                $this->assertFalse(!filter_var($Entry->id, FILTER_VALIDATE_INT)); 
                $this->assertEquals($now->format("Y-m-d"), $Entry->Date->Format("Y-m-d"));
            }
            
        }
        
        /**
         * @depends testAddEntry
         */
        
        public function test_newDecade() {
            
            $Decade = new Decade(1988);
            
            $this->assertInstanceOf("Railpage\\Chronicle\\Decade", $Decade);
            
            $this->assertEquals(1980, $Decade->decade);
            
            foreach ($Decade->yieldEntries() as $Entry) {
                $this->assertEquals(true, filter_var($Entry->id, FILTER_VALIDATE_INT)); 
                $this->assertEquals($Decade->decade, floor($Entry->Date->format("Y") / 10) * 10);
            }
            
        }
        
        /**
         * @depends testUpdateEntry
         */
        
        public function test_newYear($entry_id) {
            
            $Entry = new Entry($entry_id);
            
            $Year = new Year($Entry->Date->format("Y"));
            $Decade = $Year->getDecade();
            
            $this->assertInstanceOf("Railpage\\Chronicle\\Year", $Year);
            
            $this->assertEquals($Entry->Date->format("Y"), $Year->year);
            $this->assertEquals(1980, $Decade->decade);
            
            foreach ($Decade->yieldEntries() as $Entry) {
                $this->assertEquals(true, filter_var($Entry->id, FILTER_VALIDATE_INT)); 
                $this->assertEquals($Decade->decade, floor($Entry->Date->format("Y") / 10) * 10);
            }
            
            foreach ($Year->yieldEntries() as $Entry) {
                $this->assertEquals($entry_id, $Entry->id);
                $this->assertFalse(is_null(filter_var($Entry->blurb, FILTER_SANITIZE_STRING))); 
            }
            
        }
        
        /**
         * @depends testAddEntry 
         */
        
        public function test_getLatestAdditions() {
            
            $Chronicle = new Chronicle;
            
            foreach ($Chronicle->getLatestAdditions() as $Entry) {
                $this->assertFalse(is_null(filter_var($Entry->blurb, FILTER_SANITIZE_STRING))); 
                $this->assertFalse(!filter_var($Entry->id, FILTER_VALIDATE_INT)); 
            }
            
        }
    }
    