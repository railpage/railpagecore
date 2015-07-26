<?php
	/**
	 * RP G1 gallery image provider for Images
	 * @since Version 3.9
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images\Provider;
	
	use Railpage\Images\Images;
	use Railpage\Images\Image;
	use Railpage\Images\ProviderInterface;
	use Railpage\AppCore;
	use Railpage\Url;
	use Exception;
	use DateTime;
	use DateTimeZone;
	use stdClass;
	use DomDocument;
	use GuzzleHttp\Client;
	use Railpage\Gallery\Album as G1Album;
	use Railpage\Gallery\Image as G1Image;
	use Railpage\Users;
	
	/**
	 * Provider
	 */
	
	class RPOldGallery extends AppCore implements ProviderInterface {
		
		/**
		 * Provider name
		 * @since Version 3.9.1
		 * @const PROVIDER_NAME
		 */
		
		const PROVIDER_NAME = "RPOldGallery";
		
		/**
		 * Get the image from the provider
		 * @since Version 3.9.1
		 * @param int $id The ID of the image from the provider
		 * @return array
		 */
		
		public function getImage($id, $force = false) {
			$Image = new G1Image($id); 
			
			/**
			 * Transform into our standard data format
			 */
			
			$Author = $Image->getOwner(); 
			
			$this->photo = array(
				"provider" => self::PROVIDER_NAME,
				"id" => $id,
				"dates" => array(
					"taken" => $Image->DateTaken,
					"uploaded" => $Image->DateUploaded,
					"updated" => ""
				),
				"author" => array(
					"id" => $Author instanceof User ? $Author->id : "",
					"username" => $Author instanceof User ? $Author->username : "",
					"realname" => $Author instanceof User ? $Author->realname : "",
					"url" => $Author instanceof User ? $Author->url->url : "",
				),
				"title" => $Image->title,
				"description" => "",
				"tags" => "",
				"sizes" => $Image->sizes,
				"urls" => "",
				"location" => "",
			);
		}
		
		/**
		 * Save the changes to this image
		 * @since Version 3.9.1
		 * @return self
		 * @param \Railpage\Images\Image $Image
		 */
		
		public function setImage(Image $Image) {
			
		}
		
		/**
		 * Get a list of images
		 * @since Version 3.9.1
		 * @param int $page
		 * @param \Railpage\Images\Image $Image
		 * @return array
		 */
		
		public function getImages($page, $items_per_page) {
			
		}
		
		/**
		 * Return the name of this provider
		 * @since Version 3.9.1
		 * @return string
		 */
		
		public function getProviderName() {
			return self::PROVIDER_NAME;
		}
		
		/**
		 * Return the context of the supplied image
		 * @since Version 3.9.1
		 * @return array
		 * @param \Railpage\Images\Image $Image
		 */
		
		public function getImageContext(Image $Image) {
			
		}
		
		/**
		 * Delete this image
		 * @since Version 3.9.1
		 * @return boolean
		 * @param \Railpage\Images\Image $Image
		 */
		
		public function deleteImage(Image $Image) {
			
		}
		
		/**
		 * Get the EXIF data for this image
		 * @since Version 3.10.0
		 * @return array
		 * @param int $photo_id
		 */
		
		public function getExif($photo_id) {
			
		}
		
	}
	