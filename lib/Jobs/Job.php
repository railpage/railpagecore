<?php
	/**
	 * Job object
	 * @since Version 3.8
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Jobs;
	
	use Railpage\Url;
	use Railpage\Organisations\Organisation;
	use Exception;
	use DateTime;
	
	/**
	 * Job 
	 * @since Version 3.8.0
	 * @version 3.8.7
	 */
	
	class Job extends Jobs {
		
		/**
		 * Job ID
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Job reference ID
		 * @var int $reference_id
		 * @since Version 3.8.7
		 */
		
		public $reference_id;
		
		/**
		 * Job title / position name
		 * @var string $title
		 */
		
		public $title;
		
		/**
		 * Job description
		 * @var string $desc;
		 */
		
		public $desc;
		
		/**
		 * Date when the job was posted
		 * @var \DateTime $Open
		 */
		
		public $Open;
		
		/**
		 * Expiry date
		 * @var \DateTime $expiry
		 */
		
		public $expiry;
		
		/**
		 * Number of days until position expires
		 * @var object $expiry_until
		 */
		
		public $expiry_until;
		
		/**
		 * Job salary
		 * @var int $salary
		 */
		
		public $salary;
		
		/**
		 * Special requirements for this job
		 * @var string $special_cond
		 */
		
		public $special_cond;
		
		/**
		 * Duration
		 * @string $duration
		 */
		
		public $duration;
		
		/**
		 * Discussion thread ID
		 * @var int $thread_id
		 */
		
		public $thread_id;
		
		/**
		 * Job URL
		 * @since Version 3.8.7
		 * @var \Railpage\Url $url
		 */
		 
		public $url;
		
		/**
		 * Number of times the job has been clicked through to the application form
		 * @since Version 3.9
		 * @var int $conversions
		 */
		
		public $conversions = 0;
		
		/**
		 * Location object
		 * @var \Railpage\Jobs\Location $Location
		 */
		
		public $Location; 
		
		/**
		 * Classification object
		 * @var \Railpage\Jobs\Classification $Classification
		 */
		
		public $Classification;
		
		/**
		 * Organisation
		 * @var \Railpage\Organisations\Organisation $Organisation
		 */
		
		public $Organisation;
		
		/**
		 * Constructor
		 * @since Version 3.8
		 * @param int $job_id
		 */
		
		public function __construct($job_id = false) {
			
			try {
				parent::__construct(); 
			} catch (Exception $e) {
				throw new Exception($e->getMessage()); 
			}
			
			if ($job_id) {
				$this->id = $job_id; 
				
				$query = "SELECT organisation_id, conversions, job_added, reference_id, job_urls, job_location_id, job_classification_id, job_thread_id, job_title, job_description, job_expiry, DATEDIFF(job_expiry, NOW()) AS job_expiry_until, job_salary, job_special_cond, job_duration FROM jn_jobs WHERE job_id = ?";
				
				if ($result = $this->db->fetchRow($query, $this->id)) {
					$this->title 		= $result['job_title'];
					$this->desc 		= $result['job_description'];
					$this->expiry 		= $this->expiry = new \DateTime($result['job_expiry']);
					$this->expiry_until	= $result['job_expiry_until'];
					$this->salary		= $result['job_salary'];
					$this->special_cond	= $result['job_special_cond'];
					$this->duration		= $result['job_duration']; 
					$this->reference_id = $result['reference_id'];
					$this->conversions = $result['conversions'];
					
					if (empty($this->duration) || $this->duration === 0) {
						$this->duration = "Ongoing"; 
					}
					
					if ($result['job_added'] != "0000-00-00 00:00:00") {
						$this->Open = new DateTime($result['job_added']);
					}
					
					$this->Organisation = new Organisation($result['organisation_id']);
					$this->Location = new Location($result['job_location_id']);
					$this->Classification = new Classification($result['job_classification_id']);
					
					$this->url = new Url(sprintf("%s/%d", $this->Module->url, $this->id));
					$this->url->conversion = sprintf("%s?apply", $this->url->url);
					
					if (is_array(json_decode($result['job_urls'], true))) {
						foreach (json_decode($result['job_urls'], true) as $title => $link) {
							
							if (!is_null($link)) {
								$this->url->$title = $link;
							}
						}
					}
				}
			}
		}
		
		/**
		 * Validate the job object before committing
		 * @since Version 3.8
		 * @return boolean
		 */
		
		public function validate() {
			if (!is_object($this->Organisation) || empty($this->Organisation->id)) {
				throw new Exception("Cannot save job - organisation is empty or invalid"); 
				return false;
			}
			
			if (!is_object($this->Location) || empty($this->Location->id)) {
				throw new Exception("Cannot save job - job location is empty or invalid"); 
				return false;
			}
			
			if (!is_object($this->Classification) || empty($this->Classification->id)) {
				throw new Exception("Cannot save job - job classification is empty or invalid"); 
				return false;
			}
			
			if (empty($this->desc)) {
				throw new Exception("Cannot save job - job description is empty or invalid"); 
				return false;
			}
			
			if (!filter_var($this->salary, FILTER_VALIDATE_INT)) {
				$this->salary = "0";
			}
			
			if (empty($this->duration) || strtolower($this->duration) == "ongoing") {
				$this->duration = "";
			}
			
			if (empty($this->title)) {
				throw new Exception("Cannot save job - job title is empty or invalid"); 
				return false;
			}
			
			if (empty($this->special_cond)) {
				$this->special_cond = ""; 
			}
			
			if (is_null($this->thread_id)) {
				$this->thread_id = 0;
			}
			
			if (!($this->expiry instanceof DateTime)) {
				$this->expiry = new DateTime($this->expiry); 
			}
			
			$this->salary = filter_var($this->salary, FILTER_SANITIZE_NUMBER_INT);
			
			return true;
		}
		
		/**
		 * Commit changes to a job
		 * @since Version 3.8
		 * @return boolean
		 */
		
		public function commit() {
			try {
				$this->validate(); 
			} catch (Exception $e) {
				throw new Exception($e->getMessage()); 
				return false;
			}
			
			/**
			 * Firstly, check if this reference ID exists anywhere in the database. If it does, update, not create.
			 */
			
			if (filter_var($this->reference_id, FILTER_VALIDATE_INT) && $this->reference_id > 0) {
				$query = "SELECT job_id FROM jn_jobs WHERE reference_id = ?";
				
				if ($id = $this->db->fetchOne($query, $this->reference_id)) {
					$this->id = $id;
				}
			}
			
			$data = array(
				"job_title" => $this->title,
				"reference_id" => $this->reference_id,
				"organisation_id" => $this->Organisation->id,
				"job_location_id" => $this->Location->id,
				"job_description" => $this->desc,
				"job_added" => $this->Open->format("Y-m-d H:i:s"),
				"job_expiry" => $this->expiry->format("Y-m-d H:i:s"),
				"job_classification_id" => $this->Classification->id,
				"job_salary" => $this->salary,
				"job_special_cond" => $this->special_cond,
				"job_duration" => $this->duration,
				"job_thread_id" => $this->thread_id,
				"job_urls" => json_encode($this->url->getUrls()),
				"conversions" => $this->conversions
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				// Update
				if ($this->db->update("jn_jobs", $data, "job_id = " . $this->id)) {
					return true;
				}
			} else {
				// Insert
				if ($this->db->insert("jn_jobs", $data)) {
					$this->id = $this->db->lastInsertId();
					$this->url = new Url(sprintf("%s?mode=view&id=%d", $this->Module->url, $this->id));
					return $this;
				}
			}
		}
	}
?>