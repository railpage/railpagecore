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
		 * @param object $Provider The provider of the image, using the \Railpage\Railcams\Provider interface
		 * @param int $id The ID of the photo from the provider
		 */
		
		public function __construct($Provider = false, $id = false) {
			
			parent::__construct(); 
			
			$implements = class_implements($Provider);
			
			if (in_array("Railpage\\Railcams\\ProviderInterface", $implements)) {
				$this->Provider = $Provider;
				
				if (filter_var($id, FILTER_VALIDATE_INT)) {
					$this->id = $id;
					$photodata = $this->Provider->getPhoto($this->id);
					
					printArray($photodata);
					
					$this->title = $photodata['title'];
					$this->description = $photodata['description'];
					$this->dates = $photodata['dates'];
					$this->author = $photodata['author'];
					$this->sizes = $photodata['sizes'];
					
					
				}
			}
		}
	}
?>