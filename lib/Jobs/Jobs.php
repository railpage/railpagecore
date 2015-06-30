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
	use Railpage\Organisations\Factory as OrganisationsFactory;
	use Railpage\Organisations\Organisation;
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
				
				if (count($where)) {
					$query .= " WHERE " . implode(" AND " , $where); 
				}
				
				$query .= " ORDER BY j.job_expiry ASC"; 
				
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
		
		/**
		 * Get all job providers
		 * @since Version 3.9
		 * @return \Railpage\Organisations\Organisation
		 * @yield \Railpage\Organisations\Organisation
		 */
		
		public function yieldProviders() {
			$query = "SELECT jn.organisation_id FROM jn_jobs AS jn LEFT JOIN organisation AS o ON o.organisation_id = jn.organisation_id GROUP BY jn.organisation_id ORDER BY organisation_name";
			
			foreach ($this->db->fetchAll($query) as $row) {
				yield OrganisationsFactory::CreateOrganisation($row['organisation_id']);
			}
		}
		
		/**
		 * Get a random job
		 * @since Version 3.9
		 * @return \Railpage\Jobs\Job
		 */
		
		public function getRandomJob() {
			$query = "SELECT job_id FROM jn_jobs WHERE job_expiry >= ?";
			
			$jobs = $this->db->fetchAll($query, date("Y-m-d H:i:s"));
			
			$job_id = array_rand($jobs);
			return new Job($jobs[$job_id]['job_id']);
		}
		
		/**
		 * Get newest jobs
		 * @since Version 3.9.1
		 * @return \Railpage\Jobs\Job
		 * @yield \Railpage\Jobs\Job
		 */
		
		public function yieldNewJobs($limit = 25) {
			$query = "SELECT job_id FROM jn_jobs ORDER BY job_added DESC LIMIT 0, ?";
			
			foreach ($this->db->fetchAll($query, $limit) as $row) {
				yield new Job($row['job_id']);
			}
		}
		
		/**
		 * Get number of jobs added in the last 30 days
		 */
		
		public function getNumNewJobs() {
			$query = "SELECT COUNT(job_id) FROM jn_jobs WHERE job_added >= ?";
			
			return $this->db->fetchOne($query, (new DateTime("@" . strtotime("30 days ago")))->format("Y-m-d H:i:s"));
		}
		
		/**
		 * Get jobs from an employer
		 * @since Version 3.9.1
		 * @return \Railpage\Jobs\Job
		 * @yield \Railpage\Jobs\Job
		 */
		
		public function getJobsFromEmployer(Organisation $Org, $page = 1, $limit = 25) {
			$query = "SELECT SQL_CALC_FOUND_ROWS job_id, job_title FROM jn_jobs WHERE organisation_id = ? ORDER BY job_added DESC LIMIT ?, ?";
			
			$page = ($page - 1) * $limit;
			
			$where = array($Org->id, $page, $limit);
			
			$jobs = $this->db->fetchAll($query, $where);
			
			$return = array(
				"page" => $page,
				"items_per_page" => $limit,
				"total" => $this->db->fetchOne("SELECT FOUND_ROWS() AS total"),
				"organisation" => array(
					"id" => $Org->id,
					"name" => $Org->name
				), 
				"jobs" => $jobs
			);
			
			return $return;
		}
	}
	