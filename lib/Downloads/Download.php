<?php
    /** 
     * Downloads 
     * @since Version 3.0
     * @version 3.8.7
     * @package Railpage
     * @author Michael Greenhill
     */
    
    namespace Railpage\Downloads;
    
    use Railpage\Users\User;
    use Railpage\Users\Factory as UserFactory;
    use Railpage\Url;
    use DateTime;
    use DateTimeZone;
    use Exception;
    
    /**
     * Download item class
     * @since Version 3.2
     * @version 3.8.7
     */
    
    class Download extends Base {
        
        /**
         * Download ID
         * @since Version 3.2
         * @var int $id The ID of the download
         */
        
        public $id; 
        
        /**
         * Download name
         * @since Version 3.2
         * @var string $name The name of the download
         */
        
        public $name;
        
        /**
         * Description
         * @since Version 3.2
         * @var string $desc Text describing the download
         */
        
        public $desc;
        
        /**
         * Download URL
         * @since Version 3.2
         * @var string $url_file The URL of the download, relative to the site root
         */
        
        public $url_file;
        
        /**
         * Filename
         * @since Version 3.2
         * @var string $filename The original filename of the download
         */
        
        public $filename;
        
        /**
         * Filepath
         * Absolute filepath 
         * @since Version 3.2
         * @var string $filepath Path to the download on the server
         */
        
        public $filepath;
        
        /**
         * User ID
         * @since Version 3.2
         * @var int $user_id The ID of the user that added this download
         */
        
        public $user_id;
        
        /**
         * Hits
         * @since Version 3.2
         * @var int $hits Number of times this has been downloaded
         */
         
        public $hits;
        
        /**
         * Filesize
         * @version 3.2
         * @var string $filesize Size of the download
         */
         
        public $filesize;
        
        /**
         * MIME type
         * @since Version 3.2
         * @var string $mime MIME type of the download
         */
        
        public $mime;
        
        /**
         * Object ID
         * @since Version 3.2
         * @var string $object_id Object ID - used to alert staff via PM. Probably not the best way to do it - might look at deprecating this in the future
         */
        
        public $object_id;
        
        /**
         * Extra data
         * @since Version 3.5
         * @var array $extra_data Any extra data about this download
         */
        
        public $extra_data;
        
        /**
         * Active ("deleted")
         * @since Version 3.2
         * @var int $active Active/inactive flag
         */
        
        public $active;
        
        /**
         * Approved
         * @since Version 3.2
         * @var int $approved Approved/pending flag
         */
        
        public $approved;
        
        /**
         * Category object
         * @since Version 3.2
         * @var \Railpage\Downloads\Category $Category The category of this download
         */
        
        public $Category;
        
        /**
         * DateTime object
         * @since Version 3.8.7
         * @var \DateTime $DateTime DateTime object representing the date it was added to the database
         */
        
        public $Date;
        
        /**
         * Author
         * @since Version 3.9.1
         * @var \Railpage\Users\User $Author
         */
        
        public $Author;
        
        /**
         * Constructor
         * @since Version 3.2
         */
        
        public function __construct() {
            foreach (func_get_args() as $arg) {
                if (filter_var($arg, FILTER_VALIDATE_INT)) {
                    $this->id = $arg;
                }
            }
            
            parent::__construct();
            
            if (!empty($this->id)) {
                
                $this->mckey = sprintf("railpage:downloads.download=%d", $this->id);
                
                // Populate the object vars
                $this->fetch(); 
            }
        }
        
        /**
         * Load the download data from the database and populate this class
         * @since Version 3.2
         * @return boolean
         */
        
        public function fetch() {
            if (empty($this->id)) {
                throw new Exception("Cannot fetch download object - no download ID given"); 
            }
            
            $this->url = new Url(sprintf("%s?mode=download.view&id=%d", $this->Module->url, $this->id));
            $this->url->download = sprintf("https://www.railpage.com.au/downloads/%s/get", $this->id);
            
            $query = "SELECT d.*, UNIX_TIMESTAMP(d.date) AS date_unix FROM download_items AS d WHERE d.id = ?";
            
            $row = $this->db->fetchRow($query, $this->id);
                
            if (!is_array($row) || count($row) === 0) {
                throw new Exception("Requested download not found");
            }
            
            // Populate the vars
            $this->name     = $row['title']; 
            $this->desc     = $row['description']; 
            $this->url_file = $row['url']; 
            $this->filename = empty($row['filename']) ? basename($row['url']) : $row['filename']; 
            $this->Date     = new DateTime($row['date'], new DateTimeZone("Australia/Melbourne"));
            $this->hits     = $row['hits'];
            $this->filesize = isset($row['filesize']) && $row['filesize'] > 0 ? formatBytes($row['filesize']) : "Unknown";
            $this->user_id  = $row['user_id'];
            $this->filepath = $row['filepath'];
            
            $this->object_id    = $row['object_id'];
            $this->approved     = $row['approved'];
            $this->active       = $row['active'];
            $this->extra_data   = $row['extra_data'];
            $this->mime = $row['mime'];
            
            if (empty($this->filepath) && !empty($this->url_file)) {
                $pathinfo = parse_url($this->url_file); 
                $this->filepath = str_replace("/uploads/", "", $pathinfo['path']);
                
                try {
                    $this->commit(); 
                } catch (Exception $e) {
                    // Do nothing
                }
            }
            
            if (!preg_match("@^(http|https)://@", $this->url_file)) {
                $this->url_file = parent::DOWNLOAD_HOST . parent::DOWNLOAD_DIR . $this->url_file; 
            }
            
            if ($row['date'] == "0000-00-00 00:00:00") {
                $this->Date = new DateTime("now", new DateTimeZone("Australia/Melbourne")); 
                $this->commit();
            }
            
            if (empty($this->user_id) && !empty($row['submitter'])) {
                $this->submitter = $row['submitter'];
            }
            
            /**
             * Load the category this download belongs to
             */
            
            $this->Category = new Category($row['category_id']); 
            
            /**
             * Load the author
             */
            
            $this->Author = UserFactory::CreateUser($this->user_id);
        }
        
        /**
         * Validate the file OK before committing it
         * @since Version 3.2
         * @version 3.8.7
         * @return boolean
         * @throws \Exception if the download name is empty
         * @throws \Exception if the download filename is empty
         */
        
        public function validate() {
            if (empty($this->name)) {
                throw new Exception("Verification failed - download must have a name");
            }
            
            if (empty($this->Date) || !$this->Date instanceof DateTime) {
                $this->Date = new DateTime;
            }
            
            if (empty($this->filename)) {
                throw new Exception("Verification failed - download must have a filename");
            }
            
            if (is_null($this->mime)) {
                $this->mime = "";
            }
            
            if (!filter_var($this->active, FILTER_VALIDATE_INT)) {
                $this->active = 1;
            } 
            
            if (!filter_var($this->approved, FILTER_VALIDATE_INT)) {
                $this->approved = 0;
            }
            
            if (!filter_var($this->hits, FILTER_VALIDATE_INT)) {
                $this->hits = 0;
            }
            
            if (empty($this->extra_data)) {
                $this->extra_data = array();
            }
            
            if ($this->Author instanceof User) {
                $this->user_id = $this->Author->id;
            }
            
            if (!filter_var($this->user_id, FILTER_VALIDATE_INT)) {
                throw new Exception("No valid owner of this download has been provided");
            }
            
            return true;
        }
        
        /**
         * Commit a file to the database
         * @since Version 3.2
         * @version 3.8.7
         * @return boolean
         */
        
        public function commit() {
            $this->validate();
            
            if (is_array($this->extra_data)) {
                $this->extra_data = json_encode($this->extra_data); 
            }
            
            $data = array(
                "category_id" => $this->Category instanceof Category ? $this->Category->id : 10,
                "title" => $this->name,
                "url" => $this->url_file,
                "filename" => $this->filename,
                "mime" => $this->mime,
                "description" => $this->desc,
                "date" => $this->Date->format("Y-m-d h:i:s"),
                "hits" => $this->hits,
                "user_id" => $this->user_id,
                "filepath" => $this->filepath,
                "object_id" => filter_var($this->object_id, FILTER_VALIDATE_INT) ? $this->object_id : 0,
                "approved" => $this->approved,
                "active" => $this->active,
                "extra_data" => $this->extra_data,
                "url" => $this->approved ? str_replace(dirname(dirname(__FILE__)), RP_PROTOCOL."://" . RP_HOST, $this->filepath) : ""
            );
            
            /**
             * Commit the changes
             */
            
            if (empty($this->id)) {
                $data['active']         = 1;
                $data['category_id']    = $this->cat_id;
                $data['filesize']       = empty($this->filesize) ? 0 : $this->filesize; 
                
                $this->db->insert("download_items", $data);
                $this->id = $this->db->lastInsertId();
                
                return $this->id;
            } else {
                $where = array(
                    "id = ?" => $this->id
                );
                
                $this->db->update("download_items", $data, $where);
            }
        }
        
        /**
         * Log to the database when this file has been downloaded
         * @since Version 3.5
         * @param string $ip The client IP address
         * @param int $user_id The user that downloaded this file
         * @param string $username The username of the user that downloaded this file
         */
        
        public function log($ip = false, $user_id = false, $username = false) {
            $data = array(
                "download_id" => $this->id,
                "date" => "NOW()",
                "ip" => $ip
            );
            
            if ($user_id && $username) {
                $data['user_id'] = $user_id;
                $data['username'] = $username;
            }
            
            $this->db->insert("log_downloads", $data);
            return true;
        }
        
        /**
         * Delete this download
         * @since Version 3.8.7
         * @return boolean
         */
        
        public function delete() {
            if (is_file($this->filepath)) {
                unlink($this->filepath);
            }
            
            $where = array(
                "id = ?" => $this->id
            );
            
            return $this->db->delete("download_items", $where);
        }
        
        /**
         * Generate a thumbnail of this file
         * @since Version 3.9.1
         * @return string
         */
        
        public function getThumbnail() {
            if (empty($this->mime) && file_exists(RP_DOWNLOAD_DIR . $this->filepath)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE); 
                $this->mime = finfo_file($finfo, RP_DOWNLOAD_DIR . $this->filepath);
            }
            
            $mime = explode("/", $this->mime); 
            $thumbnail = sprintf("%s.thumbnail.jpg", $this->filepath);
            
            if (file_exists(RP_DOWNLOAD_DIR . $thumbnail)) {
                $info = getimagesize(RP_DOWNLOAD_DIR . $thumbnail);
                
                return array(
                    "file" => $thumbnail,
                    "width" => $info[0],
                    "height" => $info[1],
                    "size" => filesize(RP_DOWNLOAD_DIR . $thumbnail)
                );
            }
            
            switch ($mime[0]) {
                case "video" : 
                    $avlib = self::getAVLib();
                    
                    exec(sprintf("%s -i %s%s -vsync 1 -r 1 -an -y -vframes 1 '%s%s'", $avlib, RP_DOWNLOAD_DIR, $this->filepath, RP_DOWNLOAD_DIR, $thumbnail), $return);
                    
                    $info = getimagesize(RP_DOWNLOAD_DIR . $thumbnail);
                
                    return array(
                        "file" => $thumbnail,
                        "width" => $info[0],
                        "height" => $info[1],
                        "size" => filesize(RP_DOWNLOAD_DIR . $thumbnail)
                    );
                    
                    break;
            }
            
            return false;
        }
        
        /**
         * Get detailed information about this file
         * @since Version 3.9.1
         * @return array
         */
        
        public function getDetails() {
            
            $mckey = sprintf("%s;details", $this->mckey); 
            
            if (!$data = $this->Memcached->fetch($mckey)) {
                if (empty($this->mime) && file_exists(RP_DOWNLOAD_DIR . $this->filepath)) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE); 
                    $this->mime = finfo_file($finfo, RP_DOWNLOAD_DIR . $this->filepath);
                }
                
                $mime = explode("/", $this->mime); 
                
                $data = array(
                    "type" => ucwords($mime[0]),
                    "size" => $this->filesize,
                    "downloads" => $this->hits,
                    "added" => array(
                        "absolute" => $this->Date->format("Y-m-d g:i:s a"),
                        "relative" => time2str($this->Date->getTimestamp())
                    ),
                    "thumbnail" => $this->getThumbnail(),
                    "video" => $this->getHTML5Video()
                );
                
                $this->Memcached->save($mckey, $data, strtotime("+12 hours"));
            }
            
            return $data;
        }
        
        /**
         * Get the AV library available on this system
         * @since Version 3.9.1
         * @return string
         * @throws \Exception if no suitable AV library is available
         */
        
        static public function getAVLib() {
            if (file_exists("/usr/bin/avconv")) {
                $avlib = "/usr/bin/avconv";
            } else {
                throw new Exception("No video library (libav or ffmpeg) was found");
            }
            
            return $avlib;
        }
        
        /**
         * Convert this file to a HTML5-compatible video format
         * @since Version 3.9.1
         * @return array
         */
        
        public function getHTML5Video() {
            if (empty($this->mime) && file_exists(RP_DOWNLOAD_DIR . $this->filepath)) {
                $this->getThumbnail(); 
            }
            
            $mime = explode("/", $this->mime); 
            
            if ($mime[0] != "video") {
                return false;
            }
            
            $avlib = self::getAVLib();
            
            $videos = array();
            
            set_time_limit(1200);
            
            /**
             * Mp4
             */
            
            $dstfile = sprintf("%s/%s.v3.mp4", pathinfo($this->filepath, PATHINFO_DIRNAME), pathinfo($this->filepath, PATHINFO_FILENAME));
            
            if (!file_exists(RP_DOWNLOAD_DIR . $dstfile)) {
                exec(sprintf("%s -i %s%s -vcodec h264 -acodec aac -strict -2 -ar 64k %s%s", $avlib, RP_DOWNLOAD_DIR, $this->filepath, RP_DOWNLOAD_DIR, $dstfile), $return);
            }
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE); 
            $mime = finfo_file($finfo, RP_DOWNLOAD_DIR . $dstfile);
            
            $videos['mp4'] = array(
                "absolute" => RP_DOWNLOAD_DIR . $dstfile,
                "file" => $dstfile,
                "size" => filesize(RP_DOWNLOAD_DIR . $dstfile),
                "mime" => $mime
            );
            
            /**
             * WebM
             */
            
            $dstfile = sprintf("%s/%s.v3.webm", pathinfo($this->filepath, PATHINFO_DIRNAME), pathinfo($this->filepath, PATHINFO_FILENAME));
            
            if (!file_exists(RP_DOWNLOAD_DIR . $dstfile)) {
                exec(sprintf("%s -i %s%s -acodec libvorbis -ac 2 -ab 96k -ar 44100 %s%s", $avlib, RP_DOWNLOAD_DIR, $this->filepath, RP_DOWNLOAD_DIR, $dstfile), $return);
            }
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE); 
            $mime = finfo_file($finfo, RP_DOWNLOAD_DIR . $dstfile);
            
            $videos['webm'] = array(
                "absolute" => RP_DOWNLOAD_DIR . $dstfile,
                "file" => $dstfile,
                "size" => filesize(RP_DOWNLOAD_DIR . $dstfile),
                "mime" => $mime
            );
            
            return $videos;
        }
    }
    