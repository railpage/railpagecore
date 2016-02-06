<?php

/**
 * Module detail object
 *
 * @since   Version 3.8.7
 * @package Railpage
 * @author  Michael Greenhill
 */

namespace Railpage;

use DateTime;
use Exception;
use stdClass;
use Railpage\Users\User;

if (!defined("RP_SITE_ROOT")) {
    define("RP_SITE_ROOT", "");
}

/**
 * Railpage module information
 *
 * @since Version 3.8.7
 */
class Module extends AppCore {

    /**
     * Name
     *
     * @since Version 3.8.7
     * @var string $name
     */

    public $name;

    /**
     * URL of this module
     *
     * @since Version 3.8.7
     * @var string $url
     */

    public $url;

    /**
     * Module namespace
     *
     * @since Version 3.8.7
     * @var string $namespace ;
     */

    public $namespace;

    /**
     * Module file paths
     *
     * @since Version 3.8.7
     * @var string $Paths
     */

    public $Paths;

    /**
     * Colour palette
     *
     * @since Version 3.8.7
     * @var \stdClass $Colours
     */

    public $Colours;

    /**
     * Constructor
     *
     * @since Version 3.8.7
     *
     * @param string $module
     */

    public function __construct($module) {

        parent::__construct();

        if (!is_string($module)) {
            return;
        }
        
        $this->Colours = new stdClass;
        $this->Colours->primary = "#666";
        $this->Colours->accent = "#ddd";
        $this->Colours->inverse = "#333";
        
        $module = self::getModuleAlternateName($module); 
        
        $this->name = ucwords($module);
        $this->url = self::getModuleUrl($module);
        
        if (self::getModuleId($module)) {
            $this->id = self::getModuleId($module); 
        }

        switch (strtolower($module)) {

            case "images.competitions" :
                $this->name = "Photo competitions";
                $this->namespace = "railpage.images.competitions";
                break;

            case "locations" :
                $this->Colours->primary = "#116416";
                $this->Colours->accent = "#54A759";
                $this->Colours->inverse = "#004304";
                break;

            case "locos" :
                $this->Colours->primary = "#3D0CE8";
                $this->Colours->accent = "#576BFF";
                $this->Colours->inverse = "#1B054E";
                break;

            case "news" :
                $this->Colours->primary = "#8A2E60";
                $this->Colours->accent = "#CE8AAF";
                $this->Colours->inverse = "#450026";
                break;

            case "privatemessages" :
                $this->name = "Private Messages";
                break;

        }

        /**
         * Lazy populate the namespace
         */

        if (empty( $this->namespace ) && !empty( $this->name )) {
            $this->namespace = sprintf("railpage.%s", strtolower($this->name));
        }

        /**
         * Lazy populate the URL
         */

        if (empty( $this->url ) && !empty( $this->name )) {
            $this->url = sprintf("modules.php?name=%s", $this->name);
        }

        /**
         * Create and populate the filesystem paths
         */

        $this->Paths = new stdClass;
        $this->Paths->module = sprintf("%s/modules/%s", RP_SITE_ROOT, $this->name);
        $this->Paths->html = sprintf("%s/modules/%s/html", RP_SITE_ROOT, $this->name);
    }
    
    /**
     * Get the proper module name from the list of alternates
     * @since Version 3.10.0
     * @param string $module
     * @return string
     */
    
    private static function getModuleAlternateName($module) {
        
        $alt_names = [
            "image" => "images",
            "orgs" => "organisations",
            "messages" => "privatemessages",
            "pm" => "privatemessages",
            "pms" => "privatemessages",
            "profiles" => "users",
        ];
        
        if (isset($alt_names[$module])) {
            return $alt_names[$module];
        }
        
        return $module;
        
    }
    
    /**
     * Get the module ID
     * @since Version 3.10.0
     * @param string $module
     * @return int
     */
    
    private static function getModuleId($module) {
        
        $module_ids = [
            "assets" => 110,
            "chronicle" => 118,
            "donations" => 108,
            "downloads" => 12,
            "events" => 119,
            "feedback" => 3,
            "flickr" => 72,
            "forums" => 22,
            "gallery" => 66,
            "glossary" => 112,
            "help" => 101,
            "home" => 98,
            "ideas" => 112,
            "images" => 120,
            "jobs" => 107,
            "links" => 76,
            "organisations" => 121,
            "place" => 122,
            "railcams" => 97,
            "search" => 8,
            "users" => 87,
            "reminders" => 112,
            "timetables" => 102,
            "images.competitions" => 120,
            "locations" => 68,
            "locos" => 86,
            "news" => 5,
            "privatemessages" => 6
        ];
        
        if (isset($module_ids[$module])) {
            return $module_ids[$module];
        }
        
        return;
        
    }
    
    /**
     * Get the module URL
     * @since Version 3.10.0
     * @param string $module
     * @return string
     */
    
    private static function getModuleUrl($module) {
        
        $alt_urls = [
            "users" => "/user",
            "forums" => "/f.htm",
            "organisations" => "/orgs",
            "privatemessages" => "/messages",
            "images.competitions" => "/gallery/comp",
        ];
        
        if (isset($alt_urls[$module])) {
            return $alt_urls[$module];
        }
        
        return "/" . $module;
        
    }

    /**
     * Get impressions on this module over a given date range
     *
     * @since Version 3.9.1
     * @return array
     *
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     */

    public function getImpressions(DateTime $dateFrom, DateTime $dateTo) {

        if (!is_object($this->db)) {
            $Registry = Registry::getInstance();
            $this->db = $Registry->get("db");
        }

        $query = "SELECT count(log_id) AS num, DATE_FORMAT(date, '%Y-%m-%d') AS date FROM log_useractivity WHERE module_id = ? AND date >= ? AND date <= ? GROUP BY DATE_FORMAT(date, '%Y-%m-%d')";

        $return = array();
        $params = [
            $this->id,
            $dateFrom->Format("Y-m-d 00:00:00"),
            $dateTo->Format("Y-m-d 23:59:59")
        ];

        foreach ($this->db->fetchAll($query, $params) as $row) {
            $return[$row['date']] = $row['num'];
        }

        return $return;
    }
    
    /**
     * Record user activity against this module
     * @since Version 3.10.0
     * @param \Railpage\Users\User $userObject
     * @param string $ip
     * @param string $url
     * @param string $pagetitle
     * @return \Railpage\Module
     */
    
    public function makeImpression(User $userObject, $ip, $url, $pagetitle) {
        
        $data = [
            "user_id" => is_null($userObject->id) ? 0 : $userObject->id,
            "ip" => $ip,
            "module_id" => $this->id,
            "url" => $url,
            "pagetitle" => $pagetitle
            
        ];
        
        $this->db->insert("log_useractivity", $data);
        
        return $this;
        
    }
}
