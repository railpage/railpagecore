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
	use Railpage\Organisations\Factory as OrganisationsFactory;
	use Railpage\Organisations\Organisation;
	use Railpage\Url;
	use Railpage\Module;
	use Railpage\Users\User;
	use Railpage\Debug;
	
	/**
	 * UserGroup class
	 */
	
	class Group extends Groups {
		
		/**
		 * Group type: open/public
		 * @since Version 3.9.1
		 * @const int TYPE_OPEN
		 */
		
		const TYPE_OPEN = 0; 
		
		/**
		 * Group type: closed
		 * @since Version 3.9.1
		 * @const int TYPE_CLOSED
		 */
		
		const TYPE_CLOSED = 1; 
		
		/**
		 * Group type: hidden
		 * @since Version 3.9.1
		 * @const int TYPE_HIDDEN
		 */
		
		const TYPE_HIDDEN = 2; 
		
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
				
				if (filter_var($this->id, FILTER_VALIDATE_INT)) {
					$this->fetch(); 
				}
			}
		}
		
		/**
		 * Populate this object
		 * @since Version 3.5
		 * @return boolean
		 */
		
		public function fetch() {
			
			$mckey = sprintf("railpage:group=%d", intval($this->id)); 
			
			if (!$row = $this->Redis->fetch($mckey)) {
			
				$query = "SELECT g.group_attrs, g.organisation_id, g.group_id AS id, g.group_name AS name, g.group_type AS type, 
							g.group_description AS description, g.group_moderator AS owner_user_id, u.username AS owner_username 
						FROM nuke_bbgroups AS g 
							INNER JOIN nuke_users AS u ON g.group_moderator = u.user_id 
						WHERE g.group_id = ?";
				
				$row = $this->db->fetchRow($query, $this->id); 
				
				$this->Redis->save($mckey, $row, 0); 
			}
			
			if (!is_array($row)) {
				throw new Exception("Could not fetch group data for group ID " . $this->id); 
			}
			
			$this->name = $row['name']; 
			$this->type = $row['type']; 
			$this->desc = $row['description']; 
			$this->owner_user_id = $row['owner_user_id']; 
			$this->owner_username = $row['owner_username']; 
			$this->attributes = json_decode($row['group_attrs'], true);
			
			if (filter_var($row['organisation_id'], FILTER_VALIDATE_INT) && $row['organisation_id'] !== 0) {
				
				$Organisation = OrganisationsFactory::CreateOrganisation(false, $this->organisation_id); 
				
				if ($Organisation instanceof Organisation) {
					$this->organisation_id = $row['organisation_id']; 
					$this->organisation = $Organisation->name; 
				}
			}
			
			$this->makeURLs(); 
		}
		
		/**
		 * Set the owner of this group
		 * @since Version 3.9.1
		 * @return \Railpage\Users\Group
		 * @param \Railpage\Users\User $User
		 */
		
		public function setOwner(User $User) {
			
			$this->owner_user_id = $User->id;
			$this->owner_username = $User->username;
			
			return $this;
		}
		
		/**
		 * Set the organisation associated to this group
		 * @since Version 3.9.1
		 * @return \Railpage\Users\Group
		 * @param \Railpage\Organisations\Organisation $Org
		 */
		
		public function setOrganisation(Organisation $Org) {
			
			$this->organisation_id = $Org->id;
			$this->organisation = $Org->name;
			
			return $this;
		}
		
		/**
		 * Make URLs
		 * @since Version 3.9.1
		 * @return void
		 */
		
		private function makeURLs() {
			
			$this->url = new Url(sprintf("%s/%d", "/usergroups", $this->id));
			$this->url->edit = sprintf("%s/edit", $this->url->url);
			$this->url->addMember = sprintf("%s/addmember", $this->url->url);
			
		}
		
		/**
		 * Get member list
		 * @since Version 3.5
		 * @param int $items_per_page
		 * @param int $page
		 * @return array
		 */
		
		public function members($items_per_page = 25, $page = 1) {
			if (!filter_var($this->id, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannot fetch group - group ID cannot be empty");
			}
			
			$thispage = ($page - 1) * $items_per_page;
			
			$params = [ $this->id, $thispage, $items_per_page ];
			
			$query = "SELECT SQL_CALC_FOUND_ROWS u.user_id, u.username, u.user_from AS user_location, u.name AS user_realname 
					FROM nuke_bbuser_group AS gm 
						INNER JOIN nuke_users AS u ON gm.user_id = u.user_id 
					WHERE gm.group_id = ? ORDER BY u.username LIMIT ?, ?";
			
			$result = $this->db->fetchAll($query, $params); 
			
			$return = array(); 
			$total = $this->db->fetchOne("SELECT FOUND_ROWS() AS total"); 
			
			$return = array(); 
			$return['total'] = $total['total']; 
			$return['page'] = $page; 
			$return['perpage'] = $items_per_page; 

			
			foreach ($result as $row) {
				$return['members'][$row['user_id']] = $row;
			}
			
			return $return;
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
			
			$mckey = sprintf("railpage:group=%d", intval($this->id)); 
			$this->Redis->delete($mckey); 
			
			if ($username && !$user_id) {
				$query = "SELECT user_id, username FROM nuke_users WHERE username = ? AND user_active = 1"; 
				$params = [ $username ];
				
				if ($row = $this->db->fetchRow($query, $params)) {
					/*
					if (count($result) === 0) {
						return false;
					}
					
					if (count($result) > 1) {
						$return = array(); 
						
						foreach ($result as $row) {
							$return[$row['user_id']] = $row['username']; 
						}
						
						$this->updateUserGroupMembership($user_id);
						
						return $return;
					}
					
					$row = $result[0]; 
					*/
					
					$user_id = $row['user_id']; 
					
					if ($org_role && $org_contact && $org_perms) {
						$data = [
							"group_id" => $this->id,
							"user_id" => $user_id,
							"organisation_role" => $org_role,
							"organisation_contact" => $org_contact,
							"organisation_privileges" => $org_perms
						];
					} else {
						$data = [
							"group_id" => $this->id,
							"user_id" => $user_id
						];
					}
						
					$this->db->insert("nuke_bbuser_group", $data); 
						
					$this->updateUserGroupMembership($user_id);
					
					return true;
				}
				
			} elseif ($user_id) {
				$data = [ 
					"group_id" => $this->id,
					"user_id" => $user_id
				];
				
				$this->db->insert("nuke_bbuser_group", $data); 
						
				$this->updateUserGroupMembership($user_id);
				
				return true;
			}
		}
		
		/**
		 * Force refresh the user group membership
		 * @since Version 3.9.1
		 * @param \Railpage\Users\User|int $user_id
		 * @return void
		 */
		
		private function updateUserGroupMembership($User) {
			
			if (!$User instanceof User) {
				$User = new User($User); 
			}
			
			$mckey = sprintf("railpage:group=%d.user_id=%d", $this->id, $User->id);
			$this->Redis->delete($mckey);
			
			$rdkey = sprintf("railpage:usergroups.user_id=%d", $User->id); 
			$this->Redis->delete($rdkey); 
			
			$User->getGroups(true);
			
			return;
			
		}
		 
		
		/**
		 * Validate changes to this group
		 * @since Version 3.5
		 * @return boolean
		 */
		
		public function validate() {
			if (empty($this->name)) {
				throw new Exception("Cannot validate group - group name cannot be empty"); 
			} 
			
			if (empty($this->desc)) {
				throw new Exception("Cannot validate group - group description cannot be empty"); 
			} 
			
			if (!filter_var($this->type, FILTER_VALIDATE_INT)) {
				$this->type = self::TYPE_OPEN;
			}
			
			if (empty($this->owner_user_id)) {
				Debug::logEvent(__METHOD__ . " : updating owner_user_id for group ID " . $this->id);
				
				$query = "SELECT user_id FROM nuke_users WHERE username = ?"; 
				
				$this->owner_user_id = $this->db->fetchOne($query, $this->owner_username); 
			}
			
			if (empty($this->owner_user_id)) {
				throw new Exception("Cannot validate group - group owner user ID cannot be empty"); 
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this group
		 * @since Version 3.5
		 * @return boolean
		 */
		
		public function commit() {
			
			$this->validate(); 
			
			$data = [
				"group_name" => $this->name,
				"group_description" => $this->desc,
				"group_moderator" => $this->owner_user_id,
				"group_type" => $this->type,
				"group_attrs" => json_encode($this->attributes)
			];
			
			if (filter_var($this->organisation_id, FILTER_VALIDATE_INT)) {
				$data['organisation_id'] = $this->organisation_id;
			}
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				
				$where = [ "group_id = ?" => $this->id ];
				$this->db->update("nuke_bbgroups", $data, $where); 
			
				$mckey = sprintf("railpage:group=%d", intval($this->id)); 
				$this->Redis->delete($mckey); 
				
			} else {
				
				$data['group_single_user'] = 0; 
				$this->db->insert("nuke_bbgroups", $data); 
				$this->id = $this->db->lastInsertId(); 
				
			}
			
			$this->makeURLs(); 
			
			return true;
		}
		
		/**
		 * Check if a user is a member of this group
		 * @since Version 3.7.5
		 * @paran int $user_id
		 * @return boolean
		 */
		 
		public function userInGroup($user_id = false) {
			if ($user_id instanceof User) {
				$user_id = $user_id->id;
			}
			
			if (!filter_var($user_id, FILTER_VALIDATE_INT)) {
				return false;
			}
			
			$mckey = sprintf("railpage:group=%d.user_id=%d", $this->id, $user_id);
		
			$timer = Debug::getTimer(); 
			
			if (!$result = $this->Redis->fetch($mckey)) {
				$query = "SELECT user_id FROM nuke_bbuser_group WHERE group_id = ? AND user_id = ? AND user_pending = 0";
				$params = [ $this->id, $user_id ];
				
				$id = $this->db->fetchOne($query, $params);
				if (filter_var($id, FILTER_VALIDATE_INT)) {
					$this->Redis->save($mckey, "yes", strtotime("+1 day")); 
					Debug::logEvent(__METHOD__ . " found user ID " . $user_id . " in group ID " . $this->id, $timer); 
					return true; 
				}
			}
				
			Debug::logEvent(__METHOD__ . " did not find ID " . $user_id . " in group ID " . $this->id, $timer); 
			
			return $result;
		}
		
		/**
		 * Remove a member from this group
		 * @since Version 3.7.5
		 * @param int $user_id
		 * @return boolean
		 */
		
		public function removeUser($user_id = false) {
			if ($user_id instanceof User) {
				$user_id = $user_id->id;
			}
			
			if (!filter_var($user_id, FILTER_VALIDATE_INT)) {
				return false;
			}
			
			$where = [ 
				"group_id = ?" => $this->id, 
				"user_id = ?" => $user_id
			]; 
			
			$this->db->delete("nuke_bbuser_group", $where); 
			
			$mckey = sprintf("railpage:group=%d", intval($this->id)); 
			$this->Redis->delete($mckey); 
						
			$this->updateUserGroupMembership($user_id);
			
			return true;
		}
		
		/**
		 * Approve user membership
		 * @since Version 3.9.1
		 * @param \Railpage\Users\User|int $User
		 * @return \Railpage\Users\Group
		 */
		
		public function approveUser($User) {
			if (!$User instanceof User) {
				$User = new User($User);
			}
			
			$data = [ "user_pending" => 0 ];
			$where = [ 
				"user_id = ?" => $User->id,
				"group_id = ?" => $this->id
			]; 
			
			$mckey = sprintf("railpage:group=%d", intval($this->id)); 
			$this->Redis->delete($mckey); 
			
			$this->db->update("nuke_bbuser_group", $data, $where); 
			
			return $this;
		}
	}
	