<?php
	/**
	 * Module detail object
	 * @since Version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	 
	namespace Railpage;
	
	use Exception;
	use stdClass;
	use Railpage\AppCore;
	
	/**
	 * Railpage module information
	 * @since Version 3.8.7
	 */
	 
	class Module extends AppCore {
		
		/**
		 * Name
		 * @since Version 3.8.7
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * URL of this module
		 * @since Version 3.8.7
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Module namespace
		 * @since Version 3.8.7
		 * @var string $namespace;
		 */
		 
		public $namespace;
		
		/**
		 * Module file paths
		 * @since Version 3.8.7
		 * @var string $Paths
		 */
		
		public $Paths;
		
		/**
		 * Colour palette
		 * @since Version 3.8.7
		 * @var \stdClass $Colours
		 */
		
		public $Colours;
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 * @param string $module
		 */
		
		public function __construct($module) {
			if (is_string($module)) {
				$this->Colours = new stdClass;
				$this->Colours->primary = "#666";
				$this->Colours->accent = "#ddd";
				$this->Colours->inverse = "#333";
				
				switch (strtolower($module)) {
					
					case "assets" : 
						$this->name = "Assets";
						$this->url = "/assets";
						break;
					
					case "diagnostics" : 
						$this->name = "Diagnostics";
						$this->url = "/diagnostics";
						break;
					
					case "donations" : 
						$this->name = "Donations";
						$this->url = "/donations";
						break;
					
					case "downloads" : 
						$this->name = "Downloads";
						$this->url = "/downloads";
						break;
					
					case "events" : 
						$this->name = "Events";
						$this->url = "/events";
						break;
					
					case "feedback" : 
						$this->name = "Feedback";
						$this->url = "/feedback";
						break;
					
					case "flickr" :
						$this->name = "Flickr";
						$this->url = "/flickr";
						break;
					
					case "forums" : 
						$this->name = "Forums";
						$this->url = "/f.htm";
						break;
					
					case "glossary" : 
						$this->name = "Glossary";
						$this->url = "/glossary";
						break;
					
					case "help" : 
						$this->name = "Help";
						$this->url = "/help";
						break;
					
					case "home" : 
						$this->name = "Home";
						$this->url = "/home";
						break;
					
					case "ideas" : 
						$this->name = "Ideas";
						$this->url = "/ideas";
						break;
					
					case "images" : 
						$this->name = "Images";
						$this->url = "/Images";
						break;
					
					case "image" : 
						$this->name = "Images";
						$this->url = "/Images";
						break;
					
					case "jobs" : 
						$this->name = "Jobs";
						$this->url = "/jobs";
						break;
					
					case "links" : 
						$this->name = "Links";
						$this->url = "/links";
						break;
					
					case "locations" : 
						$this->name = "Locations";
						$this->url = "/locations";
						$this->Colours->primary = "#116416";
						$this->Colours->accent = "#54A759";
						$this->Colours->inverse = "#004304";
						break;
					
					case "locos" : 
						$this->name = "Locos";
						$this->url = "/locos";
						$this->Colours->primary = "#3D0CE8";
						$this->Colours->accent = "#576BFF";
						$this->Colours->inverse = "#1B054E";
						break;
					
					case "news" : 
						$this->name = "News";
						$this->url = "/news";
						$this->Colours->primary = "#8A2E60";
						$this->Colours->accent = "#CE8AAF";
						$this->Colours->inverse = "#450026";
						break;
					
					case "organisations" : 
						$this->name = "Organisations";
						$this->url = "/orgs";
						break;
					
					case "orgs" : 
						$this->name = "Organisations";
						$this->url = "/orgs";
						break;
					
					case "place" : 
						$this->name = "Place";
						$this->url = "/place";
						break;
					
					case "privatemessages" : 
						$this->name = "Private Messages";
						$this->url = "/messages";
						break;
					
					case "messages" : 
						$this->name = "Private Messages";
						$this->url = "/messages";
						break;
					
					case "pm" : 
						$this->name = "Private Messages";
						$this->url = "/messages";
						break;
						
					case "railcams" :
						$this->name = "Railcams";
						$this->url = "/railcams";
						break;
					
					case "search" : 
						$this->name = "Search";
						$this->url = "/search";
						break;
					
					case "users" : 
						$this->name = "Users";
						$this->url = "/user";
						break;
					
					case "reminders" : 
						$this->name = "Reminders";
						$this->url = "/reminders";
						break;
						
				}
				
				/**
				 * Lazy populate the namespace
				 */
				 
				if (empty($this->namespace) && !empty($this->name)) {
					$this->namespace = sprintf("railpage.%s", strtolower($this->name));
				}
				
				/**
				 * Lazy populate the URL
				 */
				 
				if (empty($this->url) && !empty($this->name)) {
					$this->url = sprintf("modules.php?name=%s", $this->name);
				}
				
				/**
				 * Create and populate the filesystem paths
				 */
				
				$this->Paths = new stdClass;
				$this->Paths->module = sprintf("%s/modules/%s", RP_SITE_ROOT, $this->name);
				$this->Paths->html = sprintf("%s/modules/%s/html", RP_SITE_ROOT, $this->name);
			}
		}
	}
?>