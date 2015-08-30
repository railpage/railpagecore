<?php
	/**
	 * Old Gallery1-migrated user album
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Gallery;
	
	use Exception;
	use DateTime;
	use Railpage\Users\User;
	use Railpage\Users\Factory as UserFactory;
	use Railpage\Module;
	use Railpage\Url;
	use Railpage\AppCore;
	
	/**
	 * Album
	 */
	
	class Album extends AppCore {
		
		/**
		 * On-disk root path
		 * @since Version 3.10.0
		 * @const string ALBUMS_DIR
		 */
		
		const ALBUMS_DIR = "/srv/railpage.com.au/old.www/public_html/modules/gallery/albums/";
		
		/**
		 * Do we need to update these photos or not?
		 * @since Version 3.8.7
		 * @const boolean UPDATE_PHOTO
		 */
		
		const UPDATE_PHOTO = true;
		
		/**
		 * Album ID
		 * @since Version 3.8.7
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Album name
		 * @since Version 3.8.7
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * URL slug
		 * @since Version 3.8.7
		 * @var string $slug
		 */
		
		public $slug;
		
		/**
		 * Album meta data
		 * @since Version 3.8.7
		 * @var array $meta
		 */
		
		public $meta;
		
		/**
		 * Album owner
		 * @since Version 3.8.7
		 * @var \Railpage\Users\User $Owner
		 */
		
		public $Owner;
		
		/**
		 * Featured image
		 * @since Version 3.8.7
		 * @var \Railpage\Gallery\Image $FeaturedImage
		 */
		
		public $FeaturedImage;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param int|string $id
		 */
		
		public function __construct($id = false) {
			
			parent::__construct();
			
			$this->Module = new Module("Gallery");
			
			// Rewrite - some album titles can be all numbers (eg 639, 8203)
			if ($id) {
				// First, assume that the album ID is a string. Let's try to get the ID from that
				$query = "SELECT id FROM gallery_mig_album WHERE name = ?";
				$this->id = $this->db->fetchOne($query, $id);
				
				// If the value returned from the database is false, there's no album 
				// by that name so let's assume the name provided is actually an ID
				if (!$this->id) {
					$this->id = $id;
				}
			}
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$this->mckey = sprintf("railpage:gallery.album=%d", $this->id);
				
				if (!$data = $this->Memcached->fetch($this->mckey)) {
					$query = "SELECT * FROM gallery_mig_album WHERE id = ?";
					
					$data = $this->db->fetchRow($query, $this->id);
					
					$this->Memcached->save($this->mckey, $data, strtotime("+1 year"));
				}
				
				$this->name = $data['title'];
				$this->meta = json_decode($data['meta'], true);
				$this->owner = $data['owner'];
				$this->slug = $data['name'];
				$this->featured_photo_id = $data['featured_photo'];
				
				$this->url = new Url(sprintf("%s?album=%s", $this->Module->url, $data['name']));
				
				if (self::UPDATE_PHOTO) {
					$data['featured_photo'] = $this->updateFeaturedImage(); 
					$this->Memcached->save($this->mckey, $data, strtotime("+1 year"));
				}
			
				if (isset($data['featured_photo']) && filter_var($data['featured_photo'], FILTER_VALIDATE_INT)) {
					$this->FeaturedImage = new Image($data['featured_photo']);
				}
			}
			
			if (!empty($this->id) && !preg_match("/([a-zA-Z]+)/", $this->id)) {
				$this->id = intval($this->id);
			}
		}
		
		/**
		 * List photos in this album
		 * @since Version 3.8.7
		 * @yield new \Railpage\Gallery\Image
		 */
		
		public function getImages() {
			$query = "SELECT id FROM gallery_mig_image WHERE album_id = ? AND hidden = ? ORDER BY id";
			
			$results = $this->db->fetchAll($query, array($this->id, 0));
			
			foreach ($results as $image) {
				yield new Image($image['id']);
			}
		}
		
		/**
		 * Get a single image from this album
		 * @since Version 3.9
		 * @yield new \Railpage\Gallery\Image
		 * @param string $image_id
		 */
		
		public function getImage($image_id) {
			if (!filter_var($image_id, FILTER_VALIDATE_INT)) {
				$query = "SELECT id FROM gallery_mig_image WHERE album_id = ? AND meta LIKE ?";
				
				$image_id = $this->db->fetchOne($query, array($this->id, "%" . $image_id . "%"));
			}
			
			return new Image($image_id);
		}
		
		/**
		 * List the albums available
		 * @since Version 3.8.7
		 * @yield \Railpage\Gallery\Album
		 */
		
		public function yieldAlbums() {
			$query = "SELECT id FROM gallery_mig_album WHERE parent_id = ? ORDER BY title";
			
			foreach ($this->db->fetchAll($query, $this->id) as $album) {
				yield new Album($album['id']);
			}
		}
		
		/**
		 * Get all child albums as an array
		 * @since Version 3.8.7
		 * @return array
		 */
		
		public function getAlbums($page, $limit = 25) {
			if (!$return = getMemcacheObject(sprintf("railpage:gallery.old.album=%d.subalbums.page=%d.perpage=%d", $this->id, $page, $limit))) {
				$Sphinx = $this->getSphinx(); 
				
				$query = $Sphinx->select("*")
						->from("idx_gallery_album")
						->where("parent_id", "=", $this->id)
						->orderBy("album_title", "ASC")
						->limit(($page - 1) * $limit, $limit);
				
				$matches = $query->execute(); 
				
				$meta = $Sphinx->query("SHOW META");
				$meta = $meta->execute();
				
				foreach ($matches as $id => $row) {
					$row['album_meta'] = json_decode($row['album_meta'], true);
					
					$matches[$id] = $row;
				}
				
				$return = array(
					"total" => $meta[1]['Value'],
					"page" => $page,
					"perpage" => $limit,
					"albums" => $matches
				);
				
				$this->Memcached->save(sprintf("railpage:gallery.old.album=%d.subalbums.page=%d.perpage=%d", $this->id, $page, $limit), $return);
			}
			
			return $return;
		}
		
		/**
		 * Get the parent album
		 * @since Version 3.8.7
		 * @return \Railpage\Gallery\Album
		 */
		
		public function getParent() {
			$query = "SELECT parent_id FROM gallery_mig_album WHERE id = ?";
			
			$id = $this->db->fetchOne($query, $this->id);
			
			if (filter_var($id, FILTER_VALIDATE_INT) && $id > 0) {
				return new Album($id);
			} else {
				return false;
			}
		}
		
		/**
		 * Load the owner of this album
		 * @since Version 3.8.7
		 * @return \Railpage\Users\User
		 */
		
		public function getOwner() {
			if (filter_var($this->owner, FILTER_VALIDATE_INT)) {
				try {
					return UserFactory::CreateUser($this->owner);
				} catch (Exception $e) {
					// Don't care
				}
			
			}
		}
		
		/**
		 * Update the featured image for this album
		 * @since Version 3.8.7
		 * @return int
		 */
		
		public function updateFeaturedImage() {
			
			$data = array(
				"featured_photo" => $this->featured_photo_id
			);
			
			/*
			 * Update the featured photo by album images
			 */
			
			if (!isset($data['featured_photo']) || !filter_var($data['featured_photo'], FILTER_VALIDATE_INT) || $data['featured_photo'] == 1) {
				foreach ($this->getImages() as $Image) {
					if (!$Image->hidden) {
						$data['featured_photo'] = $Image->id;
						
						$this->db->update("gallery_mig_album", $data, array("id = ?" => $this->id));
						$this->Memcached->delete($this->mckey);
						#$this->Memcached->save($this->mckey, $data, strtotime("+1 year"));
						break;
					}
				}
			}
			
			/**
			 * Update the featured photo by sub-album images
			 */
			
			if (!isset($data['featured_photo']) || !filter_var($data['featured_photo'], FILTER_VALIDATE_INT) || $data['featured_photo'] == 1) {
				foreach ($this->yieldAlbums() as $Album) {
					foreach ($Album->getImages() as $Image) {
						if (!$Image->hidden) {
							$data['featured_photo'] = $Image->id;
							
							$this->db->update("gallery_mig_album", $data, array("id = ?" => $this->id));
							$this->Memcached->delete($this->mckey);
							#$this->Memcached->save($this->mckey, $data, strtotime("+1 year"));
							break;
						}
					}
					
					if (isset($data['featured_photo']) && filter_var($data['featured_photo'], FILTER_VALIDATE_INT)) {
						break;
					}
				}
			}
			
			return $data['featured_photo'];
		}
	}
	