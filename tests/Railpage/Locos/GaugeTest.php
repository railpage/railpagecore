<?php
    
    use Railpage\Locos\Gauge;
    
    class GaugeTest extends PHPUnit_Framework_TestCase {
        
        public function test_addGauge() {
            
            $Gauge = new Gauge;
            $Gauge->name = "test gauge"; 
            $Gauge->width_metric = "21313";
            $Gauge->width_imperial = "2 ft";
            $Gauge->commit(); 
            
            $NewGauge = new Gauge; 
            $NewGauge->name = "test gauge";
            $NewGauge->width_metric = "21313";
            $NewGauge->commit(); 
            
            $this->assertNotEquals($Gauge->slug, $NewGauge->slug); 
            $this->assertNotEquals($Gauge->id, $NewGauge->id);
            
            return $Gauge; 
            
        }
        
        /**
         * @depends test_addGauge
         */
        
        public function test_loadGauge($Gauge) {
            
            $NewGauge = new Gauge($Gauge->id); 
            $this->assertEquals($NewGauge->id, $Gauge->id); 
            
            $NewGauge->Memcached->delete($NewGauge->mckey); 
            
            $NewGauge = new Gauge($Gauge->slug); 
            $this->assertEquals($NewGauge->id, $Gauge->id); 
            
        }
        
        /**
         * @depends test_addGauge
         */
        
        public function test_updateGauge($Gauge) {
            
            $Gauge->title = "blah"; 
            $Gauge->commit(); 
            
        }
        
        public function test_break_name() {
            
            $this->setExpectedException("Exception", "Name cannot be empty"); 
            
            $Gauge = new Gauge; 
            $Gauge->commit(); 
            
        }
        
        public function test_break_width_metric() {
            
            $this->setExpectedException("Exception", "Metric gauge width cannot be empty"); 
            
            $Gauge = new Gauge;
            $Gauge->name = "Test";
            $Gauge->commit();
            
        }
        
        public function test_break_width_imperial() {
            
            $this->setExpectedException("Exception", "Could not create an imperial width for this gauge"); 
            
            $Gauge = new Gauge;
            $Gauge->name = "Test";
            $Gauge->width_metric = "sdfsadg";
            $Gauge->commit();
            
        }
        
        
    }