<?php
	/**
	 * Find images
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images\Utility;
	
	use Railpage\Images\Images;
	use Railpage\Images\Image;
	use Railpage\Images\MapImage;
	use Railpage\Locos\LocoClass;
	use Railpage\Locos\Locomotive;
	use Railpage\Locos\Liveries\Livery;
	use Railpage\Debug;
	use Railpage\Url;
	use Railpage\Place;
	use Railpage\AppCore;
	use Exception;
	use InvalidArgumentException;
	
	class Finder {
		
		/**
		 * Find photos that fit within the given object(s)
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public static function find() {
			
			$args = func_get_args(); 
			
			$Loco = false;
			$LocoClass = false;
			$Livery = false;
			$Place = false;
			
			foreach ($args as $arg) {
				if ($arg instanceof LocoClass) {
					$LocoClass = clone $arg;
					continue;
				}
				
				if ($arg instanceof Livery) {
					$Livery = clone $arg;
					continue;
				}
				
				if ($arg instanceof Locomotive) {
					$Loco = clone $arg;
					continue;
				}
			}
			
			if ($Loco && $Livery) {
				return self::findLocoAndLivery($Loco, $Livery); 
			}
			
			if ($LocoClass && $Livery) {
				return self::findLocoClassAndLivery($LocoClass, $Livery); 
			}
			
			if ($Livery) {
				return self::findLivery($Livery); 
			}
			
			if ($Loco) {
				return self::findLoco($Loco); 
			}
			
			if ($LocoClass) {
				return self::findLocoClass($LocoClass);
			}
			
			return false;
			
		}
		
		/**
		 * Find photos of locos wearing a specific livery
		 * @since Version 3.9.1
		 * @param \Railpage\Locos\Locomotive $Loco
		 * @param \Railpage\Locos\Liveries\Livery $Livery
		 * @return array
		 */
		
		private static function findLocoAndLivery(Locomotive $Loco, Livery $Livery) {
			
			$query = "SELECT loco.image_id, image.*, g.country_code, g.country_name, g.region_code, g.region_name, g.neighbourhood, g.timezone, X(g.point) AS geoplace_lat, Y(g.point) AS geoplace_lon
				FROM image_link AS loco 
				LEFT JOIN image ON loco.image_id = image.id 
				LEFT JOIN geoplace AS g ON g.id = image.geoplace
				WHERE loco.namespace = ? AND loco.namespace_key = ? AND image_id IN (
					SELECT livery.image_id FROM image_link AS livery WHERE livery.namespace = ? AND livery.namespace_key = ?
				) AND loco.ignored = 0";
			
			$params = [ 
				$Loco->namespace,
				$Loco->id,
				$Livery->namespace,
				$Livery->id
			];
			
			return self::fetch($query, $params);
			
		}
		
		/**
		 * Find photos of loco classes wearing a specific livery
		 * @since Version 3.9.1
		 * @param \Railpage\Locos\LocoClass $LocoClass
		 * @param \Railpage\Locos\Liveries\Livery $Livery
		 * @return array
		 */
		
		private static function findLocoClassAndLivery(LocoClass $LocoClass, Livery $Livery) {
			
			$query = "SELECT lococlass.image_id, image.* , g.country_code, g.country_name, g.region_code, g.region_name, g.neighbourhood, g.timezone, X(g.point) AS geoplace_lat, Y(g.point) AS geoplace_lon
				FROM image_link AS lococlass 
				LEFT JOIN image ON lococlass.image_id = image.id 
				LEFT JOIN geoplace AS g ON g.id = image.geoplace
				WHERE lococlass.namespace = ? AND lococlass.namespace_key = ? AND image_id IN (
					SELECT livery.image_id FROM image_link AS livery WHERE livery.namespace = ? AND livery.namespace_key = ?
				) AND lococlass.ignored = 0";
			
			$params = [ 
				$LocoClass->namespace,
				$LocoClass->id,
				$Livery->namespace,
				$Livery->id
			];
			
			return self::fetch($query, $params); 
			
		}
		
		/**
		 * Find photos of a specific livery
		 * @since Version 3.9.1
		 * @param \Railpage\Locos\Liveries\Livery $Livery
		 * @return array
		 */
		
		private static function findLivery(Livery $Livery) {
			
			$query = "SELECT il.image_id, i.*, g.country_code, g.country_name, g.region_code, g.region_name, g.neighbourhood, g.timezone, X(g.point) AS geoplace_lat, Y(g.point) AS geoplace_lon
				FROM image_link AS il 
				LEFT JOIN image AS i ON i.id = il.image_id 
				LEFT JOIN geoplace AS g ON g.id = i.geoplace
				WHERE il.namespace = ? AND il.namespace_key = ? AND il.ignored = 0";
			
			$params = [ 
				$Livery->namespace,
				$Livery->id
			];
			
			return self::fetch($query, $params); 
			
		}
		
		/**
		 * Find photos of a specific locomotive
		 * @since Version 3.9.1
		 * @param \Railpage\Locos\Locomotive $Loco
		 * @return array
		 */
		
		private static function findLoco(Locomotive $Loco) {
			
			$query = "SELECT il.image_id, i.*, g.country_code, g.country_name, g.region_code, g.region_name, g.neighbourhood, g.timezone, X(g.point) AS geoplace_lat, Y(g.point) AS geoplace_lon
				FROM image_link AS il 
				LEFT JOIN image AS i ON i.id = il.image_id 
				LEFT JOIN geoplace AS g ON g.id = i.geoplace
				WHERE il.namespace = ? AND il.namespace_key = ? AND il.ignored = 0";
			
			$params = [ 
				$Loco->namespace,
				$Loco->id
			];
			
			return self::fetch($query, $params); 
			
		}
		
		/**
		 * Find photos of a specific locomotive class
		 * @since Version 3.9.1
		 * @param \Railpage\Locos\LocoClass $LocoClass
		 * @return array
		 */
		
		private static function findLocoClass(LocoClass $LocoClass) {
			
			$query = "SELECT il.image_id, i.*, g.country_code, g.country_name, g.region_code, g.region_name, g.neighbourhood, g.timezone, X(g.point) AS geoplace_lat, Y(g.point) AS geoplace_lon
				FROM image_link AS il 
				LEFT JOIN image AS i ON i.id = il.image_id 
				LEFT JOIN geoplace AS g ON g.id = i.geoplace
				WHERE il.namespace = ? AND il.namespace_key = ? AND il.ignored = 0";
			
			$params = [ 
				$LocoClass->namespace,
				$LocoClass->id
			];
			
			return self::fetch($query, $params); 
			
		}
		
		/**
		 * Fetch the requested SQL query
		 * @since Version 3.9.1
		 * @param string $query The SQL query to be executed
		 * @param array $params Parameters for the SQL query
		 * @return array
		 */
		
		private static function fetch($query, $params) {
			
			$Database = (new AppCore)->getDatabaseConnection();
			
			$result = $Database->fetchAll($query, $params);
			
			foreach ($result as $key => $data) {
				$result[$key]['meta'] = json_decode($data['meta'], true); 
				$result[$key]['meta']['sizes'] = Images::NormaliseSizes($result[$key]['meta']['sizes']); 
				
				if (!empty($data['country_code'])) {
					#$MapImage = new MapImage($data['geoplace_lat'], $data['geoplace_lon']);
					$urlstring = "http://maps.googleapis.com/maps/api/staticmap?center=%s,%s&zoom=%d&size=%dx%d&maptype=roadmap&markers=color:red%%7C%s,%s";
					
					$result[$key]['geoplace_image'] = sprintf($urlstring, $data['geoplace_lat'], $data['geoplace_lon'], 12, 800, 600, $data['geoplace_lat'], $data['geoplace_lon']);
					$result[$key]['geoplace_photo'] = $result[$key]['meta']['sizes']['small']['source'];
				}
			}
			
			return $result;
			
		}
		
		/**
		 * Find top photos this week
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public static function topPhotosThisWeek() {
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$query = "SELECT * FROM image ORDER BY hits_weekly DESC LIMIT 0, 6";
			$result = $Database->fetchAll($query);
			
			foreach ($result as $key => $data) {
				$result[$key]['meta'] = json_decode($data['meta'], true); 
				$result[$key]['meta']['sizes'] = Images::NormaliseSizes($result[$key]['meta']['sizes']); 
			}
			
			return $result;
		}
		
	}