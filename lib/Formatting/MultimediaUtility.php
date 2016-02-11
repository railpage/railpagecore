<?php

/**
 * Multimedia formatter
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */
 
namespace Railpage\Formatting;

use Railpage\ContentUtility;
use Railpage\AppCore;
use Railpage\Debug;
use Railpage\Url;
use phpQuery;
use DateTime;
use Exception;
use InvalidArgumentException;
use Error;
use DOMElement;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

if (!defined("RP_HOST")) {
    define("RP_HOST", "www.railpage.com.au"); 
}

/**
 * Multimedia formatter
 */

class MultimediaUtility {
    
    /**
     * Convert YouTube links into embedded content
     * @since Version 3.10.0
     * @param \DOMElement $e
     * @return \DOMElement
     */
    
    public static function EmbedYouTube(DOMElement $e) {
        
        if (!preg_match("/(?:https?:\/\/)?(?:www\.)?youtu\.?be(?:\.com)?\/?.*(?:watch|embed)?(?:.*v=|v\/|\/)([\w\-_]+)\&?/", pq($e)->attr("href"), $matches)) {
            return $e;
        }
        
        $video_iframe = pq("<iframe />");
        $video_iframe->attr("type", "text/html")->attr("width", "640")->attr("height", "390")->attr("frameborder", "0");
        $video_iframe->attr("src", "//www.youtube.com/embed/" . $matches[1] . "?autoplay=0&origin=http://" . RP_HOST);
        
        pq($e)->after("<br><br><a href='" . pq($e)->attr("href") . "'>" . pq($e)->attr("href") . "</a>")->replaceWith($video_iframe);
        
        return $e;
        
    }
    
    /**
     * Convert Vimeo links into embedded content
     * @since Version 3.10.0
     * @param \DOMElement $e
     * @return \DOMElement
     */
    
    public static function EmbedVimeo(DOMElement $e) {
        
        $url = pq($e)->attr("href"); 
        
        if (strpos($url, "vimeo.com") === false) {
            return $e;
        }
        
        // Fetch oEmbed
        $oembed = Url::oEmbedLookup(sprintf("https://vimeo.com/api/oembed.json?url=%s", $url));
        
        if (!is_array($oembed) || !isset($oembed['html'])) {
            return $e;
        }
        
        $oembed['html'] .= "<br><br><a href='" . $url . "'>" . $oembed['title'] . " by " . $oembed['author_name'] . "</a>";
        
        pq($e)->replaceWith($oembed['html']); 
        
        return $e;
        
    }
    
    /**
     * Convert a Flickr group link into embedded content
     * @since Version 3.10.0
     * @param \DOMElement $e
     * @return \DOMElement
     */
    
    public static function EmbedFlickrGroup(DOMElement $e) {
        
        if (!(preg_match("#:\/\/www.flickr.com\/groups/([a-zA-Z0-9\@]+)\/\z#", pq($e)->attr("href"), $matches) && pq($e)->attr("href") == pq($e)->html() && !pq($e)->hasClass("rp-coverphoto"))) {
            return $e;
        }
        
        $og = ContentUtility::GetOpenGraphTags(pq($e)->attr("href")); 
                    
        if (isset($og['image'])) {
            
            $html = "<a href='%s' class='rp-coverphoto' style='display:block;height: %dpx; width: %dpx; background-image: url(%s);'><span class='details-date'>%s</span><span class='details-big'>%s</span></a>"; 
            $html = sprintf($html, pq($e)->attr("href"), $og['image:height'], $og['image:width'], $og['image'], $og['site_name'], $og['title']);
            
            pq($e)->empty();
            pq($e)->replaceWith($html);
            
            
        }
        
        return $e;
        
    }
    
    /**
     * Convert a Google Maps link into embedded content
     * @since Version 3.10.0
     * @param \DOMElement $e
     * @return \DOMElement
     */
    
    public static function EmbedGoogleMap(DOMElement $e) {
        
        $Config = AppCore::GetConfig(); 
        
        $lookup = pq($e)->attr("href"); 
        
        // Prevent this from fucking with links in the middle of sentences
        if (pq($e)->text() != $lookup) {
            return $e;
        }
        
        if (!preg_match("#google.com(.au)?/maps/(.*)\@([0-9\-.]{8,13}),([0-9\-.]{8,13})#", $lookup, $matches[0]) &&
            !preg_match("#goo.gl/maps/([a-zA-Z0-9]{10,13})#", $lookup, $matches[1])) {
                return $e;
        }
        
        $basehtml = '<iframe width="%s" height="%s" frameborder="0" style="border:0"
src="https://www.google.com/maps/embed/v1/view?key=%s
&zoom=%d&maptype=%s&center=%s" allowfullscreen>
</iframe>'; 
        
        $params = [
            "100%",
            600,
            $Config->Google->API_Key,
            15,
            "satellite",
        ];
        
        foreach ($matches as $key => $val) {
            if (!count($val)) {
                continue;
            }
            
            // Co-ordinates known, great
            if (count($val) === 5) {
                $params[] = $val[3] . "," . $val[4];
                continue;
            }
            
            // Co-ordinates not known. Shit. Better look 'em up
            if (count($val) === 2) {
                
                $Memcached = AppCore::GetMemcached(); 
                $cachekey = sprintf("google:url.shortner=%s", $val[1]); 
                
                if (!$return = $Memcached->fetch($cachekey)) {
                
                    $GuzzleClient = new Client;
                    $url = sprintf("https://www.googleapis.com/urlshortener/v1/url?shortUrl=%s&key=%s", $lookup, "AIzaSyC1lUe1h-gwmFqj9xDTDYI9HYVTUxNscCA"); 
                    $response = $GuzzleClient->get($url);
                    
                    // Fucked it
                    if ($response->getStatusCode() != 200) {
                        return $e; 
                    }
                    
                    $return = json_decode($response->getBody(), true);
                    
                    $Memcached->save($cachekey, $return); 
                    
                }
                
                // Get out if it looks problematic
                if ($return['status'] != "OK") {
                    return $e; 
                }
                
                pq($e)->attr("href", $return['longUrl'])->text($return['longUrl']); 
                
                return self::EmbedGoogleMap($e);
                
                continue;
            }
        }
        
        pq($e)->replaceWith(vsprintf($basehtml, $params)); 
        
        return $e;
        
    }
    
}