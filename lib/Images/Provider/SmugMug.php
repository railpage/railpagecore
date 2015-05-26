<?php
	/**
	 * SmugMug image provider
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images\Provider;
	
	use Railpage\Images\Images;
	use Railpage\Images\Image;
	use Railpage\Images\ProviderInterface;
	use Railpage\AppCore;
	use Railpage\Url;
	use Exception;
	use DateTime;
	use DateTimeZone;
	use GuzzleHttp\Client;
	
	/**
	 * SmugMug image provider
	 */
	
	class SmugMug extends AppCore implements ProviderInterface {
		
		/**
		 * Provider name
		 * @since Version 3.9.1
		 * @const string PROVIDER_NAME
		 */
		
		const PROVIDER_NAME = "SmugMug";
		
		/**
		 * API endpoint
		 * @since Version 3.9.1
		 * @const string API_ENDPOINT
		 */
		
		const API_ENDPOINT = "https://api.smugmug.com/services/api/json/1.3.0/";
		
		/**
		 * GuzzleHTTP Client
		 * @since Version 3.9.1
		 * @var \GuzzleHttp\Client $Client
		 */
		
		private $Client;
		
		/**
		 * SmugMug API key
		 * @since Version 3.9.1
		 * @var string $api_key
		 */
		
		private $api_key;
		
		/** 
		 * SmugMug API secret
		 * @since Version 3.9.1
		 * @var string $api_secret
		 */
		
		private $api_secret;
		
		/**
		 * Constructor
		 * @since Verison 3.9.1
		 */
		
		public function __construct($api_key = false, $api_secret = false) {
			parent::__construct(); 
			
			if ($api_key) {
				$this->api_key = $api_key;
			}
			
			if ($api_secret) {
				$this->api_secret = $api_secret;
			}
			
			if (!$api_key && defined("SMUGMUG_API_KEY")) {
				$this->api_key = SMUGMUG_API_KEY;
			}
			
			if (!$api_secret && defined("SMUGMUG_API_SECRET")) {
				$this->api_secret = SMUGMUG_API_SECRET;
			}
			
			if (!$api_secret && $Config = AppCore::getConfig()) {
				$this->api_key = $Config->SmugMug->APIKey;
			}
			
			$this->Client = new Client;
		}
		
		/**
		 * Send a request to the SmugMug API
		 * @since Version 3.9.1
		 * @param string $method
		 * @param array $data
		 * @return array
		 */
		
		private function send($method = false, $data = false) {
			
			if (is_null(filter_var($method, FILTER_SANITIZE_STRING))) {
				throw new InvalidArgumentException("Flickr API call failed: no API method requested"); 
			}
			
			$params = array(
				"method" => $method,
				"APIKey" => $this->api_key
			);
			
			if (is_array($data)) {
				$params = array_merge($params, $data);
			}
			
			$params = http_build_query($params);
			
			$response = $this->Client->get(sprintf("%s?%s", self::API_ENDPOINT, $params)); 
			$result = $response->json(); 
			
			return $result;
		}
		
		/**
		 * Get the image from the provider
		 * @since Version 3.9.1
		 * @param int $id The ID of the image from the provider
		 * @return array
		 */
		
		public function getImage($id, $force = false) {
			$params = array(
				"ImageKey" => $id
			);
			
			$response = $this->send("smugmug.images.getInfo", $params);
			
			if ($response['stat'] == "ok") {
				
				// List of image sizes to look for
				$sizes = array(
					"TinyURL", "ThumbURL", "SmallURL", "MediumURL", "LargeURL", "XLargeURL", "X2LargeURL", "X3LargeURL", "OriginalURL"
				);
				
				// List of image links to look for
				$links = array(
					"URL", "LightboxURL"
				);
				
				/**
				 * Start assembling the photo data
				 */
				
				$this->photo = array(
					"provider" => self::PROVIDER_NAME,
					"id" => $id,
					"dates" => array(
						"taken" => new DateTime($response['Image']['LastUpdated']),
						"updated" => new DateTime($response['Image']['LastUpdated']),
						"uploaded" => new DateTime($response['Image']['LastUpdated'])
					),
					"author" => array(
						
					),
					"title" => $response['Image']['Caption'],
					"description" => $response['Image']['FileName'],
					"tags" => $response['Image']['Keywords'],
					"sizes" => array(),
					"urls" => array()
				);
				
				/**
				 * Grab all the image sizes
				 */
				
				foreach ($sizes as $size) {
					if (isset($response['Image'][$size]) && !empty($response['Image'][$size])) {
						
						$dimensions = getimagesize($response['Image'][$size]);
						
						ini_set("max_execution_time", 3200);
						
						$this->photo['sizes'][$size] = array(
							"source" => $response['Image'][$size],
							"width" => $dimensions[0],
							"height" => $dimensions[1]
						);
					}
				}
				
				$this->photo['sizes'] = Images::normaliseSizes($this->photo['sizes']);
				
				/**
				 * Grab all the image links
				 */
				
				foreach ($links as $link) {
					if (isset($response['Image'][$link]) && !empty($response['Image'][$link])) {
						$this->photo['urls'][$link] = $response['Image'][$link];
					}
				}
				
				/**
				 * Grab the image owner
				 */
				
				if (preg_match("#(http|https)://([a-zA-Z0-9\-_]+).smugmug#", $response['Image']['URL'], $matches)) {
					$nickname = $matches[2]; 
					
					if ($response = $this->send("smugmug.users.getInfo", array("NickName" => $nickname))) {
						if ($response['stat'] == "ok") {
							$this->photo['author'] = array(
								"id" => $nickname,
								"username" => $response['User']['NickName'],
								"realname" => $response['User']['Name'],
								"url" => $response['User']['URL']
							);
						}
					}
				}
				
				return $this->photo;
			}
		}
		
		/**
		 * Save the changes to this image
		 * @since Version 3.9.1
		 * @return self
		 * @param \Railpage\Images\Image $Image
		 */
		
		public function setImage(Image $Image) {
			
		}
		
		/**
		 * Get a list of images
		 * @since Version 3.9.1
		 * @param int $page
		 * @param \Railpage\Images\Image $Image
		 * @return array
		 */
		
		public function getImages($page, $items_per_page) {
			
		}
		
		/**
		 * Return the name of this provider
		 * @since Version 3.9.1
		 * @return string
		 */
		
		public function getProviderName() {
			return self::PROVIDER_NAME;
		}
		
		/**
		 * Return the context of the supplied image
		 * @since Version 3.9.1
		 * @return array
		 * @param \Railpage\Images\Image $Image
		 */
		
		public function getImageContext(Image $Image) {
			
		}
		
		/**
		 * Delete this image
		 * @since Version 3.9.1
		 * @return boolean
		 * @param \Railpage\Images\Image $Image
		 */
		
		public function deleteImage(Image $Image) {
			
		}
	}