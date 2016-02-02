<?php
    
    use Railpage\Pagination;
    
    class PaginationTest extends PHPUnit_Framework_TestCase {
        
        public function testPagination() {
            
            $Pagination = new Pagination("/page/%d", 10, 12353); 
            
            $Pagination->__toString(); 
            
        }
        
    }
    