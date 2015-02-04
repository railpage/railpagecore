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
	use GuzzleHttp\Client;
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
					),
					array(
						"timeout" => 30,
						"connect_timeout" => 5
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
				
				#printArray($key);
				
				/**
				 * RSS
				 */
				
				if ($rss->getElementsByTagName("item")->length > 0) {
					foreach ($rss->getElementsByTagName("item") as $node) {
						
						$date = $node->getElementsByTagName("date")->length > 0 ? $node->getElementsByTagName("date")->item(0)->nodeValue : $node->getElementsByTagName("pubDate")->item(0)->nodeValue;
						
						$item = array(
							"id" => $node->getElementsByTagName("link")->item(0)->nodeValue,
							"title" => $node->getElementsByTagName("title")->item(0)->nodeValue,
							"desc" => $node->getElementsByTagName("description")->item(0)->nodeValue,
							"link" => $node->getElementsByTagName("link")->item(0)->nodeValue,
							"date" => $date,
							"tags" => $node->getElementsByTagName("link")->item(0)->nodeValue
						);
						
						/**
						 * Get the tags
						 */
						
						if ($node->getElementsByTagName("tag")->length > 0) {
							$tags = array(); 
							
							foreach ($node->getElementsByTagName("tag") as $tag) {
								$tags[] = $tag->nodeValue;
							}
							
							$item['tags'] = implode(",", $tags);
						}
						
						/**
						 * Get the category
						 */
						
						if ($node->getElementsByTagName("category")->length > 0) {
							$tags = array(); 
							
							foreach ($node->getElementsByTagName("category") as $tag) {
								$tags[] = $tag->nodeValue;
							}
							
							$item['tags'] = implode(",", $tags);
						}
						
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
						 * Get the item summary
						 */
						
						$item['summary'] = $this->createSummary($item['desc']);
						$item['body'] = $this->stripSummaryFromBody($item['summary'], $item['desc']);
						
						/**
						 * Add this item to the array of processed items
						 */
						
						$this->feeds[$key]['items'][] = $item;
					}
				}
			}
			
			/**
			 * Atom
			 */
			
			if ($rss->getElementsByTagName("item")->length == 0 && $rss->getElementsByTagName("entry")->length > 1) {
				foreach ($rss->getElementsByTagName("entry") as $node) {
					foreach ($node->getElementsByTagName("link") as $link) {
						if ($link->getAttribute("rel") == "alternate") {
							$link = $link->getAttribute("href");
							break;
						}
					}
					
					$item = array(
						"id" => $node->getElementsByTagName("id")->item(0)->nodeValue,
						"title" => $node->getElementsByTagName("title")->item(0)->nodeValue,
						"desc" => $node->getElementsByTagName("content")->item(0)->nodeValue,
						"link" => $link,
						"date" => $node->getElementsByTagName("updated")->item(0)->nodeValue,
						"tags" => $link
					);
						
					/**
					 * Get the tags
					 */
					
					if ($node->getElementsByTagName("tag")->length > 0) {
						$tags = array(); 
						
						foreach ($node->getElementsByTagName("tag") as $tag) {
							$tags[] = $tag->nodeValue;
						}
						
						$item['tags'] = implode(",", $tags);
					}
						
					/**
					 * Process / tidy up the feed item
					 */
					
					$item = $this->process($item);
					
					/**
					 * Get the item summary
					 */
					
					$item['summary'] = $this->createSummary($item['desc']);
					$item['body'] = $this->stripSummaryFromBody($item['summary'], $item['desc']);
					
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
			
			$item['desc'] = preg_replace('#<br\s*/?>#i', "\n", $item['desc']);
			
			$Doc = new DOMDocument;
			@$Doc->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $item['desc']); # UTF-8 hinting from http://stackoverflow.com/a/11310258/319922
			
			/**
			 * Remove P elements with BRs and convert to newlines - @blame RailwayGazette
			 */
			
			if ($Doc->getElementsByTagName("p")->length == 1) {
				$node = $Doc->getElementsByTagName("p")->item(0);
				$text = explode("\n", $node->nodeValue);
				$text = implode("\n\n", $text);
				
				$node->parentNode->replaceChild($Doc->createTextNode($text), $node);
			}
			
			/**
			 * Remove redundant line breaks from within P elements - @blame YarraTrams
			 */
			
			if ($Doc->getElementsByTagName("p")->length > 1) {
				foreach ($Doc->getElementsByTagName("p") as $node) {
					$node->nodeValue = htmlentities(str_replace("\n", " ", str_replace("\r\n", " ", $node->nodeValue))); # Without htmlentities() DOMDocument whinges and bitches something unforgivable
					
					$node->nodeValue = htmlentities(str_replace("&nbsp;", " ", $node->nodeValue));
					
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
			
			/**
			 * Remove all HTML tags except those we want to keep
			 */
			
			$item['desc'] = trim(strip_tags($item['desc'], "<img><a><ul><li><ol><table><thead><tbody><tfoot><tr><th><td>"));
			
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
		
		/**
		 * Create summary / lead 
		 * @since Version 3.9.1
		 * @return string
		 * @param string $str The desc field to extract the summary from
		 * @param int $n Maximum number of characters to return
		 * @param string $end_char
		 */
		
		private function createSummary($str, $n = 1024, $end_char = '&#8230;') {
			
			$str = strip_tags($str);
			
			$str = explode("\n", $str); 
			
			if (count($str) < 3) {
				return implode("\n\n", $str);
			}
			
			/**
			 * Remove empty lines
			 */
			
			foreach ($str as $k => $v) {
				if (trim($v) == "") {
					unset($str[$k]);
				} else {
					break;
				}
			}
			
			/**
			 * Loop through again until we have two lines of text
			 */
			
			$n = 0;
			$text = array();
			
			foreach ($str as $v) {
				$text[] = $v;
				
				if (trim($v) != "") {
					$n++;
				}
				
				if ($n == 2) {
					break;
				}
			}
			
			return implode("\n\n", $text); 
		}
		
		/**
		 * Strip the summary text from the main body
		 * @since Version 3.9.1
		 * @param string $summary
		 * @param string $body
		 * @return string
		 */
		
		private function stripSummaryFromBody($summary, $body) {
			
			$body = explode("\n", $body); 
			
			if (count($body) <= 3) {
				return "";
			}
			
			foreach (explode("\n\n", $summary) as $sline) {
				foreach ($body as $k => $bline) {
					if (strip_tags(trim($bline)) == trim($sline)) {
						unset($body[$k]);
					}
				}
			}
			
			$body = implode("\n\n", $body); 
			
			return $body;
		}
	}
	