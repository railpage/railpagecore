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
     * @param \Railpage\Images\Image $imageObject
     * @return array
     */
     
    public static function generateSrcSet(Image $imageObject) {
        
        $sources = array(); 
        $widths = array(); 
        
        foreach ($imageObject->sizes as $size) {
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
            
            //$multiplier = intval($size['width'] > 1600) + 1;
            
            $sources[$k] = sprintf("%s %dw", $size['source'], $size['width']);
            $widths[] = $size['width'];
        }
        
        return array_values($sources);
        
    }
    
    /**
     * Get the SVG string for a gaussian blur of an image thumbnail, blown up to full size
     * Displayed while the full image loads in the background
     * @since Version 3.10.0
     * @param \Railpage\Images\Images $imageObject
     * @return string
     */
    
    public static function GetLoadingSVG(Image $imageObject) {
        
        $cachekey = sprintf("railpage:base64.image.svg=%d", $imageObject->id); 
        
        $Memcached = AppCore::GetMemcached(); 
        
        // Check our base64 hash against a known, shitty hash, itself hashed in md5
        $badhash = [
            "f8984b3824a761805223862ca156bf1e",
            "10a7bf41c903ba2b3fab231fc34e4637",
        ];
        
        $base64 = $Memcached->Fetch($cachekey); 
        
        if (!$base64 || in_array(md5($base64), $badhash)) {
        /*
        global $User; 
        if ($User->id == 45) {
            $base64 = $Memcached->Fetch($cachekey); 
            
            //echo $base64;die;
        }
        
        if (!$base64 = $Memcached->Fetch($cachekey)) {*/
            $thumbnail = $imageObject->sizes['thumb']['source']; 
            $cached_url = ImageCache::cache($thumbnail);
            
            $base64 = base64_encode(file_get_contents($cached_url)); 
            
            $Memcached->save($cachekey, $base64); 
        }
        
        $dstw = $imageObject->sizes['largest']['width'];
        $dsth = $imageObject->sizes['largest']['height'];
        
        $string = '
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="' . $dstw . '" height="' . $dsth . '" viewBox="0 0 ' . $dstw . ' ' . $dsth . '">
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
        
        return "data:image/svg+xml;charset=utf-8," . str_replace($find, $replace, trim($string));
        
        
    }
    
    /**
     * Return an instance of an image provider from the name of the provider
     * @since Version 3.10.0
     * @param string $provider
     * @param array $options An array of options for creating the provider
     * @return object
     */
    
    public static function CreateImageProvider($provider, $options) {
        
        $Config = AppCore::GetConfig(); 
        
        $imageprovider = __NAMESPACE__ . "\\Provider\\" . ucfirst($provider);
        $params = array();

        switch ($provider) {
            case "smugmug" :
                $imageprovider = __NAMESPACE__ . "\\Provider\\SmugMug";
                break;

            case "picasaweb" :
                $imageprovider = __NAMESPACE__ . "\\Provider\\PicasaWeb";
                break;

            case "rpoldgallery" :
                $imageprovider = __NAMESPACE__ . "\\Provider\RPOldGallery";
                break;

            case "fivehundredpx" :
                $imageprovider = __NAMESPACE__ . "\\Provider\FiveHundredPx";
                break;

            case "flickr" :
                $params = array_merge(array(
                    "oauth_token"  => "",
                    "oauth_secret" => ""
                ), $options);

                if (isset($Config->Flickr->APIKey)) {
                    $params['api_key'] = $Config->Flickr->APIKey;
                }

                break;
        }
        
        $imageprovider = str_replace("\\Utility\\", "\\", $imageprovider);

        return new $imageprovider($params);
        
    }
    
}