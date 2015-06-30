<?php
	/**
	 * Image factory
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images;
	
	use Exception;
	use DateTime;
	use Railpage\AppCore;
	use Railpage\Debug;
	use Railpage\Url;
	
	class ImageFactory {
		
		/**
		 * Return a new instance of Image
		 * @since Version 3.9.1
		 * @param int|string $id
		 * @param string $provider
		 * @param int $options
		 * @return \Railpage\Images\Image
		 */
		
		public static function CreateImage($id = false, $provider = false, $options = false) {
			
			if ($id && !$provider) {
				return new Image($id, $options); 
			}
			
			return (new Images)->findImage($provider, $id, $options);
			
		}
		
		/**
		 * Return a new instance of Image from a data array
		 * @since Version 3.9.1
		 * @param array $data
		 * @return \Rialpage\Images\Image
		 */
		 
		public static function CreateImageFromArray($data) {
			
			$Image = new Image;
			$Image->populateFromArray($data); 
			
			return $Image;
			
		}
		
		/**
		 * Get thumbnails of images from an array of image IDs
		 * @since Version 3.9.1
		 * @param array $ids
		 * @return array
		 */
		
		public static function GetThumbnails($ids) {
			
			$Memcached = AppCore::getMemcached(); 
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			if (is_array($ids)) {
				$ids = implode(",", $ids);
			}
			
			$cachekey = md5($ids) . ".v2";
			
			if (!$return = $Memcached->fetch($cachekey)) {
			
				$query = "SELECT id, meta, title FROM image WHERE id IN (" . $ids . ")";
				$return = array(); 
				
				#echo $query;
				
				foreach ($Database->fetchAll($query) as $row) {
					
					$meta = json_decode($row['meta'], true); 
					$meta['sizes'] = Images::normaliseSizes($meta['sizes']); 
					$return[] = array(
						"id" => $row['id'],
						"url" => sprintf("/photos/%d", $row['id']),
						"thumbnail" => $meta['sizes']['thumb']['source'],
						"html" => sprintf("<a href='%s' class='thumbnail' style='background-image:url(%s);'></a>", sprintf("/photos/%d", $row['id']), $meta['sizes']['thumb']['source'])
					);
					
				}
				
				$Memcached->save($cachekey, $return); 
				
			}
			
			return $return;
			
		}
		
	}