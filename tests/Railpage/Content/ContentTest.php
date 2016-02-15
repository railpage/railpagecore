<?php

use Railpage\Content\Page;
use Railpage\Content\PageFromPermalink;
use Railpage\Content\Content;

class ContentTest extends PHPUnit_Framework_Testcase {
    
    function testNewPage() {
        
        $Page = new Page; 
        $Page->title = "Test page";
        $Page->subtitle = "Subtitle";
        $Page->active = 1;
        $Page->header = "tasdfasdff";
        $Page->body = "asdfasdfa876safasdf";
        $Page->footer = "sadfadf11111";
        $Page->commit(); 
        
        $Page->subtitle = "zzzzzzzzzzzz";
        $Page->commit(); 
        
        $NewPage = new Page($Page->permalink); 
        
        $NewPage = new Page($Page->id); 
        
        $Page = new Page; 
        $Page->title = "Test page";
        $Page->subtitle = "Subtitle";
        $Page->active = 1;
        $Page->header = "tasdfasdff";
        $Page->body = "asdfasdfa876safasdf";
        $Page->footer = "sadfadf11111";
        $Page->commit(); 
        
    }
    
    function test_break_title() {
        
        $this->setExpectedException("Exception", "Title cannot be empty"); 
        
        $Page = new Page;
        $Page->commit(); 
        
    }
    
}