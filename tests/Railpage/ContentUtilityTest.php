<?php
	
	use Railpage\ContentUtility;
	
	class ContentUtilityTest extends PHPUnit_Framework_TestCase {
		
		const TEXT_FROM = "A really, really long block of text with a heap of letters and things!";
		const TEXT_TO = "a-really-really-long-block-of";
		
		public function test_generateUrlSlug() {
			
			$this->assertEquals(self::TEXT_TO, ContentUtility::generateUrlSlug(self::TEXT_FROM, 30)); 
			
		}
	}