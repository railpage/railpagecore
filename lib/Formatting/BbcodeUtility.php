<?php

/**
 * Process BBCode inside a block of text and transform to HTML
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Formatting;

use Railpage\Debug;
use phpQuery;
use SBBCodeParser\Node_Container_Document;
use SBBCodeParser\BBCode;
use SBBCodeParser_Document;
use Decoda\Decoda;
use Decoda\Filter\UrlFilter;
use Decoda\Filter\EmailFilter;
use Decoda\Hook\EmoticonHook;
use Decoda\Hook\ClickableHook;
use Decoda\Hook\CensorHook;
use Decoda\Hook\CodeHook;

class BbcodeUtility {
    
    /**
     * Process bbcode inside a string
     * @since Version 3.10.0
     * @param string|DOMDocument $string The HTML or text block to process
     * @return DOMDocument
     */
    
    public static function Process($string) {
        
        $timer = Debug::getTimer(); 
        
        /**
         * Pre-process the string before we send it through the BBCode parser
         */
        
        $string = self::preProcessBBCodeUIDs($string);
        
        $parser = new Decoda($string); 
        $parser->defaults(); 
        $parser->setMaxNewlines(2);
        $parser->addHook(new EmoticonHook);
        $parser->addHook(new ClickableHook);
        $parser->addHook(new CodeHook);
        
        $string = $parser->parse(); 
		
        /*
        if (is_string($string)) {
            $string = phpQuery::newDocumentHTML($string);
        }
        */
        
        Debug::LogEvent(__METHOD__, $timer); 
        
        return $string;

    }
    
    /**
     * Pre-process: tidy up some bbcode UIDs
     * @since Version 3.10.0
     * @param string $string
     * @return string
     */
    
    private static function preProcessBBCodeUIDs($string) {
        
        $format_search	= array(
            '#\[url=(.*?)\](.*?)\[/url\] by \[url=(.*?)\](.*?)\[/url\], on Flickr#i',
            "@\[b:([a-zA-Z0-9]+)]@i", 
            "@\[quote:([a-zA-Z0-9]+)=@i", 
            "@\[/quote:([a-zA-Z0-9]+)\]@i",
            "@\[flash=http://www.flickr.com/apps/video/stewart.swf\?v=([0-9]+)\]@i",
            "@\[/list:u]@i",
            '#\[url=/(.*?)\](.*?)\[/url\]#i' // Relative links - ones with leading slashes
        );
        
        $format_replace	= array(
            '<a href="$1" class="rp-drawflickr">$1</a>',
            "[b]",
            "[quote=", 
            "[/quote]",
            "[flash=\"http://www.flickr.com/apps/video/stewart.swf?v=$1\"]",
            "[/list]",
            '<a href="/$1">$2</a>'
        );
        
        $string = preg_replace($format_search, $format_replace, $string);
        
        $uc_search = array(
            "[URL",
            "[/URL]",
            "[IMG",
            "[/IMG]"
        );
        
        $uc_replace = array(); 
        
        foreach ($uc_search as $find) {
            $uc_replace[] = strtolower($find); 
        }
        
        $string = str_replace($uc_search, $uc_replace, $string);
        
        return $string;

    }
    
}