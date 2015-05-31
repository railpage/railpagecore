<?php
	/**
	 * Images master class for Railpage
	 * 
	 * Find an image by provider ID etc
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images;
	
	use Railpage\AppCore;
	use Exception;
	use DateTime;
	
	/**
	 * Images class
	 * @since Version 3.8.7
	 */
	
	class Images extends AppCore {
		
		/**
		 * Option: Do not load a \Railpage\Place object
		 * @since Version 3.9.1
		 * @const int OPT_NOPLACE
		 */
		
		const OPT_NOPLACE = 7;
		
		/**
		 * Flag: Force a refresh of this image data
		 * @since Version 3.9.1
		 * @const int OPT_REFRESH
		 */
		
		const OPT_REFRESH = 1432;
		
		/**
		 * Photo sizes
		 * @since Version 3.9.1
		 * @var array $sizes
		 */
		
		private static $sizes = array(); 
		
		/**
		 * Constructor
		 */
		
		public function __construct() {
			parent::__construct(); 
			
			/**
			 * Record this in the debug log
			 */
			
			if (function_exists("debug_recordInstance")) {
				debug_recordInstance(__CLASS__);
			}
		}
		
		/**
		 * Find an image by provider and provider image ID
		 * @since Version 3.8.7
		 * @param string $provider
		 * @param int $id
		 * @throws \Exception if $provider is null
		 * @throws \Exception if $photo_id is null
		 * @param int $option
		 */
		
		public function findImage($provider = NULL, $photo_id = NULL, $option = NULL) {
			if (is_null($provider)) {
				throw new Exception("Cannot lookup image from image provider - no provider given (hint: Flickr, WestonLangford)");
			}
			
			if (!preg_match("/([a-zA-Z0-9]+)/", $photo_id) || $photo_id === 0) {
				throw new Exception("Cannot lookup image from image provider - no provider image ID given");
			}
			
			$mckey = sprintf("railpage:image;provider=%s;id=%s", $provider, $photo_id);
			
			if ($option != self::OPT_REFRESH && !$id = $this->Redis->fetch($mckey)) {
				$id = $this->db->fetchOne("SELECT id FROM image WHERE provider = ? AND photo_id = ?", array($provider, $photo_id));
				$this->Redis->save($mckey, $id, strtotime("+1 month"));
			}
			
			if (isset($id) && filter_var($id, FILTER_VALIDATE_INT)) {
				return new Image($id, $option);
			}
			
			$Image = new Image;
			$Image->provider = $provider;
			$Image->photo_id = $photo_id;
			
			$Image->populate(true, $option);
			
			return $Image;
		}
		
		/**
		 * Find images of a locomotive
		 * @since Version 3.8.7
		 * @param int $loco_id
		 * @param int $livery_id
		 * @return array
		 */
		
		public function findLocoImage($loco_id = NULL, $livery_id = NULL) {
			if (is_null($loco_id)) {
				throw new Exception("Cannot find loco image - no loco ID given");
			}
			
			if (is_null($livery_id)) {
				$query = "SELECT i.id FROM image_link AS il INNER JOIN image AS i ON il.image_id = i.id WHERE il.namespace = ? AND il.namespace_key = ? AND il.ignored = 0";
				$args = array(
					"railpage.locos.loco",
					$loco_id
				);
				
				$image_id = $this->db->fetchOne($query, $args); 
			} else {
				$query = "SELECT il.image_id FROM image_link AS il WHERE il.namespace = ? AND il.namespace_key = ? AND il.image_id IN (SELECT i.id FROM image_link AS il INNER JOIN image AS i ON il.image_id = i.id WHERE il.namespace = ? AND il.namespace_key = ? AND il.ignored = 0)";
				$args = array(
					"railpage.locos.liveries.livery",
					$livery_id,
					"railpage.locos.loco",
					$loco_id
				);
				
				$image_id = $results = $this->db->fetchOne($query, $args); 
			}
			
			if (isset($image_id) && filter_var($image_id, FILTER_VALIDATE_INT)) {
				$Image = new Image($image_id);
				#$Image->populate();
				
				return $Image;
			}
			
			return false;
		}
		
		/**
		 * Get a photo from its source URL
		 * @since Version 3.9.1
		 * @param string $url
		 * @param int|null $option
		 * @return boolean|\Railpage\Images\Image
		 */
		
		public function getImageFromUrl($url = false, $option = NULL) {
			
			/**
			 * Flickr
			 */
			
			if (preg_match("#flickr.com/photos/([a-zA-Z0-9\-\_\@]+)/([0-9]+)#", $url, $matches) && $Image = $this->findImage("flickr", $matches[2], $option)) {
				return $Image;
			}
			
			if (preg_match("#flic.kr/p/([a-zA-Z0-9]+)#", $url, $matches)) {
				$photo_id = self::getBase58PhotoId($matches[1]);
								
				if ($Image = $this->findImage("flickr", $photo_id, $option)) {
					return $Image;
				}
			}
			
			/**
			 * SmugMug
			 */
			
			if (preg_match("#smugmug.com#", $url)) {
				$parts = explode("/", $url); 
				array_reverse($parts); 
				
				foreach ($parts as $part) {
					if (preg_match("#i-([a-zA-Z0-9]+)#", $part, $matches)) {
						if ($Image = $this->findImage("SmugMug", $matches[1], $option)) {
							return $Image;
						}
					}
				}
			}
			
			/**
			 * Vicsig
			 */
			
			if (preg_match("#vicsig.net/photo#", $url, $matches)) {
				// Do nothing yet
			}
		}
		
		/**
		 * Get a photo ID from a base58-encoded Flickr photo ID
		 * @since Version 3.9.1
		 * @param string $base58
		 * @return string
		 */
		
		private static function getBase58PhotoId($base58) {
			
			$alphabet = "123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
			$decoded = 0;
			$multi = 1;
			
			while (strlen($base58) > 0) {
				$digit = $base58[strlen($base58)-1];
				$decoded += $multi * strpos($alphabet, $digit);
				$multi = $multi * strlen($alphabet);
				$base58 = substr($base58, 0, -1);
			}
			
			return $base58;
			
		}
		
		/**
		 * Normalise image sizes
		 * @since Version 3.9.1
		 * @param array $sizes
		 * @return array
		 */
		
		public static function normaliseSizes($sizes) {
			
			self::$sizes = $sizes;
			
			self::normaliseSizes_addMissingSizes("thumb", 280, 150);
			self::normaliseSizes_addMissingSizes("small", 500, 281);	
			self::normaliseSizes_addMissingSizes("medium", 800, 480);
			
			self::normaliseSizes_addShorthands("fullscreen", 1919);
			self::normaliseSizes_addShorthands("larger", 1024, 1920);
			self::normaliseSizes_addShorthands("large", 1023, 1025);
			self::normaliseSizes_addShorthands("medium", 799, 801);
			
			if (!isset(self::$sizes['larger'])) {
				self::$sizes['larger'] = self::$sizes['largest'];
			}
			
			if (!isset($sizes['large'])) {
				self::$sizes['large'] = self::$sizes['larger'];
			}
			
			if (!isset($sizes['medium'])) {
				self::$sizes['medium'] = self::$sizes['large'];
			}
			
			return self::$sizes;
		}
		
		/**
		 * Normalise sizes: add missing sizes
		 * @since Version 3.9.1
		 * @param string $missing_size
		 * @param int $min_width
		 * @param int $min_height
		 * @return void
		 */
		
		private static function normaliseSizes_addMissingSizes($missing_size, $min_width, $min_height) {
			
			if (isset(self::$sizes[$missing_size])) {
				return;
			}
			
			foreach (self::$sizes as $size) {
				if ($size['width'] >= $min_width && $size['height'] >= $min_height) {
					self::$sizes[$missing_size] = $size;
					break;
				}
			}
			
			return;
			
		}
		
		/**
		 * Normalise sizes: add shorthand names to sizes
		 * @since Version 3.9.1
		 * @param string $missing_size
		 * @param int $min_width
		 * @param int $min_height
		 * @return void
		 */
		
		private static function normaliseSizes_addShorthands($missing_size, $min_width = 0, $max_width = 99999) {
			
			if (isset(self::$sizes[$missing_size])) {
				return;
			}
			
			foreach (self::$sizes as $size) {
				if ($size['width'] > $missing_size && $size['width'] <= 1920) {
					self::$sizes[$missing_size] = $size;
					
					if ($min_width === 0) {
						break;
					} else {
						$min_width = $size['width'];
					}
				}
			}
			
			return;
			
		}
	}
	