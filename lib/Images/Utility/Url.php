<?php
	/**
	 * Image URL builder
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images\Utility;
	
	use Railpage\Url as RealUrl;
	
	class Url {
		
		/**
		 * Create URL object from an image ID
		 * @since Version 3.9.1
		 * @param int $image_id
		 * @return \Railpage\Url
		 */
		
		public static function CreateFromImageID($image_id) {
			
			$Url = new RealUrl(sprintf("/photos/%d", $image_id));
			$Url->favourite = sprintf("%s?mode=image.favourite", $Url->url);
            
            if ($Url->canonical == "http://" . $Url->url) {
                $Url->canonical = sprintf("http://railpage.com.au%s", $Url->url);
            }
			
			return $Url;
			
		}
	}