<?php
    
    use Railpage\Users\Users;
    use Railpage\Users\Admin;
    use Railpage\Users\Base;
    use Railpage\Users\User;
    use Railpage\Warnings\Warning;
    use Railpage\Forums\Forums;
    use Railpage\Forums\Post;
    use Railpage\Forums\Thread;
    use Railpage\Forums\Forum;
    use Railpage\Forums\Category;
    use Railpage\AppCore;
    use Railpage\Registry;
    
    
    class UserAdminTest extends PHPUnit_Framework_TestCase {
    
        public function test_pending() {
            
            $this->assertEquals(0, count((new Admin)->pending())); 
            
        }
    }