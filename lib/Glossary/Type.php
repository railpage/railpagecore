<?php
	/**
	 * Glossary type - term, acronym, etc
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Glossary;
	
	use Railpage\AppCore;
	use Railpage\Module;
	use Exception;
	use DateTime;
	use stdClass;
	
	/**
	 * Type
	 */
	
	class Type extends AppCore {
		
		/**
		 * ID
		 * @var string $id;
		 */
		
		public $id;
		
		/**
		 * Type name
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * URL
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param string $type
		 */
		
		public function __construct($type = false) {
			parent::__construct();
			
			$this->Module = new Module("glossary");
			
			if ($type) {
				$this->url = sprintf("%s?mode=type&type=%s", $this->Module->url, $type);
				$this->id = $type;
				
				switch ($type) {
					case "code" : 
						$this->name = "Codes";
						break;
						
					case "term" : 
						$this->name = "Terminology";
						break;
					
					case "acronym" : 
						$this->name = "Acronyms";
						break;
					
					case "station" : 
						$this->name = "Stations";
						break;
					
					case "slang" : 
						$this->name = "Slang";
						break;
					
					case "general" :
					default :
						$this->name = "General";
						break;
				}
			}
		}
		
		/**
		 * Get glossary entries of this type
		 * @since Version 3.8.7
		 * @yield \Railpage\Glossary\Entry
		 */
		
		public function getEntries() {
			$query = "SELECT id FROM glossary WHERE type = ? AND status = ? ORDER BY short";
			
			foreach ($this->db->fetchAll($query, array($this->id, Entry::STATUS_APPROVED)) as $row) {
				$id = $row['id'];
				
				yield new Entry($id);
			}
		}
	}
?>