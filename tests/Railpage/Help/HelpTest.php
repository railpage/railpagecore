<?php
	
	use Railpage\Help\Category;
	use Railpage\Help\Help;
	use Railpage\Help\Item;
	
	class HelpTest extends PHPUnit_Framework_TestCase {
		
		const ITEM_TITLE = "Test help item";
		const ITEM_TEXT = "blah blah 123123123 blah iouadfasdfksdf asf asdfaf asf 234 wf";
		const CAT_NAME = "Help test category";
		
		public function testAddCategory() {
			
			$Category = new Category;
			$Category->name = self::CAT_NAME;
			$Category->commit(); 
			
			$this->assertFalse(!filter_var($Category->id, FILTER_VALIDATE_INT)); 
			
		}
		
		public function testAddItem() {
			
			$Category = new Category(1); 
			$Item = new Item;
			$Item->title = self::ITEM_TITLE;
			$Item->text = self::ITEM_TEXT;
			$Item->category = $Category;
			$Item->commit(); 
			
			$this->assertFalse(!filter_var($Item->id, FILTER_VALIDATE_INT)); 
			
		}
		
		public function testCompareItem() {
			
			$Item = new Item(1); 
			
			$this->assertEquals(self::ITEM_TITLE, $Item->title);
			$this->assertEquals(self::ITEM_TEXT, $Item->text); 
			$this->assertEquals(self::CAT_NAME, $Item->category->name);	
			
		}
	}
	