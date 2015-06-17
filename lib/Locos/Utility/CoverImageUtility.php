<?php
	/**
	 * Locomotive / loco class cover photo utility
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos\Utility;
	
	use Railpage\Locos\LocoClass;
	use Railpage\Locos\Locomotive; 
	use Railpage\Asset;
	use Railpage\AppCore;
	use Exception;
	use Railpage\Debug;
	
	
	class CoverImageUtility {
		
		/**
		 * Check if the given object has a valid cover photo
		 * @since Version 3.9.1
		 * @param object $Object
		 * @return boolean
		 */
		
		public static function hasCoverImage($Object) {
			
			/**
			 * Image stored in meta data
			 */
			
			if (isset($Object->meta['coverimage']) && isset($Object->meta['coverimage']['id']) && !empty($Object->meta['coverimage']['id'])) {
				return true;
			}
			
			/**
			 * Asset
			 */
			
			if ($Object->Asset instanceof Asset) {
				return true;
			}
			
			/**
			 * Ordinary Flickr image
			 */
			
			if (isset($Object->photo_id) && filter_var($Object->photo_id, FILTER_VALIDATE_INT) && $Object->photo_id > 0) {
				return true;
			}
			
			if (isset($Object->flickr_image_id) && filter_var($Object->flickr_image_id, FILTER_VALIDATE_INT) && $Object->flickr_image_id > 0) {
				return true;
			}
			
			/**
			 * No cover image!
			 */
			
			return false;
			
		}
		
	}