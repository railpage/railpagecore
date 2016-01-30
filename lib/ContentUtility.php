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
    
    class ContentUtility {
        
        /**
         * Format URL slugs for consistency
         * @since Version 3.9.1
         * @param string $text
         * @param int $maxlength
         * @return string
         */
        
        static public function generateUrlSlug($text, $maxlength = 200) {
            $find = array(
                "(",
                ")",
                "-",
                "?",
                "!",
                "#",
                "$",
                "%",
                "^",
                "&",
                "*",
                "+",
                "=",
                "'",
                "\""
            );
            
            $replace = array_fill(0, count($find), ""); 
            
            $text = str_replace($find, $replace, strtolower(trim($text)));
                
            $text = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', trim($text)));
            $text = substr($text, 0, $maxlength); 
            
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
        
        static public function relativeTime($timestamp, $now = false, $format = false) {
            
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
         * @param \DateTime $Start
         * @param \DateTime $End
         * @return string
         */
        
        public static function getDateDifference($Start, $End) {
            
            $age = $Start->diff($End)->format("%Y year");
            
            if ($Start->diff($End)->format("%Y") > 1) {
                $age .= "s";
            }
            
            if ($Start->diff($End)->format("%M") > 0) {
                $age .= $Start->diff($End)->format(" and %m month");
            
                if ($Start->diff($End)->format("%m") > 1) {
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
        
        public static function FixJSONEncode_UTF8($json) {
            
            $json = array_map(function($row) {
                if (!is_array($row)) {
                    return iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($row)); 
                }
                
                return array_map(function($sub) {
                    if (!is_array($sub)) {
                        return iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($sub)); 
                    } 
                }, $row); 
            }, $json);
            
            /*
            foreach ($json as $key => $val) {
                if (!is_array($val)) {
                    $json[$key] = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($val));
                } else {
                    foreach ($json[$key][$val] as $k => $v) {
                        if (!is_array($v)) {
                            $json[$key][$val][$k] = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($v));
                        }
                    }
                }
            }
            */
            
            $json = json_encode($json);
            
            return $json;
            
        }
        
        /**
         * Currency converter
         * @since Version 3.9.1
         * @param int $amount
         * @param \DateTime $Date
         * @return int
         */
        
        public static function convertCurrency($amount, $Date = false) {
            
            if (!$Date instanceof DateTime) {
                return;
            }
            
            $Client = new Client;
            
            if ($Date->format("Y") <= 1966) {
            
                $lookup = "#calculatedAnnualDollarValue";
                
                $url = "http://www.rba.gov.au/calculator/annualPreDecimal.html";
                $data = [
                    "body" => [ 
                        "annualPound" => $amount,
                        "annualStartYear" => $Date->format("Y"),
                        "annualEndYear" => (date("Y") - 1)
                    ]
                ];
            } else {
                $url = "http://www.rba.gov.au/calculator/annualDecimal.html";
                
                $data = [
                    "body" => [
                        "annualDollar" => $amount,
                        "annualStartYear" => $Date->format("Y"),
                        "annualEndYear" => (date("Y") - 1)
                    ]
                ];
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
         * @param array $parsed_url
         * @return string
         */
        
        public static function unparse_url($parsed_url) { 
        
            if (is_array($parsed_url['query'])) {
                $parsed_url['query'] = implode("&", $parsed_url['query']); 
            }
            
            $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
            $host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
            $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
            $user     = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
            $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
            $pass     = ($user || $pass) ? $pass . "@" : ''; 
            $path     = isset($parsed_url['path']) ? $parsed_url['path'] : ''; 
            $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
            $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
            
            return $scheme . $user . $pass . $host . $port . $path . $query . $fragment; 
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