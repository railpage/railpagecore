<?php
	
	use Railpage\ContentUtility;
	
	class ContentUtilityTest extends PHPUnit_Framework_TestCase {
		
		const TEXT_FROM = "A really, really long block of text with a heap of letters and things!";
		const TEXT_TO = "a-really-really-long-block-of";
		
		public function test_generateUrlSlug() {
			
			$this->assertEquals(self::TEXT_TO, ContentUtility::generateUrlSlug(self::TEXT_FROM, 30)); 
			
		}
		
		public function test_relativeTime() {
			
			$this->assertEquals("4 seconds ago", ContentUtility::relativeTime(new DateTime("4 seconds ago"), new DateTime)); 
			
			$this->assertEquals("one minute ago", ContentUtility::relativeTime(strtotime("1 minutes ago"))); 
			$this->assertEquals("4 minutes ago", ContentUtility::relativeTime(strtotime("4 minutes ago"))); 
			
			$this->assertEquals("4 hours ago", ContentUtility::relativeTime(strtotime("4 hours ago"))); 
			
			$this->assertEquals("yesterday", ContentUtility::relativeTime(strtotime("24 hours ago")));  
			$this->assertEquals("2 days ago", ContentUtility::relativeTime(strtotime("2 days ago"))); 
			
			$this->assertEquals("one week ago", ContentUtility::relativeTime(strtotime("7 days ago"))); 
			$this->assertEquals("2 weeks ago", ContentUtility::relativeTime(strtotime("14 days ago"))); 
			
			$this->assertEquals("last month", ContentUtility::relativeTime(strtotime("1 month ago")));
			$this->assertEquals("2 months ago", ContentUtility::relativeTime(strtotime("2 months ago")));  
			
			$this->assertEquals("last year", ContentUtility::relativeTime(strtotime("12 months ago"))); 
			$this->assertEquals("10 years ago", ContentUtility::relativeTime(strtotime("10 years ago"))); 
			
			
			$this->assertEquals("3 years ago", ContentUtility::relativeTime(strtotime("5 years ago"), new DateTime("2 years ago"))); 
			
		}
	}