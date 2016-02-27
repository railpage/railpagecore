<?php

/**
 * Railcam photo provider interface
 * @since Version 3.9
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Railcams;

/**
 * Provider
 */

interface ProviderInterface {
    
    /**
     * Get the photo from the provider
     * @since Version 3.9
     * @param int $id The ID of the photo from the provider
     * @return array
     */
    
    public function getPhoto($id);
    
    /**
     * Save the changes to this photo
     * @since Version 3.9
     * @return self
     * @param \Railpage\Railcams\Photo $photoObject
     */
    
    public function setPhoto(Photo $photoObject);
    
    /**
     * Get a list of photos
     * @since Version 3.9
     * @param int $page
     * @param int $itemsPerPage
     * @return array
     */
    
    public function getPhotos($page, $itemsPerPage);
    
    /**
     * Return the name of this provider
     * @since Version 3.9
     * @return string
     */
    
    public function getProviderName(); 
    
    /**
     * Return the context of the supplied photo
     * @since Version 3.9
     * @return array
     * @param \Railpage\Railcams\Photo $photoObject
     */
    
    public function getPhotoContext(Photo $photoObject);
    
    /**
     * Delete this photo
     * @since Version 3.9.1
     * @return boolean
     * @param \Railpage\Railcams\Photo $photoObject
     */
    
    public function deletePhoto(Photo $photoObject);
    
}
