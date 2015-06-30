<?php
	/**
	 * Find a geographic place closest to the provided co-ordinates
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage;
	
	use Railpage\AppCore;
	use Railpage\Debug;
	use Railpage\Place;
	use Exception;
	use InvalidArgumentException;
	use DateTime;
	use DateTimeZone;
	use Zend_Db_Expr;
	use GuzzleHttp\Client;
	
	class PlaceUtility {
		
		/**
		 * Get WoE data for a latitude/longitude lookup
		 * @since Version 3.9.1
		 * @param float $lat
		 * @param float $lon
		 * @return array
		 */
		
		public static function LatLonWoELookup($lat, $lon) {
			
			$Config = AppCore::getConfig(); 
			$Redis = AppCore::getRedis();
			
			$placetypes = array(7,8,9,10,22,24); 
			
			$mckey = sprintf("railpage:woe=%s,%s;types=", $lat, $lon, implode(",", $placetypes)); 
			
			if (!$return = $Redis->fetch($mckey)) {
				$url = sprintf("http://where.yahooapis.com/v1/places\$and(.q('%s,%s'),.type(%s))?lang=en&appid=%s&format=json", $lat, $lon, implode(",", $placetypes), $Config->Yahoo->ApplicationID);
				
				$GuzzleClient = new Client;
				$response = $GuzzleClient->get($url);
				
				if ($response->getStatusCode() == 200) {
					$result = json_decode($response->getBody(), true);
				}
				
				switch ($response->getStatusCode()) {
					case 200 :
						$return = json_decode($response->getBody(), true);
						break;
					
					case 503 : 
						throw new Exception("Your call to Yahoo Web Services failed and returned an HTTP status of 503. That means: Service unavailable. An internal problem prevented us from returning data to you.");
						break;
					
					case 403 : 
						throw new Exception("Your call to Yahoo Web Services failed and returned an HTTP status of 403. That means: Forbidden. You do not have permission to access this resource, or are over your rate limit.");
						break;
					
					case 400 : 
						throw new Exception(sprintf("Your call to Yahoo Web Services failed and returned an HTTP status of 400. That means:  Bad request. The parameters passed to the service did not match as expected. The exact error is returned in the XML/JSON response. The URL sent was: %s", $url));
						break;
					
					default : 
						throw new Exception("Your call to Yahoo Web Services returned an unexpected HTTP status of: " . $response->getStatusCode());
						
				}
				
				$return['url'] = $url;
			}

			return $return;
			
		}
		
		/**
		 * Format WoE data
		 * @since Version 3.9.1
		 * @param array $woe
		 * @return array
		 */
		
		public static function formatWoE($woe) {
			
			$thiswoe = $woe['places']['place'][0];
			
			$data = array(
				"country_code" => $thiswoe['country attrs']['code'],
				"country_name" => $thiswoe['country'],
				"timezone" => $thiswoe['timezone'],
				"neighbourhood" => $thiswoe['name'],
			);
			
			$prev = NULL;
			foreach ($thiswoe as $key => $val) {
				if (isset($val['type']) && strtolower($val['type']) != "country") {
					$data['region_code'] = str_replace($data['country_code'] . "-", "", $val['code']);
					
					$data['region_name'] = $prev;
					break;
				}
				
				$prev = $val;
			}
			
			if ($thiswoe['placeTypeName'] == "Point of Interest") {
				foreach ($thiswoe as $key => $val) {
					if (is_array($val) && isset($val['type']) && $val['type'] == "Town") {
						break;
					}
					
					if (!is_array($val)) {
						$data['neighbourhood'] = $val;
					}
				}
			}
			
			return $data;
			
		}
		
		/**
		 * Get an instance of \Railpage\Place representing this geographic location
		 * @since Version 3.9.1
		 * @return int
		 */
		
		public static function findGeoPlaceID($lat, $lon) {
			
			if (!filter_var($lat, FILTER_VALIDATE_FLOAT) || !filter_var($lon, FILTER_VALIDATE_FLOAT)) {
				throw new InvalidArgumentException("Invalid geographic co-ordinates");
			}
			
			#$Place = new Place;
			#$woe = $Place->getWOEData($lat . "," . $lon, array(7,8,9,10,22,24)); 
			$woe = self::LatLonWoELookup($lat, $lon);
			
			if (!isset($woe['places']['place'][0])) {
				return;
			}
			
			$data = self::formatWoE($woe);
			
			$thiswoe = $woe['places']['place'][0];
			
			$data = array_merge($data, array(
				"point" => $thiswoe['centroid'],
				"bb_southwest" => $thiswoe['boundingBox']['southWest'],
				"bb_northeast" => $thiswoe['boundingBox']['northEast'],
			));
			
			/**
			 * Find this place
			 */
			
			$data = array_merge($data, array(
				"point" => new Zend_Db_Expr(sprintf("GeomFromText('POINT(%s %s)')", $data['point']['latitude'], $data['point']['longitude'])),
				"bb_southwest" => new Zend_Db_Expr(sprintf("GeomFromText('POINT(%s %s)')", $data['bb_southwest']['latitude'], $data['bb_southwest']['longitude'])),
				"bb_northeast" => new Zend_Db_Expr(sprintf("GeomFromText('POINT(%s %s)')", $data['bb_northeast']['latitude'], $data['bb_northeast']['longitude'])),
			));
			
			$Database = (new AppCore)->getDatabaseConnection();
			
			$query = "SELECT id FROM geoplace WHERE SQRT(POW(X(point) - " . $thiswoe['centroid']['latitude'] . " , 2) + POW(Y(`point`) - " . $thiswoe['centroid']['longitude'] . ", 2)) * 100 < 1";
			
			$id = $Database->fetchOne($query);
			
			if (!$id) {
				$Database->insert("geoplace", $data);
				$id = $Database->lastInsertId(); 
			}
			
			return $id;
			
		}
		
		/**
		 * Find the geoplace country name from a country code
		 * @since Version 3.9.1
		 * @param string $code
		 * @return string
		 */
		
		public static function getCountryNameFromCode($code) {
			
			$Database = (new AppCore)->getDatabaseConnection();
			
			$query = "SELECT country_name FROM geoplace WHERE country_code = ?";
			
			return $Database->fetchOne($query, $code); 
			
		}
		
		/**
		 * Find the geoplace country name from a country code
		 * @since Version 3.9.1
		 * @param string $region_code
		 * @param string|boolean $region_code
		 * @return string
		 */
		
		public static function getRegionNameFromCode($region_code, $country_code = false) {
			
			$Database = (new AppCore)->getDatabaseConnection();
			
			$query = "SELECT region_name FROM geoplace WHERE region_code = ?";
			$params = [ $region_code ];
			
			if ($country_code) {
				$query .= " AND country_code = ?";
				$params[] = $country_code;
			}
			
			return $Database->fetchOne($query, $params); 
			
		}
		
	}