<?php
	/**
	 * Flickr image provider for Images
	 * @since Version 3.9
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
	use flickr_railpage;
	use GuzzleHttp\Client;
	
	/**
	 * Flickr image provider
	 */
	
	class Flickr extends AppCore implements ProviderInterface {
		
		/**
		 * Provider name
		 * @since Version 3.9
		 * @const PROVIDER_NAME
		 */
		
		const PROVIDER_NAME = "Flickr";
		
		/**
		 * API endpoint
		 * @since Version 3.9.1
		 * @const string API_ENDPOINT
		 */
		
		const API_ENDPOINT = "https://api.flickr.com/services/rest/";
		
		/**
		 * Flickr OAuth token
		 * @since Version 3.9
		 * @var string $oauth_token
		 */
		
		private $oauth_token;
		
		/**
		 * Flickr OAuth secret
		 * @since Version 3.9
		 * @var string $oauth_secret
		 */
		
		public $oauth_secret;
		
		/**
		 * Flickr API key
		 * @since Version 3.9
		 * @var string $flickr_api_key
		 */
		
		private $flickr_api_key;
		
		/**
		 * Flickr API secret
		 * @since Version 3.9
		 * @var string $flickr_api_secret
		 */
		
		private $flickr_api_secret;
		
		/**
		 * Object representing the connection to Flickr
		 * @since Version 3.9
		 * @var \flickr_railpage $cn
		 */
		
		public $cn;
		
		/**
		 * The photo data as extracted from Flickr
		 * @since Version 3.9
		 * @var array $photo
		 */
		
		private $photo;
		
		/**
		 * API response format
		 * @since Version 3.9.1
		 * @var string $format
		 */
		
		public $format = "json";
		
		/**
		 * Last error code returned from the provider's API
		 * @since Version 3.9.1
		 * @var int $errcode
		 */
		
		private $errcode;
		
		/**
		 * Last error message returned from the provider's API
		 * @since Version 3.9.1
		 * @var string $errmessage
		 */
		
		private $errmessage;
		
		/**
		 * GuzzleHTTP Client
		 * @since Version 3.9.1
		 * @var \GuzzleHttp\Client $Client
		 */
		
		private $Client;
		
		/**
		 * Constructor
		 * @since Version 3.9
		 * @param array $params
		 */
		
		public function __construct($params = false) {
			
			parent::__construct(); 
			
			if ($params === false) {
				$Config = AppCore::getConfig();
				
				$params = array(
					"api_key" => $Config->Flickr->APIKey,
					"api_secret" => $Config->Flickr->APISecret
				);
			}
			
			$opts = array(
				"flickr_api_key" => "api_key",
				"flickr_api_secret" => "api_secret",
				"oauth_token" => "oauth_token",
				"oauth_secret" => "oauth_secret"
			);
			
			foreach ($opts as $var => $val) {
				if (!isset($params[$val]) || is_null(filter_var($params[$val], FILTER_SANITIZE_STRING))) {
					$this->$var = NULL;
				} else {
					$this->$var = $params[$val];
				}
			}
			
			if (!is_null($this->flickr_api_key)) {
				$this->cn = new flickr_railpage($this->flickr_api_key);
				$this->cn->cache = false;
				
				if (!is_null($this->oauth_token) && !is_null($this->oauth_secret)) {
					$this->cn->oauth_token = $this->oauth_token;
					$this->cn->oauth_secret = $this->oauth_secret;
				}
			}
			
			$this->Client = new Client;
			
		}
		
		/**
		 * Get the photo from the provider
		 * @since Version 3.9
		 * @param int $id The ID of the photo from the provider
		 * @param boolean $force
		 * @return array
		 */
		
		public function getImage($id, $force = false) {
			$mckey = sprintf("railpage:image.provider=%s;image=%d", self::PROVIDER_NAME, $id);
			
			if (!$force && $this->photo = $this->Memcached->fetch($mckey)) {
				return $this->photo;
			} else {
				$return = array(); 
				
				if ($return = $this->get("flickr.photos.getInfo", array("photo_id" => $id))) {
					$return['photo']['sizes'] = $this->get("flickr.photos.getSizes", array("photo_id" => $id));
				}
				
				if (empty($return)) {
					throw new Exception(sprintf("Unable to fetch data from %s: %s (%d)", self::PROVIDER_NAME, $this->getErrorMessage(), $this->getErrorCode()));
				}
				
				/**
				 * Transform Flickr's result into our standard data format
				 */
				
				$this->photo = array(
					"provider" => self::PROVIDER_NAME,
					"id" => $id,
					"dates" => array(
						"taken" => new DateTime($return['photo']['dates']['taken']),
						"uploaded" => isset($return['photo']['dateuploaded']) ? new DateTime(sprintf("@%s", $return['photo']['dateuploaded'])) : new DateTime($return['photo']['dates']['taken']),
						"updated" => isset($return['photo']['dates']['lastupdate']) ? new DateTime(sprintf("@%s", $return['photo']['dates']['lastupdate'])) : new DateTime($return['photo']['dates']['taken'])
					),
					"author" => array(
						"id" => $return['photo']['owner']['nsid'],
						"username" => $return['photo']['owner']['username'],
						"realname" => $return['photo']['owner']['realname'],
						"url" => new Url(sprintf("https://www.flickr.com/photos/%s", $return['photo']['owner']['nsid']))
					),
					"title" => $return['photo']['title'],
					"description" => $return['photo']['description'],
					"tags" => $return['photo']['tags']['tag'],
					"sizes" => $return['photo']['sizes'],
					"urls" => $return['photo']['urls'],
				);
				
				if (isset($return['photo']['location'])) {
					$this->photo['location'] = $return['photo']['location'];
				}
				
				/*
				 * Check if the author is on Railpage
				 */
				
				$query = "SELECT user_id FROM nuke_users WHERE flickr_nsid = ?";
				
				if ($tmp_user_id = $this->db->fetchOne($query, $this->photo['author']['id'])) {
					$this->photo['author']['railpage_id'] = $tmp_user_id;
				}
				
				if (function_exists("setMemcacheObject")) {
					setMemcacheObject($mckey, $this->photo, strtotime("+2 days"));
				}
				
				return $this->photo;
			}
		}
		
		/**
		 * Save the changes to this photo
		 * @since Version 3.9
		 * @return self
		 * @param \Railpage\Images\Image $Image
		 */
		
		public function setImage(Image $Image) {
			
			/** 
			 * Flush Memcache
			 */
			
			$mckey = sprintf("railpage:image.provider=%s;image=%d", self::PROVIDER_NAME, $Image->id);
			
			/**
			 * Check if the title and/or description have changed
			 */
			
			if ($Image->title != $this->photo['title'] || $Image->description != $this->photo['description']) {
				$result = $this->cn->photos_setMeta($Image->id, $Image->title, $Image->description);
				
				$this->photo['title'] = $Image->title;
				$this->photo['description'] = $Image->description;
				
				if (!$result) {
					throw new Exception(sprintf("Could not update photo. The error returned from %s is: (%d) %s", self::PROVIDER_NAME, $this->cn->getErrorCode(), $this->cn->getErrorMsg()));
				}
			}
			
			if (function_exists("setMemcacheObject")) {
				setMemcacheObject($mckey, $this->photo, strtotime("+2 days"));
			}
			
			return $this;
		}
		
		/**
		 * Get a list of photos
		 * @since Version 3.9
		 * @param int $page
		 * @param int $items_per_page
		 * @return array
		 */
		
		public function getImages($page, $items_per_page) {
			
		}
		
		/**
		 * Return the name of this provider
		 * @since Version 3.9
		 * @return string
		 */
		
		public function getProviderName() {
			return self::PROVIDER_NAME;
		}
		
		/**
		 * Return the context of the supplied photo
		 * @since Version 3.9
		 * @return array
		 */
		
		public function getImageContext(Image $Image) {
			$rs = $this->cn->photos_getContext($Image->id);
			
			$return = array(
				"previous" => false,
				"next" => false
			);
			
			if (isset($rs['prevphoto']) && is_array($rs['prevphoto'])) {
				$return['previous'] = array(
					"id" => $rs['prevphoto']['id'],
					"title" => isset($rs['prevphoto']['title']) ? $rs['prevphoto']['title'] : "Untitled"
				);
			}
			
			if (isset($rs['nextphoto']) && is_array($rs['nextphoto'])) {
				$return['next'] = array(
					"id" => $rs['nextphoto']['id'],
					"title" => isset($rs['nextphoto']['title']) ? $rs['nextphoto']['title'] : "Untitled"
				);
			}
			
			return $return;
		}
		
		/**
		 * Delete this photo
		 * @since Version 3.9.1
		 * @return boolean
		 * @param \Railpage\Images\Image $Image
		 */
		
		public function deleteImage(Image $Image) {
			return $this->cn->photos_delete($Image->id);
		}
		
		/**
		 * Fetch a request from Flickr's API
		 * @since Version 3.9.1
		 * @param string $method
		 * @param array $params
		 * @return array
		 */
		
		public function get($method = false, $params = array()) {
			
			if (is_null(filter_var($method, FILTER_SANITIZE_STRING))) {
				throw new InvalidArgumentException("Flickr API call failed: no API method requested"); 
			}
			
			$params['method'] = $method;
			$params['api_key'] = $this->flickr_api_key;
			$params['format'] = $this->format;
			
			if ($params['format'] === "json") {
				$params['nojsoncallback'] = "1";
			}
			
			$params = http_build_query($params);
			$url = sprintf("%s?%s", self::API_ENDPOINT, $params);
			
			$response = $this->Client->get($url); 
			
			if ($response->getStatusCode() != 200) {
				throw new Exception(sprintf("An unexpected HTTP response code of %s was returned from %s", $response->getStatusCode(), self::PROVIDER_NAME));
			}
			
			$result = $response->json(); 
			
			if ($result['stat'] != "ok") {
				$this->errcode = $result['code'];
				$this->errmessage = $result['message'];
				
				return false;
			}
			
			$result = $this->normaliseContent($result);
			$result = $this->normaliseSizes($result);
			
			return $result;
			
		}
		
		/**
		 * Get error code
		 * @since Version 3.9.1
		 * @return int
		 */
		
		public function getErrorCode() {
			return $this->errcode;
		}
		
		/**
		 * Get the last error message
		 * @since Version 3.9.1
		 * @return string
		 */
		
		public function getErrorMessage() {
			return $this->errmessage;
		}
		
		/**
		 * Normalise the array content
		 * @since Version 3.9.1
		 * @return array
		 * @param array $array
		 */
		
		private function normaliseContent($array) {
			foreach ($array as $key => $val) {
				if (is_array($val) && count($val) === 1 && isset($val['_content'])) {
					$array[$key] = $val['_content'];
				} elseif (is_array($val)) {
					$array[$key] = $this->normaliseContent($val); 
				}
			}
			
			return $array;
		}
		
		/**
		 * Normalise the image sizes
		 * @since Version 3.9.1
		 * @return array
		 * @param array $result
		 */
		
		private function normaliseSizes($result) {
			if (isset($result['sizes']['size']) && is_array($result['sizes']['size'])) {
				return $result['sizes']['size'];
			}
			
			return $result;
		}
	}
	