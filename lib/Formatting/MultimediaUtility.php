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
		
	}