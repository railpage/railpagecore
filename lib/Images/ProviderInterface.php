<?php
    /**
     * Image provider interface
     * @since Version 3.9.1
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Images;
    
    /**
     * Provider
     */
    
    interface ProviderInterface {
        
        /**
         * Get the image from the provider
         * @since Version 3.9.1
         * @param int $id The ID of the image from the provider
         * @return array
         */
        
        public function getImage($id);
        
        /**
         * Save the changes to this image
         * @since Version 3.9.1
         * @return self
         * @param \Railpage\Images\Image $Image
         */
        
        public function setImage(Image $Image);
        
        /**
         * Get a list of images
         * @since Version 3.9.1
         * @param int $page
         * @param \Railpage\Images\Image $Image
         * @return array
         */
        
        public function getImages($page, $items_per_page);
        
        /**
         * Return the name of this provider
         * @since Version 3.9.1
         * @return string
         */
        
        public function getProviderName(); 
        
        /**
         * Return the context of the supplied image
         * @since Version 3.9.1
         * @return array
         * @param \Railpage\Images\Image $Image
         */
        
        public function getImageContext(Image $Image);
        
        /**
         * Delete this image
         * @since Version 3.9.1
         * @return boolean
         * @param \Railpage\Images\Image $Image
         */
        
        public function deleteImage(Image $Image);
        
    }
    