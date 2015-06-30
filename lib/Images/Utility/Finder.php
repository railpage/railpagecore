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
	use Railpage\Images\Collection;
	use Railpage\Images\MapImage;
	use Railpage\Locos\LocoClass;
	use Railpage\Locos\Locomotive;
	use Railpage\Locos\Liveries\Livery;
	use Railpage\Debug;
	use Railpage\Place;
	use Railpage\PlaceUtility;
	use Railpage\AppCore;
	use Railpage\Users\User;
	use Railpage\Users\Factory as UsersFactory;
	use Exception;
	use InvalidArgumentException;
	
	class Finder {
		
		/**
		 * Filters
		 * @since Version 3.9.1
		 * @var array $filters
		 */
		
		private static $filters;
		
		/**
		 * Filter params
		 * @since Version 3.9.1
		 * @var array $params
		 */
		
		private static $params;
		
		/**
		 * Total number of rows found
		 * @since Version 3.9.1
		 * @var int $numresults
		 */
		
		public static $numresults;
		
		/**
		 * Items per page to show
		 * @since Version 3.9.1
		 * @var int $perpage
		 */
		
		public static $perpage = 100;
		
		/**
		 * Search results aage number 
		 * @since Version 3.9.1
		 * @var int $pagenum
		 */
		
		public static $pagenum = 1;
		
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
			$ThisUser = false;
			
			foreach ($args as $arg) {
				if ($arg instanceof User) {
					$ThisUser = clone $arg;
					
					$filter = [ "user_id" => $ThisUser->id ];
					
					self::addFilter("image", $filter);
					
					continue;
				}
				
				if (is_array($arg) && isset($arg['country_code'])) {
					
					$filter = [ "country_code" => $arg['country_code'] ];
					
					self::addFilter("g", $filter);
					
				}
				
				if (is_array($arg) && isset($arg['region_code'])) {
					
					$filter = [ "region_code" => $arg['region_code'] ];
					
					self::addFilter("g", $filter);
					
				}
				
				if (is_array($arg) && isset($arg['geoplace'])) {
					
					$filter = [ "geoplace" => $arg['geoplace'] ];
					
					self::addFilter("image", $filter);
					
				}
				
				if (is_array($arg) && isset($arg['latlng'])) {
					
					$latlng = explode(",", $arg['latlng']);
					
					$filter = [ "lat" => $latlng[0] ];
					self::addFilter("image", $filter);
					$filter = [ "lon" => $latlng[1] ];
					self::addFilter("image", $filter);
					
				}
				
				if (is_array($arg) && isset($arg['decade'])) {
					
					$decade = round($arg['decade'], -1); 
					
					$filter = [ "captured" => sprintf("%d-01-01 00:00:00", $decade) ];
					self::addFilter("image", $filter, ">=");
					
					$filter = [ "captured" => sprintf("%d-01-01 00:00:00", $decade + 10) ];
					self::addFilter("image", $filter, "<");
					
				}
				
				if ($arg instanceof LocoClass) {
					$LocoClass = clone $arg;
					
					$filter = [ 
						"namespace" => $LocoClass->namespace,
						"namespace_key" => $LocoClass->id
					];
					
					self::addFilter("image_link", $filter);
					
					continue;
				}
				
				if ($arg instanceof Livery) {
					$Livery = clone $arg;
					
					$filter = [ 
						"namespace" => $Livery->namespace,
						"namespace_key" => $Livery->id
					];
					
					self::addFilter("image_link", $filter);
					
					continue;
				}
				
				if ($arg instanceof Locomotive) {
					$Loco = clone $arg;
					
					$filter = [ 
						"namespace" => $Loco->namespace,
						"namespace_key" => $Loco->id
					];
					
					self::addFilter("image_link", $filter);
					
					continue;
				}
			}
			
			if (count(self::$filters)) {
				return self::fetch(); 
			}
			
			return false;
			
		}
		
		/**
		 * Add something to our filter array
		 * @since Version 3.9.1
		 * @return void
		 * @param string $table
		 * @param array $filter
		 * @param string $operator
		 */
		 
		private static function addFilter($table, $filter, $operator = "=") {
			
			$c = isset(self::$filters[$table]) ? count(self::$filters[$table]) + 1 : 1; 
			
			foreach ($filter as $key => $val) {
				$clause = sprintf("%s %s ?", $key, $operator); 
				
				self::$filters[$table][$c][$clause] = $val;
				self::$params[] = $val;
			}
			
			return;
			
		}
		
		/**
		 * Take the specified filters and create an SQL query
		 * @since Version 3.9.1
		 * @return string
		 */
		
		private static function makeQuery() {
			
			$basequery = "SELECT SQL_CALC_FOUND_ROWS image.*, g.country_code, g.country_name, g.region_code, g.region_name, g.neighbourhood, g.timezone, X(g.point) AS geoplace_lat, Y(g.point) AS geoplace_lon
				FROM image
				LEFT JOIN geoplace AS g ON g.id = image.geoplace";
			
			$where = array(
				"image.hidden = 0"
			); 
			$params = array();
			
			foreach (self::$filters as $table => $data) {
				if ($table == "image") {
					$alias = "image";
				}
				
				if ($table == "geoplace" || $table == "g") {
					$alias = "g";
				}
				
				if ($table == "image_link") {
					$alias = "il1";
					$basequery .= " LEFT JOIN " . $table . " AS " . $alias . " ON " . $alias . ".image_id = image.id";
				}
				
				if ($table == "image_link") {
					foreach ($data[1] as $key => $val) {
						$where[] = $alias . "." . $key;
						$params[] = $val;
					}
					
					if (count($data) > 1) {
						$alias++;
						$subquery = " image.id IN ( SELECT " . $alias . ".image_id FROM " . $table . " AS " . $alias . " WHERE ";
						$subwhere = array(); 
						
						foreach ($data[2] as $key => $val) {
							$subwhere[] = $alias . "." . $key; 
							$params[] = $val;
						}
						
						$subquery .= implode(" AND ", $subwhere) . " ) ";
						
						$where[] = $subquery;
					}
				} else {
					
					foreach ($data as $row) {
						foreach ($row as $key => $val) {
							$where[] = $key;
							$params[] = $val;
						}
					}
					
				}
			}
			
			$basequery .= " WHERE " . implode(" AND ", $where); 
			
			return array("query" => $basequery, "params" => $params);
			
		}
		
		/**
		 * Fetch the requested SQL query
		 * @since Version 3.9.1
		 * @param string $query The SQL query to be executed
		 * @param array $params Parameters for the SQL query
		 * @return array
		 */
		
		private static function fetch($query = false, $params = false) {
			
			$Database = (new AppCore)->getDatabaseConnection();
			
			$Config = AppCore::GetConfig(); 
			
			/**
			 * Filter our query
			 */
			
			$prep = self::makeQuery(); 
			$query = $prep['query'];
			$params = $prep['params'];
			
			/**
			 * Apply limits
			 */
			
			$query .= " LIMIT ?, ?";
			$params = array_merge($params, array(
				(self::$pagenum - 1) * self::$perpage,
				self::$perpage
			));
			
			#printArray($query); printArray($params);die;
			
			/**
			 * Exexcute it
			 */
			
			$result = $Database->fetchAll($query, $params);
			
			/**
			 * Get the total number of results found excluding limits
			 */
			
			self::$numresults = $Database->fetchOne("SELECT FOUND_ROWS() AS total"); 
			
			foreach ($result as $key => $data) {
				$result[$key] = self::ProcessPhoto($data); 
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
		
		/**
		 * Process the photo array
		 * @since Version 3.9.1
		 * @param array $data
		 * @return array
		 */
		
		private static function ProcessPhoto($data) {
			
			$Config = AppCore::GetConfig(); 
			
			$data['meta'] = json_decode($data['meta'], true); 
			$data['meta']['sizes'] = Images::NormaliseSizes($data['meta']['sizes']); 
			
			if (!empty($data['country_code'])) {
				$urlstring = "http://maps.googleapis.com/maps/api/staticmap?key=%s&center=%s,%s&zoom=%d&size=%dx%d&maptype=roadmap&markers=color:red%%7C%s,%s";
				
				$data['geoplace_image'] = sprintf($urlstring, $Config->Google->API_Key, $data['geoplace_lat'], $data['geoplace_lon'], 12, 800, 600, $data['geoplace_lat'], $data['geoplace_lon']);
				$data['geoplace_photo'] = isset($data['meta']['sizes']['small']) ? $data['meta']['sizes']['small']['source'] : NULL;
			}
			
			return $data;
			
		}
		
		/**
		 * Return all photos in the pool (paginated)
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public static function getPhotoPool() {
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$query = "SELECT SQL_CALC_FOUND_ROWS image.*, g.country_code, g.country_name, g.region_code, g.region_name, g.neighbourhood, g.timezone, X(g.point) AS geoplace_lat, Y(g.point) AS geoplace_lon
				FROM image
				LEFT JOIN geoplace AS g ON g.id = image.geoplace
				WHERE image.hidden = ?
				ORDER BY image.id DESC
				LIMIT ?, ?";
			
			$params = [ 
				0,
				(self::$pagenum - 1) * self::$perpage,
				self::$perpage
			];
			
			$result = $Database->fetchAll($query, $params);
			
			self::$numresults = $Database->fetchOne("SELECT FOUND_ROWS() AS total"); 
			
			foreach ($result as $key => $data) {
				$result[$key] = self::ProcessPhoto($data); 
			}
			
			return $result;
			
		}
		
		/**
		 * Get photos in a collection
		 * @since Version 3.9.1
		 * @return array
		 * @param \Railpage\Images\Collection
		 */
		
		public static function getPhotosInCollection($Collection) {
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$query = "SELECT SQL_CALC_FOUND_ROWS image.*, g.country_code, g.country_name, g.region_code, g.region_name, g.neighbourhood, g.timezone, X(g.point) AS geoplace_lat, Y(g.point) AS geoplace_lon
				FROM image
				LEFT JOIN geoplace AS g ON g.id = image.geoplace
				LEFT JOIN image_link AS il ON image.id = il.image_id
				WHERE image.hidden = ?
					AND il.namespace = ? 
					AND il.namespace_key = ?
				ORDER BY image.id DESC
				LIMIT ?, ?";
			
			$params = [ 
				0,
				$Collection instanceof Collection ? $Collection->namespace : $Collection['namespace'],
				$Collection instanceof Collection ? $Collection->id : $Collection['id'],
				(self::$pagenum - 1) * self::$perpage,
				self::$perpage
			];
			
			$result = $Database->fetchAll($query, $params);
			
			self::$numresults = $Database->fetchOne("SELECT FOUND_ROWS() AS total"); 
			
			foreach ($result as $key => $data) {
				$result[$key] = self::ProcessPhoto($data); 
			}
			
			return $result;
			
		}
		
		/**
		 * Get number of photos by decade
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public static function getNumberPhotosPerDecade() {
			
			$Database = (new AppCore)->getDatabaseConnection();
			
			$query = "SELECT FLOOR(YEAR(captured) / 10) * 10 AS decade, COUNT(*) AS num FROM image WHERE captured <= NOW() GROUP BY FLOOR(YEAR(captured) / 10) * 10";
			
			return $Database->fetchAll($query); 
			
		}
		
	}