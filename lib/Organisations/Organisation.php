<?php
	/** 
	 * Create and manage official organisations
	 * The purpose of an organisation is to identify a forum member as an official representative of an organisation or company
	 * @since Version 3.2
	 * @version 3.87
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Organisations;
	
	use Railpage\Events\Events;
	use Railpage\Events\Event;
	use Railpage\Events\EventDate;
	use Railpage\Jobs\Jobs;
	use Railpage\Jobs\Job;
	use Railpage\AppCore;
	use Exception;
	use DateTime;
	use DateInterval;
	
	/**
	 * Organisation object
	 * @since Version 3.2
	 * @version 3.8.7
	 * @author Michael Greenhill
	 */
	
	class Organisation extends Base {
		
		/**
		 * ID
		 * @since Version 3.2
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Name
		 * @since Version 3.2
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Description
		 * @since Version 3.2
		 * @var string $desc
		 */
		
		public $desc;
		
		/**
		 * Date added (timestamp)
		 * @since Version 3.2
		 * @var int $created
		 */
		
		public $created;
		
		/**
		 * Onwer user ID
		 * @since Version 3.2
		 * @var int $owner
		 */
		
		public $owner;
		
		/**
		 * Website
		 * @since Version 3.2
		 * @var string $contact_website
		 */
		
		public $contact_website;
		
		/**
		 * Phone number
		 * @since Version 3.2
		 * @var string $contact_phone
		 */
		
		public $contact_phone;
		
		/**
		 * Fax number
		 * @since Version 3.2
		 * @var string $contact_fax
		 */
		
		public $contact_fax;
		
		/**
		 * Email address
		 * @since Version 3.2
		 * @var string $contact_email
		 */
		
		public $contact_email;
		
		/**
		 * Members
		 * @since Version 3.2
		 * @var array $members
		 */
		
		public $members;
		
		/**
		 * Roles
		 * @since Version 3.2
		 * @var array $roles
		 */
		
		public $roles;
		
		/**
		 * Logo 
		 * @since Version 3.2
		 * @var string $logo
		 */
		
		public $logo;
		
		/**
		 * Flickr photo ID
		 * @since Version 3.2
		 * @var string $flickr_photo_id
		 */
		
		public $flickr_photo_id;
		
		/**
		 * URL slug
		 * @since Version 3.7.5
		 * @var string $slug
		 */
		
		public $slug;
		
		/**
		 * Public URL relative to the site root
		 * @since Version 3.7.5
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Constructor
		 * @since Version 3.2
		 * @version 3.7.5
		 * @param object $db
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			
			parent::__construct();
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
			} else {
				$this->id = $this->db->fetchOne("SELECT organisation_id FROM organisation WHERE organisation_slug = ?", $id); 
			}
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				// Retrieve this organisation from the database
				
				if ($this->db instanceof \sql_db) {
					$query = "SELECT o.* FROM organisation AS o WHERE organisation_id = ".$this->db->real_escape_string($this->id);
					
					if ($rs = $this->db->query($query)) {
						$row = $rs->fetch_assoc();
						
						// Populate the vars above
						$this->name 	= $row['organisation_name'];
						$this->desc 	= $row['organisation_desc'];
						$this->created 	= $row['organisation_dateadded'];
						$this->owner 	= $row['organisation_owner'];
						$this->contact_website 	= $row['organisation_website'];
						$this->contact_phone 	= $row['organisation_phone'];
						$this->contact_fax 		= $row['organisation_fax'];
						$this->contact_email 	= $row['organisation_email'];
						
						$this->logo = $row['organisation_logo']; 
						$this->flickr_photo_id = $row['flickr_photo_id'];
						
						// Get the roles for this org
						$query = "SELECT * FROM organisation_roles WHERE organisation_id = ".$this->db->real_escape_string($this->id);
						
						if ($rs = $this->db->query($query)) {
							while ($row = $rs->fetch_assoc()) {
								$this->roles[$row['role_id']] = $row['role_name'];
							}
						} else {
							throw new Exception($this->db->error."\n".$query);
						}
					} else {
						throw new Exception("Could not find organisation ID ".$this->id);
						throw new Exception($this->db->error."\n".$query);
					}
				} else {
					$query = "SELECT o.* FROM organisation AS o WHERE organisation_id = ?";
					
					if ($row = $this->db->fetchRow($query, $this->id)) {
						$this->name 	= $row['organisation_name'];
						$this->desc 	= $row['organisation_desc'];
						$this->created 	= $row['organisation_dateadded'];
						$this->owner 	= $row['organisation_owner'];
						$this->contact_website 	= $row['organisation_website'];
						$this->contact_phone 	= $row['organisation_phone'];
						$this->contact_fax 		= $row['organisation_fax'];
						$this->contact_email 	= $row['organisation_email'];
						
						$this->logo = $row['organisation_logo']; 
						$this->flickr_photo_id = $row['flickr_photo_id'];
						
						$this->slug = $row['organisation_slug']; 
						
						if (empty($this->slug)) {
							$this->slug = parent::createSlug();
						}
						
						$this->url = parent::makePermaLink($this->slug); 
						
						// Get the roles for this organisation
						$query = "SELECT * FROM organisation_roles WHERE organisation_id = ?";
						
						if ($result = $this->db->fetchAll($query, $this->id)) {
							foreach ($result as $row) {
								$this->roles[$row['role_id']] = $row['role_name'];
							}
						}
					}
				}
			}
		}
		
		/** 
		 * Check that an organisation is OK to commit
		 * @since Version 3.2
		 * @version 3.2
		 * @return boolean
		 */
		
		public function verify() {
			if (empty($this->slug)) {
				$this->slug = "";
			}
			
			if (empty($this->name) || empty($this->desc)) {
				return false;
			} else {
				return true;
			}
		}
		
		/**
		 * Commit changes to / add new organisation
		 * @since Version 3.2
		 * @version 3.2
		 * @return boolean
		 */
		
		public function commit() {
			if (!$this->verify()) {
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$dataArray = array(); 
				$dataArray['organisation_name']			= $this->db->real_escape_string($this->name);
				$dataArray['organisation_desc']			= $this->db->real_escape_string($this->desc);
				$dataArray['organisation_dateadded']	= $this->db->real_escape_string(time()); 
				$dataArray['organisation_owner']		= $this->db->real_escape_string($this->owner);
				$dataArray['organisation_website']		= $this->db->real_escape_string($this->contact_website);
				$dataArray['organisation_phone']		= $this->db->real_escape_string($this->contact_phone); 
				$dataArray['organisation_fax']			= $this->db->real_escape_string($this->contact_fax);
				$dataArray['organisation_email']		= $this->db->real_escape_string($this->contact_email); 
				$dataArray['organisation_logo'] 		= $this->db->real_escape_string($this->logo); 
				$dataArray['flickr_photo_id']			= $this->db->real_escape_string($this->flickr_photo_id); 
				
				if ($this->id) {
					$where = array("organisation_id" => $this->id); 
					$query = $this->db->buildQuery($dataArray, "organisation", $where); 
				} else {
					$query = $this->db->buildQuery($dataArray, "organisation"); 
				} 
				
				if ($rs = $this->db->query($query)) {
					if (empty($this->id)) {
						return $this->db->insert_id;
					} else {
						return true;
					}
				} else {
					throw new Exception($this->db->error."\n".$query); 
					return false;
				}
			} else {
				$data = array(
					"organisation_name"			=> $this->name,
					"organisation_desc"			=> $this->desc,
					"organisation_dateadded"	=> time(),
					"organisation_owner"		=> $this->owner,
					"organisation_website"		=> $this->contact_website,
					"organisation_phone"		=> $this->contact_phone,
					"organisation_fax"			=> $this->contact_fax,
					"organisation_email"		=> $this->contact_email,
					"organisation_logo"			=> $this->logo,
					"flickr_photo_id"			=> $this->flickr_photo_id,
					"organisation_slug"			=> $this->slug
				);
				
				if (filter_var($this->id, FILTER_VALIDATE_INT)) {
					// Update
					if ($this->db->update("organisation", $data, "organisation_id = " . $this->id)) {
						return true;
					}
				} else {
					// Insert
					if ($this->db->insert("organisation", $data)) {
						$this->id = $this->db->lastInsertId();
						
						$this->slug = $this->createSlug();
						return true;
					}
				}
			}
		}
		
		/** 
		 * Find operators / owners linked to this organisation
		 * @since Version 3.2
		 * @return array
		 */
		
		public function operator_id() {
			if (!$this->id) {
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "SELECT operator_id FROM operators WHERE organisation_id = ".$this->id;
				
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows == 0) {
						return false;
					}
					
					$row = $rs->fetch_assoc(); 
					
					return $row['operator_id'];
				} else {
					throw new Exception($this->db->error."\n\n".$query); 
					return false;
				}
			} else {
				$query = "SELECT operator_id FROM operators WHERE organisation_id = ?";
				
				return $this->db->fetchOne($query, $this->id);
			}
		}
		
		/**
		 * Yield upcoming events for this organisation
		 * @since Version 3.9
		 * @return \Railpage\Events\EventDate
		 * @yield \Railpage\Events\EventDate
		 */
		
		public function yieldUpcomingEvents() {
			$Events = new Events;
			
			foreach ($Events->getUpcomingEventsForOrganisation($this) as $date) {
				foreach ($date as $row) {
					yield new EventDate($row['event_date']);
				}
			}
		}
		
		/**
		 * Yield jobs from this organisation
		 * @since Version 3.9
		 * @return \Railpage\Jobs\Job
		 * @yield \Railpage\Jobs\Job
		 * @param \DateTime|boolean $DateFrom
		 * @param \DateTime|boolean $DateTo
		 */
		
		public function yieldJobs($DateFrom = false, $DateTo = false) {
			if (!$DateFrom instanceof DateTime) {
				$DateFrom = new DateTime;
			}
			
			if (!$DateTo instanceof DateTime) {
				$DateTo = (new DateTime)->add(new DateInterval("P1Y"));
			}
			
			$query = "SELECT job_id FROM jn_jobs WHERE organisation_id = ? AND job_added >= ? AND job_expiry <= ?";
			
			foreach ($this->db->fetchAll($query, array($this->id, $DateFrom->format("Y-m-d"), $DateTo->format("Y-m-d"))) as $row) {
				yield new Job($row['job_id']);
			}
		}
	}
?>