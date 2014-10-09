<?php
	/**
	 * Content class
	 * @since Version 3.5
	 * @package Railpage
	 * @author Michael Greenhill
	 * @todo Add validate() and commit() methods
	 */
	
	namespace Railpage\Content;
	
	use Exception;
	use DateTime;
	use Railpage\Url;
	
	/**
	 * Content
	 */
	
	class Page extends Content {
		
		/**
		 * Page ID
		 * @var int $id
		 */
		
		public $id; 
		
		/**
		 * Active flag
		 * @var boolean $active
		 */
		
		public $active;
		
		/**
		 * Page title 
		 * @var string $title
		 */
		
		public $title;
		
		/**
		 * Page subtitle
		 * @var string $subtitle
		 */
		
		public $subtitle; 
		
		/**
		 * Page header
		 * @var string $header
		 */
		
		public $header;
		
		/**
		 * Page body
		 * @var string $body
		 */
		
		public $body; 
		
		/**
		 * Page footer
		 * @var string $footer
		 */
		
		public $footer; 
		
		/**
		 * Date published
		 * @var object $date
		 */
		
		public $date; 
		
		/**
		 * Hits counter
		 * @var int $hits
		 */
		 
		public $hits;
		
		/**
		 * Page language
		 * @var string $langauge
		 */
		
		public $language; 
		
		/**
		 * Page URL stub / permalink
		 * @var string $permalink
		 */
		
		public $permalink;
		
		/**
		 * Constructor
		 * @since Version 3.5
		 * @param int|string $id
		 */
		
		public function __construct($id = false) {
			
			parent::__construct();
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;  
			} elseif (is_string($id) && strlen($id) > 1) {
				$query = "SELECT pid FROM nuke_pages WHERE shortname = ?";
				
				$this->id = $this->db->fetchOne($query, $id); 
			}
			
			if (filter_var($this->id)) {
				$this->mckey = sprintf("railpage:page=%d", $this->id);
				$this->fetch();
			}
		}
		
		/**
		 * Fetch a page
		 * @since Version 3.5
		 */
		
		public function fetch() {
			if (!$this->id) {
				throw new Exception("Cannot fetch page - no page ID given"); 
				return false;
			}
			
			if (!$row = getMemcacheObject($this->mckey)) {
				$query = "SELECT * FROM nuke_pages WHERE pid = ?";
				
				$row = $this->db->fetchRow($query, $this->id);
				
				foreach ($row as $key => $val) {
					$row[$key] = stripslashes($val);
				}
				
				setMemcacheObject($this->mckey, $row, strtotime("+1 month"));
			}
			
			$this->title 		= $row['title']; 
			$this->subtitle 	= $row['subtitle']; 
			$this->active 		= $row['active']; 
			$this->header		= $row['page_header']; 
			$this->body			= $row['text']; 
			$this->footer		= $row['page_footer']; 
			$this->date			= new DateTime($row['date']);
			$this->hits			= $row['counter']; 
			$this->langauge		= isset($row['language']) ? $row['language'] : NULL;
			$this->permalink	= $row['shortname']; 
			
			$this->url = new Url(sprintf("/static-%s.htm", $this->permalink));
			$this->url->edit = sprintf("/admin/pages/edit/%d", $this->id);
			
			return true;
		}
	}
?>