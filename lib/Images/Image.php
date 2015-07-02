<?php
	/**
	 * Store and fetch image data from our local database
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images;
	
	use Railpage\Users\User;
	use Railpage\Users\Factory as UserFactory;
	use Railpage\API;
	use Railpage\AppCore;
	use Railpage\Place;
	use Railpage\PlaceUtility;
	use Railpage\Locos\Factory as LocosFactory;
	use Railpage\Locos\Locomotive;
	use Railpage\Locos\LocoClass;
	use Railpage\Locos\Liveries\Livery;
	use Railpage\Module;
	use Railpage\Url;
	use Railpage\Debug;
	use Exception;
	use DateTime;
	use DateTimeZone;
	use DateInterval;
	use stdClass;
	use DomDocument;
	use GuzzleHttp\Client;
	use Zend_Db_Expr;
	use Railpage\ContentUtility;
	
	/**
	 * Store and fetch data of Flickr, Weston Langford, etc images in our local database
	 * @since Version 3.8.7
	 */
	
	class Image extends AppCore {
		
		/**
		 * Max age of data before it requires refreshing
		 */
		
		const MAXAGE = 14;
		
		/**
		 * Image ID in sparta
		 * @since Version 3.8.7
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Image title
		 * @since Version 3.8.7
		 * @var string $title
		 */
		
		public $title;
		
		/**
		 * Image description
		 * @since Version 3.8.7
		 * @var string $description
		 */
		
		public $description;
		
		/**
		 * Image provider
		 * @since Version 3.8.7
		 * @var string $provider
		 */
		
		public $provider;
		
		/**
		 * Photo ID from the image provider
		 * @since Version 3.8.7
		 * @var int $photo_id
		 */
		
		public $photo_id;
		
		/**
		 * Geographic place where this photo was taken
		 * @since Version 3.8.7
		 * @var \Railpage\Place $Place
		 */
		
		public $Place;
		
		/**
		 * Nearest geoplace to this photo
		 * @since Version 3.9.1
		 * @var \Railpage\Place $GeoPlace
		 */
		
		public $GeoPlace;
		
		/**
		 * Object of image sizes and their source URLs
		 * @since Version 3.8.7
		 * @var \stdClass $sizes
		 */
		
		public $sizes;
		
		/**
		 * Object of image page URLs
		 * @since Version 3.8.7
		 * @var \stdClass $links
		 */
		
		public $links;
		
		/**
		 * Object representing the image author
		 * @since Version 3.8.7
		 * @var \stdClass $author
		 */
		
		public $author;
		
		/**
		 * URL to this image on Railpage
		 * @since Version 3.8.7
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Source URL to this image
		 * @since Version 3.8.7
		 * @var string $source
		 */
		
		public $source;
		
		/**
		 * Image meta data
		 * @since Version 3.8.7
		 * @var array $meta
		 */
		
		public $meta;
		
		/**
		 * Date modified
		 * @since Version 3.8.7
		 * @var \DateTime $Date
		 */
		
		public $Date;
		
		/**
		 * Date the photo was taken
		 * @since Version 3.9.1
		 * @var \DateTime $DateCaptured
		 */
		
		public $DateCaptured;
		
		/**
		 * Memcached identifier key
		 * @since Version 3.8.7
		 * @var string $mckey
		 */
		
		public $mckey;
		
		/**
		 * JSON data string of this image
		 * @since Version 3.8.7
		 * @var string $json
		 */
		
		public $json;
		
		/**
		 * Image provider options
		 * @since Version 3.9.1
		 * @var array $providerOptions
		 */
		
		private $providerOptions = array(); 
		
		/**
		 * Image provider
		 * @since Version 3.9.1
		 * @var object $ImageProvider
		 */
		
		private $ImageProvider;
		
		/**
		 * Latitude
		 * @since Version 3.9.1
		 * @var float $lat
		 */
		
		private $lat;
		
		/**
		 * Longitude
		 * @since Version 3.9.1
		 * @var float $lon
		 */
		
		private $lon;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param int $id
		 * @param int $option
		 */
		
		public function __construct($id = NULL, $option = NULL) {
			
			parent::__construct();
			
			$timer = Debug::getTimer();
			
			$this->GuzzleClient = new Client;
			
			$this->Module = new Module("images");
			
			if ($this->id = filter_var($id, FILTER_VALIDATE_INT)) {
				$this->load($option); 
			}
			
			Debug::logEvent(__METHOD__, $timer); 
			
		}
		
		/**
		 * Populate this object from an array
		 * @since Version 3.9.1
		 * @return void
		 */
		
		public function populateFromArray($row) {
			
			$this->provider = $row['provider'];
			$this->photo_id = $row['photo_id'];
			$this->Date = new DateTime($row['modified']);
			$this->DateCaptured = !isset($row['captured']) || is_null($row['captured']) ? NULL : new DateTime($row['captured']);
			
			$this->title = !empty($row['meta']['title']) ? ContentUtility::FormatTitle($row['meta']['title']) : "Untitled";
			$this->description = $row['meta']['description'];
			$this->sizes = $row['meta']['sizes'];
			$this->links = $row['meta']['links'];
			$this->meta = $row['meta']['data'];
			$this->lat = $row['lat'];
			$this->lon = $row['lon'];
			
			#printArray($row['meta']);die;
			
			if (!$this->DateCaptured instanceof DateTime) {
				if (isset($row['meta']['data']['dates']['taken'])) {
					$this->DateCaptured = new DateTime($row['meta']['data']['dates']['taken']);
				}
			}
			
			/**
			 * Normalize some sizes
			 */
			
			if (count($this->sizes)) {
				$this->sizes = Images::normaliseSizes($this->sizes);
			}
			
			$this->url = Utility\Url::CreateFromImageID($this->id); 
			
			if (empty($this->mckey)) {
				$this->mckey = sprintf("railpage:image=%d", $this->id);
			}
			
		}
		
		/**
		 * Populate this image object
		 * @since Version 3.9.1
		 * @return void
		 * @param int $option
		 */
		
		private function load($option = NULL) {
			
			Debug::RecordInstance(); 
			
			$this->mckey = sprintf("railpage:image=%d", $this->id);
			
			if ((defined("NOREDIS") && NOREDIS == true) || !$row = $this->Redis->fetch($this->mckey)) {
			
				Debug::LogCLI("Fetching data for " . $this->id . " from database");
				
				$query = "SELECT i.title, i.description, i.id, i.provider, i.photo_id, i.modified, i.meta, i.lat, i.lon, i.user_id, i.geoplace, i.captured FROM image AS i WHERE i.id = ?";
			
				$row = $this->db->fetchRow($query, $this->id);
				$row['meta'] = json_decode($row['meta'], true);
				
				$this->Redis->save($this->mckey, $row, strtotime("+24 hours"));
			}
			
			$this->populateFromArray($row);
			
			if ($this->provider == "rpoldgallery") {
				$GalleryImage = new \Railpage\Gallery\Image($this->photo_id);
				$this->url->source = $GalleryImage->url->url;
				
				if (empty($this->meta['source'])) {
					$this->meta['source'] = $this->url->source; 
				}
			}
			
			if (!isset($row['user_id'])) {
				$row['user_id'] = 0;
			}
			
			/**
			 * "Hit"
			 * Such detail, many description
			 */
			
			$this->hit(); 
			
			/**
			 * Update the database row
			 */
			
			if (((!isset($row['title']) || empty($row['title']) || is_null($row['title'])) && !empty($this->title)) || 
				((!isset($row['description']) || empty($row['description']) || is_null($row['description'])) && !empty($this->description))) {
				$row['title'] = $this->title;
				$row['description'] = $this->description;
				
				$this->Redis->save($this->mckey, $row, strtotime("+24 hours"));
				
				$this->commit();
			}
			
			/**
			 * Load the author. If we don't know who it is, attempt to re-populate the data
			 */
			
			if (isset($row['meta']['author'])) {
				$this->author = json_decode(json_encode($row['meta']['author']));
				
				if (isset($this->author->railpage_id)) {
					$this->author->User = UserFactory::CreateUser($this->author->railpage_id);
					
					if ($this->author->User instanceof User && $this->author->User->id != $row['user_id']) {
						$this->commit(); 
					}
				}
			} else {
				Debug::LogCLI("No author found in local cache - refreshing from " . $this->provider);
				Debug::LogEvent("No author found in local cache - refreshing from " . $this->provider);
				$this->populate(true, $option);
			}
			
			/**
			 * Unless otherwise instructed load the places object if lat/lng are present
			 */
			
			if ($option != Images::OPT_NOPLACE && round($row['lat'], 3) != "0.000" && round($row['lon'], 3) != "0.000") {
				try {
					$this->Place = Place::Factory($row['lat'], $row['lon']);
				} catch (Exception $e) {
					// Throw it away. Don't care.
				}
			}
			
			/**
			 * Set the source URL
			 */
			
			if (isset($this->meta['source'])) {
				$this->source = $this->meta['source'];
			} else {
				switch ($this->provider) {
					case "flickr" :
						if (function_exists("base58_encode")) {
							$this->source = "https://flic.kr/p/" . base58_encode($this->photo_id);
						}
				}
			}
			
			/**
			 * Create an array/JSON object
			 */
		
			$this->getJSON();
			
		}
		
		/**
		 * Validate changes to this image
		 * @since Version 3.8.7
		 * @return boolean
		 * @throws \Exception if $this->provider is empty
		 * @throws \Exception if $this->photo_id is empty
		 */
		
		public function validate() {
			if (empty($this->provider)) {
				throw new Exception("Image provider cannot be empty");
			}
			
			if (!filter_var($this->photo_id)) {
				throw new Exception("Photo ID from the image provider cannot be empty");
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this image
		 * @since Version 3.8.7
		 * @return boolean
		 */
		
		public function commit() {
			$this->validate();
			
			$user_id = isset($this->author->User) && $this->author->User instanceof User ? $this->author->User->id : 0;
			
			$author = $this->author;
			unset($author->User);
			
			$data = array(
				"title" => $this->title,
				"description" => $this->description,
				"captured" => $this->DateCaptured instanceof DateTime ? $this->DateCaptured->format("Y-m-d H:i:s") : NULL,
				"provider" => $this->provider,
				"photo_id" => $this->photo_id,
				"user_id" => $user_id,
				"meta" => json_encode(array(
					"title" => $this->title,
					"description" => $this->description,
					"sizes" => $this->sizes,
					"links" => $this->links,
					"data" => $this->meta,
					"author" => $author
				))
			);
			
			if ($this->Place instanceof Place) {
				$data['lat'] = $this->Place->lat;
				$data['lon'] = $this->Place->lon;
			}
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$this->Memcached->delete($this->mckey);
				$this->Redis->delete($this->mckey); 
				
				$where = array(
					"id = ?" => $this->id
				);
				
				$Date = new DateTime();
				$data['modified'] = $Date->format("Y-m-d g:i:s");
				
				$this->db->update("image", $data, $where);
			} else {
				$this->db->insert("image", $data);
				$this->id = $this->db->lastInsertId();
				$this->url = Utility\Url::CreateFromImageID($this->id); 
			}
			
			$this->getJSON();
			
			return $this;
		}
		
		/**
		 * Check if this image has become stale
		 * @since Version 3.8.7
		 * @return boolean
		 */
		
		public function isStale() {
			if (!$this->Date instanceof DateTime) {
				return true;
			}
			
			$Now = new DateTime;
			$Diff = $this->Date->diff($Now);
			
			if ($Diff->d >= self::MAXAGE) {
				$this->Memcached->delete($this->mckey);
			
				Debug::LogCLI("Image ID " . $this->id . " (photo ID " . $this->photo_id . ") is stale"); 
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Get an instance of the image provider
		 * @since Version 3.9.1
		 * @return object
		 */
		
		public function getProvider() {
			
			if (!is_null($this->ImageProvider)) {
				return $this->ImageProvider; 
			}
			
			#printArray($this->provider);die;
			
			$imageprovider = __NAMESPACE__ . "\\Provider\\" . ucfirst($this->provider);
			$params = array();
			
			switch ($this->provider) {
				case "picasaweb" :
					$imageprovider = __NAMESPACE__ . "\\Provider\\PicasaWeb";
					break;
				
				case "rpoldgallery" : 
					$imageprovider = __NAMESPACE__ . "\\Provider\RPOldGallery";
					break;
				
				case "flickr" : 
					$params = array_merge(array(
						"oauth_token" => "",
						"oauth_secret" => ""
					), $this->providerOptions);
					
					if (isset($this->Config->Flickr->APIKey)) {
						$params['api_key'] = $this->Config->Flickr->APIKey;
					}
					
					break;
			}
			
			return new $imageprovider($params); 
			
		}
		
		/**
		 * Set the image provider's options
		 * @since Version 3.9.1
		 * @param array $options
		 * @return \Railpage\Images\Image
		 */
		 
		public function setProviderOptions($options) {
			
			$this->providerOptions = $options; 
			
			if (!is_null($this->ImageProvider)) {
				$this->ImageProvider->setOptions($this->providerOptions);
			}
			
			return $this;
			
		}
		
		/**
		 * Populate this image with fresh data
		 * @since Version 3.8.7
		 * @return $this
		 * @param boolean $force
		 * @param int $option
		 */
		
		public function populate($force = false, $option = NULL) {
			
			if ($force === false && !$this->isStale()) {
				return $this;
			}
			
			Debug::LogCLI("Fetching data from " . $this->provider . " for image ID " . $this->id . " (photo ID " . $this->photo_id . ")"); 
			
			/**
			 * Start the debug timer
			 */
			
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			/**
			 * New and improved populator using image providers
			 */
			
			$Provider = $this->getProvider();
			
			try {
				$data = $Provider->getImage($this->photo_id, $force); 
			} catch (Exception $e) {
				$expected = array(
					sprintf("Unable to fetch data from Flickr: Photo \"%s\" not found (invalid ID) (1)", $this->photo_id),
					"Unable to fetch data from Flickr: Photo not found (1)"
				);
				
				if (in_array($e->getMessage(), $expected)) {
					
					$where = [ "image_id = ?" => $this->id ];
					$this->db->delete("image_link", $where); 
					
					$where = [ "id = ?" => $this->id ];
					$this->db->delete("image", $where); 
					
					throw new Exception("Photo no longer available from " . $this->provider);
				}
			}
			
			if ($data) {
				$this->sizes = $data['sizes'];
				$this->title = empty($data['title']) ? "Untitled" : $data['title'];
				$this->description = $data['description'];
				$this->meta = array(
					"dates" => array(
						"posted" => $data['dates']['uploaded'] instanceof DateTime ? $data['dates']['uploaded']->format("Y-m-d H:i:s") : $data['dates']['uploaded']['date'],
						"taken" => $data['dates']['taken'] instanceof DateTime ? $data['dates']['taken']->format("Y-m-d H:i:s") : $data['dates']['taken']['date'],
					)
				);
				
				$this->author = new stdClass;
				$this->author->username = $data['author']['username'];
				$this->author->realname = !empty($data['author']['realname']) ? $data['author']['realname'] : $data['author']['username'];
				$this->author->id = $data['author']['id'];
				$this->author->url = "https://www.flickr.com/photos/" . $this->author->id;
				
				if (isset($data['author']['railpage_id']) && filter_var($data['author']['railpage_id'], FILTER_VALIDATE_INT)) {
					$this->author->User = UserFactory::CreateUser($data['author']['railpage_id']); 
				}
				
				/**
				 * Load the tags
				 */
				
				if (isset($data['tags']) && is_array($data['tags']) && count($data['tags'])) {
					foreach ($data['tags'] as $row) {
						$this->meta['tags'][] = $row['raw'];
					}
				}
				
				/**
				 * Load the Place object
				 */
				
				if ($option != Images::OPT_NOPLACE && isset($data['location'])) {
					try {
						$this->Place = Place::Factory($data['location']['latitude'], $data['location']['longitude']);
					} catch (Exception $e) {
						// Throw it away. Don't care.
					}
				}
				
				$this->links = new stdClass;
				$this->links->provider = isset($data['urls']['url'][0]['_content']) ? $data['urls']['url'][0]['_content'] : $data['urls'][key($data['urls'])];
				
				$this->commit();
				$this->cacheGeoData(); 
				
				return true;
			}
			
			/**
			 * Fetch data in various ways for different photo providers
			 */
			
			switch ($this->provider) {
				
				/**
				 * Picasa
				 */
				
				case "picasaweb" : 
					
					if (empty($this->meta) && !is_null(filter_input(INPUT_SERVER, "HTTP_REFERER", FILTER_SANITIZE_URL)) && strpos(filter_input(INPUT_SERVER, "HTTP_REFERER", FILTER_SANITIZE_URL), "picasaweb.google.com")) {
						$album = preg_replace("@(http|https)://picasaweb.google.com/([a-zA-Z\-\.]+)/(.+)@", "$2", filter_input(INPUT_SERVER, "HTTP_REFERER", FILTER_SANITIZE_URL));
						
						if (is_string($album)) {
							$update_url = sprintf("https://picasaweb.google.com/data/feed/api/user/%s/photoid/%s?alt=json", $album, $this->photo_id);
						}
					}
					
					if (isset($update_url)) {
						$data = file_get_contents($update_url);
						$json = json_decode($data, true);
						
						$this->meta = array(
							"title" => $json['feed']['subtitle']['$t'],
							"description" => $json['feed']['title']['$t'],
							"dates" => array(
								"posted" => date("Y-m-d H:i:s", $json['feed']['gphoto$timestamp']['$t']),
							),
							"sizes" => array(
								"original" => array(
									"width" => $json['feed']['gphoto$width']['$t'],
									"height" => $json['feed']['gphoto$height']['$t'],
									"source" => str_replace(
													sprintf("/s%d/", $json['feed']['media$group']['media$thumbnail'][0]['width']), 
													sprintf("/s%d/", $json['feed']['gphoto$width']['$t']),
													$json['feed']['media$group']['media$thumbnail'][0]['url']
									),
								),
								"largest" => array(
									"width" => $json['feed']['gphoto$width']['$t'],
									"height" => $json['feed']['gphoto$height']['$t'],
									"source" => str_replace(
													sprintf("/s%d/", $json['feed']['media$group']['media$thumbnail'][0]['width']), 
													sprintf("/s%d/", $json['feed']['gphoto$width']['$t']),
													$json['feed']['media$group']['media$thumbnail'][0]['url']
									),
								),
							),
							"photo_id" => $json['feed']['gphoto$id']['$t'],
							"album_id" => $json['feed']['gphoto$albumid']['$t'],
							"updateurl" => sprintf("%s?alt=json", $json['feed']['id']['$t'])
						);
						
						foreach ($json['feed']['media$group']['media$thumbnail'] as $size) {
							if ($size['width'] <= 500 && $size['width'] > 200) {
								$this->meta['sizes']['small'] = array(
									"width" => $size['width'],
									"height" => $size['height'],
									"source" => $size['url']
								);
							}
							
							if ($size['width'] <= 200) {
								$this->meta['sizes']['small'] = array(
									"width" => $size['width'],
									"height" => $size['height'],
									"source" => $size['url']
								);
							}
							
							if ($size['width'] <= 1024 && $size['width'] > 500) {
								$this->meta['sizes']['large'] = array(
									"width" => $size['width'],
									"height" => $size['height'],
									"source" => $size['url']
								);
							}
						}
						
						foreach ($json['feed']['link'] as $link) {
							if ($link['rel'] == "alternate" && $link['type'] == "text/html") {
								$this->meta['source'] = $link['href'];
							}
						}
						
						if ($option != Images::OPT_NOPLACE && isset($json['feed']['georss$where']['gml$Point']) && is_array($json['feed']['georss$where']['gml$Point'])) {
							$pos = explode(" ", $json['feed']['georss$where']['gml$Point']['gml$pos']['$t']);
							$this->Place = Place::Factory($pos[0], $pos[1]);
						}
						
						$this->title = $this->meta['title'];
						$this->description = $this->meta['description'];
						
						$this->author = new stdClass;
						$this->author->username = $album;
						$this->author->id = $album;
						$this->author->url = sprintf("%s/%s", $json['feed']['generator']['uri'], $album);
					}
					
					$this->sizes = $this->meta['sizes'];
					
					$this->commit();
					break;
				
				/**
				 * Vicsig
				 */
				
				case "vicsig" : 
					
					if (strpos(filter_input(INPUT_SERVER, "HTTP_REFERER", FILTER_SANITIZE_URL), "vicsig.net/photo")) {
						$this->meta['source'] = filter_input(INPUT_SERVER, "HTTP_REFERER", FILTER_SANITIZE_STRING); 
						
						$response = $this->GuzzleClient->get($this->meta['source']);
						
						if ($response->getStatusCode() != 200) {
							throw new Exception(sprintf("Failed to fetch image data from %s: HTTP error %s", $this->provider, $response->getStatusCode()));
						}
						
						/**
						 * Start fetching it
						 */
						
						$data = $response->getBody();
						
						$doc = new DomDocument(); 
						$doc->loadHTML($data);
						
						$images = $doc->getElementsByTagName("img"); 
						
						foreach ($images as $element) {
							
							if (!empty($element->getAttribute("src")) && !empty($element->getAttribute("alt"))) {
								#$image_title = $element->getAttribute("alt");
								
								$this->sizes['original'] = array(
									"source" => $element->getAttribute("src"),
									"width" => $element->getAttribute("width"), 
									"height" => $element->getAttribute("height"),
								);
								
								if (substr($this->sizes['original']['source'], 0, 1) == "/") {
									$this->sizes['original']['source'] = "http://www.vicsig.net" . $this->sizes['original']['source'];
								}
								
								break;
							}
						}
						
						$desc = $doc->getElementsByTagName("i");
						
						foreach ($desc as $element) {
							if (!isset($image_desc)) {
								$text = trim($element->nodeValue); 
								$text = str_replace("\r\n", "\n", $text); 
								$text = explode("\n", $text);
								
								/**
								 * Loop through the exploded text and remove the obvious date/author/etc
								 */ 
								
								foreach ($text as $k => $line) {
									
									// Get the author
									if (preg_match("@Photo: @i", $line)) {
										$this->author = new stdClass;
										$this->author->realname = str_replace("Photo: ", "", $line); 
										$this->author->url = filter_input(INPUT_SERVER, "HTTP_REFERER", FILTER_SANITIZE_STRING); 
										unset($text[$k]);
									}
									
									// Get the date
									try {
										$this->meta['dates']['posted'] = (new DateTime($line))->format("Y-m-d H:i:s"); 
										unset($text[$k]);
									} catch (Exception $e) {
										// Throw it away
									}
								}
								
								/**
								 * Whatever's left must be the photo title and description
								 */
								
								foreach ($text as $k => $line) {
									if (empty($this->title)) {
										$this->title = $line;
										continue;
									}
									
									$this->description .= $line;
								}
								
								$this->links = new stdClass;
								$this->links->provider = filter_input(INPUT_SERVER, "HTTP_REFERER", FILTER_SANITIZE_STRING); 
								
								$this->commit();
							}
						}
						
					}
					
					break;
				
			}
			
			/**
			 * End the debug timer
			 */
				
			if (RP_DEBUG) {
				$site_debug[] = __CLASS__ . "::" . __FUNCTION__ . "() : completed in " . round(microtime(true) - $debug_timer_start, 5) . "s";
			}
			
			/**
			 * Find objects (locomotives, loco classes, liveries) in this picture
			 */
			
			/*
			$this->findObjects("railpage.locos.loco")
				 ->findObjects("railpage.locos.class")
				 ->findObjects("railpage.locos.liveries.livery");
			*/
			
			return $this;
		}
		
		/**
		 * Link this image to a loco, location, etc
		 * @param string $namespace
		 * @param int $id
		 * @throws \Exception if $namespace is null
		 * @throws \Exception if $namespace_key is null
		 */
		
		public function addLink($namespace = NULL, $namespace_key = NULL) {
			if (is_null($namespace)) {
				throw new Exception("Parameter 1 (namespace) cannot be empty");
			}
			
			if (!filter_var($namespace_key, FILTER_VALIDATE_INT)) {
				throw new Exception("Parameter 2 (namespace_key) cannot be empty");
			}
			
			$id = $this->db->fetchOne("SELECT id FROM image_link WHERE namespace = ? AND namespace_key = ? AND image_id = ?", array($namespace, $namespace_key, $this->id)); 
			
			if (!filter_var($id, FILTER_VALIDATE_INT)) {
				$data = array(
					"image_id" => $this->id,
					"namespace" => $namespace,
					"namespace_key" => $namespace_key,
					"ignored" => 0
				);
				
				$this->db->insert("image_link", $data);
			}
			
			$this->getJSON();
			
			return $this;
		}
		
		/**
		 * Generate the JSON data string
		 * @return $this
		 */
		
		public function getJSON() {
			if (isset($this->author)) {
				$author = clone $this->author;
				
				if (isset($author->User) && $author->User instanceof User) {
					$author->User = $author->User->getArray(); 
				}
			}
			
			$data = array(
				"id" => $this->id,
				"title" => $this->title,
				"description" => $this->description,
				"provider" => array(
					"name" => $this->provider,
					"photo_id" => $this->photo_id
				),
				"sizes" => $this->sizes,
				"author" => isset($author) ? $author : false,
				"url" => $this->url instanceof Url ? $this->url->getURLs() : array(),
				"dates" => array()
			);
			
			$times = [ "posted", "taken" ]; 
			
			
			#printArray($this->meta['dates']);die;
			
			foreach ($times as $time) {
				if (isset($this->meta['dates'][$time])) {
					$Date = filter_var($this->meta['dates'][$time], FILTER_VALIDATE_INT) ? new DateTime("@" . $this->meta['dates'][$time]) : new DateTime($this->meta['dates'][$time]);
					
					$data['dates'][$time] = array(
						"absolute" => $Date->format("Y-m-d H:i:s"),
						"nice" => $Date->Format("d H:i:s") == "01 00:00:00" ? $Date->Format("F Y") : $Date->format("F j, Y, g:i a"),
						"relative" => ContentUtility::RelativeTime($Date)
					);
				}
			}
			
			if ($this->Place instanceof Place) {
				$data['place'] = array(
					"url" => $this->Place->url,
					"lat" => $this->Place->lat,
					"lon" => $this->Place->lon,
					"name" => $this->Place->name,
					"country" => array(
						"code" => $this->Place->Country->code,
						"name" => $this->Place->Country->name,
						"url" => $this->Place->Country->url
					)
				);
			}
			
			$this->json = json_encode($data);
			
			return $this;
		}
		
		/**
		 * Get locos in this image
		 * @since Version 3.8.7
		 * @return array
		 */
		
		public function getLocos() {
			$query = "SELECT namespace_key as loco_id FROM image_link WHERE image_id = ? AND namespace = ? AND ignored = 0";
			
			$return = array();
			
			foreach ($this->db->fetchAll($query, array($this->id, "railpage.locos.loco")) as $row) {
				$return[] = $row['loco_id'];
			}
			
			return $return;
		}
		
		/**
		 * Find Railpage objects (loco, class, livery) in this image
		 * @since Version 3.8.7
		 * @param string $namespace
		 * @param boolean $force
		 * @return \Railpage\Images\Image;
		 */
		
		public function findObjects($namespace = NULL, $force = false) {
			
			if (is_null($namespace)) {
				throw new Exception("Parameter 1 (namespace) cannot be empty");
			}
			
			$key = sprintf("railpage:images.image=%d;objects.namespace=%s;lastupdate", $this->id, $namespace); 
			
			$lastupdate = $this->Memcached->fetch($key);
			
			if (!$force && $lastupdate && $lastupdate > strtotime("1 day ago")) {
				return $this;
			}
			
			/**
			 * Start the debug timer
			 */
			
			$timer = Debug::GetTimer(); 
			
			switch ($namespace) {
				
				case "railpage.locos.loco" :
					if (isset($this->meta['tags'])) {
						
						foreach ($this->meta['tags'] as $tag) {
							if (preg_match("@railpage:class=([0-9]+)@", $tag, $matches)) {
								Debug::LogEvent(__METHOD__ . " :: #1 Instantating new LocoClass object with ID " . $matches[1] . "  "); 
								$LocoClass = LocosFactory::CreateLocoClass($matches[1]); 
							}
						}
						
						foreach ($this->meta['tags'] as $tag) {
							if (isset($LocoClass) && $LocoClass instanceof LocoClass && preg_match("@railpage:loco=([a-zA-Z0-9]+)@", $tag, $matches)) {
								Debug::LogEvent(__METHOD__ . " :: #2 Instantating new LocoClass object with class ID " . $LocoClass->id . " and loco number " . $matches[1] . "  "); 
								$Loco = LocosFactory::CreateLocomotive(false, $LocoClass->id, $matches[1]); 
								
								if (filter_var($Loco->id, FILTER_VALIDATE_INT)) {
									$this->addLink($Loco->namespace, $Loco->id);
								}
							}
						}
						
						foreach ($this->db->fetchAll("SELECT id AS class_id, flickr_tag AS class_tag FROM loco_class") as $row) {
							foreach ($this->meta['tags'] as $tag) {
								if (stristr($tag, $row['class_tag']) && strlen(str_replace($row['class_tag'] . "-", "", $tag) > 0)) {
									$loco_num = str_replace($row['class_tag'] . "-", "", $tag);
									Debug::LogEvent(__METHOD__ . " :: #3 Instantating new LocoClass object with class ID " . $row['class_id'] . " and loco number " . $loco_num . "  "); 
									$Loco = LocosFactory::CreateLocomotive(false, $row['class_id'], $loco_num);
									
									if (filter_var($Loco->id, FILTER_VALIDATE_INT)) {
										$this->addLink($Loco->namespace, $Loco->id);
										
										if (!$Loco->hasCoverImage()) {
											$Loco->setCoverImage($this);
										}
										
										if (!$Loco->Class->hasCoverImage()) {
											$Loco->Class->setCoverImage($this);
										}
									}
								}
							}
						}
					}
					
					break;
				
				case "railpage.locos.class" :
					if (isset($this->meta['tags'])) {
						foreach ($this->db->fetchAll("SELECT id AS class_id, flickr_tag AS class_tag FROM loco_class") as $row) {
							foreach ($this->meta['tags'] as $tag) {
								if ($tag == $row['class_tag']) {
									$LocoClass = LocosFactory::CreateLocoClass($row['class_id']);
									
									if (filter_var($LocoClass->id, FILTER_VALIDATE_INT)) {
										$this->addLink($LocoClass->namespace, $LocoClass->id);
									}
								}
							}
						}
						
						foreach ($this->meta['tags'] as $tag) {
							if (preg_match("@railpage:class=([0-9]+)@", $tag, $matches)) {
								$LocoClass = LocosFactory::CreateLocoClass($matches[1]); 
								
								if (filter_var($LocoClass->id, FILTER_VALIDATE_INT)) {
									$this->addLink($LocoClass->namespace, $LocoClass->id);
										
									if (!$LocoClass->hasCoverImage()) {
										$LocoClass->setCoverImage($this);
									}
								}
							}
						}
					}
					
					break;
				
				case "railpage.locos.liveries.livery" : 
					if (isset($this->meta['tags'])) {
						foreach ($this->meta['tags'] as $tag) {
							if (preg_match("@railpage:livery=([0-9]+)@", $tag, $matches)) {
								$Livery = new Livery($matches[1]); 
								
								if (filter_var($Livery->id, FILTER_VALIDATE_INT)) {
									$this->addLink($Livery->namespace, $Livery->id);
								}
							}
						}
					}
					
					break;
			}
			
			Debug::LogEvent(__METHOD__ . "(\"" . $namespace . "\")", $timer);
			
			$this->Memcached->save($key, time());
			
			return $this;
		}
		
		/**
		 * Get objects linked to this image
		 * @since Version 3.8.7
		 * @return array
		 * @param string $namespace
		 */
		
		public function getObjects($namespace = NULL) {
			$params = array(
				$this->id
			);
			
			if (!is_null($namespace)) {
				$params[] = $namespace;
				$where_namespace = "AND namespace = ?";
			} else {
				$where_namespace = "";
			}
			
			#printArray($params);
			
			$rs = $this->db->fetchAll("SELECT * FROM image_link WHERE image_id = ? " . $where_namespace . " AND ignored = 0", $params);
			
			#printArray($rs);die;
			
			return $rs;
		}
		
		/**
		 * Mark an image as ignored
		 * @since Version 3.8.7
		 * @param boolean $ignored
		 */
		
		public function ignored($ignored = true, $link_id = 0) {
			$data = array(
				"ignored" => intval($ignored)
			);
			
			$where = array(
				"image_id = ?" => $this->id
			);
			
			if (filter_var($link_id, FILTER_VALIDATE_INT) && $link_id > 0) {
				$where['id = ?'] = $link_id;
			}
			
			$this->db->update("image_link", $data, $where);
			
			return true;
		}
		
		/**
		 * Get an associative array representing this object
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getArray() {
			$this->getJSON();
			
			return json_decode($this->json, true);
		}
		
		/**
		 * Suggest locos to tag
		 * @since Version 3.9.1
		 * @return array
		 * @param boolean $skiptagged Remove locos already tagged in this photo from the list of suggested locos
		 */
		
		public function suggestLocos($skiptagged = true) {
			
			$locolookup = array(); 
			$locos = array(); 
			
			$regexes = array(
				"[a-zA-Z0-9\w+]{4,6}",
				"[0-9\w+]{3,5}",
				"[a-zA-Z0-9\w+]{2}",
				"[a-zA-Z0-9\s\w+]{4,6}"
			);
			
			// Strip dates from our lookup
			$stripdates = array(
				"[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}",  // 12/05/2015
				"[0-9]{1}\/[0-9]{2}\/[0-9]{2}",        // 1/05/2015
				"[0-9]{4}\/[0-9]{2}\/[0-9]{2}",        // 2015/05/12
				"[0-9]{2}\/[0-9]{4}",                  // 05/2015
				"[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}",      // 12-05-2015
				"[0-9]{1,2}-[0-9]{1,2}-[0-9]{2}",      // 12-05-15
				"[0-9]{4}-[0-9]{2}-[0-9]{2}",          // 2015-05-12
				"[0-9]{4}s",                           // 1990s
				"[0-9]{2}s",                           // 90s
				"[0-9]{4}-[0-9]{2}",                   // 2015-05
				"[0-9]{2}:[0-9]{2}",                   // 16:30
				"(January|February|March|April|May|June|July|August|September|October|November|December)\s[0-9]{2,4}",
				"(Jan|Feb|Mar|Apr|May|Jun|Jul|Augt|Sep|Sept|Oct|Nov|Dec)\s[0-9]{2,4}"
			);
			
			$stripdates = "/(" . implode("|", $stripdates) . ")/";
			
			$stripetc = array(
				"[\#0-9]{5}",
				"railpage:livery=[0-9]+",
				"railpage:class=[0-9]+"
			);
			
			$stripetc = "/(" . implode("|", $stripetc) . ")/";
				
			$title = $this->title;
			$desc = $this->description;
			
			$title = preg_replace($stripdates, "", $title);
			$desc = preg_replace($stripdates, "", $desc);
			
			$title = preg_replace($stripetc, "", $title);
			$desc = preg_replace($stripetc, "", $desc);
			
			/**
			 * Loop through all our possible regexes and search
			 */
			
			foreach ($regexes as $regex) {
				$regex = "/\b(" . $regex . ")\b/";
			
				preg_match_all($regex, $title, $matches['title']);
				preg_match_all($regex, $desc, $matches['description']);
				
				if (isset($this->meta['tags']) && count($this->meta['tags'])) {
					foreach ($this->meta['tags'] as $tag) {
						// strip the tags
						$tag = trim(preg_replace($stripetc, "", $tag));
						
						if (!empty($tag)) {
							preg_match_all($regex, $tag, $matches[]);
						}
					}
				}
				
				foreach ($matches as $area => $matched) {
					foreach ($matched as $key => $array) {
						foreach ($array as $k => $v) {
							if (!empty($v) && preg_match("/([0-9])/", $v) && !preg_match("/(and|to|or|for)/", $v)) {
								if (!in_array(trim($v), $locolookup)) {
									$locolookup[] = trim($v);
								}
							}
						}
					}
				}
			}
			
			/**
			 * Try to include loco numbers with spaces (eg RT 40 vs RT40) in the lookup
			 */
			
			foreach ($locolookup as $k => $num) {
				if (preg_match("/(\s)/", $num)) {
					preg_match("/([a-zA-Z0-9]+)(\s)([a-zA-Z0-9]+)/", $num, $matches);
					
					if (isset($matches[3])) {
						$prop = sprintf("%s%s", $matches[1], $matches[3]); 
						if (!in_array($prop, $locolookup)) {
							$locolookup[] = $prop; 
						}
					}
				} elseif (strlen($num) == 5) {
					preg_match("/([a-zA-Z0-9]{2})([0-9]{3})/", $num, $matches);
					
					if (isset($matches[2])) {
						$prop = sprintf("%s %s", $matches[1], $matches[2]); 
						if (!in_array($prop, $locolookup)) {
							$locolookup[] = $prop; 
						}
					}
				}
			}
			
			$locolookup = array_unique($locolookup);
			
			/**
			 * Prepare the SQL query 
			 */
			
			$query = "SELECT l.loco_id, l.loco_num, l.class_id, c.name AS class_name, s.name AS status_name, s.id AS status_id, t.id AS type_id, t.title AS type_name, g.gauge_id, CONCAT(g.gauge_name, ' ', g.gauge_imperial) AS gauge_formatted, o.operator_id, o.operator_name
				FROM loco_unit AS l 
					LEFT JOIN loco_class AS c ON l.class_id = c.id 
					LEFT JOIN loco_type AS t ON c.loco_type_id = t.id
					LEFT JOIN loco_status AS s ON l.loco_status_id = s.id
					LEFT JOIN loco_gauge AS g ON g.gauge_id = l.loco_gauge_id
					LEFT JOIN operators AS o ON l.operator_id = o.operator_id
				WHERE l.loco_num IN ('" . implode("','", $locolookup) . "') 
					AND l.loco_status_id NOT IN (2)";
			
			/**
			 * Remove existing tags from our DB query
			 */
			
			if ($skiptagged === true) {
				$tags = $this->getObjects("railpage.locos.loco"); 
				
				if (count($tags)) {
					$ids = array(); 
					
					foreach ($tags as $tag) {
						$ids[] = $tag['namespace_key'];
					}
					
					$query .= " AND l.loco_id NOT IN (" . implode(",", $ids) . ")";
				}
				
				$query .= " ORDER BY CHAR_LENGTH(l.loco_num) DESC";
			}
			
			/**
			 * Loop through the DB results
			 */
			
			$i = 0; 
			
			foreach ($this->db->fetchAll($query) as $row) {
				$row['object'] = "Railpage\Locos\Locomotive";
				$locos[$row['loco_id']] = $row;
				
				$i++; 
				
				if ($i == 5) {
					break;
				}
			}
			
			return $locos;
		}
		
		/**
		 * Suggest liveries to tag based on other locos in this class
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function suggestLiveries() {
			$query = '
				SELECT livery.livery_id AS id, livery.livery AS name, livery.photo_id
				FROM loco_livery AS livery
					LEFT JOIN image_link AS link ON link.namespace_key = livery.livery_id
				WHERE link.namespace = "railpage.locos.liveries.livery"
					AND image_id IN (
						SELECT image_id 
						FROM image_link 
						WHERE namespace = ? 
							AND namespace_key IN (
								SELECT namespace_key AS class_id 
								FROM image_link 
								WHERE namespace = ? 
									AND image_id = ?
									AND ignored = 0
							)
					)
					AND livery_id NOT IN (
						SELECT namespace_key FROM image_link WHERE namespace = "railpage.locos.liveries.livery" AND image_id = ?
					)
				GROUP BY livery.livery_id
				ORDER BY link.id DESC';
			
			$params = [
				"railpage.locos.loco",
				"railpage.locos.loco",
				$this->id,
				$this->id
			];
					
			$liveries = $this->db->fetchAll($query, $params);
			
			if (count($liveries) === 0) {
				$params = [
					"railpage.locos.class",
					"railpage.locos.class",
					$this->id,
					$this->id
				];
			
				$liveries = $this->db->fetchAll($query, $params); 
			}
			
			return $liveries;
		}
		
		/**
		 * Insert this photo into the geocache
		 * @since Version 3.9.1
		 * @return \Railpage\Images\Image
		 */
		
		private function cacheGeoData() {
			
			if (!$this->Place instanceof Place) {
				return $this;
			}
			
			$data = [
				"photo_id" => $this->photo_id,
				"lat" => $this->Place->lat, 
				"lon" => $this->Place->lon,
				"owner" => $this->author->url,
				"ownername" => $this->author->username,
				"title" => $this->title,
				"tags" => isset($this->meta['tags']) ? $this->meta['tags'] : array(),
				"dateadded" => "",
				"dateupload" => "",
				"datetaken" => ""
			];
			
			$sizes = [ 75, 100, 240, 500, 640, 1024, 320, 800 ];
				
			foreach ($this->sizes as $k => $row) {
				$i = array_search($row['width'], $sizes);
				if ($i !== false) {
					$size = sprintf("size%d", $i); 
					$width = sprintf("%s_w", $size);
					$height = sprintf("%s_h", $size);
			
					$data[$size] = $row['source'];
					$data[$width] = $row['width'];
					$data[$height] = $row['height'];
					
					continue;
				}
				
				if ($k == "original" || $k == "largest" || $row['width'] >= 1024) {
					$size = sprintf("size%d", 8); 
					$width = sprintf("%s_w", $size);
					$height = sprintf("%s_h", $size);
					
					$data[$size] = $row['source'];
					$data[$width] = $row['width'];
					$data[$height] = $row['height'];
					
					continue;
				}
			}
			
			if (is_array($data['tags'])) {
				$data['tags'] = implode(" ", $data['tags']); 
			}
			
			$query = "INSERT IGNORE INTO flickr_geodata SET ";
			
			$terms = count($data);
			foreach ($data as $key => $val) {
				$terms--;
				
				$query .= $key . ' = ' . $this->db->quote($val);
				
				if ($terms) {
					$query .= ', ';
				}
			}
			
			$cn = $this->db->getConnection();
			
			$result = $cn->query($query); 
			
			return $this;
			
		}
		
		/**
		 * Bump the hit counter
		 * @since Version 3.9.1
		 * @return \Railpage\Images\Image
		 */
		
		public function hit() {
			
			$data = [
				"hits_today" => new Zend_Db_Expr('hits_today + 1'),
				"hits_weekly" => new Zend_Db_Expr('hits_weekly + 1'),
				"hits_overall" => new Zend_Db_Expr('hits_overall + 1')
			];
			
			$where = [
				"id = ?" => $this->id
			];
			
			$this->db->update("image", $data, $where);
			
			return $this;
			
		}
		
		/**
		 * Update the geoplace reference for this image
		 * @since Version 3.9.1
		 * @return void
		 */
		
		public function updateGeoPlace() {
			
			if (!filter_var($this->lat, FILTER_VALIDATE_FLOAT) || !filter_var($this->lat, FILTER_VALIDATE_FLOAT)) {
				return;
			}
			
			$timer = microtime(true);
			
			$GeoPlaceID = PlaceUtility::findGeoPlaceID($this->lat, $this->lon); 
			
			#var_dump($GeoPlaceID);die;
			
			$data = [ "geoplace" => $GeoPlaceID	];
			
			$where = [ "id = ?" => $this->id ];
			$this->db->update("image", $data, $where); 
			
			$this->Memcached->delete($this->mckey);
			$this->Redis->delete($this->mckey); 
			
			Debug::logEvent(__METHOD__, $timer);
			Debug::LogCLI(__METHOD__, $timer);
			
			return;
			
		}
		
		/**
		 * Hide this photo from the pool and searches
		 * @since Version 3.9.1
		 * @return \Railpage\Images\Image
		 */
		
		public function hide() {
			
			$data = [ "hidden" => 1 ];
			$where = [ "id = ?" => $this->id ];
			
			$this->db->update("image", $data, $where); 
			
			return $this;
			
		}
	}
	