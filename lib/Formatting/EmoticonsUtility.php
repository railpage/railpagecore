<?php

/**
 * Process emoticons inside a block of text
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Formatting;

use Railpage\Debug;
use phpQuery;

class EmoticonsUtility {
    
    /**
     * Process emoticons inside a string
     * @since Version 3.10.0
     * @param string|DOMDocument $string The HTML or text block to process
     * @return DOMDocument
     */
    
    public static function Process($string) {

		$attr = "data-sceditor-emoticon";
        
        $timer = Debug::getTimer(); 
			
        if (is_string($string)) {
            $string = phpQuery::newDocumentHTML($string);
        }
        
        //phpQuery::selectDocument($doc);
        
        // Remove #tinymce and .mceContentBody tags
        foreach (pq('img') as $e) {
            if (pq($e)->attr($attr)) {
                $emoticon = pq($e)->attr($attr);
                
                if (strlen($emoticon) > 0) { 
                    pq($e)->replaceWith(str_replace('\"', "", $emoticon)); 
                }
            }
        }
        
        Debug::LogEvent(__METHOD__, $timer); 
        
        return $string;

    }
    
}