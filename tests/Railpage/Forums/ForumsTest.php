<?php
    
    use Railpage\Forums\Forums;
    use Railpage\Forums\Forum;
    use Railpage\Forums\Category;
    use Railpage\Forums\Index;
    use Railpage\Forums\Permissions;
    use Railpage\Forums\Post;
    use Railpage\Forums\Thread;
    use Railpage\Forums\Stats;
    use Railpage\Forums\ForumsFactory;
    use Railpage\Forums\Utility\ForumsUtility;
    use Railpage\Users\User;
    
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
        
        public function testBreakCategory_Load() {
            $this->setExpectedException('InvalidArgumentException', "An invalid category ID was provided");
            $Category = new Category;
            $Category->load();
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
            
            $Forum->refreshForumStats(); 
            $Forum->refresh(); 
            
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
        
        public function testCreateUser() {
            
            $User = new User;
            $User->username = __FUNCTION__; 
            $User->contact_email = sprintf("phpunit+%s@railpage.com.au", $User->username); 
            $User->setPassword("sdfadfa7986asfsdf"); 
            $User->commit(); 
            
            return $User;
            
        }
        
        /**
         * @depends testCreateUser
         * @depends testAddCategory
         * @depends testAddForum
         */
        
        public function testgetForums($User, $cat_id, $forum_id) {
            
            $Category = new Category($cat_id);
            $Category->setUser($User); 
            
            //$Category->getForums(); 
        }
        
        /**
         * @depends testAddForum
         */
        
        public function testAddSubForum($forum_id) {
            
            $Forum = ForumsFactory::CreateForum($forum_id); 
            
            $Sub = new Forum; 
            $Sub->Parent = $Forum;
            $Sub->setCategory($Forum->category);
            $Sub->name = "Sub forum zomg";
            $Sub->commit(); 
            
            $Sub = new Forum($Sub->id); 
            
        }
        
        public function testBreakForum_load() {
            $this->setExpectedException("Exception", "No valid forum ID or shortname was provided"); 
            $Forum = new Forum;
            $Forum->load(); 
        }
        
        /**
         * @depends testAddCategory
         */
        
        public function testBreakForum_validate_name($cat_id) {
            $Category = new Category($cat_id); 
            
            $this->setExpectedException("Exception", "No forum name has been set"); 
            $Forum = new Forum;
            $Forum->category = $Category;
            $Forum->commit(); 
        }
        
        /**
         * @depends testAddForum
         * @depends testCreateUser
         */
        
        public function testAddThread($forum_id, $User) {
            
            $Forum = ForumsFactory::CreateForum($forum_id); 
            
            $Thread = new Thread;
            $Thread->setAuthor($User)->setForum($Forum); 
            $Thread->title = "Test thread";
            $Thread->commit(); 
            
            $Post = new Post; 
            $Post->setAuthor($User)->setThread($Thread); 
            $Post->text = "asdfasffasasfa87s9fsas989sfa9ffds";
            $Post->commit(); 
            
            $NewThread = ForumsFactory::CreateThread($Thread->id); 
            $NewPost = ForumsFactory::CreatePost($Post->id); 
            $Index = ForumsFactory::CreateIndex(); 
            
            ForumsUtility::updateUserThreadView($Thread, $User); 
            ForumsUtility::updateUserThreadView($Thread); 
            ForumsUtility::getForumNotifications($User); 
            
        }
        
    }
