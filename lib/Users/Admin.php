<?php
	/**
	 * Base user class
	 * @since Version 3.0.1
	 * @version 3.6
	 * @author Michael Greenhill
	 * @package Railpage
	 */
	
	namespace Railpage\Users;
	
	use Exception;
	use DateTime;
	use DateTimeZone;
	use Railpage\BanControl\BanControl;
	
	/**
	 * User Admin class
	 * @since Version 3.0.1
	 * @version 3.0.1
	 * @author Michael Greenhill
	 */
	
	class Admin extends Base {
		
		/** 
		 * Return a list of pending users
		 * @since Version 3.0.1
		 * @version 3.0.1
		 * @author Michael Greenhill
		 * @return mixed
		 */
		
		public function pending() {
			
			$query	= "SELECT * FROM nuke_users_temp ORDER BY time ASC"; 
			
			return $this->db->fetchAll($query); 
		}
		
		/**
		 * Search the database for a user
		 * @since Version 3.1
		 * @version 3.1
		 * @author Michael Greenhill
		 * @return array|false
		 * @param string $username
		 * @param boolean $strict
		 */
		 
		public function find($username = false, $strict = false) {
			if ($username === false || strlen($username) === 0 || $username === NULL) {
				throw new Exception("Cannot perform user lookup because no partial username was provided");
			}
			
			// Escape MySQL wildcard
			$operator = $strict === true ? "=" : "LIKE";
			$username = $strict === true ? $username : sprintf("%%%s%%", $username);
			
			$query = "SELECT user_id, username, user_email, user_active FROM nuke_users WHERE username " . $operator . " ?";
			
			foreach ($this->db->fetchAll($query, $username) as $user) {
				try {
					yield new User($user['user_id']);
				} catch (Exception $e) {
					// throw it away
				}
			}
		}
		
		/**
		 * Delete a user
		 *
		 * NOT FOR COMMON USE! DEACTIVATE, DO NOT DELETE
		 * @since Version 3.2
		 * @version 3.2
		 * @author Michael Greenhill
		 * @return boolean
		 * @param int $id
		 */
		
		/*
		public function delete($id = false) {
			if (!$this->db || !$id) {
				return false;
			}
			
			if ($this->db instanceof \sql_db) {
				$query = "DELETE FROM nuke_users WHERE user_id = '".$this->db->real_escape_string($id)."'";
				
				if ($this->db->query($query)) {
					return true; 
				} else {
					trigger_error("UserAdmin: could not delete user id ".$id." from the active users table"); 
					trigger_error($this->db->error); 
					trigger_error($query); 
					
					return false;
				}
			}
		}
		*/
		
		/**
		 * Check username availability
		 * @since Version 3.0.1
		 * @version 3.9.1
		 * @author Michael Greenhill
		 * @return boolean
		 * @param string $username
		 */
		
		public function username_available($username = false) {
			return (new Base)->username_available($username); 
		}
		
		/**
		 * Check email address availability
		 * @since Version 3.0.1
		 * @version 3.9.1
		 * @author Michael Greenhill
		 * @return boolean
		 * @param string $email
		 */
		
		public function email_available($email = false) {
			return (new Base)->email_available($email); 
		}
		
		/**
		 * List all members
		 * @since Version 3.2
		 * @version 3.2
		 * @return mixed
		 * @param int $page
		 * @param int $items_per_page
		 */
		
		public function memberList($page = 1, $items_per_page = 25) {
			
			$start = $page > 0 ? ($page - 1) * $items_per_page : 0;
			$return = array(); 
			
			// Get banned usernames
			$BanControl = new BanControl;
			$BanControl->loadUsers(); 
			$banned_user_ids = array_keys($BanControl->users);
			
			$bancontrol_sql = count($banned_user_ids) ? " AND user_id NOT IN(" . implode(",", $banned_user_ids) . ")" : ""; 
			
			$query = "SELECT SQL_CALC_FOUND_ROWS user_id AS id, username, name AS real_name, user_avatar AS avatar, user_avatar_width AS avatar_width, 
						user_avatar_height AS avatar_height, user_email AS contact_email, user_viewemail AS email_show, user_lastvisit AS lastvisit, 
						user_session_time AS last_activity, user_regdate AS regdate, user_regdate_nice, user_posts AS posts, timezone AS timezone, flickr_nsid 
					FROM nuke_users 
					WHERE user_active = 1 
						AND user_id != 0
						AND user_session_time > 0 
						" . $bancontrol_sql . "
					ORDER BY user_id LIMIT ?, ?";
			
			if ($result = $this->db->fetchAll($query, array($start, $items_per_page))) {
				$return['page'] 	= $page;
				$return['perpage'] 	= $items_per_page; 
				$return['total'] 	= $this->db->fetchOne("SELECT FOUND_ROWS() AS total"); 
				$return['num_pages'] = ceil($return['total'] / $items_per_page);
				
				foreach ($result as $row) {
					if (empty($row['user_regdate_nice']) || $row['user_regdate_nice'] == "0000-00-00") {
						try {
							$datetime = new DateTime($row['regdate']);
							
							$update['user_regdate_nice'] = $datetime->format("Y-m-d");
							
							$this->db->update("nuke_users", $update, array("user_id = ?" => $row['id']));
						} catch (Exception $e) { 
							// Throw it away, Manuel!
						}
					}
					
					$return['members'][$row['id']] = $row;
				}
			}
			
			return $return;
		}
		
		/**
		 * Get the list of ranks
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function ranks() {
			$query = "SELECT * FROM nuke_bbranks ORDER BY rank_special, rank_min, rank_title";
			
			$return['stat'] = "ok"; 
			
			foreach ($this->db->fetchAll($query) as $row) {
				$return['ranks'][$row['rank_id']] = $row;
			}
			
			$return['count'] = count($return['ranks']);	
					
			return $return;
		}
		
		/**
		 * List users with multi accounts
		 * @since Version 3.2
		 * @version 3.2
		 * @return array
		 */
		
		public function multiUsers() {
			ini_set("memory_limit", "300M");
			
			$query = "SELECT h.*, u.username 
						FROM nuke_users_hash AS h
						LEFT JOIN nuke_users AS u ON u.user_id = h.user_id
						WHERE h.date > ".strtotime("2 weeks ago")."
						ORDER BY h.date DESC";
						
			$users = array();
			$ip = array();
			
			foreach ($this->db->fetchAll($query) as $row) {
				$users[$row['hash']][$row['user_id']] = $row; 
				$ip[$row['ip']][$row['user_id']] = $row; 
			}
			
			// Unset hashes with only one user
			foreach ($users as $id => $data) {
				if (count($data) < 2) {
					unset($users[$id]); 
				}
			}
			
			// Unset IP addresses with only one user
			foreach ($ip as $id => $data) {
				if (count($data) < 2) {
					unset($ip[$id]); 
				}
			}
			
			$return['devices'] = $users;
			$return['ip'] = $ip;
			
			return $return;
		}
	}
	