<?php
	/**
	 * URL class
	 * Provide links to various aspects (SELF, UPDATE, whatever) while retaining a __toString() function for older code
	 * Also provide basic URL lookup functions, eg oEmbed fetch
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage;
	
	use Railpage\fwlink;
	use Railpage\Debug;
	use Exception;
	use GuzzleHttp\Client;
	
	/**
	 * URLs
	 */
	
	class Url {
		
		/**
		 * Default URL
		 * @since Version 3.8.7
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
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
		 * @return string
		 */
		
		public function __toString() {
			
			return $this->url;
			
		}
		
		/**
		 * Get the list of URLs as an associative array
		 * @since Version 3.8.7
		 * @return array
		 */
		
		public function getURLs() {
			
			return get_object_vars($this);
			
		}
		
		/**
		 * Get oEmbed content from a given URL, and cache it in Memcached for better performance
		 * @since Version 3.10.0
		 * @param string $url
		 * @return array
		 */
		
		public static function oEmbedLookup($url) {
			
			$Cache = AppCore::GetRedis(); 
			
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
			
			$Cache->save($cachekey, $body, strtotime("+1 month")); 
			
			return $body;
			
		}
	}
	