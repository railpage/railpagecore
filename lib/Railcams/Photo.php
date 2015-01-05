<?php
	/**
	 * Railcam photo
	 * @since Version 3.9
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Railcams;
	
	use Exception;
	use DateTime;
	use flickr_railpage;
	use Railpage\Url;
	
	/**
	 * Railcam photo
	 */
	
	class Photo extends Railcams {
		
		/**
		 * Photo provider
		 * @since Version 3.9
		 */
		
		private $Provider;
		
		/**
		 * Railcam which took this photo
		 * @var \Railpage\Railcams\Camera
		 * @since Version 3.9
		 */
		
		private $Camera;
		
		/**
		 * Photo ID
		 * @since Version 3.9
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Photo title
		 * @since Version 3.9
		 * @var string $title
		 */
		
		public $title;
		
		/**
		 * Photo description
		 * @since Version 3.9
		 * @var string $description
		 */
		
		public $description;
		
		/**
		 * Array of DateTime instances appliccable to this photo
		 * @since Version 3.9
		 * @var array $dates
		 */
		
		public $dates;
		
		/**
		 * Associative array of photo author details
		 * @since Version 3.9
		 * @var array $author
		 */
		
		public $author;
		
		/**
		 * Array of different photo sizes
		 * @since Version 3.9
		 * @var array $sizes
		 */
		
		public $sizes;
		
		/**
		 * Constructor
		 * @since Version 3.9
		 * @param int $id The ID of the photo from the provider
		 * @param object $Provider The provider of the image, using the \Railpage\Railcams\Provider interface
		 */
		
		public function __construct($id = false, $Provider = false) {
			
			parent::__construct(); 
			
			if ($Provider) {
				$this->setProvider($Provider);
			}
				
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
				
				if (is_object($this->Provider)) {
					$this->load();
				}
			}
		}
		
		/**
		 * Set the provider of this photo
		 * @since Version 3.9
		 * @param object $Provider The provider of the image, using the \Railpage\Railcams\Provider interface
		 * @return \Railpage\Railcams\Photo
		 */
		
		public function setProvider($Provider = false) {
			$implements = class_implements($Provider);
			
			if (in_array("Railpage\\Railcams\\ProviderInterface", $implements)) {
				$this->Provider = $Provider;
			} else {
				throw new Exception("The specified object " . get_class($Provider) . " does not implement \\Railpage\\Railcams\\ProviderInterface");
			}
			
			return $this;
		}
		
		/**
		 * Set the railcam which took this photo
		 * @since Version 3.9
		 * @param \Railpage\Railcams\Camera $Camera
		 * @return \Railpage\Railcams\Photo
		 */
		
		public function setCamera(Camera $Camera) {
			$this->Camera = $Camera;
			
			return $this;
		}
		
		/**
		 * Load this photo
		 * @since Version 3.9
		 * @return \Railpage\Railcams\Photo
		 */
		
		public function load() {
			$photodata = $this->Provider->getPhoto($this->id);
					
			$this->title = $photodata['title'];
			$this->description = $photodata['description'];
			$this->dates = $photodata['dates'];
			$this->author = $photodata['author'];
			$this->sizes = $photodata['sizes'];
			
			if ($this->Camera instanceof Camera) {
				$this->url = new Url(sprintf("%s%d", $this->Camera->url->photo, $this->id));
			}
			
			return $this;
		}
		
		/**
		 * Get the name of this photo provider
		 * @since Version 3.9
		 * @return string
		 */
		
		public function getProviderName() {
			return $this->Provider->getProviderName(); 
		}
		
		/**
		 * Save changes to this photo
		 * @since Version 3.9
		 * @return \Railpage\Railcams\Photo
		 */
		
		public function commit() {
			return $this->Provider->setPhoto($this);
		}
	}
?>