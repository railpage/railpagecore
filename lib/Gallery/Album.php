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
    use Railpage\ContentUtility;
    
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
         * The parent album
         * @since Version 3.10.0
         * @var \Railpage\Gallery\Album $ParentAlbum
         */
        
        private $ParentAlbum;
        
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
                $this->url->edit = sprintf("%s?album=%s&mode=album.edit", $this->Module->url, $data['name']);
                $this->url->new = sprintf("%s?mode=album.edit&parent_id=%d", $this->Module->url, $this->id);
                $this->url->upload = sprintf("%s?album=%s&mode=album.upload", $this->Module->url, $data['name']);
                
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
            if (!$return = $this->Memcached->fetch(sprintf("railpage:gallery.old.album=%d.subalbums.page=%d.perpage=%d", $this->id, $page, $limit))) {
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
            
            if ($this->ParentAlbum instanceof Album) {
                return $this->ParentAlbum;
            }
            
            $query = "SELECT parent_id FROM gallery_mig_album WHERE id = ?";
            
            $id = $this->db->fetchOne($query, $this->id);
            
            if (filter_var($id, FILTER_VALIDATE_INT) && $id > 0) {
                return new Album($id);
            }
            
            return false;
            
        }
        
        /**
         * Set the parent album
         * @since Version 3.10.0
         * @param \Railpage\Gallery\Album $Album
         * @return \Railpage\Gallery\Album $this
         */
        
        public function setParent(Album $Album) {
            
            $this->ParentAlbum = $Album;
            
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
        
        /**
         * Get this album as an array
         * @since Version 3.10.0
         * @return array
         */
        
        public function getArray() {
            
            $album = array(
                "id" => $this->id,
                "name" => $this->name,
                "url" => $this->url instanceof Url ? $this->url->getUrls() : array(),
                "num_photos" => $this->meta['fields']['cached_photo_count'],
                "num_albums" => 0,
                "mckey" => urlencode($this->mckey)
            );
            
            $AlbumOwner = $this->getOwner();
            
            if ($AlbumOwner instanceof User) {
                $album['owner'] = array(
                    "id" => $AlbumOwner->id,
                    "username" => $AlbumOwner->username,
                    "url" => $AlbumOwner->url->getUrls(),
                    "avatar" => array(
                        "small" => format_avatar($AlbumOwner->avatar, 40),
                        "large" => format_avatar($AlbumOwner->avatar, 120)
                    )
                );
            }
            
            return $album;
            
        }
        
        /**
         * Validate changes to this album
         * @since Version 3.10.0
         * @return boolean
         * @throws \Exception if $this->name is empty
         * @throws \Exception if $this->Author is empty
         */
        
        private function validate() {
            
            if (empty($this->name)) {
                throw new Exception("Album name is empty"); 
            }
            
            if (empty($this->slug)) {
                $this->slug = ContentUtility::generateUrlSlug($this->name, 30);
                
                $query = "SELECT id FROM gallery_mig_album WHERE name = ?";
                $rs = $this->db->fetchAll($query, $this->slug); 
                
                if (count($rs)) {
                    $this->slug .= count($rs); 
                }
            }
            
            if (!$this->Owner instanceof User) {
                $this->Owner = $this->getOwner();
            }
            
            if (!$this->Owner instanceof User) {
                throw new Exception("No valid album owner has been set"); 
            }
            
            return true;
            
        }
        
        /**
         * Flush the cache for this album
         * @since Version 3.10.0
         * @return \Railpage\Gallery\Album
         */
        
        public function flushCache() {
            
            $this->Memcached->delete($this->mckey); 
            
            for ($i = 1; $i < 10; $i++) {
                $this->Memcached->delete(sprintf("railpage:gallery.old.album=%d.subalbums.page=%d.perpage=%d", $this->id, $i, 25));
            }
            
            return $this;
            
        }
        
        /**
         * Commit changes
         * @since Version 3.10.0
         * @return \Railpage\Gallery\Album
         */
        
        public function commit() {
            
            $this->validate(); 
            
            $data = [
                "title" => $this->name,
                "meta" => json_encode($this->meta),
                "owner" => $this->Owner->id,
                "owner_id" => $this->Owner->id,
                "name" => $this->slug,
                "featured_photo" => $this->featured_photo_id,
            ];
            
            if ($Album = $this->getParent()) {
                $data['parent_id'] = $Album->id;
                $data['parent'] = $Album->slug;
            }
            
            if (filter_var($this->id, FILTER_VALIDATE_INT)) {
                $where = [ "id = ?" => $this->id ];
                $this->db->update("gallery_mig_album", $data, $where); 
            } else {
                $this->db->insert("gallery_mig_album", $data); 
                $this->id = $this->db->lastInsertId(); 
            }
            
            $this->flushCache(); 
            $this->getParent()->flushCache(); 
            
            return $this;
            
        }
    }
    