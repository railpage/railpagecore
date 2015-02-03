<?php
	/**
	 * Abstract RSS Scraper (because all "RSS" is not a standard)
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\RSS;
	
	use DOMDocument;
	use DateTime;
	use DateTimeZone;
	use DateInterval;
	use Guzzle\Http\Client;
	use Railpage\AppCore;
	use Railpage\Url;
	use Railpage\Module;
	
	class Consume extends AppCore {
		
		/**
		 * RSS scraper user agent
		 * @since Version 3.9.1
		 * @const string SCRAPER_AGENT
		 */
		
		const SCRAPER_AGENT = "Railpage/Railpage";
		
		/**
		 * RSS scraper user agent version
		 * @since Version 3.9.1
		 * @const string SCRAPER_VERSION
		 */
		
		const SCRAPER_VERSION = "0.1";
		
		/**
		 * Feed name
		 * @since Version 3.9.1
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Feed URL
		 * @since Version 3.9.1
		 * @var array $feeds
		 */
		
		private $feeds = array();
		
		/**
		 * Guzzle HTTP client
		 * @since Version 3.9.1
		 * @var object $GuzzleClient
		 */
		
		private $GuzzleClient;
		
		/**
		 * Constructor
		 * @since Version 3.9.1
		 * @param string $url
		 */
		
		public function __construct($url = false) {
			
			parent::__construct(); 
			
			$this->GuzzleClient = new Client;
			
			if ($url) {
				$this->addFeed($url);
			}
			
		}
		
		/**
		 * Add an RSS feed to this scraper
		 * @since Version 3.9.1
		 * @var string $url
		 * @return \Railpage\RSS\Consume
		 */
		
		public function addFeed($url = false) {
			if (!in_array($url, array_keys($this->feeds))) {
				$this->feeds[$url] = array(
					"url" => $url,
					"name" => "",
					"scraped" => "",
					"body" => "",
					"items" => array()
				);
			}
			
			return $this;
		}
		
		/**
		 * Scrape the RSS feed
		 * @since Version 3.9.1
		 * @return \Railpage\RSS\Consume
		 */
		
		public function scrape() {
			foreach ($this->feeds as $key => $feed) {
				$request = $this->GuzzleClient->get(
					$feed['url'], 
					array(
						"User-Agent" => self::SCRAPER_AGENT . ' v.' . self::SCRAPER_VERSION
					)
				);
				
				$response = $request->send(); 
				
				if (!$response->isSuccessful()) {
					throw new Exception(sprintf("Failed to scrape RSS feed %s: Error %s", $feed['url'], $response->getStatusCode()));
				}
				
				$this->feeds[$key]['body'] = $response->getBody(); 
			}
			
			return $this;
		}
		
		/**
		 * Parse through the scraped content and structure it
		 * @since Version 3.9.1
		 * @return \Railpage\RSS\Consume
		 */
		
		public function parse() {
			if (empty($this->feeds)) {
				throw new Exception("No scraped RSS data was found (hint: Consume::scrape())");
			}
			
			$items = array(); 
			
			foreach ($this->feeds as $key => $feed) {
				$rss = new DOMDocument;
				$rss->loadXML($feed['body']);
				
				foreach ($rss->getElementsByTagName("item") as $node) {
					
					$date = $node->getElementsByTagName("date")->length > 0 ? $node->getElementsByTagName("date")->item(0)->nodeValue : $node->getElementsByTagName("pubDate")->item(0)->nodeValue;
					
					$item = array(
						"id" => $node->getElementsByTagName("link")->item(0)->nodeValue,
						"title" => $node->getElementsByTagName("title")->item(0)->nodeValue,
						"desc" => $node->getElementsByTagName("description")->item(0)->nodeValue,
						"link" => $node->getElementsByTagName("link")->item(0)->nodeValue,
						"date" => $date
					);
					
					/**
					 * Look for encoded descriptions
					 */
					
					$nodealias = array(
						"content" => "desc"
					);
					
					if ($node->getElementsByTagNameNS("http://purl.org/rss/1.0/modules/content/", "encoded")->length > 0) {
						foreach ($node->getElementsByTagNameNS("http://purl.org/rss/1.0/modules/content/", "encoded") as $nodens) {
							if (isset($nodealias[$nodens->prefix])) {
								$item[$nodealias[$nodens->prefix]] = $nodens->nodeValue;
							} else {
								$item[$nodens->prefix] = $nodens->nodeValue;
							}
						}
					}
					
					/**
					 * Process / tidy up the feed item
					 */
					
					$item = $this->process($item);
					
					/**
					 * Add this item to the array of processed items
					 */
					
					$this->feeds[$key]['items'][] = $item;
				}
			}
			
			return $this;
		}
		
		/**
		 * Process / tidy up a feed item
		 * @since Version 3.9.1
		 * @param array $item
		 * @return array
		 */
		
		private function process(array $item) {
			
			/**
			 * Process the desc field
			 */
			
			$Doc = new DOMDocument;
			@$Doc->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $item['desc']); # UTF-8 hinting from http://stackoverflow.com/a/11310258/319922
			
			/**
			 * Remove redundant line breaks from within P elements - @blame YarraTrams
			 */
			
			if ($Doc->getElementsByTagName("p")->length > 0) {
				foreach ($Doc->getElementsByTagName("p") as $node) {
					$node->nodeValue = htmlentities(str_replace("\n", " ", str_replace("\r\n", " ", $node->nodeValue))); # Without htmlentities() DOMDocument whinges and bitches something unforgivable
					
					# Drop empty nodes
					if (empty(trim($node->nodeValue))) {
						$parent = $node->parentNode;
						$parent->removeChild($node);
					}
				}
			}
			
			/**
			 * Remove redundant line breaks from within LI elements - @blame YarraTrams
			 */
			
			if ($Doc->getElementsByTagName("li")->length > 0) {
				foreach ($Doc->getElementsByTagName("li") as $node) {
					$node->nodeValue = htmlentities(str_replace("\n", " ", str_replace("\r\n", " ", $node->nodeValue))); # Without htmlentities() DOMDocument whinges and bitches something unforgivable
					
					# Drop empty nodes
					if (empty(trim($node->nodeValue))) {
						$parent = $node->parentNode;
						$parent->removeChild($node);
					}
				}
			}
			
			/**
			 * Fix images with a relative URL - @blame YarraTrams
			 */
			
			if ($Doc->getElementsByTagName("img")->length > 0) {
				foreach ($Doc->getElementsByTagName("img") as $node) {
					$src = $node->getAttribute("src");
					
					if (substr($src, 0, 1) == "/") {
						$url = parse_url($item['link']);
						$src = sprintf("%s://%s%s", $url['scheme'], $url['host'], $src); 
						$node->setAttribute("src", $src);
					}
					
					#$parent = $node->parentNode;
					#$node->parentNode->replaceChild($Doc->createTextNode(sprintf("[img]%s[/img]", $src)), $node);
				}
			}
			
			/**
			 * Remove SCRIPT elements - @blame YarraTrams
			 */
			
			if ($Doc->getElementsByTagName("script")->length > 0) {
				foreach ($Doc->getElementsByTagName("script") as $node) {
					$parent = $node->parentNode;
					$parent->removeChild($node);
				}
			}
			
			/** 
			 * Get the updated HTML
			 */
			
			$item['desc'] = $Doc->saveHTML();
			
			#printArray($item['desc']);
			
			/**
			 * Remove all HTML tags except those we want to keep
			 */
			
			$item['desc'] = trim(strip_tags($item['desc'], "<img><a><ul><li><ol><table><thead><tbody><tfoot><tr><th><td>"));
			#$item['desc'] = html_entity_decode($item['desc']);
			
			return $item;
		}
		
		/**
		 * Get the scraped and processed RSS feed
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getFeeds() {
			return $this->feeds; 
		}
	}
	