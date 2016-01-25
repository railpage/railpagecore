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
	use Railpage\Registry;
	
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
			
			$Redis = AppCore::GetRedis(); 
			$Registry = Registry::GetInstance(); 
				
			$cachekey = sprintf("rp:v2;cache.image=%s;o=%s", $id, crc32(json_encode($options))); 
			
			if ($id && !$provider) {
				
				return new Image($id, $options); 
				
				try {
					$Image = $Registry->get($cachekey); 
				} catch (Exception $e) {
					
					if (!$Image = $Redis->fetch($cachekey)) {
						
						$Image = new Image($id, $options); 
						
						$Redis->save($cachekey, $Image, strtotime("+10 minutes")); 
						
					}
					
					$Registry->set($cachekey, $Image); 
					
				}
				
				return $Image;
			}
			
			$cachekey .= sprintf(";p=%s", $provider); 
			#$Registry = Registry::getInstance();
			
			#echo $cachekey;die;
			
			return (new Images)->findImage($provider, $id, $options);
			
			try {
				$Image = $Registry->get($cachekey); 
			} catch (Exception $e) {
				
				if ($Image = $Redis->fetch($cachekey) && $Image instanceof Image) {
					$Registry->set($cachekey, $Image); 
					return $Image;
				}
				
				try {
					$Images = new Images;
					$Image = $Images->findImage($provider, $id, $options);
					
					$Redis->save($cachekey, $Image, strtotime("+10 minutes")); 
					
					$Registry->set($cachekey, $Image);
				} catch (Exception $e) {
					$Image = false;
				}
			}
			
			return $Image;
			
		}
		
		/**
		 * Return a new instance of Image from a data array
		 * @since Version 3.9.1
		 * @param array $data
		 * @return \Railpage\Images\Image
		 */
		 
		public static function CreateImageFromArray($data) {
			
			$Image = new Image;
			$Image->populateFromArray($data); 
			
			return $Image;
			
		}
        
        /**
         * Create a camera object from an ID or URL slug
         * @since Version 3.10.0
         * @param string|int $id
         * @return \Railpage\Images\Camera
         */
        
        public static function CreateCamera($id) {
            
            $Database = AppCore::GetDatabase(); 
            $Memcached = AppCore::GetMemcached(); 
			$Redis = AppCore::getRedis();
			$Registry = Registry::getInstance(); 
            
            if (!filter_var($id, FILTER_VALIDATE_INT)) {
                
                $cachekey = sprintf("railpage:images.camera.id=%s", $id); 
                
                if (!$lookup = $Memcached->fetch($cachekey)) {
                    $lookup = $Database->fetchOne("SELECT id FROM image_camera WHERE url_slug = ?", $id); 
                    
                    if ($lookup) {
                        $Memcached->save($cachekey, $lookup); 
                    }
                }
                
                if (!filter_var($lookup, FILTER_VALIDATE_INT)) {
                    throw new Exception("Could not find a camera ID from URL slug " . $id); 
                }
                
                $id = $lookup;
                
            }
            
            $regkey = sprintf(Camera::CACHE_KEY, $id);
            
            try {
				$Camera = $Registry->get($regkey); 
			} catch (Exception $e) {
				if (!$Camera = $Redis->fetch($regkey)) {
					$Camera = new Camera($id); 
					$Redis->save($regkey, $Camera, strtotime("+1 day"));
				}
				
				$Registry->set($regkey, $Camera); 
			}
            
            return $Camera;
            
        }
        
        /**
         * Create a photo competition object from an ID or URL slug
         * @since Version 3.10.0
         * @param string|int $id
         * @return \Railpage\Images\Competition
         */
        
        public static function CreatePhotoComp($id) {
            
            $Database = AppCore::GetDatabase(); 
            $Memcached = AppCore::GetMemcached(); 
			$Redis = AppCore::getRedis();
			$Registry = Registry::getInstance(); 
            
            if (!filter_var($id, FILTER_VALIDATE_INT)) {
                
                $lookup = Utility\CompetitionUtility::getIDFromSlug($id); 
                
                if (!filter_var($lookup, FILTER_VALIDATE_INT)) {
                    throw new Exception("Could not find a competition ID from URL slug " . $id); 
                }
                
                $id = $lookup;
                
            }
            
            $regkey = sprintf(Competition::CACHE_KEY, $id);
            
            try {
				$Competition = $Registry->get($regkey); 
			} catch (Exception $e) {
				#if (!$Competition = $Redis->fetch($regkey)) {
					$Competition = new Competition($id); 
					$Redis->save($regkey, $Competition, strtotime("+1 day"));
				#}
				
				$Registry->set($regkey, $Competition); 
			}
            
            return $Competition;
            
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