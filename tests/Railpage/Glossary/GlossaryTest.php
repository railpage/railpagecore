<?php
    
    use Railpage\Glossary\Entry;
    use Railpage\Glossary\Type;
    use Railpage\Glossary\Glossary;
    use Railpage\Users\User;
    
    class GlossaryTest extends PHPUnit_Framework_TestCase {
        
        const TYPE = "Slang";
        const NAME = "Testing";
        const TEXT = "Evaluating a situation to see if the expected outcome matches the actual outcome";
        const EXAMPLE = "I'm testing Railpage's code by running it through PHPUnit";
        const AUTHOR = "Glossary tester";
        
        public function testAddUser() {
            
            $User = new User;
            $User->username = self::AUTHOR;
            $User->setPassword('zasdfasdfadfag');
            $User->contact_email = "phpunit+glossary@railpage.com.au";
            $User->commit(); 
            
            $this->assertFalse(!filter_var($User->id, FILTER_VALIDATE_INT));
            
            return $User;
            
        }
        
        /**
         * @depends testAddUser
         */
        
        public function testAddEntry($User) {
            
            $Type = new Type(self::TYPE);
            $Entry = new Entry;
            $Entry->name = self::NAME;
            $Entry->text = self::TEXT;
            $Entry->example = self::EXAMPLE;
            $Entry->Type = $Type;
            $Entry->setAuthor($User); 
            
            $Entry->commit(); 
            
            $Entry->name = "test"; 
            $Entry->commit(); 
            $Entry->name = self::NAME;
            $Entry->commit(); 
            
            $Entry = new Entry($Entry->id);
            
            $this->assertFalse(!filter_var($Entry->id, FILTER_VALIDATE_INT));
            
            $this->assertEquals($User->id, $Entry->Author->id);
            
            $Glossary = new Glossary;
            
            foreach ($Glossary->getPendingEntries() as $Entry) {
                // meh
            }
            
            $Entry->approve(); 
            
            foreach ($Glossary->getNewEntries() as $Entry) {
                
            }
            
            $Entry->slug = null;
            $Entry->commit(); 
            
            $Entry->getArray(); 
            
            return $Entry;
            
        }
        
        /**
         * @depends testAddEntry
         */
        
        public function testAllTheGlossary($Entry) {
            
            $Type = new Type; 
            
        }
        
        /**
         * @depends testAddEntry
         * @depends testAddUser
         */
        
        public function testCompareEntry($Entry, $User) {
            
            $NewEntry = new Entry($Entry->id); 
            
            $this->assertEquals($Entry->name, $NewEntry->name);
            
            $this->assertEquals(self::TYPE, $NewEntry->Type->name); 
            $this->assertEquals(self::NAME, $NewEntry->name);
            $this->assertEquals(self::TEXT, $NewEntry->text); 
            $this->assertEquals(self::EXAMPLE, $NewEntry->example); 
            $this->assertEquals($Entry->Author->id, $NewEntry->Author->id); 
            $this->assertEquals($Entry->Author->username, $NewEntry->Author->username); 
            
            $this->assertEquals($Entry->Author->id, $User->id); 
            $this->assertTrue($User->id > 0);
            
        }
        
        /**
         * @depends testAddEntry
         */
        
        public function testGetEntry() {
            
            $Type = new Type(self::TYPE); 
            
            foreach ($Type->getEntries() as $Entry) {
                $this->assertEquals(self::NAME, $Entry->name); 
            }
            
            $Entry = new Entry(9999999); 
            
        }
        
        public function test_loadTypes() {
            
            $types = [ "code", "acronym", "station", "slang", "general", "term" ];
            
            foreach ($types as $type) {
                
                $GlossaryType = new Type($type); 
                
            }
        }
        
        /**
         * @depends testAddUser
         */
        
        public function test_delete($User) {
            
            $Type = new Type(self::TYPE);
            $Entry = new Entry;
            $Entry->name = self::NAME;
            $Entry->text = self::TEXT;
            $Entry->example = self::EXAMPLE;
            $Entry->Type = $Type;
            $Entry->setAuthor($User); 
            
            $Entry->commit(); 
            
            $Entry->reject(); 
            
        }
        
        public function test_break_name() {
            
            $this->setExpectedException("Exception", "Entry name cannot be empty");
            
            $Entry = new Entry;
            $Entry->commit(); 
            
        }
        
        public function test_break_text() {
            
            $this->setExpectedException("Exception", "Entry text cannot be empty");
            
            $Entry = new Entry;
            $Entry->name = "asdf";
            $Entry->commit(); 
            
        }
        
        public function test_break_type() {
            
            $this->setExpectedException("Exception", "Entry type is invalid");
            
            $Entry = new Entry;
            $Entry->name = "asdf";
            $Entry->text = "asfdfafadfsaf";
            $Entry->commit(); 
            
        }
        
        public function test_break_user() {
            
            $this->setExpectedException("Exception", "No author given for glossary entry");
            
            $Entry = new Entry;
            $Entry->Type = new Type(self::TYPE);
            $Entry->example = NULL;
            $Entry->name = "asdf";
            $Entry->text = "asfdfafadfsaf";
            $Entry->commit(); 
            
        }
        
        /**
         * @depends testAddUser
         */
        
        public function test_break_longname($User) {
            
            $this->setExpectedException("Exception", sprintf("The title of this entry is too long: the maximum allowed is %d", Entry::SHORT_MAX_CHARS));
            
            $Entry = new Entry;
            $Entry->Type = new Type(self::TYPE);
            $Entry->example = NULL;
            $Entry->name = "asfdfafadfsafasfdfafadfsafasfdfafadfsafasfdfafadfsafasfdfafadfsafasfdfafadfsafasfdfafadfsafasfdfafadfsaf";
            $Entry->text = "asfdfafadfsaf";
            $Entry->Author = $User; 
            $Entry->commit(); 
            
        }
        
        /**
         * @depends testAddEntry
         */
        
        public function test_nukeDate($Entry) {
            
            $Database = $Entry->getDatabaseConnection(); 
            
            $data = [ "date" => "0000-00-00 00:00:00" ];
            $where = [ "id = ?" => $Entry->id ];
            $Database->update("glossary", $data, $where); 
            
            $user_id = $Database->fetchOne("SELECT author FROM glossary WHERE id = ?", $Entry->id);
            
            $this->assertTrue($user_id > 0); 
            $this->assertTrue($Entry->id > 0);
            $this->assertInstanceOf("\Railpage\Users\User", $Entry->Author);
            
            $this->assertFalse(!filter_var($Entry->Author->id, FILTER_VALIDATE_INT)); 
            $this->assertEquals($Entry->Author->id, $user_id); 
            
            
            $Entry = new Entry($Entry->id); 
            
        }
    }
    