<?php
	/**
	 * Image utility class
	 * @since Version 3.10.0
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	 
	namespace Railpage\Images\Utility;
	
	use Exception;
	use DateTime;
	use Railpage\AppCore;
	//use Railpage\Url;
	use Railpage\Debug;
	use Railpage\Images\Image;
	
	class ImageUtility {
		
		/**
		 * Generate the HTML5 picture srcset string
		 * @since Version 3.10.0
		 * @param \Railpage\Images\Image $Image
		 * @return string
		 */
		 
		static public function generateSrcSet(Image $Image) {
			
			$sources = array(); 
			
			foreach ($Image->sizes as $size) {
				$k = md5($size['source']); 
				
				if (isset($sources[$k])) {
					continue;
				}
				
				if ($size['width'] > 1600 && $size['height'] > 600) {
					continue;
				}
				
				$multiplier = intval($size['width'] > 1600) + 1;
				
				$sources[$k] = sprintf("%s %dw", $size['source'], $size['width']);
			}
			
			return array_values($sources);
			
		}
		
	}