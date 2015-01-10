<?php
	/**
	 * Flickr image provider for Railcams
	 * @since Version 3.9
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Railcams\Provider;
	
	use Railpage\Railcams\Railcams;
	use Railpage\Railcams\Photo;
	use Railpage\Railcams\Type;
	use Railpage\Railcams\ProviderInterface;
	use Railpage\AppCore;
	use Railpage\Url;
	use Exception;
	use DateTime;
	use DateTimeZone;
	use flickr_railpage;
	
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
		 * Object representing the connection to Flickr
		 * @since Version 3.9
		 * @var \flickr_railpage $cn
		 */
		
		private $cn;
		
		/**
		 * The photo data as extracted from Flickr
		 * @since Version 3.9
		 * @var array $photo
		 */
		
		private $photo;
		
		/**
		 * Constructor
		 * @since Version 3.9
		 * @param array $params
		 */
		
		public function __construct($params = false) {
			
			parent::__construct(); 
			
			if (is_array($params) && isset($params['oauth_token']) && isset($params['oauth_secret']) && isset($params['api_key'])) {
				$this->oauth_token = $params['oauth_token'];
				$this->oauth_secret = $params['oauth_secret'];
				$this->flickr_api_key = $params['api_key'];
				
				$this->cn = new flickr_railpage($this->flickr_api_key);
				$this->cn->oauth_token = $this->oauth_token;
				$this->cn->oauth_secret = $this->oauth_secret;
				$this->cn->cache = false;
			}
			
		}
		
		/**
		 * Get the photo from the provider
		 * @since Version 3.9
		 * @param int $id The ID of the photo from the provider
		 * @return array
		 */
		
		public function getPhoto($id) {
			$mckey = sprintf("railpage:railcam.provider=%s;railcam.image=%d", self::PROVIDER_NAME, $id);
			
			if (function_exists("getMemcacheObject") && $this->photo = getMemcacheObject($mckey)) {
				return $this->photo;
			} else {
				$return = array(); 
				
				if ($return = $this->cn->photos_getInfo($id)) {
					$return['photo']['sizes'] = $this->cn->photos_getSizes($id);
				}
				
				/**
				 * Transform Flickr's result into our standard data format
				 */
				
				$this->photo = array(
					"provider" => self::PROVIDER_NAME,
					"id" => $id,
					"dates" => array(
						"taken" => new DateTime($return['photo']['dates']['taken']),
						"uploaded" => new DateTime(sprintf("@%s", $return['photo']['dateuploaded'])),
						"updated" => new DateTime(sprintf("@%s", $return['photo']['dates']['lastupdate']))
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
					"sizes" => $return['photo']['sizes']
				);
				
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
		 * @param \Railpage\Railcams\Photo $Photo
		 */
		
		public function setPhoto(Photo $Photo) {
			
			/** 
			 * Flush Memcache
			 */
			
			$mckey = sprintf("railpage:railcam.provider=%s;railcam.image=%d", self::PROVIDER_NAME, $Photo->id);
			
			/**
			 * Check if the title and/or description have changed
			 */
			
			if ($Photo->title != $this->photo['title'] || $Photo->description != $this->photo['description']) {
				$result = $this->cn->photos_setMeta($Photo->id, $Photo->title, $Photo->description);
				
				$this->photo['title'] = $Photo->title;
				$this->photo['description'] = $Photo->description;
				
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
		
		public function getPhotos($page, $items_per_page) {
			
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
		
		public function getPhotoContext(Photo $Photo) {
			$rs = $this->cn->photos_getContext($Photo->id);
			
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
		
	}
?>