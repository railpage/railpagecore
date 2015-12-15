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
    use Railpage\Images\ImageCache;
	
	class ImageUtility {
		
		/**
		 * Generate the HTML5 picture srcset string
		 * @since Version 3.10.0
		 * @param \Railpage\Images\Image $Image
		 * @return array
		 */
		 
		public static function generateSrcSet(Image $Image) {
			
			$sources = array(); 
			$widths = array(); 
			
			foreach ($Image->sizes as $size) {
				$k = md5($size['source']); 
				
				if (isset($sources[$k])) {
					continue;
				}
				
				if ($size['width'] > 1600 && $size['height'] > 600) {
					continue;
				}
				
				if (in_array($size['width'], $widths)) {
					continue;
				}
				
				$multiplier = intval($size['width'] > 1600) + 1;
				
				$sources[$k] = sprintf("%s %dw", $size['source'], $size['width']);
				$widths[] = $size['width'];
			}
			
			return array_values($sources);
			
		}
        
        /**
         * Get the SVG string for a gaussian blur of an image thumbnail, blown up to full size
         * Displayed while the full image loads in the background
         * @since Version 3.10.0
         * @param \Railpage\Images\Images $Image
         * @return string
         */
        
        public static function GetLoadingSVG(Image $Image) {
            
            $cachekey = sprintf("railpage:base64.image.svg=%d", $Image->id); 
            
            $Memcached = AppCore::GetMemcached(); 
            
            if (!$base64 = $Memcached->Fetch($cachekey)) {
                $thumbnail = $Image->sizes['thumb']['source']; 
                $cached_url = ImageCache::cache($thumbnail);
                
                $base64 = base64_encode(file_get_contents($cached_url)); 
                
                $Memcached->save($cachekey, $base64); 
            }
            
            $string = '
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="1024" height="1024" viewBox="0 0 1024 1024">
  <filter id="blur" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
    <feGaussianBlur stdDeviation="20 20" edgeMode="duplicate" />
    <feComponentTransfer>
      <feFuncA type="discrete" tableValues="1 1" />
    </feComponentTransfer>
  </filter>
  <image filter="url(#blur)" xlink:href="data:image/jpeg;base64,' . $base64 . '" x="0" y="0" height="100%25" width="100%25"/>
</svg>';
        
        $find = [
            " ",
            "<",
            ">",
            "\"",
            ":",
            "(",
            ")",
            ";",
            ",",
            "#",
            "=",
            "\n",
        ];
        
        $replace = [
            "%20",
            "%3C",
            "%3E",
            "%22",
            "%3A",
            "%28",
            "%29",
            "%3B",
            "%2C",
            "%23",
            "%3D",
            "%0A",
        ];
        
        #return "data:image/svg+xml;charset=utf-8," . $string; 
        
        return "data:image/svg+xml;charset=utf-8," . str_replace($find, $replace, trim($string));
            
            
        }
		
	}