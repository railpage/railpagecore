<?php
    /**
     * Construct a breadcrumb menu
     * @since Version 3.8.6
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage; 
    
    use Railpage\AppCore;
    use Railpage\Debug;
    use Exception;
    
    /**
     * Breadcrumb menu
     *
     * Programmatically constructs a breadcrumb menu (home / page / subpage)
     * @since Version 3.8.6
     */
    
    class Breadcrumb extends AppCore {
        
        /**
         * Menu array
         * @since Version 3.8.6
         * @var array $menu An array of items in this menu
         */
        
        public $menu = array();
        
        /**
         * Separator
         * @since Version 3.8.6
         * @var string $separator The default separator for items in the rendered breadcrumb
         */
        
        public $separator = "&raquo;";
        
        /**
         * Constructor
         */
        
        public function __construct() {
            
            parent::__construct(); 
            
            /**
             * Record this in the debug log
             */
            
            Debug::recordInstance(); 
        }
        
        /**
         * Generate and return the HTML for this breadcrumb menu
         * @return string
         */
        
        public function __toString() {
            $items = array();
            
            foreach ($this->menu as $i => $data) {
                if (isset($data['url']) && is_string($data['url'])) {
                    $child = $i == 0 ? "itemprop='child'" : "";
                    $items[] = sprintf("<li %s itemscope itemtype='http://data-vocabulary.org/Breadcrumb'><a href='%s' itemprop='url'><span itemprop='title'>%s</a></a></li>", $child, htmlspecialchars($data['url'], ENT_QUOTES), $data['title']);
                } else {
                    $items[] = sprintf("<li>%s</li>", $data['title']); 
                }
            }
            
            return implode("", $items);
        }
        
        /**
         * Add menu entry
         * @since Version 3.8.6
         * @param string $title The title of the breadcrumb item
         * @param string $url An optional URL for this breadcrumb item
         */
        
        public function Add($title = false, $url = false) {
            if (!$title) {
                #throw new Exception("Cannot add item to menu - no title given"); 
                return $this;
            }
            
            $i = count($this->menu); 
            
            $this->menu[$i]['title'] = $title; 
            
            if ($url) {
                $this->menu[$i]['url'] = strval($url);
                
                $Smarty = AppCore::getSmarty();
    
                /**
                 * Pre-rendering
                 */
                
                if ($Smarty instanceof \Railpage\Template) {
                    $Smarty->prerender($url);
                }
                
            }
            
            return $this;
        }
        
        /**
         * Remove a title from the menu
         * @since Version 3.8.6
         * @param string $title The title of the breadcrumb entry to remove
         * @return boolean
         */
        
        public function Remove($title = false) {
            if (!$title) {
                return $this;
            }
            
            foreach ($this->menu as $i => $data) {
                if ($data['title'] == $title) {
                    unset($this->menu[$i]);
                    break;
                }
            }
            
            return $this;
        }
        
        /**
         * Replace an item
         * @since Version 3.8.6
         * @param string $title The title of the breadcrumb entry to find
         * @param string $url The replacement URL
         */
        
        public function ReplaceItem($title = false, $url = false) {
            if (!$title) {
                return $this;
            }
            
            foreach ($this->menu as $i => $data) {
                if ($data['title'] == $title) {
                    $this->menu[$i]['url'] = $url;
                    break;
                }
            }
            
            return $this;
        }
    }
    