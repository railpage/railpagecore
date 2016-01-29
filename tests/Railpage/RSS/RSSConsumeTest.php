<?php
    
    use Railpage\RSS\Consume;
    
    class RSSConsumeTest extends PHPUnit_Framework_TestCase {
        
        const RSS_FEED = "http://puffingbillyworkshops.blogspot.com/feeds/posts/default";
        
        public function testScrape() {
            
            $Consume = new Consume;
            $Consume->addFeed(self::RSS_FEED)->scrape()->parse();
            
            foreach ($Consume->getFeeds() as $feed) {
                foreach ($feed['items'] as $item) {
                    $this->assertFalse(is_null(filter_var($item['summary'], FILTER_SANITIZE_STRING))); 
                    $this->assertFalse(is_null(filter_var($item['description'], FILTER_SANITIZE_STRING))); 
                    $this->assertFalse(is_null(filter_var($item['link'], FILTER_SANITIZE_STRING))); 
                    $this->assertFalse(is_null(filter_var($item['date'], FILTER_SANITIZE_STRING))); 
                    
                    break;
                }
                
                break;
            }
        }
    }