<?php
    
    use Railpage\Forums\Forums;
    use Railpage\Forums\Forum;
    use Railpage\Forums\Category;
    use Railpage\Forums\Index;
    use Railpage\Forums\Permissions;
    use Railpage\Forums\Post;
    use Railpage\Forums\Thread;
    use Railpage\Forums\Stats;
    
    class ForumsTest extends PHPUnit_Framework_TestCase {
        
        const CAT_TITLE = "Test category";
        const CAT_TITLE_UPDATE = "Test category 12132faff";
        const FORUM_NAME = "Test forum";
        const FORUM_NAME_UPDATE = "Test forum 12331313";
        
        public function testAddCategory() {
            
            $Category = new Category;
            $Category->title = self::CAT_TITLE;
            $Category->commit(); 
            
            $this->assertEquals(self::CAT_TITLE, $Category->title); 
            $this->assertEquals(0, $Category->order); 
            $this->assertFalse(!filter_var($Category->id, FILTER_VALIDATE_INT));
            
            $category_id = $Category->id;
            
            // reload it
            $Category = new Category($category_id);
            $this->assertEquals(self::CAT_TITLE, $Category->title); 
            
            return $category_id;
        }
        
        /**
         * @depends testAddCategory
         */
        
        public function testUpdateCategory($category_id) {
            $Category = new Category($category_id); 
            $Category->title = self::CAT_TITLE_UPDATE;
            $Category->commit(); 
            
            $Category = new Category($category_id); 
            $this->assertEquals(self::CAT_TITLE_UPDATE, $Category->title); 
        }
        
        public function testBreakCategory() {
            $this->setExpectedException('Exception');
            $Broken = new Category;
            $Broken->commit(); 
        }
        
        /**
         * @depends testAddCategory
         */
        
        public function testAddForum($category_id) {
            
            $Category = new Category($category_id);
            
            $Forum = new Forum;
            $Forum->setCategory($Category);
            
            $this->assertEquals($category_id, $Forum->catid);
            $this->assertEquals($category_id, $Forum->category->id);
            
            $Forum->name = self::FORUM_NAME;
            $Forum->commit();
            
            $this->assertFalse(!filter_var($Forum->id, FILTER_VALIDATE_INT));
            
            $forum_id = $Forum->id;
            
            // reload it
            $Forum = new Forum($forum_id); 
            $this->assertEquals(self::FORUM_NAME, $Forum->name); 
            $this->assertEquals($forum_id, $Forum->id);
            
            return $forum_id;   
        }
        
        /**
         * @depends testAddForum
         */
         
        public function testUpdateForum($forum_id) {
            $Forum = new Forum($forum_id);
            $Forum->name = self::FORUM_NAME_UPDATE;
            $Forum->commit(); 
            
            $Forum = new Forum($forum_id); 
             
            $this->assertEquals($forum_id, $Forum->id);
            $this->assertEquals(self::FORUM_NAME_UPDATE, $Forum->name); 
        }
        
        public function testBreakForum() {
            $this->setExpectedException('Exception');
            $Broken = new Forum;
            $Broken->commit(); 
        }
        
        
    }
