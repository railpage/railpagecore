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
		 */
		
		public function setPhoto();
		
		/**
		 * Get a list of photos
		 * @since Version 3.9
		 * @param int $page
		 * @param int $items_per_page
		 * @return array
		 */
		
		public function getPhotos($page, $items_per_page);
		
		
	}
?>