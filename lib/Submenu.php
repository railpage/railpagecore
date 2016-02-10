<?php

/**
 * Construct a submenu for a page
 * @since Version 3.4
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage; 

use Railpage\AppCore;
use Railpage\Debug;
use Railpage\Url;
use Exception;
use InvalidArgumentException;

/**
 * Submenu class
 * @since Version 3.4
 */

class Submenu extends AppCore {
    
    /**
     * Menu array
     * @since Version 3.4
     * @var array $menu
     */
    
    public $menu;
    
    /**
     * The internal pointer for the submenu section we're defaulting to
     * @since Version 3.9.1
     * @var string $section
     */
    
    public $section;
    
    /**
     * Constructor
     * @since Version 3.4
     */
    
    public function __construct() {
        // Placeholder - do nothing for now
        
        Debug::RecordInstance(); 
        
    }
    
    /**
     * Add a menu grouping
     * @since Version 3.8.7
     * @param string $title The title of this submenu category
     * @param string $subtitle A subtitle to apply to this category
     */
    
    public function AddGrouping($title = null, $subtitle = null) {
        if (empty(filter_var($title, FILTER_SANITIZE_STRING))) {
            throw new InvalidArgumentException("Cannot add menu grouping - no title given"); 
        }
        
        if (!isset($this->menu[$title])) {
            $this->menu[$title] = array(
                "title" => $title,
                "subtitle" => $subtitle,
                "menu" => array()
            );
        }
        
        return $this;
    }
    
    /**
     * Set subtitle for a grouping
     * @since Version 3.8.7
     * @param string $grouping The title of the grouping/category to change
     * @param string $subtitle The subtitle to apply to ths grouping/category
     */
    
    public function SetGroupingSubtitle($grouping = null, $subtitle = null) {
        
        if ($grouping == null || $subtitle == null) {
            return;
        }
        
        if (isset($this->menu[$grouping])) {
            $this->menu[$grouping]['subtitle'] = $subtitle;
        }
    }
    
    /**
     * Add menu entry
     * @since Version 3.4
     * @param string $title The title of the menu item
     * @param string $url A link to apply
     * @param string $grouping An optional grouping/category this menu item belongs to
     * @param array $meta An optional array of parameters to apply to this menu item
     */
    
    public function Add($title = null, $url = null, $grouping = null, $meta = null) {
        if (empty(filter_var($title, FILTER_SANITIZE_STRING))) {
            throw new InvalidArgumentException("Cannot add item to menu - no title given"); 
        }
        
        $i = count($this->menu); 
        
        if ($grouping != null || (isset($this->section) && !empty($this->section))) {
            
            if (isset($this->section) && !empty($this->section)) {
                $grouping = $this->section;
            }
            
            $this->AddGrouping($grouping);
            
            $i = count($this->menu[$grouping]['menu']); 
            
            // Check if this already exists
            foreach ($this->menu[$grouping]['menu'] as $k => $menu) {
                if ($menu['title'] == $title) {
                    $i = $k;
                    break;
                }
            }
            
            $this->menu[$grouping]['menu'][$i]['title'] = $title; 
            
            if ($url != null) {
                $this->menu[$grouping]['menu'][$i]['url'] = $url;
            }
            
            if ($meta != null) {
                $this->menu[$grouping]['menu'][$i]['meta'] = $meta;
            }
            
            return $this;
            
        }
        
        $this->menu[$i]['title'] = $title; 
        
        if ($url != null) {
            $this->menu[$i]['url'] = $url;
        }
        
        if ($meta != null) {
            $this->menu[$i]['meta'] = $meta;
        }
        
        return $this;
    }
    
    /**
     * Get the URL for a menu item
     * @param string $title
     * @return string
     */
    
    public function GetURL($title = null) {
        if (empty(filter_var($title, FILTER_SANITIZE_STRING))) {
            throw new InvalidArgumentException("Cannot return submenu URL - no title provided to look for");
        }
        
        foreach ($this->menu as $key => $data) {
            if ($data['title'] == $title) {
                return $data['url'];
            }
        }
        
        return false;
    }
    
    /**
     * Get menu as HTML
     * @return string
     */
    
    public function GetHTML() {
        $Smarty = AppCore::GetSmarty(); 
        
        $Smarty->Assign("submenu", $this->menu);
        return $Smarty->Fetch(RP_SITE_ROOT . DS . "content" . DS . "inc.submenu.tpl");
    }
    
    /**
     * Set the section
     * @since Version 3.8.7
     * @param string $section
     */
    
    public function Section($title = null) {
        if (isset($this->menu[$title])) {
            $this->section = $title;
            
            return $this;
        }
        
        $this->section = $title;
        $this->AddGrouping($title);
        
        return $this;
    }
    
    /**
     * Add a section but don't switch to it
     * @since Version 3.8.7
     * @param string $section
     */
    
    public function AddSection($title = null) {
        return $this->AddGrouping($title);
    }
    
    /**
     * Check if there's anything in this menu
     * @since Version 3.9
     * @return boolean
     */
    
    public function HasItems() {
        if (!is_array($this->menu) || count($this->menu) === 0) {
            return false;
        }
        
        foreach ($this->menu as $section) {
            if (isset($section['menu']) && count($section['menu'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Add arbitrary HTML to the submenu
     * @since Version 3.9.1
     * @param string $html
     * @return void
     */
    
    public function AddHTML($html) {
        
        if (isset($this->section) && !empty($this->section)) {
            $this->menu[$this->section]['html'][] = $html;
            return;
        }
        
        $this->menu['html'][] = $html;
        
    }
}
