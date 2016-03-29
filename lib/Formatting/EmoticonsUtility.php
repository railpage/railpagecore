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
use Emojione\Emojione; 
use Emojione\Client as EmojioneClient;
use Emojione\Ruleset as EmojioneRuleset;

class EmoticonsUtility {
    
    /**
     * Process emoticons inside a string
     * @since Version 3.10.0
     * @param string|DOMDocument $string The HTML or text block to process
     * @param boolean $doEmoticons Boolean flag for processing or skipping emoticons
     * @return DOMDocument
     */
    
    public static function Process($string, $doEmoticons = true) {
        
        if (!$doEmoticons) {
            return $string; 
        }
        
        $emojiOne = new EmojioneClient(new EmoticonsRuleset); 
        $emojiOne->ascii = true;
        
        $string = $emojiOne->toImage($string); 

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
    
    /**
     * Convert emojis to their short code
     * @since Version 3.11.0
     * @param string|DOMDocument $string The HTML or text block to process
     * @return string
     */
    
    public static function toShortcode($string) {
        
        if (is_object($string)) {
            $string = $string->__toString(); 
        }
        
        $emojiOne = new EmojioneClient(new EmoticonsRuleset); 
        $emojiOne->ascii = true;
        
        $string = $emojiOne->toShort($string); 
        $string = self::replaceExtraEmojis($string); 
        
        return $string;
        
    }
    
    /**
     * Replace emojis not found in EmojiOne with their shortcodes
     * Look up unicode emojis at http://apps.timwhitlock.info/emoji/tables/unicode
     * @since Version 3.10.0
     * @param string $string
     * @return string
     */
    
    private static function replaceExtraEmojis($string) {
        
        $lookups = [
            "\xE2\x98\xBA"      => ":slight_smile:", // FUCKIN BINGO FINALLY
            "\xF0\x9F\x98\x81"  => ":grin:",
            "\xF0\x9F\x98\xA2"  => ":cry:",
            "\xF0\x9F\x98\x9C"  => ":stuck_out_tongue_winking_eye:",
            "\xF0\x9F\x91\x85"  => ":tongue:",
        ];
        
        $string = str_replace(array_keys($lookups), array_values($lookups), $string); 
        
        return $string; 
        
    }
    
    /**
     * Return the array of default emoticons by file => text equivalent
     * @since Version 3.10.0
     * @return array
     */
    
    public static function getEmoticons() {
        
        return [
            'icon_mad'      => [ ':angry:', '&gt;(', '&gt;:(', '&gt;[', '&gt;:[' ],
            'icon_cool'     => [ ':sunglasses:', ':cool:', '8)', '8]', '[:cool:]', '8-)' ],
            'icon_rage'     => [ ':rage:', '&gt;:D', '&gt;&lt;', ':x', ':-x', ':anger:', ':fury:', ':furious:' ],
            'icon_smile'    => [ ':slight_smile:', ':relaxed:', ':]', ':-)', ':)', ':happy:', '1F603', '263A' ],
            'icon_biggrin'  => [ ':grinning:', ":D", ':-D', ':grin:' ],
            'icon_sad'      => [ ':slight_frown:', ':(', ':-(', ':[', ';(', ';[', ':\'(', ':\'[', ';\'(', ';\'[', ':sad:' ],
            'icon_confused' => [ ':confused:', ':what:', ':s', ':S' ],
            'icon_wink'     => [ ':wink:', ';)', ';-)',';]', ';D', ':stuck_out_tongue_winking_eye:' ],
            'icon_redface'  => [ ':flushed:', ':oops:', ':P', ':p', ':-P', ':-p', ':tongue:' ],
            'icon_razz'     => [ ':stuck_out_tongue_winking_eye:', ':razz:' ],
            'icon_surprised'    => [ ':astonished:', ':o', ':O', ':O', ':-o', ':-O', ":eek:" ],
            'icon_lol'          => [ ':laughing:', ':lol:' ],
            'icon_cry'          => [ ':sob:', ':cry:' ],
            'icon_evil'         => [ ':imp:', ':evil:' ],
            'icon_twisted'      => [ ':smiling_imp:', ':twisted:' ],
            'icon_rolleyes'     => [ ':rolling_eyes:', ':roll:' ],
            'icon_exclaim'      => [ ':exclamation:', ':bangbang:', ':!:', ':exclaim:' ],
            'icon_question'     => [ ':question:', ':grey_question:', ':?:', ':?', ':-?', ],
            'icon_idea'         => [ ':idea:' ],
            'icon_arrow'        => [ ':arrow_right:', ':arrow:' ],
            'icon_neutral'      => [ ':neutral_face:', ':|', ':-|', ':neutral:' ],
            'icon_mrgreen'      => [ ':mrgreen:' ],
            'icon_urazz'        => [ ':stuck_out_tongue_closed_eyes:', 'P:', 'P-:', ':urazz:' ],
            'spew'              => [ ':thermometer_face:', ':spew:' ],
            'whip'              => [ ':whip:' ],
            'banghead'          => [ ':banghead:' ],
            'bounce'            => [ ':bounce:' ],
            'bwahaha'           => [ ':mwahaha:' ],
            'clap'              => [ ':clap:' ],
            'evil3'             => [ ':posessed:' ],
            'great'             => [ ':welldone: '],
            'killme'            => [ ':killme:' ],
            'love'              => [ ':heart_eyes:', ':love:' ],
            'pissed'            => [ ':beers:', ':drunk:', ':pissed:' ],
            'pukeface'          => [ ':thermometer_face:', ':puke:', ':sick:', ':vomit: '],
            'rockon'            => [ ':rocknroll:' ],
            'sleepy'            => [ ':sleepy:' ],
            'paypal'            => [ ':paypal:' ],
            'locky'             => [ ':lock:', ':locky:' ],
        ];
           
    }
    
    /**
     * Get all emoticons by text equivalent => file
     * @since Version 3.10.0
     * @return array
     */
    
    public static function getEmoticonsByText() {
        
        $smilies = []; 
        $ext = "gif";
        $path = "<img src='//static.railpage.com.au/images/smiles/%s.%s' class='emoticon'>"; 
        
        foreach (self::getEmoticons() as $file => $emoticons) {
            foreach ($emoticons as $text) {
                $smilies[$text] = sprintf($path, $file, $ext); 
            }
        }
        
        return $smilies; 
        
    }
    
    /**
     * Get a standardised short code from any given ASCII emoticon
     * @since Version 3.10.0
     * @param string $ascii
     * @return string
     */
    
    public static function normaliseEmoticon($ascii) {
        
        $icons = self::getEmoticons(); 
        
        $key = array_search($ascii, array_column($icons));
        
        printArray($key); 
        
    }
    
}