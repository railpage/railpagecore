<?php
    /**
     * FWlink class
     * @since Version 3.8.6
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage;
    
    use Railpage\AppCore;
    use Exception;
    
    /**
     * FWlink class
     */
    
    class fwlink extends AppCore {
        
        /**
         * Link ID
         * @since Version 3.8.6
         * @var int $id
         */
        
        public $id;
        
        /**
         * Original URL
         * @since Version 3.8.6
         * @var string $url
         */
        
        public $url;
        
        /**
         * Canonical URL
         * @since Version 3.8.6
         * @var string $url_canonical
         */
        
        public $url_canonical;
        
        /**
         * Short URL
         * @since Version 3.8.6
         * @var string $url_short
         */
        
        public $url_short;
        
        /**
         * Title
         * @since Version 3.7.6
         * @var string $title
         */
        
        public $title;
        
        /**
         * Memcached ID 
         * @since Version 3.9.1
         * @var string $mckey
         */
        
        public $mckey = NULL;
        
        /**
         * Constructor
         * @since Version 3.8.6
         * @param int|string $id
         */
        
        public function __construct($id = false) {
            
            parent::__construct();
            
            if ($id !== false && !filter_var($id, FILTER_VALIDATE_INT)) {
                #if (!$newid = $this->Memcached->fetch(sprintf("railpage:fwlink.fromurl=%s", $id))) {
                    $query = "SELECT id FROM fwlink WHERE url = ?";
                    $newid = $this->db->fetchOne($query, $id);
                #}
                
                if (filter_var($newid, FILTER_VALIDATE_INT)) {
                    $id = $newid;
                }
            }
            
            if (filter_var($id, FILTER_VALIDATE_INT)) {
                $this->id = $id; 
                $this->load(); 
                
                if (!empty($this->url)) {
                    $this->makeURLs(); 
                }
            }
        
        }
        
        /**
         * Populate this object
         * @since Version 3.9.1
         * @return void
         */
        
        private function load() {
            $this->mckey = sprintf("railpage:fwlink=%s", md5($this->id));
            
            if (!$row = $this->Memcached->fetch($this->mckey)) {
                
                $query = "SELECT * FROM fwlink WHERE id = ?";
            
                $row = $this->db->fetchRow($query, $this->id);
                
            }
            
            if (isset($row) && count($row)) {
                $this->id = $row['id']; 
                $this->url = $row['url'];
                $this->title = $row['title'];
                
                $this->Memcached->save($this->mckey, $row, strtotime("+1 month"));
            }
                
            return;
            
        }
        
        /**
         * Make extra URLs
         * @since Version 3.9.1
         * @return void
         */
        
        private function makeURLs() {
            $this->url_canonical = sprintf("http://%s%s", RP_HOST, $this->url);
            $this->url_short = sprintf("http://%s/go/%d", "railpage.com.au", $this->id);
        }
        
        /**
         * Return the short URL
         * @since Version 3.8.6
         * @return string
         */
        
        public function __toString() {
            return !empty($this->url_short) ? $this->url_short : "";
        }
        
        /**
         * Validate changes to this URL
         * @since Version 3.8.6
         * @return boolean
         */
        
        public function validate() {
            if (is_object($this->url)) {
                $this->url = strval($this->url);
            }
            
            if (empty($this->url) || !is_string($this->url)) {
                throw new Exception("Cannot validate new link - \$url is empty or not a string"); 
            }
            
            if (empty($this->title) || !is_string($this->title)) {
                throw new Exception("Cannot validate new link - \$title is empty or not a string"); 
            }
            
            return true;
        }
        
        /**
         * Commit changes to this URL
         * @since Version 3.8.6
         * @return boolean
         */
         
        public function commit() {
            $this->validate(); 
            
            $data = array(
                "url" => $this->url,
                "title" => $this->title
            );
            
            $this->Memcached->delete($this->mckey); 
            
            if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
                $this->db->insert("fwlink", $data); 
                $this->id = $this->db->lastInsertId(); 
            } else {
                $where = array(
                    "id = ?" => $this->id
                );
                
                $this->db->update("fwlink", $data, $where); 
            }
            
            $this->makeURLs(); 
            
            return true;
        }
        
    }
    