<?php
	/** 
	 * Downloads 
	 * @since Version 3.0
	 * @version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Downloads;
	
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
				
				// Populate the object vars
				try {
					$this->fetch(); 
				} catch (Exception $e) {
					throw new \Exception($e->getMessage()); 
				}
			}
		}
		
		/**
		 * Load the download data from the database and populate this class
		 * @since Version 3.2
		 * @return boolean
		 */
		
		public function fetch() {
			if (empty($this->id)) {
				throw new \Exception("Cannot fetch download object - no download ID given"); 
				return false;
			}
			
			$this->url = sprintf("https://www.railpage.com.au/downloads/%s/get", $this->id);
			
			$query = "SELECT d.*, UNIX_TIMESTAMP(d.date) AS date_unix FROM download_items AS d WHERE d.id = ?";
			
			$row = $this->db->fetchRow($query, $this->id);
				
			if (isset($row)) {
				// Populate the vars
				$this->name 	= $row['title']; 
				$this->desc		= $row['description']; 
				$this->url_file	= $row['url']; 
				$this->filename	= empty($row['filename']) ? basename($row['url']) : $row['filename']; 
				$this->Date		= new DateTime($row['date'], new DateTimeZone("Australia/Melbourne"));
				$this->hits		= $row['hits'];
				$this->filesize	= isset($row['filesize']) && $row['filesize'] > 0 ? formatBytes($row['filesize']) : "Unknown";
				$this->user_id	= $row['user_id'];
				$this->filepath	= $row['filepath'];
				
				$this->object_id	= $row['object_id'];
				$this->approved		= $row['approved'];
				$this->active		= $row['active'];
				$this->extra_data	= $row['extra_data'];
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
				
				// Load the Category object
				try {
					$this->Category = new Category($row['category_id']); 
				} catch (Exception $e) {
					throw new \Exception($e->getMessage()); 
				}
			}
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
			
			if (empty($this->mime)) {
				$this->mime = "";
			}
			
			if (!filter_var($this->active, FILTER_VALIDATE_INT)) {
				$this->active = 1;
			} 
			
			if (!filter_var($this->approved, FILTER_VALIDATE_INT)) {
				$this->approved = 0;
			}
			
			if (empty($this->extra_data)) {
				$this->extra_data = array();
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
			
			$dataArray = array(); 
			$dataArray['category_id'] 	= $this->Category instanceof Category ? $this->Category->id : 10; 
			$dataArray['title']			= $this->name; 
			$dataArray['url']			= $this->url_file; 
			$dataArray['filename']		= $this->filename;
			$dataArray['mime']			= $this->mime;
			$dataArray['description'] 	= $this->desc; 
			$dataArray['date']			= $this->Date->format("Y-m-d h:i:s");
			$dataArray['hits']			= empty($this->hits) ? 0 : $this->hits; 
			$dataArray['user_id']		= $this->user_id;
			$dataArray['filepath']		= $this->filepath;
			$dataArray['object_id']		= empty($this->object_id) ? 0 : $this->object_id; 
			$dataArray['approved']		= $this->approved;
			$dataArray['active']		= $this->active;
			$dataArray['extra_data']	= $this->extra_data;
			
			if ($this->approved) {
				$dataArray['url'] = str_replace(dirname(dirname(__FILE__)), RP_PROTOCOL."://" . RP_HOST, $this->filepath); 
			} else {
				$dataArray['url'] = "";
			}
			
			/**
			 * Commit the changes
			 */
			
			if (empty($this->id)) {
				$dataArray['active'] 		= 1;
				$dataArray['category_id']	= $this->cat_id;
				$dataArray['filesize']		= empty($this->filesize) ? 0 : $this->filesize; 
				
				$this->db->insert("download_items", $dataArray);
				$this->id = $this->db->lastInsertId();
				
				return $this->id;
			} else {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("download_items", $dataArray, $where);
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
	}
?>