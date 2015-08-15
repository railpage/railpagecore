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
	
	/**
	 * Image
	 */
	
	class Image extends AppCore {
		
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
		 * Constructor
		 * @since Version 3.8.7
		 * @param int|string $id
		 */
		
		public function __construct($id = false) {
			
			parent::__construct();
			
			$this->Module = new Module("Gallery");
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
				$this->mckey = sprintf("railpage:gallery.album.image=%d", $this->id);
				
				if (!$row = $this->Memcached->fetch($this->mckey)) {
					$query = "SELECT * FROM gallery_mig_image WHERE id = ?";
					
					$row = $this->db->fetchRow($query, $id);
					$this->Memcached->save($this->mckey, $row, strtotime("+1 year"));
				}
				
				$this->title = $row['title'];
				$this->DateTaken = new DateTime($row['date_taken']);
				$this->DateUploaded = new DateTime($row['date_uploaded']);
				$this->path = $row['path'];
				$this->url = new Url(sprintf("%s?image=%d", $this->Module->url, $this->id));
				$this->meta = json_decode($row['meta'], true);
				$this->hidden = isset($row['hidden']) ? $row['hidden'] : false;
				
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
			}
		}
		
		/**
		 * Get the album this photo belongs to
		 * @since Version 3.8.7
		 * @return \Railpage\Gallery\Album
		 */
		
		public function getAlbum() {
			$album_id = $this->db->fetchOne("SELECT album_id FROM gallery_mig_image WHERE id = ?", $this->id);
			
			return new Album($album_id);
		}
		
		/**
		 * Commit changes to this image
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		public function commit() {
			$data = array(
				"title" => $this->title,
				"meta" => json_encode($this->meta),
				"hidden" => $this->hidden
			);
			
			$where = array(
				"id = ?" => $this->id,
			);
			
			$this->db->update("gallery_mig_image", $data, $where);
			
			$this->Memcached->delete($this->mckey);
			
			#$this->Memcached->delete("railpage:gallery.album=717");
			
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
			
			return $this;
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
	