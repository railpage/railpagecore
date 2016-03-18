<?php

/**
 * Root class for pre-rendering of content
 * @since Version 3.10.0
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage\Prerender;

use Railpage\AppCore;
use Railpage\Users\User;
use Railpage\Users\Factory as UserFactory;
use Exception;
use DateTime;
use InvalidArgumentException;
use Smarty;

class Prerender {
    
    /**
     * Smarty object
     * @since Version 3.10.0
     * @var object $smarty
     */
    
    protected $smarty;
    
    /**
     * Cache object
     * @since Version 3.10.0
     * @var object $cacheProvider
     */
    
    protected $cacheProvider; 
    
    /**
     * User object
     * @since Version 3.10.0
     * @var \Railpage\Users\User $userObject
     */
    
    protected $userObject;
    
    /**
     * Template file
     * @since Version 3.10.0
     * @var string $template
     */
     
    protected $template;
    
    /**
     * Unique ID for rendering templates
     * @since Version 3.10.0
     * @var mixed $unique
     */
    
    protected $unique;
    
    /**
     * Assorted parameters for rendering
     * @since Version 3.10.0
     * @var array $params
     */
    
    protected $params = [];
    
    /**
     * Constructor
     * @since Version 3.10.0
     */
    
    public function __construct() {
        
        $this->smarty = AppCore::GetSmarty(); 
        $this->cacheProvider = AppCore::GetMemcached(); 
        
    }
    
    /**
     * Set the user
     * @since Version 3.10.0
     * @param \Railpage\Users\User $userObject
     * @return \Railpage\Prerender\Prerender
     */
    
    public function setUser(User $userObject) {
        
        $this->userObject = $userObject;
        return $this;
        
    }
    
    /**
     * Set the template 
     * @since Version 3.10.0
     * @param string $template
     * @return \Railpage\Prerender\Prerender
     */
    
    public function setTemplate($template = null) {
        
        if ($template == null) {
            throw new InvalidArgumentException("No template file provided"); 
        }
        
        $this->template = $template;
        
        return $this;
        
    }
    
    /**
     * Set params
     * @since Version 3.10.0
     * @param array $params
     * @return \Railpage\Prerender\Prerender
     */
    
    public function setParams($params = null) {
        
        $defaultParams = [
            "handheld" => false,
            "touch" => false
        ];
        
        $params = array_merge($defaultParams, (($params == null) ? [] : $params));
        
        $this->params = $params;
        
        return $this;
        
    }
    
    /**
     * Set a unique identififer for this template render
     * @since Version 3.10.0
     * @param mixed $unique
     * @return \Railpage\Prerender\Prerender
     */
    
    public function setUnique($unique = null) {
        
        $this->unique = $unique; 
        
        return $this;
        
    }
    
    /**
     * Set caching
     * @since Version 3.10.0
     * @param boolean $isEnabled
     * @param int $lifetime
     * @param boolean $isCompileCheckEnabled
     * @return \Railpage\Prerender\Prerender
     */
    
    public function setCaching($isEnabled = true, $lifetime = 300, $isCompileCheckEnabled = true) {
	
        $this->smarty->caching = $isEnabled;
        $this->smarty->setCacheLifetime($lifetime);
        $this->smarty->setCompileCheck($isCompileCheckEnabled);
        $this->smarty->setCaching(Smarty::CACHING_LIFETIME_CURRENT);
        
        return $this;
    
    }
    
}