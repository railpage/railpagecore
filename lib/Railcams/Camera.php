<?php
	/**
	 * Railcam object
	 * @since Version 3.4
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Railcams;
	
	use Exception;
	use DateTime;
	use DateTimeZone;
	use Railpage\Url;
	
	use Rezzza\Flickr\Metadata;
	use Rezzza\Flickr\ApiFactory;
	use Rezzza\Flickr\Http\GuzzleAdapter;
	use GuzzleHttp\Client;
	use DOMDocument;
	use DOMXpath;
	
	/**
	 * Railcam class
	 */
	
	class Camera extends Railcams {
		
		/**
		 * Camera ID
		 * @since Version 3.4
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Railcam type ID
		 * @since Version 3.8
		 * @var int $type_id
		 */
		
		public $type_id;
		
		/**
		 * Railcam type object
		 * @since Version 3.8
		 * @var object $type;
		 */
		
		public $type;
		
		/**
		 * Permalink 
		 * @since Version 3.4
		 * @var string $permalink
		 */
		
		public $permalink;
		
		/**
		 * Camera name
		 * @since Version 3.4
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Latitude
		 * @since Version 3.4
		 * @var string $lat
		 */
		
		public $lat;
		
		/**
		 * Longitude
		 * @since Version 3.4
		 * @var string $lon
		 */
		
		public $lon;
		
		/**
		 * Camera description 
		 * @since Version 3.4
		 * @var string $desc
		 */
		
		public $desc;
		
		/**
		 * Flickr NSID
		 * @since Version 3.4
		 * @var string $nsid
		 */
		
		public $nsid;
		
		/**
		 * Camera timezone
		 * @since Version 3.4
		 * @var string $timezone
		 */
		
		public $timezone = "";
		
		/**
		 * Route ID
		 * @since Version 3.4
		 * @var int $route_id
		 */
		
		public $route_id;
		
		/**
		 * Flickr OAuth token
		 * @since Version 3.5
		 * @var string $flickr_oauth_token
		 */
		
		public $flickr_oauth_token = "";
		
		/**
		 * Flickr OAuth secret
		 * @since Version 3.5
		 * @var string $flickr_oauth_secret
		 */
		
		public $flickr_oauth_secret = "";
		
		/**
		 * Video store URL
		 * @since Version 3.5
		 * @var string $video_store_url
		 */
		
		public $video_store_url = "";
		
		/**
		 * Live image URL
		 * @since Version 3.5
		 * @var string $live_image_url
		 */
		
		public $live_image_url = "";
		
		/**
		 * Live video URL
		 * @since Version 3.7.5
		 * @var string $live_video_url
		 */
		
		public $live_video_url = "";
		
		/**
		 * What is to the left of camera - eg Melbourne or Stawell
		 * @since Version 3.8
		 * @var string $left
		 */
		
		public $left;
		
		/**
		 * What is to the right of camera - eg Melbourne or Stawell
		 * @since Version 3.8
		 * @var string $right
		 */
		
		public $right;
		
		/**
		 * URL to this railcam
		 * @since Version 3.8.7
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Photo provider
		 * @since Version 3.9
		 * @var object $Provider
		 */
		
		public $Provider;
		
		/**
		 * Constructor
		 * @since Version 3.4
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			parent::__construct(); 
			
			$this->GuzzleClient = new Client;
			
			if ($id) {
				$this->id = $id;
				
				// Fetch Railcam data
				try {
					$this->load(); 
				} catch (Exception $e) {
					throw new Exception($e->getMessage());
				}
			}
		}
		
		/**
		 * Load Railcam data from database
		 * @since Version 3.4
		 * @return boolean
		 */
		
		public function load() {
			if (empty($this->id) || $this->id == false) {
				throw new Exception("Cannot load Railcam - empty or invalid ID given"); 
				return false;
			}
			
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				$this->id = $this->db->fetchOne("SELECT id FROM railcams WHERE permalink = ?", $this->id); 
			}
				
			$query = "SELECT * FROM railcams WHERE id = ?"; 
			
			if ($row = $this->db->fetchRow($query, $this->id)) {
				
				$this->name 		= $row['name']; 
				$this->type_id		= $row['type_id'];
				$this->lat			= $row['lat']; 
				$this->lon			= $row['lon']; 
				$this->nsid			= $row['nsid']; 
				$this->desc			= $row['desc']; 
				$this->timezone		= $row['timezone'];
				$this->permalink	= $row['permalink'];
				$this->route_id		= $row['route_id'];
				$this->video_store_url 		= $row['video_store_url']; 
				$this->live_image_url 		= $row['live_image_url']; 
				$this->live_video_url		= $row['live_video_url'];
				$this->flickr_oauth_token	= $row['flickr_oauth_token'];
				$this->flickr_oauth_secret	= $row['flickr_oauth_secret'];
				$this->right = $row['right']; 
				$this->left = $row['left'];
				
				$this->url = new Url(sprintf("%s/%s", $this->Module->url, $row['permalink']));
				$this->url->edit = sprintf("%s/edit", $this->url->url);
				$this->url->archive = sprintf("%s/archive", $this->url->url);
				$this->url->gallery = sprintf("%s/flickr/tag/railpage:railcam=%d", RP_WEB_ROOT, $this->id);
				$this->url->live = sprintf("%s/live", $this->url->url);
				$this->url->recent = sprintf("%s/recent", $this->url->url);
				$this->url->photo = sprintf("%s/photo/", $this->url->url);
				$this->url->flickr_auth = sprintf("%s/authenticate", $this->url->url);
				
				switch ($row['provider']) {
					case "Flickr" : 
						$params = array(
							"oauth_token" => $this->flickr_oauth_token,
							"oauth_secret" => $this->flickr_oauth_secret,
							"api_key" => RP_FLICKR_API_KEY
						);
				}
				
				$provider = sprintf("\\Railpage\\Railcams\\Provider\\%s", $row['provider']);
				$this->Provider = new $provider($params);
			}
			
			if (filter_var($this->type_id, FILTER_VALIDATE_INT) && $this->type_id > 0) {
				$this->type = new Type($this->type_id);
			}
		}
		
		/**
		 * Get photos from this camera
		 * @since Version 3.4
		 * @return array
		 * @param int $items_per_page
		 * @param int $page
		 * @param string $extras
		 * @param boolean $sort
		 */
		
		public function photos($items_per_page, $page, $extras, $sort = true) {
			$url = "https://api.flickr.com/services/rest/?method=flickr.people.getPublicPhotos&api_key=" . RP_FLICKR_API_KEY . "&user_id=" . $this->nsid . "&extras=" . $extras . "&per_page=" . $items_per_page . "&page=" . $page . "&format=json&nojsoncallback=1";
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			
			$data = curl_exec($ch);
			curl_close($ch);
			
			$data = json_decode($data, true);
			
			$photos = array(); 
			
			// Localise the time to the camera's timezone
			$now 		= new DateTime("now", new DateTimeZone($this->timezone)); 
			$yesterday 	= new DateTime("yesterday", new DateTimeZone($this->timezone)); 
			
			if ($sort) {
				foreach ($data['photos']['photo'] as $photo) {
					$timestamp = DateTime::createFromFormat("Y-m-d H:i:s", $photo['datetaken'], new DateTimeZone($this->timezone)); 
					$date = $timestamp->format("Y-m-d");
					$hour = $timestamp->format("H");
					
					if ($date == $yesterday->format("Y-m-d")) {
						if (!isset($photos[$date][$hour]['count'])) {
							$photos[$date][$hour]['count'] = 1;
						} else {
							$photos[$date][$hour]['count']++;
						}
						
						$photos[$date][$hour]['photos'][] = $photo;
					}
				}
				
				foreach ($photos as $day => $data) {
					ksort($photos[$day]);
				}
				
				return $photos;
			} else {
				return $data['photos'];
			}
		}
		
		/**
		 * Validate changes to this railcam
		 * @since Version 3.5
		 * @return boolean
		 */
		
		public function validate() {
			if (empty($this->name)) {
				throw new Exception("Could not validate railcam - name cannot be empty"); 
				return false;
			}
			
			if (empty($this->nsid)) {
				throw new Exception("Could not validate railcam - Flickr NSID cannot be empty"); 
				return false;
			}
			
			if (empty($this->permalink)) {
				throw new Exception("Could not validate railcam - URL slug (permalink) cannot be empty"); 
				return false;
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this railcam
		 * @since Version 3.5
		 * @return boolean
		 */
		
		public function commit() {
			try {
				$this->validate(); 
			} catch (Exception $e) {
				throw new Exception($e->getMessage()); 
				return false;
			}
			
			$data = array(
				"provider" => $this->Provider->getProviderName(),
				"permalink"	=> $this->permalink,
				"lat" => $this->lat,
				"lon" => $this->lon,
				"name" => $this->name,
				"desc" => $this->desc,
				"nsid" => $this->nsid,
				"route_id" => $this->route_id,
				"timezone" => $this->timezone,
				"flickr_oauth_token" => $this->flickr_oauth_token,
				"flickr_oauth_secret" => $this->flickr_oauth_secret,
				"video_store_url" => $this->video_store_url,
				"live_image_url" => $this->live_image_url,
				"live_video_url" => $this->live_video_url,
				"type_id" => $this->type_id,
				"left" => $this->left,
				"right" => $this->right,
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				// Update
			
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("railcams", $data, $where);
				return true;
			} else {
				// Insert
				$this->db->insert("railcams", $data);
				return true;
			}
		}
		
		/**
		 * List videos in video store
		 * @since Version 3.5
		 * @return array
		 * @param int $num
		 */
		
		public function getVideos($num = 25) {
			if (!$this->video_store_url) {
				return false;
			}
			
			$videos = array(); 
			$index 	= NULL;
			
			if (substr($this->video_store_url, -1) != "/") {
				$this->video_store_url .= "/";
			}
			
			$response = $this->GuzzleClient->get($this->video_store_url);
			
			if ($response->getStatusCode() != 200) {
				throw new Exception(sprintf("Failed to fetch videos from railcam: Error %s", $response->getStatusCode()));
			}
			
			$index = $response->getBody();
			
			$doc = new DOMDocument();
			$doc->loadHTML($index);
			
			$xpath = new DOMXpath($doc);
			$nodes = $xpath->query('//a');
			
			$i = 0;
			
			foreach($nodes as $node) {
				$url = $node->getAttribute('href');
				
				if ($i < $num && $url != "/" && substr($url, 0, 3) != "?C=") {
					if (!strstr($url, $this->video_store_url)) {
						$url = $this->video_store_url.$url;
					}
					
					$videos[] = $url;
					
					$i++;
				}
			}
			
			return $videos;
		}
		
		/**
		 * List private photos in Flickr using OAuth
		 * @since Version 3.5
		 * @param int $items_per_page
		 * @param int $page_num
		 * @param \DateTime|int $date_from
		 * @param \DateTime|int $date_to
		 * @return array
		 */
		 
		public function getPhotos($items_per_page = 25, $page_num = 1, $date_from = false, $date_to = false) {
			if (!empty($this->flickr_oauth_secret) && !empty($this->flickr_oauth_token)) {
				// Fetch photos using OAuth
				$f = new \flickr_railpage(RP_FLICKR_API_KEY);
				$f->oauth_token 	= $this->flickr_oauth_token;
				$f->oauth_secret 	= $this->flickr_oauth_secret;
				$f->cache = false;
				
				$extras = "description,date_upload,date_taken,owner_name,original_format,last_update,geo,tags,machine_tags,o_dims,views,media,url_sq,url_t,url_s,url_q,url_m,url_n,url_z,url_c,url_l,url_o";
				
				$args = array(
					"extras" => $extras,
					"per_page" => $items_per_page, 
					"page" => $page_num
				);
				
				if ($date_from != false) {
					
					if (!$date_from instanceof DateTime) {
						$date_from = new DateTime($date_from); 
					}
					
					$args['min_taken_date'] = $date_from->format("Y-m-d 00:00:00");
				}
				
				if ($date_to != false) {
					
					if (!$date_to instanceof DateTime) {
						$date_to = new DateTime($date_to);
					}
					
					$args['max_taken_date'] = $date_to->format("Y-m-d 23:59:59");
				}
				
				$photos = $f->people_getPhotos(
					$this->nsid, 
					$args
				);
				
				$return = $photos['photos']; 
			} else {
				// Fetch photos using public Flickr APIs
				
				$extras = "description,date_upload,date_taken,owner_name,icon_server,original_format,last_update,geo,tags,machine_tags,o_dims,views,media,path_alias,url_sq,url_t,url_s,url_q,url_m,url_n,url_z,url_c,url_l,url_o";
				
				$photos = $this->photos($items_per_page, $page_num, $extras, false);
				
				$return = $photos;
			}
			
			foreach ($return['photo'] as $id => $row) {
				$return['photo'][$id]['time_relative'] = relative_date($row['dateupload']); 
				$return['photo'][$id]['description'] = isset($row['description']['_content']) ? $row['description']['_content'] : NULL;
				$return['photo'][$id]['tags'] = explode(" ", $row['tags']);
				
				// To-do: check for locos and liveries
			}
			
			return $return;
		}
		
		/**
		 * Return a new Railpage\Railcams\Photo object
		 * @since Version 3.9
		 * @param int $photo_id
		 * @return \Railpage\Railcams\Photo
		 */
		
		public function getPhoto($photo_id = false) {
			if (!filter_var($photo_id, FILTER_VALIDATE_INT)) {
				throw new Exception(sprintf("Cannot fetch photo from railcam because \"%s\" is not a valid photo ID", $photo_id));
			}
			
			$Photo = (new Photo($photo_id))->setProvider($this->Provider)->setCamera($this)->load(); 
			
			return $Photo;
		}
		
		/**
		 * Get photo info and sizes
		 * @since Version 3.5
		 * @param int $photo_id
		 * @return array
		 */
		
		public function getPhotoLegacy($photo_id = false) {
			if (!$photo_id) {
				throw new Exception("Cannot fetch photo info and sizes - no photo ID given"); 
				return false;
			}
			
			$mckey = "railpage:railcam.photo.id=" . $photo_id;
			
			#deleteMemcacheObject($mckey);
			
			if ($return = getMemcacheObject($mckey)) {
				$return['photo']['time_relative'] = relative_date($return['photo']['dateuploaded']);
				
				return $return;
			} else {
				
				$use_rezzza = false;
				
				if ($use_rezzza) {
					$metadata = new Metadata(RP_FLICKR_API_KEY, RP_FLICKR_API_SECRET);
					$metadata->setOauthAccess($this->flickr_oauth_token, $this->flickr_oauth_secret);
					$factory = new ApiFactory($metadata, new GuzzleAdapter());
					
					$photo_info = $factory->call('flickr.photos.getInfo', array(
						'photo_id' => $photo_id,
					));
					
					if ($photo_info) {
						$photo_sizes = $factory->call('flickr.photos.getSizes', array(
							'photo_id' => $photo_id
						));
					}
					
					/**
					 * do stuff!
					 */
					
					if ($photo_info && $photo_sizes) {
						$return = array();
						
						/**
						 * Photo info
						 */
						
						foreach ($photo_info->photo->attributes() as $a => $b) {
							$return['photo'][$a] = $b->__toString();
						}
						
						foreach ($photo_info->photo->children() as $element) {
							foreach ($element->attributes() as $a => $b) {
								$return['photo'][$element->getName()][$a] = $b->__toString();
							}
							
							foreach ($element->children() as $child) {
								foreach ($child->attributes() as $a => $b) {
									$return['photo'][$element->getName()][$child->getName()][$a] = $b->__toString();
								}
								
								foreach ($child->children() as $blah) {
									$return['photo'][$element->getName()][$child->getName()][$blah->getName()][$a] = $b->__toString();
									
									foreach ($blah->attributes() as $a => $b) {
										$return['photo'][$element->getName()][$child->getName()][$blah->getName()][$a] = $b->__toString();
									}
								}
							}
						}
						
						/**
						 * Photo sizes
						 */
						
						$i = 0;
						foreach ($photo_sizes->sizes->size as $key => $element) {
							foreach ($element->attributes() as $a => $b) {
								$return['photo']['sizes'][$i][$a] = $b->__toString();
							}
							$i++;
							
						}
					}
					
					return $return;
				}
				
				$f = new \flickr_railpage(RP_FLICKR_API_KEY);
				$f->oauth_token 	= $this->flickr_oauth_token;
				$f->oauth_secret 	= $this->flickr_oauth_secret;
				$f->cache = false;
				
				$return = array(); 
				
				if ($return = $f->photos_getInfo($photo_id)) {
					$return['photo']['sizes'] = $f->photos_getSizes(
						$photo_id
					);
					
					$return['photo']['time_relative'] = relative_date($return['photo']['dateuploaded']);
					
					setMemcacheObject($mckey, $return, strtotime("+2 hours"));
				}
				
				return $return;
			}
		}
		
		/**
		 * Get photo context
		 * @since Version 3.7.2
		 * @param int $photo_id
		 * @return array
		 */
		
		public function getContext($photo_id = false) {
			if (!$photo_id) {
				throw new Exception("Cannot get photo context - no photo ID given"); 
				return false;
			}
			
			$f = new \flickr_railpage(RP_FLICKR_API_KEY);
			$f->oauth_token 	= $this->flickr_oauth_token;
			$f->oauth_secret 	= $this->flickr_oauth_secret;
			$f->cache = false;
			
			$return = array(); 
			
			$return = $f->photos_getContext(
				$photo_id
			);
			
			/*
			$return['photo']['sizes'] = $f->photos_getSizes(
				$photo_id
			);
			
			$return['photo']['time_relative'] = relative_date($return['photo']['dateuploaded']);
			*/
			
			return $return;
		}
		
		/**
		 * Get years within the camera archive
		 * @since Version 3.9
		 * @return array
		 */
		
		public function getArchiveYears() {
			
		}
		
		/**
		 * Get months within the camera archive for a given year
		 * @since Version 3.9
		 * @param int $year
		 * @return array
		 */
		
		public function getArchiveMonths($year) {
			
		}
		
		/**
		 * Get a associative array of this railcam data in a standardised format
		 * @since Version 3.9
		 * @return array
		 */
		
		public function getArray() {
			return array(
				"id" => $this->id,
				"name" => $this->name,
				"description" => $this->desc,
				"provider" => $this->Provider->getProviderName(),
				"url" => $this->url->getURLs(),
				"lat" => $this->lat,
				"lon" => $this->lon
			);
		}
	}
?>