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
	use Railpage\Module;
	use Railpage\Url;
	use Railpage\AppCore;
	
	/**
	 * Album
	 */
	
	class Album extends AppCore {
		
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
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
			} elseif (is_string($id)) {
				$query = "SELECT id FROM gallery_mig_album WHERE name = ?";
				$this->id = $this->db->fetchOne($query, $id);
			}
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$this->mckey = sprintf("railpage:gallery.album=%d", $this->id);
				
				if (!$data = getMemcacheObject($this->mckey)) {
					$query = "SELECT * FROM gallery_mig_album WHERE id = ?";
					
					$data = $this->db->fetchRow($query, $this->id);
					
					setMemcacheObject($this->mckey, $data, strtotime("+1 year"));
				}
				
				$this->name = $data['title'];
				$this->meta = json_decode($data['meta'], true);
				$this->owner = $data['owner'];
				
				$this->url = new Url(sprintf("%s?album=%s", $this->Module->url, $data['name']));
				
				if (self::UPDATE_PHOTO) {
				
					/**
					 * Update the featured photo by album images
					 */
					
					if (!isset($data['featured_photo']) || !filter_var($data['featured_photo'], FILTER_VALIDATE_INT)) {
						foreach ($this->getImages() as $Image) {
							if (!$Image->hidden) {
								$data['featured_photo'] = $Image->id;
								
								$this->db->update("gallery_mig_album", $data, array("id = ?" => $this->id));
								setMemcacheObject($this->mckey, $data, strtotime("+1 year"));
								break;
							}
						}
					}
					
					/**
					 * Update the featured photo by sub-album images
					 */
					
					if (!isset($data['featured_photo']) || !filter_var($data['featured_photo'], FILTER_VALIDATE_INT)) {
						foreach ($this->getAlbums() as $Album) {
							foreach ($Album->getImages() as $Image) {
								if (!$Image->hidden) {
									$data['featured_photo'] = $Image->id;
									
									$this->db->update("gallery_mig_album", $data, array("id = ?" => $this->id));
									setMemcacheObject($this->mckey, $data, strtotime("+1 year"));
									break;
								}
							}
							
							if (isset($data['featured_photo']) && filter_var($data['featured_photo'], FILTER_VALIDATE_INT)) {
								break;
							}
						}
					}
				}
				
				if (isset($data['featured_photo']) && filter_var($data['featured_photo'], FILTER_VALIDATE_INT)) {
					$this->FeaturedImage = new Image($data['featured_photo']);
				}
			}
		}
		
		/**
		 * List photos in this album
		 * @since Version 3.8.7
		 * @yield new \Railpage\Gallery\Image
		 */
		
		public function getImages() {
			$query = "SELECT id FROM gallery_mig_image WHERE album_id = ? AND hidden = ?";
			
			$results = $this->db->fetchAll($query, array($this->id, 0));
			
			foreach ($results as $image) {
				yield new Image($image['id']);
			}
		}
		
		/**
		 * List the albums available
		 * @since Version 3.8.7
		 * @yield \Railpage\Gallery\Album
		 */
		
		public function getAlbums() {
			$query = "SELECT id FROM gallery_mig_album WHERE parent_id = ? ORDER BY title";
			
			foreach ($this->db->fetchAll($query, $this->id) as $album) {
				yield new Album($album['id']);
			}
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
					return new User($this->owner);
				} catch (Exception $e) {
					// Don't care
				}
			}
		}
	}
?>