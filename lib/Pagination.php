<?php
    /**
     * Create and draw page navigation UI (Pagination)
     * @since Version 3.10.0
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage;
    
    use Exception;
    use InvalidArgumentException;
    
    class Pagination {
        
        /**
         * Name of the default pagination template
         * @since Version 3.10.0
         * @const DEFAULT_TEMPLATE
         */
        
        const DEFAULT_TEMPLATE = "template.pagination";
        
        /**
         * Default left/previous navigation text
         * @since Version 3.10.0
         * @const DEFAULT_NAV_LEFT
         */
        
        const DEFAULT_NAV_LEFT = "<span class='glyphicon glyphicon-chevron-left'></span>";
        
        /**
         * Default right/more/next navigation text
         * @since Version 3.10.0
         * @const DEFAULT_NAV_RIGHT
         */
        
        const DEFAULT_NAV_RIGHT = "<span class='glyphicon glyphicon-chevron-right'></span>";
        
        /**
         * An array of configuration parameters 
         * @since Version 3.10.0
         * @var array $params
         */
        
        public $params = [];
        
        /**
         * Smarty instance
         * @since Version 3.10.0
         * @var \Railpage\Template $Smarty
         */
        
        private $Smarty;
        
        /**
         * Constructor
         * @since Version 3.10.0
         * @param string $base_url
         * @param int $num_pages
         */
        
        public function __construct($url_format, $current_page, $num_items, $per_page = 25) {
            
            $this->Smarty = AppCore::GetSmarty();
            
            $this->setParam("current_page", $current_page)
                 ->setParam("url_format", $url_format)
                 ->setParam("num_items", $num_items)
                 ->setParam("per_page", $per_page); 
            
        }
        
        /**
         * Set a configuration parameter
         * @since Version 3.10.0
         * @param string $param
         * @param mixed $value
         * @return \Railpage\Pagination
         */
         
        public function setParam($param, $value) {
            
            $this->params[$param] = $value;
            
            return $this;
            
        }
        
        /**
         * Get a configuration parameter
         * @since Version 3.10.0
         * @param string $param
         * @return mixed
         */
        
        public function getParam($param) {
            
            if (isset($this->params[$param]) && !empty($this->params[$param])) {
                return $this->params[$param]; 
            }
            
            return false;
            
        }
        
        /**
         * Set the URL format
         * @since Version 3.10.0
         * @param string $url_format
         * @return \Railpage\Pagination
         */
        
        public function setUrlFormat($format) {
            
            $this->params['url_format'] = $format;
            
            return $this;
            
        }
        
        /**
         * Validate the pagination data
         * @since Version 3.10.0
         * @return \Railpage\Pagination;
         */
        
        private function validate() {
            
            if (!$this->getParam("url_format")) {
                throw new Exception("No URL format has been set");
            }
            
            if (!$this->getParam("template")) {
                $this->setParam("template", self::DEFAULT_TEMPLATE); 
            }
            
            if (!$this->getParam("nav_left")) {
                $this->setParam("nav_left", self::DEFAULT_NAV_LEFT); 
            }
            
            if (!$this->getParam("nav_right")) {
                $this->setParam("nav_right", self::DEFAULT_NAV_RIGHT); 
            }
            
            $this->setParam("total_pages", ceil($this->getParam("num_items") / $this->getParam("per_page")));
            
            return $this;
            
        }
        
        /**
         * Add a link to the pagination
         * @since Version 3.10.0
         * @param int $increment
         * @param boolean $current_page
         * @return \Railpage\Pagination
         */
        
        private function addLink($increment, $current_page = false) {
            
            $links = $this->getParam("links"); 
            
            $href = sprintf($this->getParam("url_format"), $increment); 
            
            $links[$increment] = [
                "href" => $href,
                "text" => $current_page ? sprintf("%s of %s", $increment, $this->getParam("total_pages")) : $increment,
                "current" => $current_page,
                "class" => $current_page ? "current" : ""
            ];
            
            $this->setParam("links", $links);
            
            return $this;
            
        }
        
        /**
         * Build the first few links
         * @since Version 3.10.0
         * @return \Railpage\Pagination
         */
        
        private function buildStartLinks() {
            
            for ($i = 1; $i <= 3; $i++) {
                $this->addLink($i); 
            }
            
            return $this;
            
        }
        
        /**
         * Build the last few links
         * @since Version 3.10.0
         * @return \Railpage\Pagination
         */
        
        private function buildEndLinks() {
            
            for ($i = $this->getParam("total_pages") - 2; $i <= $this->getParam("total_pages"); $i++) {
                $this->addLink($i); 
            }
            
            return $this;
            
        }
        
        /**
         * Add the current page if it's not already in the array, and mark it as current
         * @since Version 3.10.0
         * @return \Railpage\Pagination
         */
        
        private function buildCurrentPage() {
            
            $links = $this->getParam("links"); 
            $current_page = $this->getParam("current_page"); 
            
            $past = false;
            
            foreach ($links as $i => $link) {
                if ($i == $current_page) {
                    $this->addLink($current_page, true); 
                    break;
                }
                
                if ($i > $current_page) {
                    $past = true;
                    for ($x = $current_page -1; $x <= $current_page + 1; $x++) {
                        $this->addLink($x, $x == $current_page); 
                    }
                    
                    $links = $this->getParam("links"); 
                    ksort($links); 
                    $this->setParam("links", $links);
                    
                    break;
                }
            }
            
            return $this;
            
        }
        
        /**
         * Add dividers between page number blocks
         * @since Version 3.10.0
         * @return \Railpage\Pagination
         */
        
        private function addDividers() {
            
            $links = $this->getParam("links");
            
            end($links); 
            $last = key($links); 
            
            foreach ($links as $i => $link) {
                
                if ($i == $last) {
                    continue;
                }
                
                if (!isset($links[$i + 1])) {
                    $links[$i + 1] = [
                        "text" => "...",
                        "current" => false,
                        "class" => "other"
                    ];
                }
                
            }
            
            ksort($links);
            
            $this->setParam("links", $links); 
            
            return $this;
            
        }
        
        /**
         * Add navigation (left, right) elements
         * @since Version 3.10.0
         * @return \Railpage\Pagniation
         */
        
        private function addNavigation() {
            
            $links = $this->getParam("links"); 
            $current = $this->getParam("current_page");
            $first = key($links); 
            
            end($links); 
            $last = key($links); 
            
            if ($current !== $first) {
                
                $links[$first - 1] = [
                    "href" => sprintf($this->getParam("url_format"), $current - 1),
                    "text" => $this->getParam("nav_left"),
                    "current" => false,
                    "class" => "navigation"
                ];
            }
            
            if ($this->getParam("current_page") !== $last) {
                $links[$last + 1] = [
                    "href" => sprintf($this->getParam("url_format"), $current + 1),
                    "text" => $this->getParam("nav_right"),
                    "current" => false,
                    "class" => "navigation"
                ];
            }
            
            ksort($links);
            
            $this->setParam("links", $links); 
            
            return $this;
            
        }
        
        /**
         * Echo the created HTML
         * @since Version 3.10.0
         * @return string
         * @todo Caching? Speed up the generation of this crap?
         */
        
        public function __toString() {
            
            $this->validate();
            
            $this->buildStartLinks()
                 ->buildEndLinks()
                 ->buildCurrentPage()
                 ->addDividers()
                 ->addNavigation(); 
            
            $tpl = $this->Smarty->ResolveTemplate($this->params['template']);
            
            $caching = false;
            if ($this->Smarty->cache) {
                $caching = true; 
                $this->Smarty->cache = false;
                $this->Smarty->clearCache($tpl);
            }
            
            $this->Smarty->Assign("pagination", $this->getParam("links")); 
            
            $html = $this->Smarty->Fetch($tpl); 
            
            if ($caching) {
                $this->Smarty->cache = true;
            }
            
            return $html;
            
        }
        
        
    }