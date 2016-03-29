<?php

/**
 * HTML formatting utility
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Formatting; 

use Railpage\AppCore;
use Railpage\Debug;
use phpQuery;

class Html {
    
    /**
     * Cleanup bad HTML
     * @since Version 3.10.0
     * @param string|DOMDocument $string
     * @param string $editorVersion
     * @return DOMDocument
     */
    
    public static function cleanupBadHtml($string, $editorVersion = 1) {
        
        $timer = Debug::getTimer(); 
			
        if (is_string($string)) {
            $string = phpQuery::newDocumentHTML($string);
        }
		
		// Parse the HTML object, clean it up
		#require_once("phpQuery.php");
		//$string = phpQuery::newDocumentHTML($string);
		
		//phpQuery::selectDocument($doc);
		
		// Replace P elements
		foreach (pq('p') as $e) {
			pq($e)->replaceWith(pq($e)->html()."\n");
		}
		
		// Turn objects into images
		foreach (pq('object') as $e) {
			$img_src = pq($e)->attr("data");
			
			if (stripos($img_src, "http://www.flickr.com/apps/video") === false) {
				$align = pq($e)->attr("align");
				$width = pq($e)->attr("width");
				$height = pq($e)->attr("height");
				
				if (empty($align)) {
					$align = "left";
					$margin = "margin-right: 1em; margin-bottom: 1em;";
				} else {
					$align = "right";
					$margin = "margin-left: 1em; margin-bottom: 1em;";
				}
				
				if (!empty($width) && !empty($height)) {
					$dims = "width='".$width."' height='".$height."'";
				}
				
				$replace = '<img class="border" src="'.$img_src.'" style="float:'.$align.';'.$margin.'" '.$dims.' />';
				
				pq($e)->empty();
				pq($e)->replaceWith($replace);
			}
		}
		
		$string = strip_tags($string, "<a><strong><b><em><ul><ol><li><img><br><p><span><dd><dt><dl>");
		
		if ($editorVersion == 1) {
			$string = wpautop($string); 
		} elseif ($editorVersion == 2) {
			$string = nl2br($string);
		} else {
			$string = preg_replace('#<br\s*/?>#i', "\n", $string);
			$string = nl2br($string);
			$string = preg_replace('#<br />(\s*<br />)+#', '<br />', $string);
		}
		
        Debug::LogEvent(__METHOD__, $timer); 
		
		return $string;

    }
    
    /**
     * Strip headers from within a block of text
     * @param string|DOMDocument $string
     * @return DOMDocument
     */
    
    public static function removeHeaders($string) {
        
        $timer = Debug::getTimer(); 
			
        if (is_string($string)) {
            $string = phpQuery::newDocumentHTML($string);
        }
        
        foreach (pq('h1') as $e) {
            pq($e)->replaceWith("<p><strong>".pq($e)->text()."</strong></p>");
        }
        
        foreach (pq('h2') as $e) {
            pq($e)->replaceWith("<p><strong>".pq($e)->text()."</strong></p>");
        }
        
        foreach (pq('h3') as $e) {
            pq($e)->replaceWith("<p><strong>".pq($e)->text()."</strong></p>");
        }
		
        Debug::LogEvent(__METHOD__, $timer); 
        
        return $string;

    }
    
}