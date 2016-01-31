<?php
    use Railpage\Locos\Manufacturer;
    
    class ManufacturerTest extends PHPUnit_Framework_TestCase {
        
        public function testAdd() {
            $Manufacturer = new Manufacturer; 
            
            $this->assertInstanceOf("Railpage\\Locos\\Manufacturer", $Manufacturer);
            
            $Manufacturer->name = "Test Manufacturer";
            $Manufacturer->desc = "Test description";
            $Manufacturer->commit(); 
            
            $NewMan = new Manufacturer($Manufacturer->slug);
            
            return $Manufacturer->id;
        }
        
        /**
         * @depends testAdd
         */
        
        public function testGet($id) {
            $Manufacturer = new Manufacturer($id); 
            
            $this->assertEquals($id, $Manufacturer->id);
            $this->assertEquals("Test Manufacturer", $Manufacturer->name);
            $this->assertEquals("Test Manufacturer", strval($Manufacturer));
            $this->assertEquals("Test description", $Manufacturer->desc);
        }
        
        /**
         * @depends testAdd
         */
        
        public function testUpdate($id) {
            $Manufacturer = new Manufacturer($id);
            
            $Manufacturer->name = "Test Manufacturer Updated";
            $Manufacturer->desc = "Test description updated";
            
            $updated_name = $Manufacturer->name;
            $updated_desc = $Manufacturer->desc;
            
            $Manufacturer->commit(); 
            
            // Reload the operator
            $Manufacturer = new Manufacturer($id);
            
            $this->assertEquals($updated_name, $Manufacturer->name);
            $this->assertEquals($updated_desc, $Manufacturer->desc);
            
            return $Manufacturer;
            
        }
        
        public function test_break_validate() {
            
            $this->setExpectedException("Exception", "Cannot validate changes to this locomotive manufacturer: manufacturer name cannot be empty");
            
            $Manufacturer = new Manufacturer; 
            $Manufacturer->commit(); 
            
        }
        
        /**
         * @depends testUpdate
         */
        
        public function testFetchNoSlug($Manufacturer) {
            
            $NewMan = new Manufacturer;
            $NewMan->name = $Manufacturer->name;
            $NewMan->desc = "asdfsdf";
            $NewMan->commit(); 
            
            $Database = $Manufacturer->getDatabaseConnection(); 
            
            $data = [ "slug" => "" ];
            $where = [ "manufacturer_id = ?" => $Manufacturer->id ];
            $Database->update("loco_manufacturer", $data, $where); 
            
            $Manufacturer = new Manufacturer($Manufacturer->id); 
            
            $data = [ "slug" => "" ];
            $where = [ "manufacturer_id = ?" => $NewMan->id ];
            $Database->update("loco_manufacturer", $data, $where); 
            
            $NewMan = new Manufacturer($NewMan->id); 
            
        }
    }
    