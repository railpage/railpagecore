<?php
	/**
	 * Photos statistics
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images;
	
	use DateTime;
	use DateTimeZone;
	use Exception;
	use InvalidArgumentException;
	use Railpage\AppCore;
	use Railpage\Debug;
	use Railpage\Url;
	
	class Statistics extends AppCore {
		
		/**
		 * Get the number of geotagged photos in each region
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getNumPhotosByRegion() {
			
			$query = "SELECT g.country_code, g.country_name, g.region_code, g.region_name, COUNT(*) AS count
				FROM image AS i
				LEFT JOIN geoplace AS g ON i.geoplace = g.id
				WHERE i.geoplace != 0
				GROUP BY g.region_code
				ORDER BY g.country_code, g.region_code";
			
			return $this->db->fetchAll($query); 
			
		}
		
		/**
		 * Get quantities
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getQuantities() {
			
			$query = 'SELECT "Total photos" AS title, FORMAT(COUNT(*), 0) AS num FROM image
				UNION SELECT "Photos with latitude/longitude" AS title, FORMAT(COUNT(*), 0) AS num FROM image WHERE ROUND(lat) != 0
				UNION SELECT "Photos without latitude/longitude" AS title, FORMAT(COUNT(*), 0) AS num FROM image WHERE ROUND(lat) = 0
				UNION SELECT "Photos with a geoplace" AS title, FORMAT(COUNT(*), 0) AS num FROM image WHERE geoplace != 0
				UNION SELECT "Photos photos of locomotives" AS title, FORMAT(COUNT(*), 0) AS num FROM image AS i LEFT JOIN image_link AS il ON i.id = il.image_id WHERE il.namespace = "railpage.locos.loco" AND ignored = 0
				UNION SELECT "Photos photos of loco liveries" AS title, FORMAT(COUNT(*), 0) AS num FROM image AS i LEFT JOIN image_link AS il ON i.id = il.image_id WHERE il.namespace = "railpage.locos.liveries.livery" AND ignored = 0
				UNION SELECT "Most photographed locomotive" AS title, l.loco_num AS num FROM loco_unit AS l LEFT JOIN loco_class AS c ON l.class_id = c.id WHERE l.loco_id = ( SELECT namespace_key FROM image_link WHERE namespace = "railpage.locos.loco" GROUP BY namespace_key ORDER BY COUNT(*) DESC, namespace_key LIMIT 0,1 )
				UNION SELECT "Most photographed loco class" AS title, c.name AS num FROM loco_class AS c WHERE c.id = ( SELECT namespace_key FROM image_link WHERE namespace = "railpage.locos.class" GROUP BY namespace_key ORDER BY COUNT(*) DESC, namespace_key LIMIT 0,1 )
				UNION SELECT * FROM (SELECT "Most popular camera" AS title, CONCAT(camera_make, " ", camera_model) AS num FROM (
    SELECT DISTINCT e.camera_id, i.user_id, c.make AS camera_make, c.model AS camera_model
    FROM image_exif AS e 
    LEFT JOIN image_camera AS c ON c.id = e.camera_id
    LEFT JOIN image AS i on e.image_id = i.id 
    WHERE e.camera_id != 0 
    AND i.user_id != 0
) AS dist WHERE camera_make != "Unknown" GROUP BY camera_id ORDER BY COUNT(*) DESC LIMIT 0, 1) AS camera';
			
			return $this->db->fetchAll($query); 
			
		}
		
		/**
		 * Get biggest contributors
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getContributors() {
			
			$query = "SELECT u.username, u.user_id, CONCAT('/user/', u.user_id) AS url, COUNT(*) AS num FROM nuke_users AS u LEFT JOIN image AS i ON i.user_id = u.user_id WHERE i.user_id != 0 AND i.provider != 'rpoldgallery' GROUP BY u.user_id ORDER BY num DESC LIMIT 0, 10";
			
			return $this->db->fetchAll($query); 
			
		}
		
		/**
		 * Get biggest contributors
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getContributorWithTaggedPhotos() {
			
			$query = "SELECT u.username, u.user_id, CONCAT('/user/', u.user_id) AS url, COUNT(*) AS num 
				FROM nuke_users AS u 
				LEFT JOIN image AS i ON i.user_id = u.user_id 
				LEFT JOIN image_link AS il ON i.id = il.image_id
				WHERE i.user_id != 0 
					AND i.provider != 'rpoldgallery' 
					AND il.id != 0
				GROUP BY u.user_id 
				ORDER BY num DESC 
				LIMIT 0, 10";
			
			return $this->db->fetchAll($query); 
			
		}
	}