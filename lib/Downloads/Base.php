<?php
    /** 
     * Downloads 
     * @since Version 3.0
     * @version 3.2
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Downloads;
    
    use Railpage\AppCore;
    use Railpage\Module;
    use Exception;
    
    if (!defined("DS")) {
        define("DS", DIRECTORY_SEPARATOR);
    }
    
    if (isset($RailpageConfig->Uploads->Directory)) {
        define("RP_DOWNLOAD_DIR", $RailpageConfig->Uploads->Directory);
    } else {
        if (defined("RP_SITE_ROOT")) {
            define("RP_DOWNLOAD_DIR", sprintf("%s%suploads%s", RP_SITE_ROOT, DS, DS));
        } else{
            define("RP_DOWNLOAD_DIR", dirname(dirname(dirname(dirname(dirname(__DIR__))))). DS . "uploads" . DS); 
        }
    }
    
    if (isset($RailpageConfig->Uploads->MaxSize)) {
        ini_set("upload_max_filesize", $RailpageConfig->Uploads->MaxSize);
        ini_set("post_max_size", $RailpageConfig->Uploads->MaxSize);
    }
    
    /**
     * Downloads base class
     *
     * Sets basic settings such as the storage directory and provides high-level methods 
     * @since Version 3.2
     */
    
    class Base extends AppCore {
        
        /**
         * Host server providing the downloads
         * @since Version 3.8.7
         * @const string DOWNLOAD_HOST
         */
        
        const DOWNLOAD_HOST = "https://static.railpage.com.au";
        
        /**
         * Subdirectory in the server hosting the files
         * @since Version 3.8.7
         * @const string DOWNLOAD_DIR
         */
         
        const DOWNLOAD_DIR = "/uploads/";
        
        /**
         * Directory to store the downloads in
         * @since Version 3.0
         * @var string $dir The folder on the server where downloads are stored in. Assumes a single source of files to serve.
         */
        
        public $dir;
        
        /**
         * Constructor
         * @since Version 3.2
         * @param string $dir The download directory to use. If null we'll use the preset one. 
         */
        
        public function __construct($dir = NULL) {
            parent::__construct();
            
            $this->Module = new Module("Downloads");
                
            if (is_null($dir)) {
                // Try to set the directory
                $this->dir = RP_DOWNLOAD_DIR; 
            } else {
                $this->dir = $dir;
            }
        }
        
        /**
         * Load download category heirachy
         * @since Version 3.2
         * @version 3.2
         * @return array
         */
        
        public function categories() {
            $query = "SELECT category_id, category_title, category_description, parentid AS category_parent_id FROM download_categories ORDER BY parentid, category_title";
            
            $return = array(
                "stat" => "ok",
                "categories" => array()
            );
            
            foreach ($this->db->fetchAll($query) as $row) {
                if ($row['category_parent_id'] == "0") {
                    $return['categories'][$row['category_id']] = $row;
                } else {
                    $return['categories'][$row['category_parent_id']]['children'][$row['category_id']] = $row;
                }
            }
            
            return $return;
        }
        
        /**
         * Normalised name for categories() method
         * @since Version 3.8.7
         * @return array
         */
        
        public function getCategories() {
            $categories = $this->categories(); 
            
            return $categories['categories'];
        }
        
        /**
         * Check uploaded files for bad mimetypes or other verboten elements. If it looks dodgy it'll be deleted
         * @since Version 3.2
         * @return boolean
         * @param string $file Absolute path to the file we want to check
         */
        
        public function safety($file) {
            if (!file_exists($file)) {
                return false;
            }
            
            $badmimes = array(
                "text/javascript",
                "script/php",
                "application/x-bittorrent",
                "text/x-php",
            );
            
            $finfo  = finfo_open(FILEINFO_MIME_TYPE);
            $mime   = finfo_file($finfo, $file);
            
            if (in_array($mime, $badmimes)) {
                unlink($file);
                return false;
            }
            
            return true;
        }
    }
    