<?php
	/**
	 * Railpage JobNet base
	 * @since Version 3.7
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Jobs; 
	
	use Railpage\AppCore;
	use Railpage\Module;
	use Exception;
	use DateTime;
	use Zend_Db_Expr;
	
	/**
	 * Base class
	 * @since Version 3.7.0
	 * @version 3.8.7
	 */
	
	class Jobs extends AppCore {
		
		/**
		 * Constructor
		 * @since Version 3.8.7
		 */
		
		public function __construct() {
			
			parent::__construct(); 
			
			$this->Module = new Module("jobs");
			
		}
		
		/**
		 * Filter jobs
		 * @since Version 3.8
		 * @param array $args
		 * @return array
		 */
		
		public function filter($args = false) {
			if ($args === false) {
				$args = array(); 
			}
			
			if (!is_array($args)) {
				throw new Exception("Cannot filter jobs - incorrect filter given"); 
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT j.*, jc.jn_classification_name AS job_classification_name, o.organisation_name , jl.jn_location_name AS job_location_name
					FROM jn_jobs AS j 
						INNER JOIN jn_classifications AS jc ON j.job_classification_id = jc.jn_classification_id
						INNER JOIN organisation AS o ON j.organisation_id = o.organisation_id
						INNER JOIN jn_locations AS jl ON j.job_location_id = jl.jn_location_id ";
				
				$where = array(); 
				
				$salary_min = isset($args['job_salary_min']) ? $args['job_salary_min'] : false; 
				$salary_max = isset($args['job_salary_max']) ? $args['job_salary_max'] : false; 
				
				foreach ($args as $column => $value) {
					if (!empty($value) && $column != "job_salary_min" && $column != "job_salary_max") {
						$where[] = "j." . $column ." = '" . $this->db->real_escape_string($value) . "'";
					}
				}
				
				if ($salary_min) {
					$where[] = "j.job_salary >= " . $this->db->real_escape_string($salary_min); 
				}
				
				if ($salary_max) {
					$where[] = "j.job_salary <= " . $this->db->real_escape_string($salary_max); 
				}
				
				if (count($where)) {
					$query .= " WHERE " . implode(" AND " , $where); 
				}
				
				$query .= " ORDER BY j.job_expiry ASC"; 
				
				if ($rs = $this->db->query($query)) {
					$return = array(); 
					$return['args'] = $args; 
					$return['count'] = $rs->num_rows; 
					
					while ($row = $rs->fetch_assoc()) {
						$row['job_description'] = format_post($row['job_description']);
						
						$return['jobs'][$row['job_id']] = $row; 
					}
					
					return $return;
				} else {
					throw new Exception($this->db->error); 
					return false;
				}
			} else {
				// Assume Zend_Db
				
				$query = "SELECT j.*, jc.jn_classification_name AS job_classification_name, o.organisation_name , jl.jn_location_name AS job_location_name
					FROM jn_jobs AS j 
						INNER JOIN jn_classifications AS jc ON j.job_classification_id = jc.jn_classification_id
						INNER JOIN organisation AS o ON j.organisation_id = o.organisation_id
						INNER JOIN jn_locations AS jl ON j.job_location_id = jl.jn_location_id ";
				
				$where = array(); 
				$params = array();
				
				$salary_min = isset($args['job_salary_min']) ? $args['job_salary_min'] : false; 
				$salary_max = isset($args['job_salary_max']) ? $args['job_salary_max'] : false; 
				
				foreach ($args as $column => $value) {
					if (!empty($value) && $column != "job_salary_min" && $column != "job_salary_max") {
						$where[] = "j." . $column ." = ?";
						$params[] = $value;
					}
				}
				
				if ($salary_min) {
					$where[] = "j.job_salary >= ?";
					$params[] = $salary_min; 
				}
				
				if ($salary_max) {
					$where[] = "j.job_salary <= ?"; 
					$params[] = $salary_max;
				}
				
				$where[] = "j.job_expiry > ?";
				$params[] = date("Y-m-d H:i:s");
				
				#printArray($where);printArray($params);die;
				
				if (count($where)) {
					$query .= " WHERE " . implode(" AND " , $where); 
				}
				
				$query .= " ORDER BY j.job_expiry ASC"; 
				
				#printArray($query);die;
				
				if ($result = $this->db->fetchAll($query, $params)) {
					$return = array(); 
					$return = array(); 
					$return['args'] = $args; 
					$return['count'] = count($result);
					
					foreach ($result as $row) {
						$row['job_description'] = format_post($row['job_description']);
						$return['jobs'][$row['job_id']] = $row; 
					}
					
					return $return;
				}
			}
		}
	}
?>