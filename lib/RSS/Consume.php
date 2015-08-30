<?php
	/**
	 * RSS consumer (because all "RSS" is not standard. Screw you, RSS.)
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\RSS;
	
	use SimpleXMLElement;
	use DOMDocument;
	use DOMXPath;
	use Exception;
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
				$response = $this->GuzzleClient->get(
					$feed['url'], 
					array(
						"headers" => array(
							"User-Agent" => self::SCRAPER_AGENT . ' v.' . self::SCRAPER_VERSION
						),
						"timeout" => 60,
						"connect_timeout" => 5
					)
				);
				
				if ($response->getStatusCode() != 200) {
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
			
			foreach ($this->feeds as $feedsrc => $feed) {
				
				/**
				 * Create a new SimpleXMLElement object using the data returned from the RSS/Atom feed
				 */
				
				$xml = new SimpleXMLElement($feed['body'], LIBXML_NOCDATA);
				
				/**
				 * Find all namespaces
				 */
				
				$ns = $xml->getNamespaces(true);
				$type = "rss";
				
				if ($xml->channel->item != NULL) {
					$loop = $xml->channel->item; 
				} else {
					$type = "atom";
					$loop = $xml->entry;
				}
				
				/**
				 * Loop through each entry in the feed
				 */
				
				foreach ($loop as $node) {
					$date = !empty($node->date->__toString()) ? $node->date->__toString() : $node->pubDate->__toString();
					
					if (empty($date) && !empty($node->updated->__toString())) {
						$date = $node->updated->__toString(); 
					}
					
					$link = $node->link->__toString();
					
					foreach ($node->link as $link) {
						if ($link['type'] == "text/html" || $link['rel'] == "alternate") {
							$link = $link['href']->__toString();
						}
					}
					
					$item = array(
						"id" => !empty($node->guid->__toString()) ? $node->guid->__toString() : $link,
						"title" => $node->title->__toString(),
						"description" => !empty($node->content->__toString()) ? $node->content->__toString() : $node->description->__toString(),
						"link" => $link,
						"tags" => $link,
						"category" => $link,
						"date" => $date,
					);
					
					/**
					 * Loop through all known namespaces and assemble the data
					 */
					
					foreach ($ns as $namespace) {
						foreach ($node->children($namespace) as $key => $data) {
							
							if (isset($item[$key]) && !empty(trim($data->__toString()))) {
								$item[$key] = trim($data->__toString()); 
							} elseif ($key == "encoded") {
								$item['description'] = $data->__toString();
							} else {
								$item['extra'][$key] = $data->__toString();
							}
						}
					}
					
					/**
					 * Organise the tag(s) situation a little better
					 */
					
					if (count($node->tag) > 0) {
						$tags = array(); 
						
						foreach ($node->tag as $tag) {
							$tags[] = $tag->__toString();
						}
						
						if (!empty(implode(",", $tags))) {
							$item['tags'] = implode(",", $tags);
						}
					}
					
					if (count($node->category) > 0) {
						$tags = array(); 
						
						foreach ($node->category as $tag) {
							$tags[] = $tag->__toString();
						}
						
						if (!empty(implode(",", $tags))) {
							$item['tags'] = implode(",", $tags);
						}
					}
						
					/**
					 * Process / tidy up the feed item
					 */
					
					$item = $this->process($item);
				
					/**
					 * Get the item summary
					 */
					
					$item['summary'] = $this->createSummary($item['description']);
					$item['body'] = $this->stripSummaryFromBody($item['summary'], $item['description']);
					
					/**
					 * Add this item to the array of processed items
					 */
					
					$this->feeds[$feedsrc]['items'][] = $item;
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
			 * Process the description field
			 */
			
			$item['description'] = preg_replace('#<br\s*/?>#i', "\n", $item['description']);
			$item['description'] = str_replace("&nbsp;", " ", $item['description']);
			
			$Doc = new DOMDocument;
			@$Doc->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $item['description'], LIBXML_NOCDATA ); # UTF-8 hinting from http://stackoverflow.com/a/11310258/319922
			
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
					
					$el = $node->childNodes->item(0);
					
					if (is_object($el) && $el->nodeName == "#text") {
						$node->nodeValue = htmlentities(str_replace("\n", " ", str_replace("\r\n", " ", $node->nodeValue))); # Without htmlentities() DOMDocument whinges and bitches something unforgivable
						
						if (empty(trim($node->nodeValue))) {
							$node->parentNode->removeChild($node);
						}
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
			
			$item['description'] = $Doc->saveHTML();
			$item['description'] = str_replace('<meta http-equiv="content-type" content="text/html; charset=utf-8">', "", $item['description']);
			
			/**
			 * Remove all HTML tags except those we want to keep
			 */
			
			$item['description'] = trim(strip_tags($item['description'], "<img><a><ul><li><ol><table><thead><tbody><tfoot><tr><th><td>"));
			
			/**
			 * Convert tags from a SimpleXML object to a string
			 */
			
			if (is_object($item['tags'])) {
				$item['tags'] = $item['tags']->__toString(); 
			}
			
			if (is_object($item['link'])) {
				$item['link'] = $item['link']->__toString(); 
			}
			
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
		 * @param string $str The description field to extract the summary from
		 * @param int $n Maximum number of characters to return
		 * @param string $end_char
		 */
		
		private function createSummary($str, $n = 1024, $end_char = '&#8230;') {
			
			$str = strip_tags($str, "<ul><li><ol>");
			
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
	