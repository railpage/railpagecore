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
			
			$Category->name = "Blah";
			$Category->commit(); 
			
			$Category->name = self::CAT_NAME;
			$Category->commit(); 
			
			return $Category;
			
		}
		
		/**
		 * @depends testAddCategory
		 */
		
		public function testAddItem($Category) {
			
			$Item = new Item;
			$Item->title = self::ITEM_TITLE;
			$Item->text = self::ITEM_TEXT;
			$Item->category = $Category;
			$Item->commit(); 
			
			$this->assertFalse(!filter_var($Item->id, FILTER_VALIDATE_INT)); 
			
			$NewItem = new Item($Item->url_slug); 
			$this->assertEquals($NewItem->id, $Item->id); 
			$this->assertEquals($NewItem->title, $Item->title); 
			
			$NewItem = new Item($Item->id); 
			$this->assertEquals($NewItem->id, $Item->id); 
			$this->assertEquals($NewItem->title, $Item->title); 
			
			return $Item;
			
		}
		
		public function test_exception_title() {
			
			$this->setExpectedException("Exception", "Cannot validate help item - title is empty");
			
			$Item = new Item;
			$Item->commit();
			
		}
		
		public function test_exception_text() {
			
			$this->setExpectedException("Exception", "Cannot validate help item - text is empty");
			
			$Item = new Item;
			$Item->title = "adfsdf";
			$Item->commit();
			
		}
		
		public function test_exception_cat() {
			
			$this->setExpectedException("Exception", "No valid category has been set");
			
			$Item = new Item;
			$Item->title = "adfsdf";
			$Item->text = "asdfasfsafsfaf";
			$Item->commit();
			
		}
		
		public function test_exception_cattitle() {
			
			$this->setExpectedException("Exception", "Cannot validate category - name cannot be empty");
			
			$Category = new Category;
			$Category->commit();
			
		}
		
		/**
		 * @depends testAddItem
		 */
		
		public function test_getItems($Item) {
			
			$Category = new Category($Item->category->id); 
			$items = $Category->getItems();
			
			$this->assertTrue(count($items) > 0); 
			
		}
		
		/**
		 * @depends testAddItem
		 */
		
		public function testCompareItem($Item) {
			
			$this->assertEquals(self::ITEM_TITLE, $Item->title);
			$this->assertEquals(self::ITEM_TEXT, $Item->text); 
			$this->assertEquals(self::CAT_NAME, $Item->category->name);	
			
		}
		
		/**
		 * @depends testAddItem
		 */
		
		public function testUpdateItem($Item) {
			
			$Item->text = "asdfasdfadfaf";
			$Item->commit(); 
			
			$Item = new Item($Item->id); 
			$this->assertEquals("asdfasdfadfaf", $Item->text);
			
			$db = $Item->getDatabaseConnection(); 
			$data = array("url_slug" => 1); 
			$where = array("id = ?" => $Item->id); 
			$db->update("nuke_faqAnswer", $data, $where); 
			
			$Item->category->getItems(); 
			$Item->commit();
			
		}
		
		/** 
		 * @depends testAddCategory
		 */
		
		public function test_getCategories($Category) {
			
			$Help = new Help;
			$cats = $Help->getCategories(); 
			$this->assertTrue(count($cats) > 0);
			
			$id = $Help->getCategoryIDFromSlug($Category->url_slug); 
			
			$this->assertFalse(!filter_var($id, FILTER_VALIDATE_INT)); 
			
			$this->setExpectedException("Exception", "Cannot fetch category ID - no URL slug given");
			$Help->getCategoryIDFromSlug();
			
		}
		
		public function test_deleteItem() {
			
			$Help = new Help;
			
			$Category = $this->testAddCategory();
			$Item = $this->testAddItem($Category); 
			
			$this->assertTrue($Help->deleteItem($Item->id)); 
			
			$this->setExpectedException("Exception", "Cannot delete help item - no ID given"); 
			$Help->deleteItem(); 
			
		}
		
		public function test_deleteCategory() {
			
			$Help = new Help;
			
			$Category = $this->testAddCategory();
			
			$this->assertTrue($Help->deleteCategory($Category->id)); 
			
			$this->setExpectedException("Exception", "Cannot delete category - no ID given"); 
			$Help->deleteCategory(); 
			
		}
	}
	