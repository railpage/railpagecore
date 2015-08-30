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
	use DateTime;
	
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
		 * Sort by
		 * @since Version 3.9.1
		 * @var string $sortby
		 */
		
		public static $sortby = "image.id";
		
		/**
		 * Sort direction
		 * @since Version 3.9.1
		 * @var string $sortdir
		 */
		
		public static $sortdir = "DESC";
		
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
				LEFT JOIN geoplace AS g ON g.id = image.geoplace
				LEFT JOIN image_flags AS f ON f.image_id = image.id";
			
			$where = array(
				"image.hidden = 0",
				"COALESCE(f.rejected, 0) = 0"
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
			 * Sort it
			 */
			
			$query .= sprintf(" ORDER BY %s %s", self::$sortby, self::$sortdir);
			
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
				LEFT JOIN image_flags AS f ON f.image_id = image.id
				WHERE image.hidden = ?
				AND COALESCE(f.rejected, 0) = 0
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
			
			$query = "SELECT FLOOR(YEAR(image.captured) / 10) * 10 AS decade, COUNT(*) AS num 
				FROM image 
				LEFT JOIN image_flags AS f ON f.image_id = image.id
				WHERE image.captured <= NOW() 
				AND COALESCE(f.rejected, 0) = 0
				GROUP BY FLOOR(YEAR(image.captured) / 10) * 10";
			
			return $Database->fetchAll($query); 
			
		}
		
		/**
		 * Get the screener's choice photos
		 * @since Version 3.10.0
		 * @return array
		 */
		
		public static function getScreenersChoice() {
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			$query = "SELECT SQL_CALC_FOUND_ROWS image.*, g.country_code, g.country_name, g.region_code, g.region_name, g.neighbourhood, g.timezone, X(g.point) AS geoplace_lat, Y(g.point) AS geoplace_lon
				FROM image
				LEFT JOIN geoplace AS g ON g.id = image.geoplace
				LEFT JOIN image_flags AS f ON f.image_id = image.id
				WHERE image.hidden = ?
				AND COALESCE(f.rejected, 0) = 0
				AND f.screened_pick = 1
				ORDER BY f.screened_on DESC
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
		 * Get photo context
		 * @since Version 3.10.0
		 * @param \Railpage\Images\Image $Image
		 * @param boolean $unapprovedonly
		 * @return array
		 */
		
		public static function getPhotoContext(Image $Image, $unapprovedonly = false) {
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			if (!$unapprovedonly) {
				
				if ($Image->DateCaptured instanceof DateTime) {
				
					$query = "(SELECT image.id, image.captured, image.title, image.description, image.meta FROM image LEFT JOIN image_flags AS f ON image.id = f.image_id WHERE COALESCE(f.rejected, 0) = 0 AND image.captured <= ? AND image.id != ? ORDER BY image.captured DESC LIMIT 0, 3)
								UNION (SELECT id, image.captured, title, description, meta FROM image WHERE id = ?)
								UNION (SELECT image.id, image.captured, image.title, image.description, image.meta FROM image LEFT JOIN image_flags AS f ON image.id = f.image_id WHERE COALESCE(f.rejected, 0) = 0 AND image.captured >= ? AND image.id != ? ORDER BY captured ASC LIMIT 0, 3)";
					
					$params = [ 
						$Image->DateCaptured->format("Y-m-d H:i:s"), 
						$Image->id,
						$Image->id, 
						$Image->DateCaptured->format("Y-m-d H:i:s"),
						$Image->id
					];
					
				} else {
				
					$query = "(SELECT image.id, image.captured, image.title, image.description, image.meta FROM image LEFT JOIN image_flags AS f ON image.id = f.image_id WHERE COALESCE(f.rejected, 0) = 0 AND image.id <= ? AND image.id != ? ORDER BY image.captured DESC LIMIT 0, 3)
								UNION (SELECT id, image.captured, title, description, meta FROM image WHERE id = ?)
								UNION (SELECT image.id, image.captured, image.title, image.description, image.meta FROM image LEFT JOIN image_flags AS f ON image.id = f.image_id WHERE COALESCE(f.rejected, 0) = 0 AND image.id >= ? AND image.id != ? ORDER BY captured ASC LIMIT 0, 3)";
					
					$params = [ 
						$Image->id, 
						$Image->id,
						$Image->id, 
						$Image->id,
						$Image->id
					];
					
				}
				
			} elseif ($Image->DateCaptured instanceof DateTime) {
				
				$query = "(SELECT image.id, image.captured, image.title, image.description, image.meta FROM image LEFT JOIN image_flags AS f ON image.id = f.image_id WHERE f.rejected IS NULL AND image.captured <= ? AND image.id != ? ORDER BY image.id DESC LIMIT 0, 6)
							UNION (SELECT id, image.captured, title, description, meta FROM image WHERE id = ?)";
				
				$params = [ 
					$Image->DateCaptured->format("Y-m-d H:i:s"), 
					$Image->id,
					$Image->id
				];
				
			} elseif (!$Image->DateCaptured instanceof DateTime) {
				
				$query = "(SELECT image.id, image.captured, image.title, image.description, image.meta FROM image LEFT JOIN image_flags AS f ON image.id = f.image_id WHERE f.rejected IS NULL AND image.id <= ? AND image.id != ? ORDER BY image.id DESC LIMIT 0, 6)
							UNION (SELECT id, image.captured, title, description, meta FROM image WHERE id = ?)";
				
				$params = [ 
					$Image->id, 
					$Image->id,
					$Image->id
				];
				
			}
			
			if (!isset($query)) {
				return; 
			}
			
			$rs = $Database->fetchAll($query, $params);
			
			/**
			 * This horrible, ugly sorting is because MySQL wasn't ordering the first SELECT correctly (ie, not at all)
			 */
			
			$before = [];
			$current = [];
			$after = [];
			
			foreach ($rs as $row) {
				$row['meta'] = json_decode($row['meta'], true); 
				$row['url'] = Url::CreateFromImageID($row['id'])->getURLs();
				$row['sizes'] = Images::normaliseSizes($row['meta']['sizes']);
				
				$Date = new DateTime($row['captured']);
				$row['unixtime'] = $Date->getTimestamp(); 
				
				if ($Date < $Image->DateCaptured) {
					$before[] = $row;
					continue;
				}
				
				if ($Date > $Image->DateCaptured) {
					$after[] = $row;
					continue;
				}
				
				if ($row['id'] == $Image->id) {
					$current[] = $row;
					continue;
				}
			}
			
			usort($before, function($a, $b) {
				return $a['unixtime'] - $b['unixtime'];
			});
			
			usort($after, function($a, $b) {
				return $a['unixtime'] + $b['unixtime'];
			});
			
			return array_merge($before, $current, $after);
			
		}
		
		/**
		 * Get a random image as an array
		 * @since Version 3.10.0
		 * @param string $namespace An optional linked namespace to filter by
		 * @param int $namespace_key An optional linked namespace key to filter by
		 * @return array
		 */
		
		public static function randomImage($namespace, $namespace_key) {
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			if (is_null($namespace) && !is_null($namespace_key)) {
				throw new InvalidArgumentException("A namespace key was specified but an associated namespace value was not.");
			}
			
			if (is_null($namespace) && is_null($namespace_key)) {
				
				$query = "SELECT * FROM image AS r1 JOIN (SELECT CEIL(RAND() * (SELECT MAX(id) FROM image)) AS randomid) AS r2 WHERE r1.id >= r2.randomid ORDER BY r1.id ASC LIMIT 1";
				
				$row = $Database->fetchRow($query); 
				$row['meta'] = json_decode($row['meta'], true);
				$row['sizes'] = Images::normaliseSizes($row['meta']['sizes']); 
				
				$row['url'] = Url::CreateFromImageID($row['id']); 
				$row['url'] = $row['url']->getURLs();
				
				return $row;
			}
			
			if (!is_null($namespace)) {
				
				$query = "SELECT il.image_id FROM image_link AS il LEFT JOIN image AS i ON i.id = il.image_id WHERE il.namespace = ? AND i.provider IS NOT NULL";
				$params = [ $namespace ];
				
				if (!is_null($namespace_key)) {
					$query .= " AND namespace_key = ?";
					$params[] = $namespace_key;
				}
				
				$ids = [];
				
				foreach ($Database->fetchAll($query, $params) as $row) {
					$ids[] = $row['image_id'];
				}
				
				$image_id = $ids[array_rand($ids)]; 
				
				$query = "SELECT * FROM image WHERE id = ?"; 
				$row = $Database->fetchRow($query, $image_id); 
				$row['meta'] = json_decode($row['meta'], true);
				$row['sizes'] = Images::normaliseSizes($row['meta']['sizes']); 
				
				$row['url'] = Url::CreateFromImageID($row['id']); 
				$row['url'] = $row['url']->getURLs();
				
				return $row;
				
			}
			
			return;
			
		}
		
		/**
		 * Find a suitable cover photo
		 * @since Version 3.10.0
		 * @param string|object $search_query
		 * @return string
		 */
		
		public static function GuessCoverPhoto($search_query) {
			
			$cachekey = sprintf("railpage:coverphoto=%s", md5($search_query)); 
			
			$Memcached = AppCore::getMemcached(); 
			
			#if ($image = $Memcached->fetch($cachekey)) {
			#	return $image;
			#}
			
			$SphinxQL = AppCore::getSphinx(); 
			
			if (is_string($search_query)) {
				$n_words = preg_match_all('/([a-zA-Z]|\xC3[\x80-\x96\x98-\xB6\xB8-\xBF]|\xC5[\x92\x93\xA0\xA1\xB8\xBD\xBE]){4,}/', $search_query, $match_arr);
				$word_arr = $match_arr[0];
				$words = implode(" || ", $word_arr);
				
				$SphinxQL->select()->from("idx_images")->match(array("title", "description"), $words, true); 
				$rs = $SphinxQL->execute();
				
				if (count($rs)) {
					$photo = $rs[0]; 
					$photo['meta'] = json_decode($photo['meta'], true); 
					$photo['sizes'] = Images::NormaliseSizes($photo['meta']['sizes']); 
					
					foreach ($photo['sizes'] as $size) {
						if ($size['width'] > 400 && $size['height'] > 300) {
							
							$Memcached->save($cachekey, $size['source'], 0); 
							
							return $size['source'];
						}
					}
				}
			}
			
			return "https://static.railpage.com.au/i/logo-fb.jpg";
			
		}
		
		
	}