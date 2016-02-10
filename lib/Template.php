<?php

/**
 * Templating engine
 * Simple wrapper for Smarty, in this case allowing for subthemes (eg handheld, kiosk, etc)
 * @since Version 3.0
 * @version 3.8.7
 * @author Michael Greenhill
 * @copyright Copyright (c) 2011 Michael Greenhill
 * @package Railpage
 */

namespace Railpage;
use Smarty;
use stdClass;
use Exception;
use Railpage\Users\User;
use Railpage\Jobs\Jobs;

/**
 * Railpage customised wrapper for Smarty
 * @since Version 3.0
 */

class Template extends Smarty {
    
    /**
     * Sub theme - eg handheld
     * @var string $subtheme
     * @since Version 3.0.1
     * @version 3.0.1
     */
    
    public $subtheme;
    
    /**
     * User selected theme
     * Used to check for customised theme files for modules etc
     * @var string $user_theme
     * @since Version 3.1
     * @version 3.1
     */
    
    public $user_theme;
    
    /**
     * Site root
     * @var string $site_root
     * @since Version 3.1
     * @version 3.1
     */
    
    public $site_root;
    
    /**
     * Head tags to add to the output
     * @since Version 3.8
     * @var array $head_tags
     */
    
    public $head_tags = array();
    
    /**
     * Stylesheets to add to the output
     * @since Version 3.8.7
     * @var array $stylesheets
     */
    
    public $stylesheets = array();
    
    /**
     * Meta tags to add to the output
     * @since Version 3.8
     * @var array $meta_tags
     */
    
    public $rp_meta_tags = array();
    
    /**
     * Links to add to the <head> section
     * @since Version 3.8.6
     * @var array $head_links
     */
    
    public $head_links = array();
    
    /**
     * Prerender/prefetch links
     * @since Version 3.8.7
     * @var array $preload
     */
    
    public $preload = array("prerender" => array(), "prefetch" => array());
    
    /**
     * User object
     * @since Version 3.8.7
     * @var object $User
     */
    
    private $User;
    
    /**
     * Site preferences and settings
     * @since Version 3.8.7
     * @var object $RailpageConfig
     */
    
    private $RailpageConfig;
    
    /**
     * Populate the user object
     * @since Version 3.8.7
     * @param \Railpage\Users\user $User
     */
    
    public function setUser(User $User) {
        $this->User = $User;
    }
    
    /**
     * Populate the site config/settings object
     * @since Version 3.8.7
     * @param \stdClass $RailpageConfig
     */
    
    public function setRailpageConfig(stdClass $RailpageConfig) {
        $this->RailpageConfig = $RailpageConfig;
    }
    
    /**
     * Add a head tag
     * @since Version 3.8
     * @param string $tag
     */
    
    public function addHeadTag($tag = false) {
        if (!empty($tag) && $tag) {
            $this->head_tags[] = $tag; 
        }
    }
    
    /**
     * Add a stylesheet
     * @since Version 3.8.7
     * @param string $stylesheet
     * @param string $media
     */
    
    public function addStylesheet($stylesheet = null, $media = "all") {
        
        if ($stylesheet == null) {
            return;
        }
        
        $this->stylesheets[] = array(
            "href" => $stylesheet,
            "rel" => "stylesheet", 
            "media" => $media
        );
        
    }
    
    /**
     * Reset stylesheets
     * @since Version 3.9.1
     * @return void
     */
    
    public function resetStylesheets() {
        $this->stylesheets = array(); 
        
        $this->addStylesheet("https://fonts.googleapis.com/css?family=Roboto:400,400italic,500,500italic,900,900italic|Droid+Sans|Ubuntu:300,400,300italic,400italic|Yanone+Kaffeesatz:300");
        $this->addStylesheet("https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css");
        
    }
    
    /**
     * Add an OpenGraph tag
     * @since Version 3.8
     * @param string $property
     * @param string $content
     */
    
    public function addOpenGraphTag($property = null, $content = null) {
        return $this->addMetaTag($property, $content);
    }
    
    /**
     * Add a meta tag
     * @since Version 3.8
     * @param string $property
     * @param string $content
     */
    
    public function addMetaTag($property = null, $content = null) {
        
        if ($property == null || $content == null) {
            return;
        }
        
        $this->rp_meta_tags[$property] = $content;
        
    }
    
    /**
     * Add a meta tag
     * @since Version 3.8
     * @param string $rel
     * @param string $href
     */
    
    public function addMetaLink($rel = null, $href = null) {
        
        if ($rel == null || $href == null) {
            return;
        }
        
        $this->head_links[$rel] = $href;
    }
    
    /**
     * Return additional head tags in a concatenated string
     * @since Version 3.8 
     * @return string
     */
    
    public function getHeadTags() {
        $tags = array(); 
        
        if (count($this->rp_meta_tags)) {
            
            foreach ($this->rp_meta_tags as $property => $content) {
                $tag = '<meta property="' . $property . '" content="' . htmlentities(format_post($content, false, false, false, false, true)) . '">';
                
                if ($property == "og:image" || $property == "twitter:image") {
                    $tag = str_replace("&amp;", "&", $tag);
                }
                
                $tags[] = $tag;
            }
        }
        
        if (count($this->head_links)) {
            foreach ($this->head_links as $rel => $href) {
                $tag = '<link rel="' . $rel . '" href="' . htmlentities($href) . '">';
                
                $tags[] = $tag;
            }
        }
        
        if (count($this->preload['prefetch'])) {
            foreach ($this->preload['prefetch'] as $href) {
                $tag = '<link rel="prefetch" href="' . htmlentities($href) . '">';
                
                $tags[] = $tag;
            }
        }
        
        if (count($this->preload['prerender'])) {
            foreach ($this->preload['prerender'] as $href) {
                $tag = '<link rel="prerender" href="' . htmlentities($href) . '">';
                
                $tags[] = $tag;
            }
        }
        
        return implode("\n\t", array_merge($tags, $this->head_tags)); 
    }
    
    /**
     * Return additional stylesheets in a concatenated string
     * @since Version 3.8 .7
     * @return string
     */
    
    public function getStylesheets() {
        $tags = array(); 
        
        $minify = array(); 
        
        if (count($this->stylesheets) === 0) {
            return "";
        }
        
        foreach ($this->stylesheets as $data) {
            if (substr($data['href'], 0, 4) == "http") {
                $tags[] = sprintf("<link href='%s' rel='%s' media='%s'>", $data['href'], $data['rel'], $data['media']);
            } else {
                
                if (file_exists(RP_SITE_ROOT . str_replace(".css", ".min.css", $data['href']))) {
                    $data['href'] = str_replace(".css", ".min.css", $data['href']);
                }
                
                $str = substr($data['href'], 0, 1) == "/" ? substr($data['href'], 1) : $data['href'];
                $str = explode("?v=", $str);
                
                if (!in_array($str[0], $minify)) {
                    $minify[] = $str[0];
                }
            }
        }
        
        if (count($minify)) {
            foreach ($minify as $k => $css) {
                if (strpos($css, "style-smooth")) {
                    unset($minify[$k]);
                    $minify[] = $css;
                }
            }
            
            $minify = implode(",", $minify);
            $tags[] = sprintf("<link href='//static.railpage.com.au/m.php?f=%s&v=%s' rel='stylesheet' media='all'>", $minify, RP_VERSION);
        }
        
        return implode("\n\t", $tags); 
    }
    
    /**
     * Display a template file
     * This function checks for the existance of a subtheme - if it's set, and a subtheme template is found, it loads that instead
     * @param string $template
     * @param int $cache_id
     * @param int $compile_id
     * @param int $parent
     * @version 3.1
     * @since Version 3.0
     */
    
    function display($template = null, $cache_id = null, $compile_id = null, $parent = null) {
    
        if (RP_DEBUG) {
            global $site_debug;
            $debug_timer_start = microtime(true);
        }
    
        $pathinfo = pathinfo($template); 
        #return parent::display($template, $cache_id, $compile_id, $parent); 
        
        // Check for theme template
        $theme_file = $this->site_root . DS . "themes" . DS . $this->user_theme . DS . "html" . DS . str_replace($this->site_root, "", $template); #substr($template, strlen($this->site_root));
        
        if (!$this->subtheme && file_exists($theme_file) && !strstr($template, $this->user_theme)) {
            $template = $theme_file;
        }
        
        // Check for mobile file
        if ($this->subtheme && !is_null($template)) {
            
            $subtpl = str_replace(".tpl", "-" . $this->subtheme . ".tpl", $template); 
            
            if (parent::templateExists($subtpl)) {
                $template = $subtpl;
            }
        }
        
        $return = parent::display($template, $cache_id, $compile_id, $parent); 
    
        if (RP_DEBUG) {
            $site_debug[] = sprintf("%s::%s (%s) completed in %ds", __CLASS__, __FUNCTION__, $template, round(microtime(true) - $debug_timer_start, 5));
        }
        
        return $return;
    }
    
    /**
     * List site themes
     * @since Version 3.1
     * @version 3.1
     * @return array
     */
    
    public function available_themes() {
        $theme_dir = dirname(__DIR__) . DS . "themes";
        
        $themes = array(); 
        
        if (is_dir($theme_dir)) {
            if ($handle = opendir($theme_dir)) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != "." && $entry != ".." && is_dir($theme_dir.$entry)) {
                        $themes[] = $entry;
                    }
                }
            }
        }
        
        natsort($themes);
        
        return $themes;
    }
    
    /**
     * Check if theme exists
     * @since Version 3.1
     * @version 3.1
     * @return boolean
     * @param string $theme
     */
    
    public function theme_exists($theme) {
        if (is_dir(dirname(__DIR__) . DS . "themes" . DS . $theme)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Draw an advertisement
     * @since Version 3.8.7
     * @return string
     */
    
    public function getAdvertisementHTML() {
        
        $showjobs = false;
        
        if ($showjobs && rand(0, 5) % 2) {
            
            $Job = (new Jobs)->getRandomJob(); 
            
            $this->assign("ad", array(
                "module" => array(
                    "url" => $Job->Module->url instanceof Url ? $Job->Module->url->url : $Job->Module->url,
                    "name" => $Job->Module->name,
                ),
                "url" => $Job->url->url,
                "title" => $Job->title,
                "text" => sprintf("%s (%s), expires %s", $Job->Organisation->name, $Job->Location->name, time2str($Job->expiry->getTimestamp()))
            ));
            
            return $this->fetch(RP_SITE_ROOT . DS . "content" . DS . "inc.ad.banner.custom.tpl");
        }
        
        if (!isset($this->User->preferences->showads) || $this->User->preferences->showads) {
            $this->assign("ad_header", $this->RailpageConfig->AdHeader);
        
            return $this->fetch(RP_SITE_ROOT . DS . "content" . DS . "inc.ad.banner.tpl");
        }
        
        return "";
    }
    
    /**
     * Get file path from a template filename
     * @since Version 3.8.7
     * @param string $template
     * @return string
     */
    
    public function resolveTemplate($template) {
        
        if (pathinfo($template, PATHINFO_EXTENSION) == "tpl" && file_exists($template)) {
            return $template;
        }
        
        $tpl = str_replace(".tpl", "", $template); 
        
        /**
         * Look through the backtrace
         */
        
        foreach (debug_backtrace() as $step) {
            if (!isset($step['file'])) {
                continue;
            }
            
            $dir = dirname($step['file']);
            
            if (!empty($this->subtheme)) {
                $prop = $dir . DS . "html" . DS . $tpl . "-" . $this->subtheme . ".tpl";
                
                if (file_exists($prop)) {
                    return $prop;
                }
            }
            
            $prop = $dir . DS . "html" . DS . $tpl . ".tpl";
            
            if (file_exists($prop)) {
                return $prop;
            }
        }
        
        /**
         * Look in the theme directory
         */
            
        if (!empty($this->subtheme)) {
            $prop = RP_SITE_ROOT . DS . "themes" . DS . $this->user_theme . DS . "html" . DS . $template . "-" . $this->subtheme . ".tpl";
            
            if (file_exists($prop)) {
                return $prop;
            }
        }
        
        $prop = RP_SITE_ROOT . DS . "themes" . DS . $this->user_theme . DS . "html" . DS . $template . ".tpl"; 
        
        if (file_exists($prop)) {
            return $prop;
        }
        
        /**
         * Look in the content directory
         */
            
        if (!empty($this->subtheme)) {
            $prop = RP_SITE_ROOT . DS . "content" . DS . $template . "-" . $this->subtheme . ".tpl";
            
            if (file_exists($prop)) {
                return $prop;
            }
        }
        
        $prop = RP_SITE_ROOT . DS . "content" . DS . $template . ".tpl";
        
        if (file_exists($prop)) {
            return $prop;
        }
        
        $prop = RP_SITE_ROOT . DS . "content" . DS . "email" . DS . $template . ".tpl";
        
        if (file_exists($prop)) {
            return $prop;
        }
        
        /**
         * Look in the etc directory for the core code
         */
        
        $prop = dirname(__DIR__) . DS . "etc" . DS . "templates" . DS . $template. ".tpl";
        
        if (file_exists($prop)) {
            return $prop;
        }
        
        throw new Exception("Cannot find a template file matching " . $template . " in any directories");
    }
    
    /**
     * Restart page generation
     * @since Version 3.8.7
     * @return $this
     */
    
    public function RestartPageGen() {
        
        ob_end_clean(); 
        
        ob_start("ob_pagetitle");
        
        $this->Display($this->ResolveTemplate("page_header"));
        $this->Display($this->ResolveTemplate("topmenu"));
        
        return $this;
    }
    
    /**
     * Prerender a page
     * @since Version 3.8.7
     * @param string $url
     * @param boolean $first Set to true if this needs to be prerendered first
     * @return $this;
     */
    
    public function prerender($url = null, $first = null) {
        
        if ($url =! null && !in_array($url, $this->preload['prerender'])) {
            if ($first != null) {
                array_unshift($this->preload['prerender'], $url); 
                return $this;
            }
            
            $this->preload['prerender'][] = $url;
            
        }
        
        return $this;
    }
    
    /**
     * Prefetch a page
     * @since Version 3.8.7
     * @param string $url
     * @param boolean $first Set to true if this needs to be prerendered first
     * @return $this;
     */
    
    public function prefetch($url = null, $first = null) {
        
        if ($url != null && !in_array($url, $this->preload['prefetch'])) {
            if ($first != null) {
                array_unshift($this->preload['prefetch'], $url);
                
                return $this;
            }
            
            $this->preload['prefetch'][] = $url;
            
        }
        
        return $this;
    }
}
