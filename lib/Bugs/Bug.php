<?php

/**
 * Bug reporting code
 * @since Version 3.8.7
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Bugs;

use Exception;
use DateTime;

/**
 * Bug
 */

class Bug extends Bugs {
    
    /**
     * Bug ID
     * @since Version 3.8.7
     * @var int $id
     */
    
    public $id;
    
    /**
     * Bug title
     * @since Version 3.8.7
     * @var string $title
     */
    
    public $title;  
    
    /**
     * Constructor
     * @since Version 3.8.7
     * @param int $bug_id
     */
    
    public function __construct($bug_id = bull) {
        
        parent::__construct();
        
        if (filter_var($bug_id, FILTER_VALIDATE_INT)) {
            
            $url = self::REDMINE_URL . "/issues.json?id=" . $bug_id;
            
            echo $url;
            
            #$response = $this->fetch($url);
            
            #printArray($response);
            
        }
        
    }
}
