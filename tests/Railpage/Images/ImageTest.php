<?php
    
    use Railpage\Images\Images;
    use Railpage\Images\Image;
    use Railpage\Images\Screener;
    use Railpage\Images\Exif;
    use Railpage\Images\ImageFactory;
    use Railpage\Images\Collection;
    use Railpage\Images\Competition;
    use Railpage\Images\Competitions;
    use Railpage\Images\Utility\CollectionUtility;
    use Railpage\Users\User;
    
    class ImageTest extends PHPUnit_Framework_TestCase {
        
        public function test_createUser() {
            
            $User = new User;
            $User->username = "ImageTester";
            $User->contact_email = "michael+phpunit+imagetester@railpage.com.au";
            $User->setPassword("asdfadfa1111zz");
            $User->commit(); 
            $User->setUserAccountStatus(User::STATUS_ACTIVE);
            
            return $User;
            
        }
        
        public function test_fetchFlickr() {
            $Image = (new Images)->getImageFromUrl("https://www.flickr.com/photos/raichase/18184061975/", Images::OPT_REFRESH);
            
            $this->assertEquals("CSR001 Passing Crystal Brook", $Image->title);
            $this->assertEquals("flickr", $Image->provider);
            $this->assertFalse(!filter_var($Image->id, FILTER_VALIDATE_INT)); 
            
            $New = ImageFactory::CreateImage($Image->id);
            
            return $Image;
        }
        
        public function test_fetchSmugMug() {
            $Image = (new Images)->getImageFromUrl("http://sjbphotography.smugmug.com/Railways/The-Adventures/Epic-Adventures/The-International-Adventures/Steel-Steam-Stars-IV/i-7kHVHtX/A", Images::OPT_REFRESH);
            
            $this->assertEquals("SmugMug", $Image->provider);
            $this->assertEquals("7kHVHtX", $Image->photo_id); 
            $this->assertTrue(count($Image->sizes) > 1);
            $this->assertFalse(!filter_var($Image->id, FILTER_VALIDATE_INT)); 
            
            return $Image;
        }
        
        /**
         * @depends test_createUser
         */
        
        public function test_createCollection($User) {
            
            $Collection = new Collection;
            $Collection->name = "Test collection";
            $Collection->description = "lasdfasdfsafadf";
            $Collection->setAuthor($User)->commit(); 
            
            $New = new Collection;
            $New->name = "Test collection";
            $New->description = "lasdfasdfsafadf";
            $New->setAuthor($User)->commit(); 
            
            $this->assertFalse(!filter_var($Collection->id, FILTER_VALIDATE_INT)); 
            
            $New = new Collection($Collection->id); 
            $New = new Collection($Collection->slug); 
            
            $Collection->getArray(); 
            
            CollectionUtility::getCollections($User);
            
            return $Collection;
            
        }
        
        /**
         * @depends test_fetchFlickr
         * @depends test_createUser
         */
        
        public function test_screenImage($Image, $User) {
            
            $Screener = new Screener;
            
            $Screener->setUser($User)->getUnreviewedImages(); 
            
            $Screener->skipImage($Image, $User); 
            $Screener->getSkippedImages(); 
            
            $Screener->reviewImage($Image, $User, false, false); 
            $Screener->getRejectedImages(); 
            $Screener->setUser($User)->undo(); 
            
            $Screener->reviewImage($Image, $User, true, false); 
            $Screener->reviewImage($Image, $User, false, true); 
            
            $Screener->getImageScreener($Image);
            $Screener->getScreeners(); 
            
        }
        
        /**
         * @depends test_fetchFlickr
         * @depends test_createCollection
         */
        
        public function test_addToCollection($Image, $Collection) {
            
            $Collection->addImage($Image); 
            $this->assertTrue($Collection->containsImage($Image));
            $Collection->addImage($Image);
            
            $Collection->removeImage($Image); 
            $this->assertFalse($Collection->containsImage($Image)); 
            
            CollectionUtility::getCollectionsFeaturingImage($Image); 
            
        }
        
        /**
         * @depends test_fetchFlickr
         */
        
        public function test_exif($Image) {
            
            $Exif = new Exif;
            $exifdata = $Exif->getImageExif($Image);
            $exifdata = $Exif->formatExif($exifdata);
            
            $Exif->getImageExif($Image);
            
            
        }
        
        
    }
    