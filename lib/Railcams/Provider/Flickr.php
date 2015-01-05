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
		
		const PROVIDER_NAME = "flickr";
		
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
		 * Constructor
		 * @since Version 3.9
		 * @param string $oauth_token
		 * @param string $oauth_secret
		 * @param string $flickr_api_key
		 */
		
		public function __construct($oauth_token = false, $oauth_secret = false, $api_key = false) {
			
			parent::__construct(); 
			
			if ($oauth_token && $oauth_secret && $api_key) {
				$this->oauth_token = $oauth_token;
				$this->oauth_secret = $oauth_secret;
				$this->flickr_api_key = $api_key;
				
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
			$mckey = sprintf("railpage.railcam.provider=%s;railcam.image=%d", self::PROVIDER_NAME, $id);
			
			if (function_exists("getMemcacheObject") && $return = getMemcacheObject($mckey)) {
				return $return;
			} else {
				$return = array(); 
				
				if ($return = $this->cn->photos_getInfo($id)) {
					$return['photo']['sizes'] = $this->cn->photos_getSizes($id);
					
				}
				
				/**
				 * Transform Flickr's result into our standard data format
				 */
				
				$transform = array(
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
					setMemcacheObject($mckey, $transform, strtotime("+2 hours"));
				}
				
				return $transform;
			}
		}
		
		/**
		 * Save the changes to this photo
		 * @since Version 3.9
		 * @return self
		 */
		
		public function setPhoto() {
			
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
		
	}
?>