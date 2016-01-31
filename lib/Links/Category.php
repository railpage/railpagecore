<?php
    /**
     * Links category
     * @since Version 3.7.5
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Links;
    
    use DateTime;
    use Exception;
    use Railpage\AppCore;
    use Railpage\Url;
    use Railpage\Module;
    
    /** 
     * Category
     */
    
    class Category extends Links {
        
        /**
         * Category ID
         * @var int $id
         */
        
        public $id;
        
        /**
         * Category name
         * @var string $Name
         */
        
        public $name;
        
        /**
         * Category description
         * @var string $desc
         */
        
        public $desc;
        
        /**
         * Category URL slug
         * @var string $slug
         */
        
        public $slug; 
        
        /**
         * Category access URL
         * @var string $url
         */
        
        public $url;
        
        /**
         * Links
         * @var array $links
         */
         
        public $links = array();
        
        /**
         * Parent category ID
         * @var int $parent_id
         */
        
        public $parent_id;
        
        /**
         * Category parent
         * @var object $parent
         */
        
        public $parent;
        
        /**
         * Constructor
         * @praam int $category_id
         */
        
        public function __construct($category_id = false, $recurse = true) {
            parent::__construct(); 
            
            if (filter_var($category_id, FILTER_VALIDATE_INT)) {
                $this->id = $category_id;
            } else {
                $this->id = self::getIdFromSlug($category_id); 
            }
            
            if (filter_var($this->id, FILTER_VALIDATE_INT)) {
                $this->mckey = sprintf("railpage.links.category=%d", $this->id);
                
                if (!$row = $this->Memcached->fetch($this->mckey)) {
                    $row = $this->db->fetchRow("SELECT * FROM nuke_links_categories WHERE cid = ?", $this->id); 
                    
                    if (!empty($row)) {
                        $this->Memcached->save($this->mckey, $row);
                    }
                }
                    
                $this->name = $row['title']; 
                $this->desc = $row['cdescription']; 
                $this->parent_id = $row['parentid']; 
                $this->slug = $row['slug']; 
                $this->url = $this->makePermalink($this->id); 
                
                if ($this->parent_id > 0 && $recurse) {
                    $this->parent = new Category($this->parent_id); 
                }
            }
        }
        
        /**
         * Get links from this category
         * @since Version 3.7.5
         * @return array
         */
         
        public function getLinks($category_id = false, $sort = false, $direction = false) {
            if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
                throw new Exception("Cannot get links from a link category - invalid category ID given"); 
                return false;
            }
            
            $query = "SELECT lid AS link_id, title AS link_title, url AS link_url, description AS link_desc, date AS link_date FROM nuke_links_links WHERE cid = ? ORDER BY title ASC";
            
            $this->links = $this->db->fetchAll($query, $this->id);
            
            return $this->links;
        }
        
        /**
         * Factory code: Create an instance of Category
         * @since Version 3.10.0
         * @param string|int $category_id
         * @return Category
         */
        
        public static function CreateCategory($category_id) {
            
            $Database = (new AppCore)->getDatabaseConnection(); 
            $Redis = AppCore::getRedis(); 
            
            if (!filter_var($category_id, FILTER_VALIDATE_INT)) {
                $category_id = self::getIdFromSlug($category_id) ;
            }
            
            if (!filter_var($category_id, FILTER_VALIDATE_INT)) {
                throw new Exception("Cannot create instance of Category:: " . $category_id . " is not a valid link category"); 
            }
            
            $key = sprintf("railpage:link.category.id=%d", $category_id); 
            
            if (!$Category = $Redis->fetch($key)) {
                $Category = new Category($category_id); 
                
                $Redis->save($key, $Category, 0); 
            }
                
            $Category->setDatabaseConnection($Database);
            
            return $Category;
            
        }
        
        /**
         * Fetch the category ID from a URL slug
         * @since Version 3.10.0
         * @param string $slug
         * @return int
         */
        
        private static function getIdFromSlug($slug) {
            
            $Database = (new AppCore)->getDatabaseConnection(); 
            $Redis = AppCore::getRedis(); 
            
            $key = sprintf("railpage:link.category.slug=%s", $slug); 
            
            if ($id = $Redis->fetch($key)) {
                return $id; 
            }
            
            $id = $Database->fetchOne("SELECT cid FROM nuke_links_categories WHERE slug = ?", $slug);
            
            $Redis->save($key, $id); 
            
            return $id; 
            
        }
    }
    