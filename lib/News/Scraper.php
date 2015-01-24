<?php
	/**
	 * RSS feed scraper for news articles
	 * @since Version 3.9
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\News;
	
	use Exception;
	use DateTime;
	use DateTimeZone;
	use DateInterval;
	use SimpleXMLElement;
	use Railpage\AppCore;
	use Railpage\Module;
	use Railpage\Url;
	use Railpage\Users\User;
	use Zend\Http\Client;
	
	/**
	 * Scraper
	 */
	
	class Scraper extends AppCore {
		
		/**
		 * RSS feed URL
		 * @since Version 3.9
		 * @var string $feed
		 */
		
		private $feed;
		
		/**
		 * RSS feed provider
		 * @since Version 3.9
		 * @var string $provider
		 */
		
		private $provider;
		
		/**
		 * Array of scraped news articles
		 * @since Version 3.9
		 * @var array $articles
		 */
		
		private $articles;
		
		/**
		 * Constructor
		 * @since Version 3.9
		 * @param string $url
		 */
		
		public function __construct($url = false, $provider = "Railway Gazette") {
			
			parent::__construct(); 
			
			$this->Module = new Module("news");
			
			if (is_string($url)) {
				$this->feed = $url;
			}
			
			if (is_string($provider)) {
				$this->provider = $provider;
			}
		}
		
		/**
		 * Scrape the RSS feed
		 * @since Version 3.9
		 * @return \Railpage\News\Scraper
		 */
		
		public function fetch() {
			if (!is_string($this->feed)) {
				throw new Exception("Cannot fetch news articles from RSS feed because no RSS feed was provided");
			}
			
			$articles = array();
			
			/**
			 * Zend HTTP config
			 */
			
			$config = array(
				'adapter' => 'Zend\Http\Client\Adapter\Curl',
				'curloptions' => array(CURLOPT_FOLLOWLOCATION => true),
			);
			
			$client = new Client($this->feed, $config);
			
			/**
			 * Fetch the RSS feed
			 */
			
			$response = $client->send();
			$content = $response->getContent();
			
			/**
			 * Load the SimpleXML object
			 */
			
			$xml = new SimpleXMLElement($content);
			
			/**
			 * Load the namespaces
			 */
			
			$ns = $xml->getNamespaces(true);
			
			/**
			 * Loop through each RSS item and build an associative array of the data we need
			 */
			
			foreach ($xml->channel->item as $item) {
				
				$content = $item->children($ns['content']);
				
				$content = strval($content->encoded);
				
				$line = explode("\n", $content); 
				$firstline = preg_replace('/([^?!.]*.).*/', '\\1', strip_tags($line[0]));
				
				$body = trim(str_replace($firstline, "", $content));
				
				$row = array(
					"title" => strval($item->title),
					"date" => (new DateTime(strval($item->pubDate)))->setTimeZone(new DateTimeZone("Australia/Melbourne")),
					"source" => strval($item->link),
					"blurb" => $firstline,
					"body" => $body,
					"topic" => News::guessTopic(json_decode(json_encode($item->category), true))
				);
				
				/**
				 * Add this job to the list of jobs found in this scrape
				 */
				
				$articles[] = $row;
			}
			
			$this->articles = $articles;
			
			return $this;
		}
		
		/**
		 * Store jobs in the database
		 * @since Version 3.9
		 * @return \Railpage\News\Scraper
		 */
		
		public function store() {
			
			/**
			 * Get Sphinx so we can lookup similar articles to prevent duplicates
			 */
			
			$Sphinx = $this->getSphinx();
			
			foreach ($this->articles as $article) {
				$query = $Sphinx->select("*")
						->from("idx_news_article")
						->orderBy("story_time_unix", "DESC")
						->where("story_time_unix", ">=", $article['date']->sub(new DateInterval("P7D"))->getTimestamp())
						->match("story_title", $article['title']);
				
				$matches = $query->execute();
				
				/**
				 * If no matches are found we'll add in the article
				 */
				
				if (!count($matches)) {
					$Article = new Article;
					
					$Article->title = $article['title'];
					$Article->blurb = $article['blurb'];
					$Article->source = $article['source'];
					$Article->body = $article['body'];
					
					$Article->setTopic($article['topic'])->setAuthor(new User(User::SYSTEM_USER_ID))->commit(true);
				}
			}
			
			return $this;
		}
		
		/**
		 * Get staged articles 
		 * @since Version 3.9
		 * @return array
		 */
		
		public function getArticles() {
			return $this->articles;
		}
	}
?>