<?php
	/**
	 * News classes
	 * @since Version 3.0.1
	 * @version 3.8.7
	 * @author Michael Greenhill
	 * @package Railpage
	 * @copyright Copyright (c) 2012 Michael Greenhill
	 */
	 
	namespace Railpage\News;
	
	/**
	 * News archive
	 * @since Version 3.0.1
	 * @version 3.8.7
	 * @package Railpage
	 * @author Michael Greenhill
	 * @copyright Copyright (c) 2012 Michael Greenhill
	 */
	
	class Archive extends Base {
		
		/** 
		 * Year
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $year
		 */
		
		public $year; 
		
		/** 
		 * Month
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @var int $month
		 */
		
		public $month; 
		
		/**
		 * Constructor
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @param object $db
		 * @param int $year
		 * @param int $month
		 */
		
		public function __construct($year = false, $month = false) {
			if (!$year) {
				return false;
			}
			
			$this->year 	= $year;
			$this->month	= $month; 
			
			parent::__construct(); 
		}
		
		/**
		 * Get stories from archive
		 * @version 3.0.1
		 * @since Version 3.0.1
		 * @return mixed
		 * @param int $year
		 * @param int $month
		 * @param int $page
		 * @param int $limit
		 */ 
		
		public function stories($page = false, $limit = false) {
			#if ($page == false || $limit == false) {
			#	return false;
			#}
			
			$return = false;
			$query	= "SELECT SQL_CALC_FOUND_ROWS s.*, t.* FROM nuke_stories s, nuke_topics t WHERE s.topic = t.topicid";
			
			if ($this->db instanceof \sql_db) {
				if ($this->month) {
					$query .= " AND s.time >= '".$this->db->real_escape_string($this->year)."-".$this->db->real_escape_string(str_pad($this->month, 2, "0", STR_PAD_LEFT))."-01' AND s.time <= '".$this->db->real_escape_string($this->year)."-".$this->db->real_escape_string(str_pad($this->month, 2, "0", STR_PAD_LEFT))."-31'";
				} else {
					$query .= " AND s.time >= '".$this->db->real_escape_string($this->year."-01-01")."' AND s.time <= '".$this->db->real_escape_string($this->year."-12-31")."'"; 
				}
				
				$query .= " ORDER BY s.time ASC";
				
				if ($page && $limit) {
					$query .= " LIMIT ".$this->db->real_escape_string($page * $limit).", ".$this->db->real_escape_string($limit); 
				}
				
				if ($rs = $this->db->query($query)) {
					$total = $this->db->query("SELECT FOUND_ROWS() AS total"); 
					$total = $total->fetch_assoc(); 
					
					$return = array(); 
					$return['total'] = $total['total']; 
					$return['page'] = $page; 
					$return['perpage'] = $limit; 
					
					require_once("includes/functions.php"); 
					
					while ($row = $rs->fetch_assoc()) {
						if (function_exists("relative_date")) {
							$row['time_relative'] = relative_date(strtotime($row['time']));
						} else {
							$row['time_relative'] = $row['time'];
						}
						
						$return['children'][] = $row; 
					}
				} else {
					trigger_error("News: could not fetch archive from ".$this->year."-".$this->month); 
					trigger_error($this->db->error); 
					trigger_error($query); 
				}
			} else {
				$params = array(); 
				
				if ($this->month) {
					$params[] = $this->year . "-" . str_pad($this->month, 2, "0", STR_PAD_LEFT) . "-01"; 
					$params[] = $this->year . "-" . str_pad($this->month, 2, "0", STR_PAD_LEFT) . "-31"; 
					$query .= " AND s.time >= ? AND s.time <= ?";
				} else {
					$params[] = $this->year . "-01-01";
					$params[] = $this->year . "-12-31";
					$query .= " AND s.time >= ? AND s.time <= ?"; 
				}
				
				$query .= " ORDER BY s.time ASC";
				
				if ($page && $limit) {
					$params[] = $page * $limit;
					$params[] = $limit;
					
					$query .= " LIMIT ?, ?"; 
				}
				
				$return = array(); 
				
				if ($result = $this->db->fetchAll($query, $params)) {
					$return['total'] 	= $this->db->fetchOne("SELECT FOUND_ROWS() AS total"); 
					$return['page'] 	= $page; 
					$return['perpage'] 	= $limit;
					
					foreach ($result as $row) {
						if (function_exists("relative_date")) {
							$row['time_relative'] = relative_date(strtotime($row['time']));
						} else {
							$row['time_relative'] = $row['time'];
						}
						
						$return['children'][] = $row; 
					}
				}
			}
			
			return $return;
		}
	}
	