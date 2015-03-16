<?php
	/**
	 * Store and fetch image data from our local database
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images;
	
	use Railpage\Users\User;
	use Railpage\API;
	use Railpage\AppCore;
	use Railpage\Place;
	use Railpage\Locos\Locomotive;
	use Railpage\Locos\LocoClass;
	use Railpage\Locos\Liveries\Livery;
	use Railpage\Module;
	use Railpage\Url;
	use Exception;
	use DateTime;
	use DateTimeZone;
	use DateInterval;
	use stdClass;
	use DomDocument;
	use GuzzleHttp\Client;
	
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
		 * Constructor
		 * @since Version 3.8.7
		 * @param int $id
		 * @param int $option
		 */
		
		public function __construct($id = NULL, $option = NULL) {
			
			parent::__construct();
			
			if (RP_DEBUG) {
				global $site_debug;
				$zzdebug_timer_start = microtime(true);
			}
			
			$this->GuzzleClient = new Client;
			
			$this->Module = new Module("images");
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
			
				/**
				 * Record this in the debug log
				 */
				
				if (function_exists("debug_recordInstance")) {
					debug_recordInstance(__CLASS__);
				}
				
				$this->mckey = sprintf("railpage:image=%d", $id);
				
				if (!$row = $this->Redis->fetch($this->mckey)) {
				
					$query = "SELECT i.id, i.provider, i.photo_id, i.modified, i.meta, i.lat, i.lon FROM image AS i WHERE i.id = ?";
				
					$row = $this->db->fetchRow($query, $id);
					$row['meta'] = json_decode($row['meta'], true);
					
					$this->Redis->save($this->mckey, $row, strtotime("+24 hours"));
				}
				
				$this->id = $id;
				$this->provider = $row['provider'];
				$this->photo_id = $row['photo_id'];
				$this->Date = new DateTime($row['modified']);
				
				$this->title = !empty($row['meta']['title']) ? (function_exists("format_topictitle") ? format_topictitle($row['meta']['title']) : $row['meta']['title']) : "Untitled";
				$this->description = $row['meta']['description'];
				$this->sizes = $row['meta']['sizes'];
				$this->links = $row['meta']['links'];
				$this->meta = $row['meta']['data'];
				$this->url = new Url("/image?id=" . $this->id);
				
				if ($this->provider == "rpoldgallery") {
					$GalleryImage = new \Railpage\Gallery\Image($this->photo_id);
					$this->url->source = $GalleryImage->url->url;
					
					if (empty($this->meta['source'])) {
						$this->meta['source'] = $this->url->source; 
					}
				}
				
				/**
				 * Normalize some sizes
				 */
				
				if (count($this->sizes)) {
				
					if (!isset($this->sizes['thumb'])) {
						foreach ($this->sizes as $size) {
							if ($size['width'] >= 280 && $size['height'] >= 150) {
								$this->sizes['thumb'] = $size;
								break;
							}
						}
					}
					
					if (!isset($this->sizes['small'])) {
						foreach ($this->sizes as $size) {
							if ($size['width'] >= 500 && $size['height'] >= 281) {
								$this->sizes['small'] = $size;
								break;
							}
						}
					}
					
					$width = 0;
					
					foreach ($this->sizes as $size) {
						if ($size['width'] > $width) {
							$this->sizes['largest'] = $size;
						
							$width = $size['width'];
						}
					}
				
					foreach ($this->sizes as $size) {
						if ($size['width'] >= 1920) {
							$this->sizes['fullscreen'] = $size;
							break;
						}
					}
				
					foreach ($this->sizes as $size) {
						if ($size['width'] > 1024 && $size['width'] <= 1920) {
							$this->sizes['larger'] = $size;
							break;
						}
					}
				
					foreach ($this->sizes as $size) {
						if ($size['width'] == 1024) {
							$this->sizes['large'] = $size;
							break;
						}
					}
				
					foreach ($this->sizes as $size) {
						if ($size['width'] == 800) {
							$this->sizes['medium'] = $size;
							break;
						}
					}
				}
				
				if (isset($row['meta']['author'])) {
					$this->author = json_decode(json_encode($row['meta']['author']));
					
					if (isset($this->author->railpage_id)) {
						$this->author->User = new User($this->author->railpage_id);
					}
				} else {
					$this->populate(true, $option);
				}
				
				if ($option != Images::OPT_NOPLACE && round($row['lat'], 3) != "0.000" && round($row['lon'], 3) != "0.000") {
					try {
						$this->Place = new Place($row['lat'], $row['lon']);
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
			
				$this->getJSON();
			}
			
			if (RP_DEBUG) {
				$site_debug[] = "Railpage: " . __CLASS__ . "(" . $this->id . ") instantiated in " . round(microtime(true) - $zzdebug_timer_start, 5) . "s";
			}
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
			
			$author = $this->author;
			unset($author->User);
			
			$data = array(
				"provider" => $this->provider,
				"photo_id" => $this->photo_id,
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
				removeMemcacheObject($this->mckey);
				
				$where = array(
					"id = ?" => $this->id
				);
				
				$Date = new DateTime();
				$data['modified'] = $Date->format("Y-m-d g:i:s");
				
				$this->db->update("image", $data, $where);
			} else {
				$this->db->insert("image", $data);
				$this->id = $this->db->lastInsertId();
				$this->url = "/image?id=" . $this->id;
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
				removeMemcacheObject($this->mckey);
				return true;
			}
			
			return false;
		}
		
		/**
		 * Populate this image with fresh data
		 * @since Version 3.8.7
		 * @return $this
		 * @param boolean $force
		 * @param int $option
		 */
		
		public function populate($force = false, $option = NULL) {
			$RailpageAPI = new API($this->Config->API->Key, $this->Config->API->Secret);
			
			if ($force === false && !$this->isStale()) {
				return $this;
			}
			
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
			
			$imageprovider = __NAMESPACE__ . "\\Provider\\" . ucfirst($this->provider);
			
			switch ($this->provider) {
				case "picasaweb" :
					$imageprovider = __NAMESPACE__ . "\\Provider\\PicasaWeb";
					$params = array();
					break;
				
				case "flickr" : 
					$params = array(
						"api_key" => $this->Config->Flickr->APIKey,
						"oauth_token" => "",
						"oauth_secret" => ""
					);
					break;
			}
			
			$Provider = new $imageprovider($params); 
			
			if ($data = $Provider->getImage($this->photo_id, $force)) {
				$this->sizes = $data['sizes'];
				$this->title = $data['title'];
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
					$this->author->User = new User($data['author']['railpage_id']); 
				}
				
				/**
				 * Load the tags
				 */
				
				if (isset($data['tags']) && count($data['tags'])) {
					foreach ($data['tags'] as $row) {
						$this->meta['tags'][] = $row['raw'];
					}
				}
				
				/**
				 * Load the Place object
				 */
				
				if ($option != Images::OPT_NOPLACE && isset($data['location'])) {
					try {
						$this->Place = new Place($data['location']['latitude'], $data['location']['longitude']);
					} catch (Exception $e) {
						// Throw it away. Don't care.
					}
				}
				
				$this->links = new stdClass;
				$this->links->provider = $data['urls']['url'][0]['_content'];
				
				$this->commit();
				
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
					
					if (empty($this->meta) && isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], "picasaweb.google.com")) {
						$album = preg_replace("@(http|https)://picasaweb.google.com/([a-zA-Z\-\.]+)/(.+)@", "$2", $_SERVER['HTTP_REFERER']);
						
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
							$this->Place = new Place($pos[0], $pos[1]);
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
					
					if (strpos($_SERVER['HTTP_REFERER'], "vicsig.net/photo")) {
						$this->meta['source'] = $_SERVER['HTTP_REFERER'];
						
						$response = $this->GuzzleClient->get($_SERVER['HTTP_REFERER']);
						
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
										$this->author->url = $_SERVER['HTTP_REFERER'];
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
								$this->links->provider = $_SERVER['HTTP_REFERER'];
								
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
			$data = array(
				"id" => $this->title,
				"title" => $this->title,
				"description" => $this->description,
				"provider" => array(
					"name" => $this->provider,
					"photo_id" => $this->photo_id
				),
				"sizes" => $this->sizes
			);
			
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
		 */
		
		public function findObjects($namespace = NULL) {
			if (is_null($namespace)) {
				throw new Exception("Parameter 1 (namespace) cannot be empty");
			}
			
			/**
			 * Start the debug timer
			 */
			
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			switch ($namespace) {
				
				case "railpage.locos.loco" :
					if (isset($this->meta['tags'])) {
						
						foreach ($this->meta['tags'] as $tag) {
							if (preg_match("@railpage:class=([0-9]+)@", $tag, $matches)) {
								$LocoClass = new LocoClass($matches[1]); 
							}
						}
						
						foreach ($this->meta['tags'] as $tag) {
							if (isset($LocoClass) && $LocoClass instanceof LocoClass && preg_match("@railpage:loco=([a-zA-Z0-9]+)@", $tag, $matches)) {
								$Loco = new Locomotive(false, $LocoClass->id, $matches[1]); 
								
								if (filter_var($Loco->id, FILTER_VALIDATE_INT)) {
									$this->addLink($Loco->namespace, $Loco->id);
								}
							}
						}
						
						foreach ($this->db->fetchAll("SELECT id AS class_id, flickr_tag AS class_tag FROM loco_class") as $row) {
							foreach ($this->meta['tags'] as $tag) {
								if (stristr($tag, $row['class_tag']) && strlen(str_replace($row['class_tag'] . "-", "", $tag) > 0)) {
									$loco_num = str_replace($row['class_tag'] . "-", "", $tag);
									$Loco = new Locomotive(false, $row['class_id'], $loco_num);
									
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
									$LocoClass = new LocoClass($row['class_id']);
									
									if (filter_var($LocoClass->id, FILTER_VALIDATE_INT)) {
										$this->addLink($LocoClass->namespace, $LocoClass->id);
									}
								}
							}
						}
						
						foreach ($this->meta['tags'] as $tag) {
							if (preg_match("@railpage:class=([0-9]+)@", $tag, $matches)) {
								$LocoClass = new LocoClass($matches[1]); 
								
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
			
			/**
			 * End the debug timer
			 */
				
			if (RP_DEBUG) {
				$site_debug[] = __CLASS__ . "::" . __FUNCTION__ . "() : completed lookup of " . $namespace . " in for image id " . $this->id . " in " . round(microtime(true) - $debug_timer_start, 5) . "s";
			}
			
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
			
			return $this->db->fetchAll("SELECT * FROM image_link WHERE image_id = ? " . $where_namespace . " AND ignored = 0", $params);
		}
		
		/**
		 * Mark an image as ignored
		 * @since Version 3.8.7
		 * @param boolean $ignored
		 */
		
		public function ignored($ignored = true) {
			$data = array(
				"ignored" => intval($ignored)
			);
			
			$where = array(
				"image_id = ?" => $this->id
			);
			
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
	}
?>