<?php
	/**
	 * Locomotive / loco class cover photo utility
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Locos\Utility;
	
	use Railpage\Locos\LocoClass;
	use Railpage\Locos\Locomotive; 
	use Railpage\Asset;
	use Railpage\AppCore;
	use Exception;
	use Railpage\Debug;
	use Railpage\Images\Images;
	use Railpage\Images\Image;
	
	
	class CoverImageUtility {
		
		/**
		 * Check if the given object has a valid cover photo
		 * @since Version 3.9.1
		 * @param object $Object
		 * @return boolean
		 */
		
		public static function hasCoverImage($Object) {
			
			/**
			 * Image stored in meta data
			 */
			
			if (isset($Object->meta['coverimage']) && isset($Object->meta['coverimage']['id']) && !empty($Object->meta['coverimage']['id'])) {
				return true;
			}
			
			/**
			 * Asset
			 */
			
			if ($Object->Asset instanceof Asset) {
				return true;
			}
			
			/**
			 * Ordinary Flickr image
			 */
			
			if (isset($Object->photo_id) && filter_var($Object->photo_id, FILTER_VALIDATE_INT) && $Object->photo_id > 0) {
				return true;
			}
			
			if (isset($Object->flickr_image_id) && filter_var($Object->flickr_image_id, FILTER_VALIDATE_INT) && $Object->flickr_image_id > 0) {
				return true;
			}
			
			/**
			 * No cover image!
			 */
			
			return false;
			
		}
		
		/**
		 * Get the cover image of the supplied object
		 * @since Version 3.9.1
		 * @param object $Object
		 * @return array
		 */
		
		public static function getCoverImageOfObject($Object) {
			
			if (!self::hasCoverImage($Object)) {
				return false;
			}
			
			$cachekey = sprintf("railpage:%s=%d;coverimage", $Object->namespace, $Object->id); 
			$Memcached = AppCore::getMemcached(); 
			
			#printArray($cachekey);die;
			
			if ($result = $Memcached->fetch($cachekey)) {
				return $result;
			}
			
			$photoidvar = isset($Object->flickr_image_id) ? "flickr_image_id" : "photo_id";
			
			if (isset($Object->meta['coverimage'])) {
				$Image = new Image($Object->meta['coverimage']['id']);
			} elseif ($Object->Asset instanceof Asset) {
				$Image = $Object->Asset;
			} elseif (isset($Object->$photoidvar) && filter_var($Object->$photoidvar, FILTER_VALIDATE_INT) && $Object->$photoidvar > 0) {
				$Image = (new Images)->findImage("flickr", $Object->$photoidvar);
			}
			
			$return = array(
				"type" => "image",
				"provider" => $Image instanceof Image ? $Image->provider : "",
				"title" => $Image instanceof Image ? $Image->title : $Asset->meta['title'],
				"author" => array(
					"id" => "",
					"username" => "",
					"realname" => "",
					"url" => ""
				)
			);
			
			if ($Image instanceof Image) {
				$return = array_merge($return, array(
					"author" => array(
						"id" => $Image->author->id,
						"username" => $Image->author->username,
						"realname" => isset($Image->author->realname) ? $Image->author->realname : $Image->author->username,
						"url" => $Image->author->url
					),
					"image" => array(
						"id" => $Image->id,
					),
					"sizes" => $Image->sizes,
					"url" => $Image->url->getURLs()
				));
			}
			
			if ($Object->Asset instanceof Asset) {
				$return = array_merge($return, array(
					"sizes" => array(
						"large" => array(
							"source" => $Asset->meta['image'],
						),
						"original" => array(
							"source" => $Asset->meta['original'],
						)
					),
					"url" => array(
						"url" => $Asset['meta']['image'],
					)
				));
			}
			
			$Memcached->save($cachekey, $return, strtotime("+1 hour")); 
			
			return $return;
			
		}

		
	}