<?php
	/**
	 * Old Gallery1-migrated album image
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
	use Railpage\Place;
	
	/**
	 * Image
	 */
	
	class Image extends AppCore {
		
		/**
		 * Maximum allowed image width
		 * @since Version 3.10.0
		 * @const int MAX_WIDTH
		 */
		
		const MAX_WIDTH = 2048;
		
		/**
		 * Maximum allowed image height
		 * @since Version 3.10.0
		 * @const int MAX_HEIGHT
		 */
		
		const MAX_HEIGHT = 1800;
		
		/**
		 * Photo ID
		 * @since Version 3.8.7
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Photo title
		 * @since Version 3.8.7
		 * @var string $title
		 */
		
		public $title;
		
		/**
		 * Photo caption
		 * @since Version 3.10.0
		 * @var string $caption
		 */
		
		public $caption;
		
		/**
		 * File path relative to albums root
		 * @since Version 3.8.7
		 * @var string $path
		 */
		
		public $path;
		
		/**
		 * Image sizes
		 * @since Version 3.8.7
		 * @var string $sizes
		 */
		
		public $sizes;
		
		/**
		 * Image meta data
		 * @since Version 3.8.7
		 * @var array $meta
		 */
		
		public $meta;
		
		/**
		 * Is this image hidden?
		 * @since Version 3.8.7
		 * @var boolean @hidden
		 */
		
		public $hidden = false;
		
		/**
		 * Date taken
		 * @since Version 3.8.7
		 * @var \DateTime $DateTaken
		 */
		
		public $DateTaken;
		
		/**
		 * Date uploaded
		 * @since Version 3.8.7
		 * @var \DateTime $DateUploaded
		 */
		 
		public $DateUploaded;
		
		/**
		 * Photo owner
		 * @since Version 3.8.7
		 * @var \Railpage\Users\User $Owner
		 */
		
		public $Owner;
		
		/**
		 * Album that this photo belongs to
		 * @since Version 3.10.0
		 * @var \Railpage\Gallery\Album $Album
		 */
		
		public $Album;
		
		/**
		 * Geographic place this photo was taken
		 * @since Version 3.10.0
		 * @var \Railpage\Place $Place
		 */
		
		public $Place;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param int|string $id
		 */
		
		public function __construct($id = false) {
			
			parent::__construct();
			
			$this->Module = new Module("Gallery");
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				
				$this->id = $id;
				
				$this->populate(); 
				$this->validateDates(); 
				
			}
			
		}
		
		/**
		 * Verify the dates of the photo
		 * @since Version 3.10.0
		 * @return void
		 */
		
		private function validateDates() {
			
			$file = sprintf("%s%s", Album::ALBUMS_DIR, $this->path);
			$exif = exif_read_data($file); 
			
			if (!$this->DateTaken instanceof DateTime && isset($exif['DateTimeOriginal'])) {
				$this->DateTaken = new DateTime("@" . $exif['DateTimeOriginal']); 
			}
			
			if (!$this->DateTaken instanceof DateTime) {
				$this->DateTaken = new DateTime;
			}
			
			return;
			
		}
		
		/**
		 * Populate this object
		 * @since Version 3.10.0
		 * @return void
		 */
		
		private function populate() {
			
			$this->mckey = sprintf("railpage:gallery.album.image=%d", $this->id);
			
			if (!$row = $this->Memcached->fetch($this->mckey)) {
				$query = "SELECT * FROM gallery_mig_image WHERE id = ?";
				
				$row = $this->db->fetchRow($query, $this->id);
				$this->Memcached->save($this->mckey, $row, strtotime("+1 year"));
			}
			
			$this->title = $row['title'];
			$this->caption = $row['caption'];
			$this->DateTaken = new DateTime($row['date_taken']);
			$this->DateUploaded = new DateTime($row['date_uploaded']);
			$this->path = $row['path'];
			$this->url = new Url(sprintf("%s?image=%d", $this->Module->url, $this->id));
			$this->meta = json_decode($row['meta'], true);
			$this->hidden = isset($row['hidden']) ? $row['hidden'] : false;
			$this->lat = isset($row['lat']) ? $row['lat'] : NULL; 
			$this->lon = isset($row['lon']) ? $row['lon'] : NULL;
			
			$this->sizes = array(
				"original" => array(
					"source" => sprintf("//static.railpage.com.au/albums/%s", $this->path),
					"width" => $this->meta['image']['raw_width'],
					"height" => $this->meta['image']['raw_height']
				)
			);
			
			if (isset($this->meta['image']['resizedName'])) {
				$this->sizes['medium'] = array(
					"source" => sprintf("//static.railpage.com.au/albums/%s/%s.%s", dirname($this->path), $this->meta['image']['resizedName'], $this->meta['image']['type']),
					"width" => $this->meta['image']['width'],
					"height" => $this->meta['image']['height']
				);
			}
			
			if (isset($this->meta['thumbnail'])) {
				$this->sizes['thumb'] = array(
					"source" => sprintf("//static.railpage.com.au/albums/%s/%s.%s", dirname($this->path), $this->meta['thumbnail']['name'], $this->meta['thumbnail']['type']),
					"width" => $this->meta['thumbnail']['width'],
					"height" => $this->meta['thumbnail']['height']
				);
			}
			
			$this->owner = $row['owner'];
			
			if (filter_var($row['owner'], FILTER_VALIDATE_INT)) {
				try {
					$this->Owner = UserFactory::CreateUser($row['owner']);
				} catch (Exception $e) {
					// Don't care
				}
			}
			
			return;
			
		}
		
		/**
		 * Get the album this photo belongs to
		 * @since Version 3.8.7
		 * @return \Railpage\Gallery\Album
		 */
		
		public function getAlbum() {
			
			if ($this->Album instanceof Album) {
				return $this->Album;
			}
			
			$album_id = $this->db->fetchOne("SELECT album_id FROM gallery_mig_image WHERE id = ?", $this->id);
			
			$this->Album = new Album($album_id);
			
			return $this->Album;
			
		}
		
		/**
		 * Set the album this image belongs to
		 * @since Version 3.10.0
		 * @return \Railpage\Gallery\Image
		 * @param \Railpage\Gallery\Album $Album
		 */
		
		public function setAlbum(Album $Album) {
			
			$this->Album = $Album;
			
			return $this;
			
		}
		
		/**
		 * Get the geoplace for this photo
		 * @since Version 3.10.0
		 * @return \Railpage\Place
		 */
		
		public function getPlace() {
			
			if (is_null($this->lat) || is_null($this->lon)) {
				return false;
			}
			
			return Place::Factory($this->lat, $this->lon); 
			
		}
		
		/**
		 * Validate changes to this image
		 * @since Version 3.10.0
		 * @return boolean
		 */
		
		private function validate() {
			
			if (empty($this->title)) {
				$this->name = $this->path;
			}
			
			if (!$this->getAlbum() instanceof Album) {
				throw new Exception("No valid album has been set"); 
			}
			
			if (!$this->getOwner() instanceof User) {
				throw new Exception("No valid image owner has been set"); 
			}
			
			if (!$this->DateTaken instanceof DateTime) {
				$this->validateDates(); 
			}
			
			if (!$this->DateUploaded instanceof DateTime) {
				$this->DateUploaded = new DateTime;
			}
			
			if (empty($this->path)) {
				throw new Exception("No image path has been set"); 
			}
			
			if (!filter_var($this->hidden, FILTER_VALIDATE_INT)) {
				$this->hidden = 0;
			}
			
			return true;
			
		}
		
		/**
		 * Get an array of this data
		 * @since Version 3.10.0
		 * @return array
		 */
		
		public function getArray() {
			
			$array = array(
				"id" => $this->id,
				"title" => $this->title,
				"caption" => $this->caption,
				"url" => $this->url->getUrls(),
				"sizes" => $this->sizes,
				"date" => array(
					"absolute" => $this->DateUploaded->format("Y-m-d H:i:s"),
					"taken" => $this->DateTaken->format("Y-m-d H:i:s"),
				),
				"lat" => $this->lat,
				"lon" => $this->lon
			);
			
			return $array;
			
		}
		
		/**
		 * Commit changes to this image
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		public function commit() {
			
			$this->validate(); 
			
			$data = array(
				"title" => $this->title,
				"caption" => $this->caption,
				"meta" => json_encode($this->meta),
				"hidden" => $this->hidden,
				"album_id" => $this->getAlbum()->id,
				"owner" => $this->getOwner()->id,
				"date_taken" => $this->DateTaken->format("Y-m-d H:i:s"),
				"date_uploaded" => $this->DateUploaded->format("Y-m-d H:i:s"),
				"path" => $this->path,
				"lat" => $this->Place instanceof Place ? $this->Place->lat : NULL,
				"lon" => $this->Place instanceof Place ? $this->Place->lon : NULL
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id,
				);
				
				$this->db->update("gallery_mig_image", $data, $where);
			
				$this->Memcached->delete($this->mckey);
			
				$albums = $this->db->fetchAll("SELECT id FROM gallery_mig_album WHERE featured_photo = ?", $this->id);
				
				foreach ($albums as $album) {
					$this->Memcached->delete(sprintf("railpage:gallery.album=%d", $album['id']));
				}
				
				$data = array(
					"featured_photo" => 0
				);
				
				$where = array(
					"featured_photo = ?" => $this->id
				);
				
				$this->db->update("gallery_mig_album", $data, $where);
			} else {
				$this->db->insert("gallery_mig_image", $data); 
				$this->id = $this->db->lastInsertId(); 
			}
			
			$this->getAlbum()->flushCache();
			
			return $this;
			
		}
		
		/**
		 * Load the owner of this album
		 * @since Version 3.8.7
		 * @return \Railpage\Users\User
		 */
		
		public function getOwner() {
			
			if ($this->Owner instanceof User) {
				return $this->Owner;
			}
			
			if (filter_var($this->owner, FILTER_VALIDATE_INT)) {
				try {
					return UserFactory::CreateUser($this->owner);
				} catch (Exception $e) {
					// Don't care
				}
			}
			
		}
		
		/**
		 * Set the album owner
		 * @since Version 3.10.0
		 * @param \Railpage\Users\User $Owner
		 * @return \Railpage\Gallery\Album
		 */
		
		public function setOwner(User $User) {
			
			$this->Owner = $User; 
			
			return $this;
			
		}
		
		/**
		 * Get previous image in album
		 * @since Version 3.8.7
		 * @return \Railpage\Gallery\Image
		 */
		
		public function getPreviousImage() {
			
			$query = "SELECT id FROM gallery_mig_image WHERE album_id = ? AND hidden = ? AND id < ? ORDER BY id DESC";
			$Album = $this->getAlbum();
			
			$params = array(
				$Album->id,
				0,
				$this->id
			);
			
			$id = $this->db->fetchOne($query, $params); 
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				return new Image($id);
			}
			
			return false;
			
		}
		
		/**
		 * Get next image in album
		 * @since Version 3.8.7
		 * @return \Railpage\Gallery\Image
		 */
		
		public function getNextImage() {
			
			$query = "SELECT id FROM gallery_mig_image WHERE album_id = ? AND hidden = ? AND id > ? ORDER BY id ASC";
			$Album = $this->getAlbum();
			
			$params = array(
				$Album->id,
				0,
				$this->id
			);
			
			$id = $this->db->fetchOne($query, $params); 
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				return new Image($id);
			}
			
			return false;
			
		}
	}
	