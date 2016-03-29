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
use Railpage\Downloads\Download;
use Railpage\Downloads\DownloadsFactory;

if (!defined("RP_HOST")) {
    define("RP_HOST", "www.railpage.com.au"); 
}

/**
 * Multimedia formatter
 */

class MultimediaUtility {
    
    /**
     * Find links in our string and attempt to process those
     * @since Version 3.10.0
     * @param string|DOMDocument $string
     * @return string
     */
    
    public static function Process($string) {
        
        $timer = Debug::GetTimer(); 
			
        if (is_string($string)) {
            $string = phpQuery::newDocumentHTML($string);
        }
        
        foreach (pq('a') as $e) {
            
            if (strlen(pq($e)->html()) == 0) {
                continue;
            }
            
            pq($e)->attr("href", self::fixCrappyUrls(pq($e)->attr("href"))); 
            
            $e = self::EmbedYouTube($e); 
            $e = self::EmbedVimeo($e); 
            $e = self::EmbedGoogleMap($e); 
            $e = self::EmbedFlickrGroup($e); 
            $e = self::EmbedFlickrPhoto($e);
            $e = self::EmbedFlickrPhotostream($e);
            $e = self::EmbedFlickrAlbum($e);
            $e = self::EmbedRailpageDownload($e); 
            
        }
        
        $string = self::GroupEmbeddedContent($string); 
        
        Debug::LogEvent(__METHOD__, $timer); 
        
        return $string;
    }
    
    /**
     * Embed a file from the downloads area
     * @since Version 3.11.0
     * @param \DOMElement $e
     * @return \DOMElement
     */
    
    private static function embedRailpageDownload($e) {
        
        if (pq($e)->attr("href") != pq($e)->text()) {
            return $e;
        }
        
        if (!preg_match("/\/downloads\?mode=download.view&id=([0-9]+)/", pq($e)->attr("href"), $matches)) {
            return $e; 
        }
        
        $Download = DownloadsFactory::createDownload($matches[1]); 
        
        $block = pq("<div />"); 
        $block->append(sprintf("<h1>%s %s</h1>", $Download->getIcon()['icon'], $Download->name)); 
        $block->append("<p>" . pq($e)->attr("href") . "</p>");
        $block->append("<p>" . nl2br($Download->desc) . "</p>"); 
        
        if ($formats = $Download->getHTML5Video()) {
            $block->addClass("embed-video"); 
            
            $video = pq("<video />"); 
            $video->attr("width", 1920)->attr("height", 1080)->attr("style", "background: #000; width: 100%; height: auto; max-height:500px;")->attr("controls", ""); 
            
            $thumb = $Download->getThumbnail(); 
            
            if ($thumb) {
                $video->attr("poster", "/uploads/" . $thumb['file']); 
            }
            
            foreach ($formats as $row) {
                $video->append('<source src="/uploads/' . $row['file'] . '" type="' . $row['mime'] . '">');
            }
            
            $video->append("Your browser does not support the video tag."); 
            
            $block->append($video);
        
            pq($e)->replaceWith($block); 
            
            return $e; 
            
        }
        
        if ($thumb = $Download->getThumbnail()) {
            
        }
        
        pq($e)->replaceWith($block); 
        
        return $e; 
        
    }
    
    /**
     * Group embedded content into blocks
     * @since Version 3.11.0
     * @param \DOMDocument $string
     * @return \DOMDocument
     */
    
    private static function groupEmbeddedContent($string) {
        
        $startNewBlock = true;
        
        foreach (pq(".embed-group-member") as $e) {
            
            if ($startNewBlock) {
                $block = pq("<div />");
                $block->addClass("embed-group-block"); 
                $block->insertBefore(pq($e));
                $startNewBlock = false; 
            }
            
            if (!pq($e)->next()->hasClass("embed-group-member")) {
                $startNewBlock = true;
            }
            
            pq($e)->appendTo($block); 
            
        }
        
        return $string;
        
    }
    
    /**
     * Fix crappy URLs with extra dodgy characters
     * @since Version 3.10.0
     * @param string $string
     * @return string
     */
    
    private function fixCrappyUrls($string) {
        
        $find = [
            "<p>%0A</p>",
            "<p>",
            "</p>",
            "%0A",
            "\r",
            "\n",
            "<br>"
        ]; 
        
        $replace = array_fill(0, count($find), ""); 
        
        $string = str_replace($find, $replace, $string); 
        
        return $string;
        
    }
    
    /**
     * Convert a link to a Flickr photo into embedded content
     * @since Version 3.10.0
     * @param \DOMElement $e
     * @return \DOMElement
     */
    
    public static function EmbedFlickrPhoto(DOMElement $e) {
        
        if (!(preg_match("@:\/\/flic.kr/p/([a-zA-Z0-9]+)@", pq($e)->attr("href"), $matches)) && 
            !preg_match("@:\/\/www.flickr.com\/photos\/([a-zA-Z0-9\@]+)\/([0-9]+)@", pq($e)->attr("href"), $matches)) {
                return $e; 
        }
        
        foreach (pq($e)->next() as $next) {
            
            if (!pq($next)->is("a")) {
                break;
            }
            
            if (preg_match("@:\/\/flic.kr/p/([a-zA-Z0-9]+)@", pq($next)->attr("href")) || preg_match("@:\/\/www.flickr.com\/photos\/([a-zA-Z0-9\@]+)\/([0-9]+)@", pq($next)->attr("href"), $matches)) {
                pq($e)->addClass("embed-group"); 
                pq($next)->addClass("embed-group"); 
            }
            
            break;
        }
        
        $e = self::drawFlickrFromOpenGraph($e); 
        
        return $e;
        
    }
    
    /**
     * Embed Flickr content from an OpenEmbed-friendly URL
     * @since Version 3.10.0
     * @param \DOMElement $e
     * @param string $group
     * @return \DOMElement
     */
    
    private static function drawFlickrFromOpenGraph(DOMElement $e, $group = null) {
        
        $og = ContentUtility::GetOpenGraphTags(pq($e)->attr("href")); 
        
        $style = [
            "background-image: url(\"" . $og['image'] . "\")",
            /*"position: absolute",
            "left: 0",
            "top: 0",
            "width: 100%",
            "height: 100%"*/
        ];
        
        $titlePrepend = [ 
            "flickr_photos:set" => "Photo album",
            "flickr_photos:photo" => "Photo"
        ];
        
        $titlePrepend = isset($titlePrepend[$og['type']]) ? $titlePrepend[$og['type']] . ": " : "";
        
        if (empty($og['title'])) {
            $og['title'] = "Untitled";
        }
        
        $og['title'] = ContentUtility::FormatTitle($og['title']); 
        
        $mediaBlock = pq("<div />");
        $mediaBlock->addClass("content-image")->addClass("content-flickr")->addClass("media"); 
        $mediaBlock->attr("style", implode(";", $style)); 
        
        $mediaBlock->html("<div class='media--content'><h1><a href='" . $og['url'] . "'>" . $titlePrepend . $og['title'] . "</a></h1><div class='media--lead'><a href='" . $og['url'] . "'>" . $og['description'] . "</a></div></div>"); 
        
        $mediaBlockWrapper = pq("<div />"); 
        $mediaBlockWrapper->addClass("content-image-wrapper"); 
        //$mediaBlockWrapper->attr('style', "height: 0;padding-bottom: 56.25%;position: relative;margin-bottom:1.4em;"); 
        
        $mediaBlockWrapper->html($mediaBlock); 
        
        if (pq($e)->hasClass("embed-group")) {
            $mediaBlockWrapper->addClass("embed-group-member"); 
        }
        
        pq($e)->replaceWith($mediaBlockWrapper); 
        
        return $e; 
    }
    
    /**
     * Convert a link to a Flickr photostream into embedded content
     * @since Version 3.10.0
     * @param \DOMElement $e
     * @return \DOMElement
     * @todo Set the replacement content using OpenGraph tags
     */
    
    public static function EmbedFlickrPhotostream(DOMElement $e) {
					
        if (!preg_match("#:\/\/www.flickr.com\/photos\/([a-zA-Z0-9\@]+)\/\z#", pq($e)->attr("href"), $matches)) {
            return $e; 
        }
        
        return self::drawFlickrFromOpenGraph($e); 
        
        /*
        if (!pq($e)->parent()->parent()->is(".quote_wrapper") && !pq($e)->parent()->parent()->parent()->is(".quote_wrapper") && strlen(pq($e)->text()) > 12) {
            $nsid = $matches[1];
            
            pq($e)->empty();
            pq($e)->replaceWith("<div class=\"rp-flickr-drawphotostream\" data-nsid=\"" . $matches[1] . "\"></div>");
        }
        
        return $e; 
        */
    }
    
    /**
     * Convert a link to a Flickr photoset/album into embedded content
     * @since Version 3.10.0
     * @param \DOMElement $e
     * @return \DOMElement
     * @todo Set the replacement content using OpenGraph tags
     */
    
    public static function EmbedFlickrAlbum(DOMElement $e) {
        
        if (!preg_match("#:\/\/www.flickr.com\/photos\/([a-zA-Z0-9\@]+)\/(sets|albums)\/([a-zA-Z0-9\@]+)\z#", pq($e)->attr("href"), $matches)) {
            return $e; 
        }
        
        return self::drawFlickrFromOpenGraph($e); 
        
        /*
        if (!pq($e)->parent()->parent()->is(".quote_wrapper") && !pq($e)->parent()->parent()->parent()->is(".quote_wrapper") && strlen(pq($e)->text()) > 12) {
            $nsid = $matches[1];
            
            pq($e)->empty();
            pq($e)->replaceWith("<div class=\"rp-flickr-drawphotoset\" data-nsid=\"" . $matches[1] . "\" data-photoset-id=\"" . $matches[3] . "\"></div>");
        }
        
        return $e; 
        */
    }
    
    /**
     * Convert a Flickr group link into embedded content
     * @since Version 3.10.0
     * @param \DOMElement $e
     * @return \DOMElement
     */
    
    public static function EmbedFlickrGroup(DOMElement $e) {
        
        if (!preg_match("/www.flickr.com\/groups\/([a-zA-Z0-9\@]+)[\/]?/", pq($e)->attr("href"))) {
            return $e; 
        }
        
        $href = pq($e)->attr("href");
        $html = self::fixCrappyUrls(pq($e)->html()); 
        
        if ($href != $html || pq($e)->hasClass("rp-coverphoto")) {
            return $e; 
        }
        
        return self::drawFlickrFromOpenGraph($e); 
        
        /*
        $timer = Debug::GetTimer(); 
        
        $og = ContentUtility::GetOpenGraphTags(pq($e)->attr("href")); 
                            
        if (isset($og['image'])) {
            
            $html = "<a href='%s' class='rp-coverphoto' style='display:block;height: %dpx; width: %dpx; background-image: url(%s);'><span class='details-date'>%s</span><span class='details-big'>%s</span></a>"; 
            $html = sprintf($html, pq($e)->attr("href"), $og['image:height'], $og['image:width'], $og['image'], $og['site_name'], $og['title']);
            
            pq($e)->empty();
            pq($e)->replaceWith($html);
            
        }
        
        Debug::LogEvent(__METHOD__, $timer); 
        
        return $e;
        */
        
    }
    
    /**
     * Convert YouTube links into embedded content
     * @since Version 3.10.0
     * @param \DOMElement $e
     * @return \DOMElement
     */
    
    public static function EmbedYouTube(DOMElement $e) {
        
        $timer = Debug::GetTimer(); 
        
        if (!preg_match("/(?:https?:\/\/)?(?:www\.)?youtu\.?be(?:\.com)?\/?.*(?:watch|embed)?(?:.*v=|v\/|\/)([\w\-_]+)\&?/", pq($e)->attr("href"), $matches)) {
            return $e;
        }
        
        parse_str(parse_url(pq($e)->attr("href"), PHP_URL_QUERY), $return);
        
        if (!isset($return['v'])) {
            return $e; 
        }
        
        $return['v'] = trim(str_replace("</p>", "", $return['v'])); 
        
        $video_iframe = pq("<iframe />");
        $video_iframe->addClass("content-iframe")->addClass("content-youtube");
        $video_iframe->attr("type", "text/html")->attr("width", "640")->attr("height", "390")->attr("frameborder", "0");
        $video_iframe->attr("src", "//www.youtube.com/embed/" . $return['v'] . "?autoplay=0&origin=http://" . RP_HOST);
        
        pq($e)->after("<br><br><a href='" . pq($e)->attr("href") . "'>" . pq($e)->attr("href") . "</a>")->replaceWith($video_iframe);
        
        Debug::LogEvent(__METHOD__, $timer); 
        
        return $e;
        
    }
    
    /**
     * Convert Vimeo links into embedded content
     * @since Version 3.10.0
     * @param \DOMElement $e
     * @return \DOMElement
     */
    
    public static function EmbedVimeo(DOMElement $e) {
        
        $timer = Debug::GetTimer(); 
        
        $url = pq($e)->attr("href"); 
        
        if (strpos($url, "vimeo.com") === false) {
            return $e;
        }
        
        // Fetch oEmbed
        $oembed = Url::oEmbedLookup(sprintf("https://vimeo.com/api/oembed.json?url=%s", $url));
        
        if (!is_array($oembed) || !isset($oembed['html'])) {
            return $e;
        }
        
        $video_iframe = pq($oembed['html']);
        $video_iframe->addClass("content-iframe")->addClass("content-vimeo");
        
        pq($e)->after("<br><br><a href='" . $url . "'>" . $oembed['title'] . " by " . $oembed['author_name'] . "</a>")->replaceWith($video_iframe);
        
        Debug::LogEvent(__METHOD__, $timer); 
        
        return $e;
        
    }
    
    /**
     * Convert a Google Maps link into embedded content
     * @since Version 3.10.0
     * @param \DOMElement $e
     * @return \DOMElement
     */
    
    public static function EmbedGoogleMap(DOMElement $e) {
        
        $timer = Debug::GetTimer(); 
        
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
        
        foreach ($matches as $val) {
            if (!count($val)) {
                continue;
            }
            
            // Co-ordinates known, great
            if (count($val) === 5) {
                $params[] = $val[3] . "," . $val[4];
                continue;
            }
            
            // Co-ordinates not known. Shit. Better look 'em up
            if (count($val) !== 2) {
                continue;
            }
                
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
        
        pq($e)->replaceWith(vsprintf($basehtml, $params)); 
        
        Debug::LogEvent(__METHOD__, $timer); 
        
        return $e;
        
    }
    
}