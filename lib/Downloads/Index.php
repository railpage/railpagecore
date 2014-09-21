<?php
	/** 
	 * Downloads 
	 * @since Version 3.0
	 * @version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Downloads;
	
	use DateTime;
	use DateTimeZone;
	use Exception;
		
	/**
	 * Downloads index
	 * @since Version 3.2
	 * @version 3.8.7
	 */
	
	class Index extends Base {
		
		/**
		 * Get the latest additions to the database
		 * @since Version 3.2
		 * @return array
		 */
		
		public function latest() {
			$query = "SELECT d.id AS download_id, d.title AS download_title, d.description AS download_desc, d.date, c.category_id, c.category_title
						FROM download_items AS d
						LEFT JOIN download_categories AS c ON d.category_id = c.category_id
						WHERE d.approved = 1
						AND d.active = 1
						ORDER BY d.date DESC
						LIMIT 0, 10"; 
						
			$return = array(
				"stat" => "ok",
				"downloads" => array()
			);
			
			foreach ($this->db->fetchAll($query) as $row) {
				$row['date'] = new DateTime($row['date'], new DateTimeZone("Australia/Melbourne"));
				$return['downloads'][$row['download_id']] = $row; 
			}
			
			return $return;
		}
		
		/**
		 * Get the most downloaded files in the database
		 * @since Version 3.2
		 * @return array
		 */
		
		public function popular() {
			$query = "SELECT d.id AS download_id, d.title AS download_title, d.description AS download_desc, d.date, c.category_id, c.category_title
						FROM download_items AS d
						LEFT JOIN download_categories AS c ON d.category_id = c.category_id
						WHERE d.approved = 1
						AND d.active = 1
						ORDER BY d.hits DESC
						LIMIT 0, 10"; 
			
		
			$return = array(
				"stat" => "ok",
				"downloads" => array()
			);
			
			foreach ($this->db->fetchAll($query) as $row) {
				$row['date'] = new DateTime($row['date'], new DateTimeZone("Australia/Melbourne"));
				$return['downloads'][$row['download_id']] = $row; 
			}
			
			return $return;
		}
	}
?>