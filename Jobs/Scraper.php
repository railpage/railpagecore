<?php
	/**
	 * RSS feed scraper for PageUp
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Jobs;
	
	use Exception;
	use DateTime;
	use DateTimeZone;
	use SimpleXMLElement;
	use Railpage\AppCore;
	use Railpage\Module;
	use Railpage\Url;
	use Railpage\Organisations\Organisation;
	use Zend\Http\Client;
	
	/**
	 * Scraper
	 */
	
	class Scraper extends AppCore {
		
		/**
		 * RSS feed URL
		 * @since Version 3.8.7
		 * @var string $feed
		 */
		
		private $feed;
		
		/**
		 * RSS feed provider
		 * @since Version 3.8.7
		 * @var string $provider
		 */
		
		private $provider;
		
		/**
		 * Company offering these jobs
		 * @since Version 3.8.7
		 * @var \Railpage\Organisations\Organisation $Organisation
		 */
		
		private $Organisation;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param string $url
		 */
		
		public function __construct($url = false, $provider = "pageuppeople", Organisation $Organisation) {
			
			parent::__construct(); 
			
			$this->Module = new Module("jobs");
			
			if (is_string($url)) {
				$this->feed = $url;
			}
			
			if (is_string($provider)) {
				$this->provider = $provider;
			}
			
			if ($Organisation instanceof Organisation) {
				$this->Organisation = $Organisation;
			}
		}
		
		/**
		 * Scrape the RSS feed
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		public function fetch() {
			if (!is_string($this->feed)) {
				throw new Exception("Cannot fetch jobs from RSS feed because no RSS feed was provided");
			}
			
			$jobs = array();
			
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
				$job = $item->children($ns['job']);
				
				$location = explode("|", $job->location);
				
				if (!is_array($location) || count($location) === 1) {
					$location = array(
						0 => "Australia",
						1 => is_array($location) ? $location[0] : $location
					);
				}
				
				$woe = getWoeData(sprintf("%s, %s", $location[1], $location[0]));
				$timezone = "Australia/Melbourne";
				
				if (count($woe)) {
					$location = array(
						"name" => $woe['places']['place'][0]['name'],
						"lat" => $woe['places']['place'][0]['centroid']['latitude'],
						"lon" => $woe['places']['place'][0]['centroid']['longitude']
					);
				} else {
					$location = array(
						"name" => sprintf("%, %s", $location[1], $location[0])
					);
				}
				
				$row = array(
					"title" => $item->title,
					"id" => $job->refNo,
					"date" => array(
						"open" => new DateTime($item->pubDate),
						"close" => new DateTime($job->closingDate)
					),
					"category" => $job->category,
					"url" => array(
						"view" => $item->link,
						"apply" => $job->applyLink
					),
					"location" => $location,
					"salary" => 0,
					"type" => explode(",", $job->workType),
					"description" => $job->description
				);
				
				$row['date']['open']->setTimeZone(new DateTimeZone($timezone));
				$row['date']['close']->setTimeZone(new DateTimeZone($timezone));
				
				$jobs[] = $row;
			}
			
			$this->jobs = $jobs;
			
			return $this;
		}
		
		/**
		 * Store jobs in the database
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		public function store() {
			
			foreach ($this->jobs as $job) {
				
				$Job = new Job;
				
				$Job->title = $job['title'];
				$Job->Organisation = $this->Organisation;
				$Job->desc = $job['description'];
				$Job->Open = $job['date']['open'];
				$Job->expiry = $job['date']['close'];
				$Job->salary = $job['salary'];
				$Job->duration = implode(", ", $job['type']);
				$Job->Location = new Location($job['location']['name']);
				$Job->Classification = new Classification(strval($job['category']));
				$Job->reference_id = $job['id'];
				$Job->url = new Url;
				$Job->url->apply = strval($job['url']['apply']);
				
				try {
					$Job->commit();
				} catch (Exception $e) {
					printArray($e->getMessage());die;
				}
			}
			
		}
	}
?>