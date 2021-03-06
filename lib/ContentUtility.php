<?php

/**
 * A series of text formatting utilities to increase code decoupling
 * @since Version 3.9.1
 * @package Railpage
 * @author Michael Greenhill
 */

namespace Railpage;

use Railpage\Url;
use Railpage\Debug;
use DateTime;
use Exception;
use InvalidArgumentException;
use GuzzleHttp\Client;
use phpQuery;
use Railpage\Formatting\MultimediaUtility;
use Railpage\Formatting\EmoticonsUtility;
use Railpage\Formatting\Html;
use Railpage\Formatting\BbcodeUtility;
use Railpage\Formatting\MakeClickable;

class ContentUtility {
    
    /**
     * Text format parser version
     * Bump this when a change to formatText() has been made to bust previously rendered (and cached) text blocks
     * @since Version 3.10.0
     * @var float $formatTextVer
     */
    
    public static $formatTextVer = "0.1b"; 
    
    /**
     * Process the emoticons, BBCode, rich text, embedded content etc in this object and return as HTML
     * @since Version 3.10.0
     * @param string $string
     * @param array $options
     * @return string
     */
    
    public static function formatText($string, $options) {
        
        AppCore::getSmarty()->addStylesheet("/themes/jiffy_simple/style/opt.embedded.css"); 
        
        $defaultOptions = [
            "bbcode_uid" => "sausages",
            "flag_html" => true,
            "flag_bbcode" => true,
            "flag_emoticons" => true,
            "strip_formatting" => false,
            "editor_version" => 1
        ];
        
        $options = array_merge($defaultOptions, (is_array($options) ? $options : [])); 
        
        if (empty(trim($string))) { 
            throw new InvalidArgumentException("Cannot execute " . __METHOD__ . " as no text was provided!"); 
        }
        
        $cacheKey = "railpage:formatText=" . sha1((string) $string . json_encode($options)) . ";" . self::$formatTextVer; 
        $cacheProvider = AppCore::getMemcached(); 
        $processedText = false;
        
        if ($processedText = $cacheProvider->fetch($cacheKey)) {
            return $processedText; 
        }
        
        /**
         * @todo
         * - make clickable - doesn't seem to do anything? 24/03/2016
         * - convert to UTF8 - is this still required? 24/03/2016
         */
        
        $string = EmoticonsUtility::Process($string, $options['flag_emoticons']); 
        $string = Html::cleanupBadHtml($string, $options['editor_version']); 
        $string = Html::removeHeaders($string);
        $string = BbcodeUtility::Process($string, $options['flag_bbcode']); 
        $string = MultimediaUtility::Process($string); 
        $string = MakeClickable::Process($string); 
        $string = Url::offsiteUrl((string) $string); 
        
        if (is_object($string)) {
            $string = $string->__toString(); 
        }
        
        if ($options['strip_formatting'] == true) {
            $string = strip_tags($string); 
        }
        
        $rs = $cacheProvider->save($cacheKey, $string, 0); // 3600 * 24 * 30); 
        
        return $string; 
        
    }
    
    /**
     * Format URL slugs for consistency
     * @since Version 3.9.1
     * @param string $text
     * @param int $maxLength
     * @return string
     */
    
    public static function generateUrlSlug($text, $maxLength = 200) {
        
        $text = strtolower(trim($text)); 
        $text = preg_replace("/\s/u", "-", $text); 
        $text = preg_replace("/[^[:alnum:][:space:]-]/u", "$1", $text); 
        
        $text = substr($text, 0, $maxLength); 
        
        if (substr($text, -1) === "-") {
            $text = substr($text, 0, -1);
        }
        
        return $text;
        
    }
    
    /**
     * Take a DateTime instance, or unix timestamp, and convert it to a relative time (eg x minutes ago)
     * @since Version 3.9.1
     * @return string
     * @param \DateTime|int $timestamp
     * @param \DateTime|int $now
     * @param string $format
     */
    
    public static function relativeTime($timestamp, $now = null, $format = null) {
        
        if ($timestamp instanceof DateTime) {
            $timestamp = $timestamp->getTimestamp(); 
        }
        
        if ($now instanceof DateTime) {
            $now = $now->getTimestamp(); 
        }
        
        if (!filter_var($now, FILTER_VALIDATE_INT)) {
            $now = time();
        }
        
        $diff = $now - $timestamp;
        $format = $format; // to shut up CodeClimate
    
        if ($diff < 60) {
            return sprintf($diff > 1 ? '%s seconds ago' : 'a second ago', $diff);
        }
    
        $diff = floor($diff / 60);
    
        if ($diff < 60) {
            return sprintf($diff > 1 ? '%s minutes ago' : 'one minute ago', $diff);
        }
    
        $diff = floor($diff / 60);
    
        if ($diff < 24) {
            return sprintf($diff > 1 ? '%s hours ago' : 'an hour ago', $diff);
        }
    
        $diff = floor($diff / 24);
    
        if ($diff < 7) {
            return sprintf($diff > 1 ? '%s days ago' : 'yesterday', $diff);
        }
    
        if ($diff < 30) {
            $diff = floor($diff / 7);
            return sprintf($diff > 1 ? '%s weeks ago' : 'one week ago', $diff);
        }
    
        $diff = floor($diff / 30);
    
        if ($diff < 12) {
            return sprintf($diff > 1 ? '%s months ago' : 'last month', $diff);
        }
    
        $diff = date('Y', $now) - date('Y', $timestamp);
    
        return sprintf($diff > 1 ? '%s years ago' : 'last year', $diff);
        
    }
    
    /**
     * Get the difference between two dates, expressed as years, months, days
     * @since Version 3.9.1
     * @param \DateTime $dateStart
     * @param \DateTime $dateEnd
     * @return string
     */
    
    public static function getDateDifference($dateStart, $dateEnd) {
        
        $age = $dateStart->diff($dateEnd)->format("%Y year");
        
        if ($dateStart->diff($dateEnd)->format("%Y") > 1) {
            $age .= "s";
        }
        
        if ($dateStart->diff($dateEnd)->format("%M") > 0) {
            $age .= $dateStart->diff($dateEnd)->format(" and %m month");
        
            if ($dateStart->diff($dateEnd)->format("%m") > 1) {
                $age .= "s";
            }
        }
        
        return $age;
        
    }
    
    /**
     * Check if a URL exists
     * @since Version 3.9.1
     * @param string $url
     * @return boolean
     * @todo Make this less crap
     */
    
    public static function url_exists($url) {
        
        // Too slow
        return true;
        
        /*
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode == 404) {
            return false;
        }
        
        return true;
        
        $file_headers = @get_headers($url);
        if ($file_headers[0] == 'HTTP/1.1 404 Not Found') {
            return false;
        }
        
        return true;
        */
        
    }
    
    /**
     * Format a title
     * @since Version 3.9.1
     * @param string $text
     * @return $text
     */
    
    public static function FormatTitle($text = NULL) {
        
        if (is_null($text)) {
            return $text;
        }
        
        $timer = Debug::getTimer(); 
        Debug::RecordInstance(); 
        
        $text = htmlentities($text, ENT_COMPAT, "UTF-8");
        $text = str_replace("&trade;&trade;", "&trade;", $text);
        
        if (function_exists("html_entity_decode_utf8")) {
            $text = html_entity_decode_utf8($text);
        }
        
        $text = stripslashes($text);
        
        if (substr($text, 0, 4) == "Re: ") {
            $text = substr($text, 4, strlen($text));
        }
        
        if (substr($text, -1) == ".") {
            $text = substr($text, 0, -1);
        }
        
        Debug::logEvent(__METHOD__, $timer); 
        
        return $text;
        
    }
    
    /**
     * Fix JSON formatting
     * @since Version 3.9.1
     * @param string $json
     * @return array
     */
    
    public static function fixJSONEncode_UTF8($json) {
        
        $json = array_map(function ($row) {
            if (!is_array($row)) {
                return iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($row)); 
            }
            
            return array_map(function ($sub) {
                if (!is_array($sub)) {
                    return iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($sub)); 
                } 
            }, $row); 
        }, $json);
        
        $json = json_encode($json);
        
        return $json;
        
    }
    
    /**
     * Currency converter
     * @since Version 3.9.1
     * @param int $amount
     * @param \DateTime $dateObject
     * @return int
     */
    
    public static function convertCurrency($amount, $dateObject = null) {
        
        if (!$dateObject instanceof DateTime) {
            return;
        }
        
        $Client = new Client;
        
        $data = [
            "body" => [ 
                "annualStartYear" => $dateObject->format("Y"),
                "annualEndYear" => (date("Y") - 1)
            ]
        ];
        
        $lookup = "#calculatedAnnualDollarValue";
        $url = $url = "http://www.rba.gov.au/calculator/";
        
        if ($dateObject->format("Y") <= 1966) {
            $url .= "annualPreDecimal.html";
            $data['body']['annualPound'] = $amount;
        }
        
        if ($dateObject->format("Y") > 1966) {
            $url .= "annualDecimal.html";
            $data['body']['annualDollar'] = $amount;
        }
        
        $response = $Client->post($url, $data); 
        $html = $response->getBody();
        
        $doc = phpQuery::newDocumentHTML($html);
        
        phpQuery::selectDocument($doc);
        
        foreach (pq($lookup) as $e) {
            $cost = pq($e)->attr("value"); 
            
            if (!empty($cost)) {
                return str_replace(",", "", $cost);
            }
        }
        
        return false;
        
    }
    
    /**
     * Un-parse a URL - reverse of parse_url()
     * @since Version 3.10.0
     * @param array $parsedUrl
     * @return string
     */
    
    public static function unparse_url($parsedUrl) { 
    
        if (is_array($parsedUrl['query'])) {
            $parsedUrl['query'] = implode("&", $parsedUrl['query']); 
        }
        
        $defaults = [
            "scheme" => "",
            "host" => "",
            "port" => "",
            "user" => "",
            "pass" => "",
            "path" => "",
            "query" => "",
            "fragment" => ""
        ];
        
        $parsedUrl = array_merge($defaults, $parsedUrl); 
        
        $scheme   = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : ''; 
        #$host     = isset($parsedUrl['host']) ? $parsedUrl['host'] : ''; 
        $port     = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : ''; 
        #$user     = isset($parsedUrl['user']) ? $parsedUrl['user'] : ''; 
        $pass     = isset($parsedUrl['pass']) ? ':' . $parsedUrl['pass']  : ''; 
        $pass     = ($parsedUrl['user'] || $pass) ? $pass . "@" : ''; 
        #$path     = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
        $query    = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : ''; 
        $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : ''; 
        
        return $scheme . $parsedUrl['user'] . $pass . $parsedUrl['host'] . $port . $parsedUrl['path'] . $query . $fragment; 
    }
    
    /**
     * Get OpenGraph tags from a specified URL
     * Really stupid and elaborate Memcached expiry handling is due to a bug in Debian's PHP5-Memcached package
     *
     * @since Version 3.10.0
     * @param string $url
     * @return array
     */
    
    public static function GetOpenGraphTags($url) {
        
        $Memcached = AppCore::GetMemcached(); 
        
        $mckey = md5($url); 
        
        if ($result = $Memcached->fetch($mckey)) {
            $exp = $Memcached->fetch(sprintf("%s-exp", $mckey)); 
            
            if ($exp < time()) {
                $Memcached->delete($mckey); 
                $Memcached->delete(sprintf("%s-exp", $mckey)); 
                $result = false; 
            }
        }
        
        if (!$result) {
        
            /**
             * Ensure our OG handler is loaded
             */
            
            require_once("vendor" . DS . "scottmac" . DS . "opengraph" . DS . "OpenGraph.php");
            $graph = \OpenGraph::fetch($url);
            
            $result = array();
            
            foreach ($graph as $key => $value) {
                $result[$key] = $value;
            }
            
            $Memcached->save($mckey, $result, 0); // 0 or will not cache
            $Memcached->save(sprintf("%s-exp", $mckey), strtotime("+1 day"), 0); // alternate method of specifying expiry
            
        }
        
        return $result;
        
    }

}