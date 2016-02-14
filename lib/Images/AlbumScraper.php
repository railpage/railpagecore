<?php

/**
 * Scrape the contents of an album 
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Images;

use Railpage\AppCore;
use Railpage\Url;
use Railpage\Debug;
use Railpage\Images\Utility\Updater;
use Railpage\Images\Utility\ImageUtility;
use DateTime;
use Exception;
use InvalidArgumentException;

class AlbumScraper extends AppCore {
    
    /**
     * Get monitored albums
     * @since Version 3.10.0
     * @return array
     * @param $provider null|string
     */
    
    public function getMonitoredAlbums($provider = null) {
        
        $query = "SELECT * FROM image_scrape_album";
        
        if ($provider != null) {
            $query .= " WHERE provider = ?";
            $result = $this->db->fetchAll($query, $provider); 
        }
        
        if ($provider == null) {
            $result = $this->db->fetchAll($query); 
        }
        
        $result = array_map(function ($row) {
            $row['meta'] = json_decode($row['meta'], true); 
            
            return $row;
        }, $result);
        
        return $result;
        
    }
    
    /**
     * Add an album
     * @since Version 3.10.0
     * @param string $provider
     * @param int|string $albumId
     * @return \Railpage\Images\AlbumScraper
     */
    
    public function addAlbum($provider, $albumId) {
        
        $albums = $this->getMonitoredAlbums($provider); 
        $albumIds = array_map(function ($row) {
            return $row['album_id'];
        }, $albums); 
        
        if (in_array($albumId, $albumIds)) {
            return $this;
        }
        
        $data = [
            "provider" => $provider,
            "album_id" => $albumId
        ];
        
        $this->db->insert("image_scrape_album", $data); 
        
        return $this;
        
    }
    
    /**
     * Delete an album
     * @since Version 3.10.0
     * @param int $id
     * @return \Railpage\Images\AlbumScraper
     */
    
    public function deleteAlbum($id) {
        
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            throw new InvalidArgumentException("An invalid ID was supplied"); 
        }
        
        $this->db->delete("image_scrape_album", [ "id = ?" => $id ]); 
        
        return $this;
        
    }
    
    /**
     * Scrape our albums
     * @since Version 3.10.0
     * @return \Railpage\Images\AlbumScraper
     */
    
    public function scrape() {
        
        $albums = $this->getMonitoredAlbums(); 
        
        foreach ($albums as $album) {
            Updater::ScrapeAlbum($album); 
        }
        
        return $this;
        
    }
    
    
}