<?php
	
	use Railpage\Images\Images;
	use Railpage\Images\Image;
	
	class ImageTest extends PHPUnit_Framework_TestCase {
		
		public function testFetchFlickr() {
			$Image = (new Images)->getImageFromUrl("https://www.flickr.com/photos/doctorjbeam/17309304565/", Images::OPT_REFRESH);
			
			$this->assertEquals("Escale", $Image->title);
			$this->assertEquals("flickr", $Image->provider);
			$this->assertFalse(!filter_var($Image->id, FILTER_VALIDATE_INT)); 
		}
		
		public function testFetchSmugMug() {
			$Image = (new Images)->getImageFromUrl("http://sjbphotography.smugmug.com/Railways/The-Adventures/Epic-Adventures/The-International-Adventures/Steel-Steam-Stars-IV/i-7kHVHtX/A", Images::OPT_REFRESH);
			
			$this->assertEquals("SmugMug", $Image->provider);
			$this->assertEquals("7kHVHtX", $Image->photo_id); 
			$this->assertTrue(count($Image->sizes) > 1);
			$this->assertFalse(!filter_var($Image->id, FILTER_VALIDATE_INT)); 
		}
	}
	