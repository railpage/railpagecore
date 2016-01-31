<?php
    /**
     * Image collection - akin to a photo album
     * @since Version 3.9.1
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Images;
    
    use Exception;
    use InvalidArgumentException;
    use DateTime;
    use DateTimeZone;
    use Railpage\Images\Utility\CollectionUtility;
    use Railpage\Users\User;
    use Railpage\Users\Factory as UsersFactory;
    use Railpage\AppCore;
    use Railpage\Debug;
    use Railpage\Url;
    use Railpage\ContentUtility;
    
    class Collection extends AppCore {
        
        /**
         * Collection ID
         * @since Version 3.9.1
         * @var int $id
         */
        
        public $id;
        
        /**
         * Collection URL slug
         * @since Version 3.9.1
         * @var string $slug
         */
        
        public $slug;
        
        /**
         * Collection name
         * @since Version 3.9.1
         * @var string $name
         */
        
        public $name;
        
        /**
         * Descriptive text
         * @since Version 3.9.1
         * @var string $description
         */
         
        public $description;
        
        /**
         * Date that the collection was created
         * @since Version 3.9.1
         * @var \DateTime $DateCreated
         */
        
        public $DateCreated;
        
        /**
         * Date that the collection was last modified
         * @since Version 3.9.1
         * @var \DateTime $DateModified
         */
        
        public $DateModified;
        
        /**
         * Owner of this collection
         * @since Version 3.9.1
         * @var \Railpage\Users\User $Author
         */
        
        public $Author;
        
        /**
         * Constructor
         * @since Version 3.9.1
         * @param int|string $id
         */
        
        public function __construct($id = false) {
            
            parent::__construct(); 
            
            $this->namespace = "railpage.images.collection";
            
            if (!filter_var($id, FILTER_VALIDATE_INT)) {
                $id = $this->getIdFromSlug($id); 
            }
            
            if (filter_var($id, FILTER_VALIDATE_INT)) {
                $this->id = $id;
                $this->load(); 
            }
            
        }
        
        /**
         * Resolve a URL slug to an ID
         * @since Version 3.9.1
         * @param string $slug
         * @return int
         */
        
        private function getIdFromSlug($slug) {
            
            $id = $this->db->fetchOne("SELECT id FROM image_collection WHERE slug = ?", $slug); 
            
            return $id;
            
        }
        
        /**
         * Populate this object
         * @since Version 3.9.1
         * @return void
         */
        
        private function load() {
            
            $query = "SELECT * FROM image_collection WHERE id = ?";
            
            $row = $this->db->fetchRow($query, $this->id); 
            
            $this->slug = $row['slug']; 
            $this->name = $row['title'];
            $this->description = $row['description'];
            $this->DateCreated = new DateTime($row['created'], new DateTimeZone("Australia/Melbourne"));
            $this->DateModified = new DateTime($row['modified'], new DateTimeZone("Australia/Melbourne"));
            $this->setAuthor(UsersFactory::CreateUser($row['user_id']));
            
            if (empty($this->slug) || $this->slug == 1) {
                $this->validate(); 
                $this->commit(); 
            }
            
            $this->makeURLs(); 
            
            return;
            
        }
        
        /**
         * Make URLs
         * @since Version 3.9.1
         * @return void
         */
        
        private function makeURLs() {
            
            $this->url = new Url(CollectionUtility::createUrl($this->slug));
            
        }
        
        /**
         * Validate changes to this collection
         * @since Version 3.9.1
         * @return void
         */
        
        private function validate() {
            
            if (empty($this->name)) {
                throw new Exception("Title cannot be empty"); 
            }
            
            if (empty($this->description)) {
                throw new Exception("Description cannot be empty"); 
            }
            
            if (empty($this->slug)) {
                $proposal = ContentUtility::generateUrlSlug($this->name); 
                
                $num = $this->db->fetchAll("SELECT id FROM image_collection WHERE slug = ?", $proposal); 
                
                if (count($num)) {
                    $proposal .= count($num); 
                }
                
                $this->slug = $proposal;
            }
            
            if (!$this->DateCreated instanceof DateTime) {
                $this->DateCreated = new DateTime;
            }
            
            $this->DateModified = new DateTime;
            
        }
        
        /**
         * Commit changes to this image collection
         * @since Version 3.9.1
         * @return \Railpage\Images\Collection
         */
        
        public function commit() {
            
            $this->validate(); 
            
            $data = [
                "slug" => $this->slug,
                "title" => $this->name, 
                "description" => $this->description,
                "created" => $this->DateCreated->format("Y-m-d H:i:s"),
                "modified" => $this->DateModified->format("Y-m-d H:i:s"),
                "user_id" => $this->Author->id
            ];
            
            if (filter_var($this->id, FILTER_VALIDATE_INT)) {
                $where = [ "id = ?" => $this->id ];
                $this->db->update("image_collection", $data, $where); 
            } else {
                $this->db->insert("image_collection", $data); 
                $this->id = $this->db->lastInsertId(); 
            }
            
            $this->makeURLs();
            
            return $this;
            
        }
        
        /**
         * Is the specified image in this collection?
         * @since Version 3.9.1
         * @param \Railpage\Images\Image $Image
         * @return boolean
         */
        
        public function containsImage(Image $Image) {
            
            // Check that it's not already in this collection
            $query = "SELECT id FROM image_link WHERE image_id = ? AND namespace = ? AND namespace_key = ?";
            
            if ($id = $this->db->fetchOne($query, array($Image->id, $this->namespace, $this->id))) {
                return true;
            }
            
            return false;
            
        }
        
        /**
         * Add an image to this collection
         * @since Version 3.9.1
         * @param \Railpage\Images\Image $Image
         * @return \Railpage\Images\Collection
         */
        
        public function addImage(Image $Image) {
            
            if ($this->containsImage($Image)) {
                return $this;
            }
            
            $data = [
                "image_id" => $Image->id,
                "namespace" => $this->namespace,
                "namespace_key" => $this->id
            ];
            
            $this->db->insert("image_link", $data); 
            
            $this->commit(); 
            
            return $this;
            
        }
        
        /**
         * Remove an image from this collection
         * @since Version 3.9.1
         * @param \Railpage\Images\Image $Imgae
         * @return \Railpage\Images\Collection
         */
        
        public function removeImage(Image $Image) {
            
            $where = [
                "image_id = ?" => $Image->id,
                "namespace = ?" => $this->namespace,
                "namespace_key = ?" => $this->id
            ];
            
            $this->db->delete("image_link", $where); 
            
            $this->commit(); 
            
            return $this;
            
        }
        
        /**
         * Get an array of data
         * @since Version 3.9.1
         * @return array
         */
        
        public function getArray() {
            
            return array(
                "id" => $this->id,
                "name" => $this->name,
                "description" => $this->description,
                "namespace" => $this->namespace,
                "created" => array(
                    "absolute" => $this->DateCreated->format("Y-m-d H:i:s"),
                    "relative" => ContentUtility::relativeTime($this->DateCreated)
                ),
                "modified" => array(
                    "absolute" => $this->DateModified->format("Y-m-d H:i:s"),
                    "relative" => ContentUtility::relativeTime($this->DateModified)
                ),
                "url" => $this->url->getURLs(),
                "owner" => $this->Author->getArray()
            );
            
        }
        
    }
    
    