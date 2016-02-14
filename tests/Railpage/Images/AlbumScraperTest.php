<?php

use Railpage\Images\AlbumScraper; 
use Railpage\Images\Provider\Flickr;

class AlbumScraperTest extends PHPUnit_Framework_TestCase {
    
    function testLoad() {
        
        $Flickr = new Flickr;
        
        $Scraper = new AlbumScraper; 
        
        $this->assertEquals(0, count($Scraper->getMonitoredAlbums())); 
        
        $Scraper->addAlbum("flickr", "72157663825349366"); 
        
        $albums = $Scraper->getMonitoredAlbums();
        
        $this->assertEquals(1, count($albums)); 
        
        $Scraper->scrape(); 
        $Scraper->deleteAlbum($albums[0]['id']); 
        
    }
    
}