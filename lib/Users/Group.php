<?php
	/**
	 * UserGroup class
	 * @since Version 3.5
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Users; 
	
	use Exception;
	use DateTime;
	use Railpage\Organisations\Organisation;
	use Railpage\Url;
	use Railpage\Module;
	
	/**
	 * UserGroup class
	 */
	
	class Group extends Groups {
		
		/**
		 * Group ID
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Group name
		 * @var string $name
		 */
		
		public $name;
		
		/**
		 * Group type
		 * @var int $type
		 */
		
		public $type;
		
		/**
		 * Group description
		 * @var string $desc
		 */
		
		public $desc;
		
		/**
		 * Group owner user ID
		 * @var int $owner_user_id
		 */
		
		public $owner_user_id;
		
		/**
		 * Group owner username
		 * @var string $owner_username
		 */
		
		public $owner_username;
		
		/**
		 * Organisation name
		 * @since Version 3.6
		 * @var string $organisation
		 */
		
		public $organisation;
		
		/**
		 * Organisation ID
		 * @since Version 3.6
		 * @var int $organisation_id
		 */
		
		public $organisation_id;
		
		/**
		 * Attributes
		 * @since Version 3.8
		 * @var array $attributes
		 */
		
		public $attributes;
		
		/**
		 * Constructor
		 * @since Version 3.5
		 * @param int $group_id
		 */
		
		public function __construct($group_id = false) {
			
			parent::__construct(); 
			
			if ($group_id) {
				$this->id = $group_id; 
				$this->fetch(); 
			}
		}
		
		/**
		 * Populate this object
		 * @since Version 3.5
		 * @return boolean
		 */
		
		public function fetch() {
			if (empty($this->id)) {
				throw new Exception("Cannot fetch group - group ID cannot be empty"); 
				return false;
			} 
			
			$query = "SELECT g.group_attrs, g.organisation_id, g.group_id AS id, g.group_name AS name, g.group_type AS type, g.group_description AS description, g.group_moderator AS owner_user_id, u.username AS owner_username FROM nuke_bbgroups AS g INNER JOIN nuke_users AS u ON g.group_moderator = u.user_id WHERE g.group_id = '".$this->db->real_escape_string($this->id)."'";
			
			if ($rs = $this->db->query($query)) {
				$row = $rs->fetch_assoc(); 
				
				$this->name = $row['name']; 
				$this->type = $row['type']; 
				$this->desc = $row['description']; 
				$this->owner_user_id = $row['owner_user_id']; 
				$this->owner_username = $row['owner_username']; 
				$this->attributes = json_decode($row['group_attrs'], true);
				
				if ($row['organisation_id'] !== 0) {
					$this->organisation_id = $row['organisation_id']; 
					try {
						$Organisation = new Organisation(false, $this->organisation_id); 
						$this->organisation = $Organisation->name; 
					} catch (Exception $e) {
						throw new Exception($e->getMessage()); 
						return false;
					}
				}
				
				$this->url = new Url(sprintf("%s/%d", "/usergroups", $this->id));
				$this->url->edit = sprintf("%s/edit", $this->url->url);
				$this->url->addMember = sprintf("%s/addmember", $this->url->url);
			} else {
				throw new Exception($this->db->error."\n\n".$query); 
				return false;
			}
		}
		
		/**
		 * Get member list
		 * @since Version 3.5
		 * @param int $items_per_page
		 * @param int $page
		 * @return array
		 */
		
		public function members($items_per_page = 25, $page = 1) {
			if (empty($this->id)) {
				throw new Exception("Cannot fetch group - group ID cannot be empty"); 
				return false;
			} 
			
			$thispage = ($page - 1) * $items_per_page;
			
			$query = "SELECT SQL_CALC_FOUND_ROWS u.user_id, u.username, u.user_from AS user_location, u.name AS user_realname FROM nuke_bbuser_group AS gm INNER JOIN nuke_users AS u ON gm.user_id = u.user_id WHERE gm.group_id = '".$this->db->real_escape_string($this->id)."' ORDER BY u.username LIMIT ".$this->db->real_escape_string($thispage).", ".$this->db->real_escape_string($items_per_page);
			
			if ($rs = $this->db->query($query)) {
				$return = array(); 
				$total = $this->db->query("SELECT FOUND_ROWS() AS total"); 
				$total = $total->fetch_assoc(); 
				
				$return = array(); 
				$return['total'] = $total['total']; 
				$return['page'] = $page; 
				$return['perpage'] = $items_per_page; 

				
				while ($row = $rs->fetch_assoc()) {
					$return['members'][$row['user_id']] = $row;
				}
				
				return $return;
			} else {
				throw new Exception($this->db->query."\n\n".$query); 
				return false;
			}
		}
		
		/**
		 * Alias of members()
		 * @since Version 3.6
		 * @param int $items_per_page
		 * @param int $page
		 */
		
		public function getMembers($items_per_page = 25, $page = 1) {
			return $this->members($items_per_page, $page); 
		}
		
		/**
		 * Add a user to this group
		 * @since Version 3.5
		 * @param string $username
		 * @param int $user_id
		 * @param string $org_role
		 * @param string $org_contact
		 * @param int $org_perms
		 * @return boolean
		 */
		
		public function addMember($username = false, $user_id = false, $org_role = false, $org_contact = false, $org_perms = false) {
			if ($username && !$user_id) {
				$query = "SELECT user_id, username FROM nuke_users WHERE username = '".$this->db->real_escape_string($username)."' AND user_active = 1"; 
				
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows == 1) {
						$row = $rs->fetch_assoc(); 
						$user_id = $row['user_id']; 
						
						#$query = "INSERT INTO nuke_bbuser_group (group_id, user_id) VALUES ('".$this->db->real_escape_string($this->id)."', '".$this->db->real_escape_string($row['user_id'])."')"; 
						
						if ($org_role && $org_contact && $org_perms) {
							$stmt = $this->db->prepare("INSERT INTO nuke_bbuser_group (group_id, user_id, organisation_role, organisation_contact, organisation_perms) VALUES (?, ?, ?, ?, ?)");
							$stmt->bind_param("iissi", $this->id, $user_id, $org_role, $org_contact, $org_perms); 
							die("blah");
						} else {
							$stmt = $this->db->prepare("INSERT INTO nuke_bbuser_group (group_id, user_id) VALUES (?, ?)"); 
							$stmt->bind_param("ii", $this->id, $user_id); 
						}
						
						if ($stmt->execute()) {
							return true; 
						} else {
							throw new Exception($stmt->error."\n\n".$query); 
							return false;
						}
					} elseif ($rs->num_rows == 0) {
						return false; 
					} elseif ($rs->num_rows > 1) {
						$return = array(); 
						
						while ($row = $rs->fetch_assoc()) {
							$return[$row['user_id']] = $row['username']; 
							return $return;
						}
					}
				}
			} elseif ($user_id) {
				$query = "INSERT INTO nuke_bbuser_group (group_id, user_id) VALUES ('".$this->db->real_escape_string($this->id)."', '".$this->db->real_escape_string($user_id)."')"; 
				
				if ($this->db->query($query)) {
					return true; 
				} else {
					throw new Exception($this->db->error."\n\n".$query); 
					return false;
				}
			}
		}
		 
		
		/**
		 * Validate changes to this group
		 * @since Version 3.5
		 * @return boolean
		 */
		
		public function validate() {
			if (empty($this->name)) {
				throw new Exception("Cannot validate group - group name cannot be empty"); 
				return false;
			} 
			
			if (empty($this->desc)) {
				throw new Exception("Cannot validate group - group description cannot be empty"); 
				return false;
			} 
			
			if (empty($this->type)) {
				throw new Exception("Cannot validate group - group type cannot be empty"); 
				return false;
			}
			
			if (empty($this->owner_user_id)) {
				throw new Exception("Cannot validate group - group owner user ID cannot be empty"); 
				return false;
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this group
		 * @since Version 3.5
		 * @return boolean
		 */
		
		public function commit() {
			if (empty($this->owner_user_id)) {
				if (RP_DEBUG) {
					global $debug;
					$debug[] = __CLASS__ . "::" . __FUNCTION__ . "() : updating owner_user_id for group ID " . $this->id;
				}
				
				$query = "SELECT user_id FROM nuke_users WHERE username = '".$this->db->real_escape_string($this->owner_username)."'"; 
				
				if ($rs = $this->db->query($query)) {
					$row = $rs->fetch_assoc();
					$this->owner_user_id = $row['user_id']; 
				}
			}
			
			try {
				$this->validate(); 
			} catch (Exception $e) {
				throw new Exception($e->getMessage()); 
				return false;
			}
			
			$dataArray = array(); 
			$dataArray['group_name'] = $this->db->real_escape_string($this->name); 
			$dataArray['group_description'] = $this->db->real_escape_string($this->desc); 
			$dataArray['group_moderator'] = $this->db->real_escape_string($this->owner_user_id);
			$dataArray['group_type'] = $this->db->real_escape_string($this->type); 
			$dataArray['group_attrs'] = $this->db->real_escape_string(json_encode($this->attributes));
		
			if (!empty($this->organisation_id)) {
				$dataArray['organisation_id'] = $this->db->real_escape_string($this->organisation_id); 
			}
			
			if (!empty($this->id)) {
				$query = $this->db->buildQuery($dataArray, "nuke_bbgroups", array("group_id" => $this->db->real_escape_string($this->id))); 
			} else {
				$dataArray['group_single_user'] = "0";
				
				$query = $this->db->buildQuery($dataArray, "nuke_bbgroups"); 
			}
			
			if ($rs = $this->db->query($query)) {
				if (empty($this->id)) {
					$this->id = $this->db->insert_id; 
				}
				
				return true;
			} else {
				throw new Exception($this->db->error."\n\n".$query); 
				return false;
			}
		}
		
		/**
		 * Check if a user is a member of this group
		 * @since Version 3.7.5
		 * @paran int $user_id
		 * @return boolean
		 */
		 
		public function userInGroup($user_id = false) {
			if (!$user_id) {
				return false;
			}
			
			$mckey = "railpage:group=" . $this->id . ".user_id=" . $user_id;
		
			if (RP_DEBUG) {
				global $site_debug;
				$debug_timer_start = microtime(true);
			}
			
			if (getMemcacheObject($mckey) == "yes") {
				return true;
			} else {
			
				$query = "SELECT user_id FROM nuke_bbuser_group WHERE group_id = '" . $this->db->real_escape_string($this->id) . "' AND user_id = '" . $this->db->real_escape_string($user_id) . "'";
				
				if ($rs = $this->db->query($query)) {
					if ($rs->num_rows == 1) {
			
						if (RP_DEBUG) {
							$site_debug[] = "Railpage: Find user ID " . $user_id . " in group ID " . $this->id . " completed in " . number_format(microtime(true) - $debug_timer_start, 8) . "s";
						}
						
						setMemcacheObject($mckey, "yes", strtotime("+1 day")); 
			
						return true;
					}
				}
			
				if (RP_DEBUG) {
					$site_debug[] = "Railpage: Find user ID " . $user_id . " in group ID " . $this->id . " completed in " . number_format(microtime(true) - $debug_timer_start, 8) . "s";
				}
				
				setMemcacheObject($mckey, "no", strtotime("+1 day")); 
				
				return false;
			}
		}
		
		/**
		 * Remove a member from this group
		 * @since Version 3.7.5
		 * @param int $user_id
		 * @return boolean
		 */
		
		public function removeUser($user_id = false) {
			if (!$user_id) {
				return false;
			}
			
			$query = "DELETE FROM nuke_bbuser_group WHERE group_id = '" . $this->db->real_escape_string($this->id) . "' AND user_id = '" . $this->db->real_escape_string($user_id) . "'";
			
			if ($this->db->query($query)) {
				return true; 
			} else {
				throw new Exception($this->db->error . "\n\n" . $query);
				return false;
			}
		}
	}
	