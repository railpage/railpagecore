<?php
	
	use Railpage\Locos\WheelArrangement;
	
	class WheelArrangementTest extends PHPUnit_Framework_TestCase {
		
		public function test_add() {
			
			$Arrangement = new WheelArrangement;
			$Arrangement->name = "Pacific";
			$Arrangement->arrangement = "4-6-2";
			$Arrangement->commit(); 
			
			$this->assertFalse(!filter_var($Arrangement->id, FILTER_VALIDATE_INT));
			
			$NewArrangement = new WheelArrangement($Arrangement->id); 
			
			$this->assertEquals($NewArrangement->id, $Arrangement->id); 
			
			$NewArrangement = new WheelArrangement($Arrangement->slug); 
			
			$this->assertEquals($NewArrangement->id, $Arrangement->id); 
			
			$NewArrangement = new WheelArrangement("adsfasdf");
			
			return $Arrangement;
			
		}
		
		/**
		 * @depends test_add
		 */
		
		public function test_update($Arrangement) {
			
			$Arrangement->name = "Pacific1";
			$Arrangement->commit(); 
			
			$Arrangement = new WheelArrangement($Arrangement->id); 
			
			$this->assertEquals("Pacific1", $Arrangement->name); 
			
		}
		
		/**
		 * @depends test_add
		 */
		
		public function test_load($Arrangement) {
			
			$Database = $Arrangement->getDatabaseConnection(); 
			
			$data = [ "slug" => "" ];
			$where = [ "id = ?" => $Arrangement->id ];
			$Database->update("wheel_arrangements", $data, $where); 
			
			$Arrangement = new WheelArrangement($Arrangement->id); 
			
		}
		
		/**
		 * @depends test_add
		 */
		
		public function test_duplicate($Arrangement) {
			
			$NewArrangement = new WheelArrangement; 
			$NewArrangement->name = $Arrangement->name;
			$NewArrangement->arrangement = $Arrangement->arrangement;
			$NewArrangement->commit(); 
			
		}
		
		public function test_break_arrangement() {
			
			$this->setExpectedException("Exception", "Cannot validate changes to this wheel arrangement: arrangement cannot be empty");
			
			$Arrangement = new WheelArrangement;
			$Arrangement->commit(); 
			
		}
		
	}