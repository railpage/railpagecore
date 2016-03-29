<?php

/**
 * Extend the EmojiOne ASCII ruleset
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Formatting;

use Emojione\Emojione; 
use Emojione\Client as EmojioneClient;
use Emojione\Ruleset as EmojioneRuleset;

class EmoticonsRuleset extends EmojioneRuleset {
    
    /**
     * Constructor
     * @since Version 3.10.0
     * @return void
     */
    
    public function __construct() {
        
        foreach (EmoticonsUtility::getEmoticons() as $key => $emoticons) {
            
            if (count($emoticons) === 1) {
                continue;
            }
            
            $first = array_slice($emoticons, 0, 1)[0]; 
            
            if (isset($this->shortcode_replace[$first])) {
                foreach ($emoticons as $icon) {
                    $this->shortcode_replace[$icon] = $this->shortcode_replace[$first]; 
                }
            }
            
            if (isset($this->unicode_replace[$first])) {
                foreach ($emoticons as $icon) {
                    $this->unicode_replace[$icon] = $this->unicode_replace[$first]; 
                }
            }
            
        }
        
    }
    
}