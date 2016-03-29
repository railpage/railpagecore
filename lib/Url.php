<?php

/**
 * URL class
 * Provide links to various aspects (SELF, UPDATE, whatever) while retaining a __toString() function for older code
 * Also provide basic URL lookup functions, eg oEmbed fetch
 *
 * @since   Version 3.8.7
 * @package Railpage
 * @author  Michael Greenhill
 */

namespace Railpage;

use Railpage\fwlink;
use Railpage\Debug;
use Exception;
use GuzzleHttp\Client;
use phpQuery;

/**
 * URLs
 */
class Url {

    /**
     * Default URL
     *
     * @since Version 3.8.7
     * @var string $url
     */

    public $url;

    /**
     * Constructor
     *
     * @since Version 3.8.7
     *
     * @param string $default_url
     */

    public function __construct($default_url = false) {

        Debug::RecordInstance();

        $timer = Debug::getTimer();

        if ($default_url !== false) {

            $this->url = $default_url;

            $fwlink = new fwlink($this->url);
            $this->short = $fwlink->url_short;

            /**
             * Create the canonical link
             */

            $rp_host = defined("RP_HOST") ? RP_HOST : "www.railpage.com.au";
            $rp_root = defined("RP_WEB_ROOT") ? RP_WEB_ROOT : "";

            if (substr($this->url, 0, 4) == "http") {
                $this->canonical = $this->url;
            } else {
                $this->canonical = sprintf("http://%s%s%s", $rp_host, $rp_root, $this->url);
            }

        }

        Debug::logEvent(__METHOD__, $timer);

    }

    /**
     * Return the default URL
     *
     * @return string
     */

    public function __toString() {

        return $this->url;

    }

    /**
     * Get the list of URLs as an associative array
     *
     * @since Version 3.8.7
     * @return array
     */

    public function getURLs() {

        return get_object_vars($this);

    }

    /**
     * Get oEmbed content from a given URL, and cache it in Memcached for better performance
     *
     * @since Version 3.10.0
     *
     * @param string $url
     *
     * @return array
     * @throws \Exception if the HTTP response code from the oEmbed source is not 200 (eg 404 or 503)
     */

    public static function oEmbedLookup($url) {

        $Cache = AppCore::getMemcached();

        $cachekey = sprintf("railpage:oembed=%s", md5($url));

        if ($result = $Cache->fetch($cachekey)) {
            return $result;
        }

        $GuzzleClient = new Client;

        $response = $GuzzleClient->get($url);

        if ($response->getStatusCode() != 200) {
            throw new Exception("Could not fetch oEmbed content from " . $url . " - server responded with " . $response->getStatusCode() . " HTTP code");
        }

        $body = $response->getBody();

        // Try a JSON conversion
        if ($rs = json_decode($body, true)) {
            $body = $rs;
        }

        $Cache->save($cachekey, $body, 3600 * 168); // save for 1 week

        return $body;

    }
    
    /**
     * Take an A element and update it to open in a new tab if it's not a Railpage URL
     * @since Version 3.10.0
     * @param \DOMElement|string $e Either a DOMElement created via phpQuery or an entire 
     *                              block of text. If the latter, function will auto 
     *                              loop through all found A elements
     * @return \DOMElement
     */
    
    public static function offsiteUrl($e) {
        
        if (is_string($e)) {
            $string = phpQuery::newDocumentHTML($e);
            
            foreach (pq('a') as $z) {
                $z = self::offsiteUrl($z); 
            }
            
            return $string->__toString();
        }
        
        $dst = pq($e)->attr("href"); 
        
        if (!(substr($dst, 0, 1) == "/" || !preg_match("/http(s?):\/\/(www.|angad.|dev.)?railpage.com.au/", $dst))) {
            return $e;
        }
        
        pq($e)->attr("target", "_blank"); 
        
        return $e; 
        
    }
    
}
