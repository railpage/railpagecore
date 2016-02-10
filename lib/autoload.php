<?php

/**
 * Railpage class modules autoloader
 * @since Version 3.8.7
 * @package Raipage
 * @author Michael Greenhill
 */
 
if (!defined("DS")) {
    define("DS", DIRECTORY_SEPARATOR);
}

spl_autoload_register(function ($class) {
    $file = explode("\\", $class);
    
    if ($file[0] == "Railpage") {
        unset($file[0]);
        $path = __DIR__ . DS . implode(DS, $file) . ".php";
        
        if (file_exists($path)) {
            require_once($path);
        }
    }
});
