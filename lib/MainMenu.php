<?php
    /**
     * Main site menu
     * @since Version 3.8.7
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage;
    
    use Railpage\AppCore;
    use Railpage\Users\User;
    use Exception;
    
    /**
     * Main site menu class
     */
    
    class MainMenu extends AppCore {
        
        /**
         * Array of menu items
         * @var array $menu
         */
        
        private $menu;
        
        /**
         * Current working section
         * @var string $section
         */
        
        private $section;
        
        /**
         * Add a menu section
         * @since Version 3.8.7
         * @param string $title
         * @param string $url
         */
        
        public function AddSection($title = NULL, $url = "javascript:void(0)") {
            if (!isset($this->menu[$title])) {
                $this->menu[$title] = array(
                    "title" => $title,
                    "url" => $url,
                    "children" => array(),
                    "auth" => 0
                );
            }
            
            $this->section = $title;
            
            return $this;
        }
        
        /**
         * Set the section
         * @since Version 3.8.7
         * @param string $section
         */
        
        public function Section($title = NULL, $url = "javascript:void(0)") {
            if (isset($this->menu[$title])) {
                $this->section = $title;
            } else {
                $this->AddSection($title, $url);
            }
            
            return $this;
        }
        
        /**
         * Add menu item
         * @since Version 3.8.7
         * @param string $title
         * @param string $url
         * @param string $class
         * @param string $auth
         */
        
        public function Add($title = NULL, $url = "javascript:void(0)", $auth = "0", $icon = NULL) {
            $this->menu[$this->section]['children'][] = array(
                "title" => $title,
                "url" => $url,
                "auth" => $auth,
                "icon" => $icon
            );
            
            return $this;
        }
        
        /**
         * Return the menu
         * @return array
         */
        
        public function Get() {
            return $this->menu;
        }
    }
    