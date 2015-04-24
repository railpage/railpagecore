<?php
	/**
	 * Ban controller
	 * @since Version 3.2
	 * @version 3.9.1
	 * @author Michael Greenhill
	 * @package Railpage
	 */
	 
	namespace Railpage\BanControl;
	
	use Railpage\AppCore;
	use Railpage\Users\User;
	use Exception;
	use DateTime;
	
	class BanControl extends AppCore {
		
		/**
		 * Cache key for all banned objects
		 * @since Version 3.9.1
		 * @const string CACHE_KEY_ALL
		 */
		
		const CACHE_KEY_ALL = "railpage:bancontrol.all;v1";
		
		/**
		 * Gzip level for caching
		 * @since Version 3.9.1
		 * @const int CACHE_GZIP_LEVEL
		 */
		
		const CACHE_GZIP_LEVEL = 9;
		 
		/**
		 * Banned users
		 * @since Version 3.2
		 * @var array $users
		 */
		 
		public $users;
		 
		/**
		 * Banned IP addresses
		 * @since Version 3.2
		 * @var array $ip_addresses
		 */
		 
		public $ip_addresses;
		
		/**
		 * Banned domain names
		 * @since Version 3.2
		 * @version 3.2
		 * @var array $domains
		 */
		
		public $domains;
		
		/**
		 * Load all 
		 * @since Version 3.9.1
		 * @return \Railpage\BanControl\BanControl
		 */
		
		public function loadAll() {
			// Attempt to load combined users & IPs first
			if (empty($this->users) || empty($this->ip_addresses)) {
				if ($array = json_decode(gzuncompress($this->Memcached->fetch(self::CACHE_KEY_ALL)), true)) {
					$this->users = $array['users'];
					$this->ip_addresses = $array['ips'];
					
					return true;
				} 
			}
			
			$this->loadUsers(); 
			$this->loadIPs(); 
			
			$this->cacheAll(); 
			
			return $this;
		}
		
		/**
		 * Save loaded ban arrays into our cache provider
		 * @since Version 3.9.1
		 * @return \Railpage\BanControl\BanControl
		 * @param boolean $force
		 */
		
		public function cacheAll($force = false) {
			if ($force) {
				$this->loadUsers($force); 
				$this->loadIPs($force);
			}
			
			$store = array(
				"users" => $this->users,
				"ips" => $this->ips
			);
			
			$this->Memcached->save(self::CACHE_KEY_ALL, gzcompress(json_encode($store), self::CACHE_GZIP_LEVEL));
			
			if (is_object($this->Redis)) {
				try {
					$this->Redis->save("railpage:bancontrol", $this);
				} catch (Exception $e) {
					// throw it away
				}
			}
			
			return $this;
		}
		
		/**
		 * Get banned users
		 * @since Version 3.2
		 * @version 3.2
		 * @return boolean
		 * @param boolean $force
		 */
		
		public function loadUsers($force = false) {
			$mckey = "railpage:bancontrol.users;v5"; 
			
			if ($force || !$this->users = json_decode(gzuncompress($this->Memcached->fetch($mckey)), true)) {
				$query = "SELECT b.id, b.user_id, b.ban_time, b.ban_expire, b.ban_reason, b.banned_by AS admin_user_id, bu.username, bu.reported_to_sfs, au.username AS admin_username
					FROM bancontrol AS b
					LEFT JOIN nuke_users AS bu ON b.user_id = bu.user_id
					LEFT JOIN nuke_users AS au ON b.banned_by = au.user_id
					WHERE b.ip = ''
					AND ban_active = 1";
				
				foreach ($this->db->fetchAll($query) as $row) {
					$this->users[$row['user_id']] = $row;
				}
				
				$this->Memcached->save($mckey, gzcompress(json_encode($this->users), self::CACHE_GZIP_LEVEL));
			}
			
			return true;
		}
		
		/**
		 * Get banned IP addresses
		 * @since Version 3.2
		 * @version 3.2
		 * @return boolean
		 * @param boolean $force
		 */
		
		public function loadIPs($force = false) {
			$mckey = "railpage:bancontrol.ips;v4"; 
			
			if ($force || !$this->ip_addresses = json_decode(gzuncompress($this->Memcached->fetch($mckey)), true)) {
				$query = "SELECT b.id, b.ip, b.ban_time, b.ban_expire, b.ban_reason, b.banned_by AS admin_user_id, au.username AS admin_username
					FROM bancontrol AS b
					LEFT JOIN nuke_users AS au ON b.banned_by = au.user_id
						WHERE b.user_id = ''
					AND ban_active = 1";
				
				foreach ($this->db->fetchAll($query) as $row) {
					$this->ip_addresses[$row['ip']] = $row;
				}
				
				$this->Memcached->save($mckey, gzcompress(json_encode($this->ip_addresses), self::CACHE_GZIP_LEVEL));
			}
			
			return true;
		}
		
		/**
		 * Get banned domain names
		 * @since Version 3.2
		 * @version 3.2
		 * @return boolean
		 */
		
		public function loadDomains() {
			$query = "SELECT * FROM ban_domains ORDER BY domain_name";
			
			if ($result = $this->db->fetchAll($query)) {
				foreach ($result as $row) {
					$this->domains[$row['domain_id']] = $row;
				}
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Ban user
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $user_id
		 * @param string $reason
		 * @param int $expiry
		 * @param int $admin_user_id
		 * @return boolean
		 * @todo use new \Railpage\Notifications\Notification for emailing out ban notice
		 */
		
		public function banUser($user_id = false, $reason = false, $expiry = false, $admin_user_id = false) {
			if (!$user_id || !$reason || !$admin_user_id) {
				return false;
			}
				
			if (!$expiry) {
				$expiry = "0";
			}
			
			/**
			 * Empty the cache
			 */
			
			$this->Memcached = AppCore::getMemcached();
			
			try {
				if ($this->Memcached->fetch("railpage:bancontrol.users")) {
					$this->Memcached->delete("railpage:bancontrol.users"); 
				}
				
				if ($this->Memcached->fetch(self::CACHE_KEY_ALL)) {
					$this->Memcached->delete(self::CACHE_KEY_ALL);
				}
			} catch (Exception $e) {
				// throw it away
			}
			
			try {
				$this->Redis->delete("railpage:bancontrol");
			} catch (Exception $e) {
				// throw it away
			}
			
			$data = array(
				"user_id"		=> $user_id,
				"ban_active"	=> 1,
				"ban_time"		=> time(),
				"ban_reason"	=> $reason,
				"banned_by"		=> $admin_user_id,
				"ban_expire"	=> $expiry
			);
			
			$this->db->insert("bancontrol", $data);
			
			/**
			 * Update the cache
			 */
			 
			$this->cacheAll(true);
			
			/**
			 * Tell the world that they've been naughty
			 */
			
			$ThisUser = new User($user_id);
			$ThisUser->active 		= 0;
			$ThisUser->location 	= "Banned"; 
			$ThisUser->signature 	= "Banned";
			$ThisUser->avatar 		= "";
			$ThisUser->interests 	= "";
			$ThisUser->occupation 	= "";
			
			try {
				$ThisUser->commit(true); 
				$return = true;
				
				$ThisUser->addNote("Banned", $_SESSION['user_id']);
				
				global $smarty;
			
				// Send the ban email
				$smarty->assign("userdata_username", $ThisUser->username);
				$smarty->assign("ban_reason", $reason);
				
				if ($expiry > 0) {
					$smarty->assign("ban_expire_nice", date($ThisUser->date_format, $expire));
				}
				
				$email_body = $smarty->fetch($smarty->ResolveTemplate("email_ban"));
				
				// Send the confirmation email
				require_once("Mail.php");
				require_once("Mail/mime.php");
				
				$crlf = "\n";
				$hdrs = array("To" => $ThisUser->contact_email, "From" => "banned@railpage.com.au", "Subject" => "Railpage account suspension");
				
				$mime = new \Mail_Mime(array("eol" => $crlf)); 
				
				$mime->setHTMLBody($email_body);
				
				$body = $mime->get();
				$hdrs = $mime->headers($hdrs);
				
				$mail =& \Mail::factory("mail");
				$send = $mail->send($ThisUser->contact_email, $hdrs, $body);
				
				return true;
			} catch (Exception $e) {
				global $Error;
				$Error->save($e, $_SESSION['user_id']);
			}
			
			return false;
		}
		
		/**
		 * Ban IP address
		 * @since Version 3.2
		 * @version 3.2
		 * @param string $ip_addr
		 * @param string $reason
		 * @param int $expiry
		 * @param int $admin_user_id
		 * @return boolean
		 */
		
		public function banIP($ip_addr = false, $reason = false, $expiry = false, $admin_user_id = false) {
			if (!$ip_addr || !$reason || !$admin_user_id) {
				return false;
			}
				
			if (!$expiry) {
				$expiry = "0";
			}
			
			/**
			 * Empty the cache
			 */
			
			try {
				$this->Memcached->delete("railpage:bancontrol.ips"); 
				$this->Memcached->delete(self::CACHE_KEY_ALL);
			} catch (Exception $e) {
				// throw it away
			}
			
			try {
				$this->Redis->delete("railpage:bancontrol");
			} catch (Exception $e) {
				// throw it away
			}
			
			$data = array(
				"ip"			=> $ip_addr,
				"ban_active"	=> 1,
				"ban_time"		=> time(),
				"ban_reason"	=> $reason,
				"banned_by"		=> $admin_user_id,
				"ban_expire"	=> $expiry
			);
			
			$this->db->insert("bancontrol", $data);
			
			/**
			 * Update the cache
			 */
			 
			$this->cacheAll(true);
			
			return true;
		}
		
		/**
		 * Unban user
		 * @since Version 3.2
		 * @version 3.2
		 * @param int $ban_id
		 * @param int $user_id
		 * @return boolean
		 * @todo use new \Railpage\Notifications\Notification for emailing out unbanned notices
		 */
		
		public function unBanUser($ban_id, $user_id = false) {
			$success = false;
			
			/**
			 * Empty the cache
			 */
			
			try {
				$this->Memcached->delete("railpage:bancontrol.users"); 
				$this->Memcached->delete(self::CACHE_KEY_ALL);
			} catch (Exception $e) {
				// throw it away
			}
			
			try {
				$this->Redis->delete("railpage:bancontrol");
			} catch (Exception $e) {
				// throw it away
			}
			
			if ($ban_id instanceof User) {
				$user_id = $ban_id->id;
			}
			
			if (!$user_id) {
				$query = "SELECT user_id FROM bancontrol WHERE id = ?"; 
			
				$user_id = $this->db->fetchOne($query, $ban_id);
			}
			
			if ($user_id > 0) {
				$data = array(
					"ban_active" => 0
				);
				
				$where = array(
					"user_id = " . $user_id
				);
				
				$this->db->update("bancontrol", $data, $where);
				$success = true;
			}
			
			if ($success) {
				// Tell the world that they've been unbanned
				$ThisUser = new User($user_id);
				$ThisUser->active 		= 1;
				$ThisUser->location 	= ""; 
				$ThisUser->signature 	= "";
				$ThisUser->avatar 		= "";
				$ThisUser->interests 	= "";
				$ThisUser->occupation 	= "";
				
				try {
					$ThisUser->commit(); 
					
					global $smarty;
		
					// Send the ban email
					$smarty->assign("userdata_username", $ThisUser->username);
					
					$email_body = $smarty->fetch($smarty->ResolveTemplate("email_unban"));
					
					// Send the confirmation email
					require_once("Mail.php");
					require_once("Mail/mime.php");
					
					$crlf = "\n";
					$hdrs = array("To" => $ThisUser->contact_email, "From" => "banned@railpage.com.au", "Subject" => "Railpage account re-activation");
					
					$mime = new \Mail_Mime(array("eol" => $crlf)); 
					
					$mime->setHTMLBody($email_body);
					
					$body = $mime->get();
					$hdrs = $mime->headers($hdrs);
					
					$mail =& \Mail::factory("mail");
					$send = $mail->send($ThisUser->contact_email, $hdrs, $body);
					
					return true;
				} catch (Exception $e) {
					global $Error;
					
					$Error->save($e, $_SESSION['user_id']);
				}
			} 
			
			return false;
		}
		
		/**
		 * Unban IP address
		 * @since Version 3.5
		 * @param int $ban_id
		 * @param string $ban_ip
		 * @return boolean
		 */
		
		public function unBanIp($ban_id, $ban_ip = false) {
			
			/**
			 * Empty the cache
			 */
			
			try {
				$this->Memcached->delete("railpage:bancontrol.ips"); 
				$this->Memcached->delete(self::CACHE_KEY_ALL);
			} catch (Exception $e) {
				// throw it away
			}
			
			try {
				$this->Redis->delete("railpage:bancontrol");
			} catch (Exception $e) {
				// throw it away
			}
			
			$data = array(
				"ban_active" => "0"
			);
			
			if ($ban_ip === false) {
				$where = array(
					"id = ?" => $ban_id
				);
			} else {
				$where = array(
					"ip = ?" => $ban_ip
				);
			}
			
			$this->db->update("bancontrol", $data, $where);
			return true;
		}
		
		/**
		 * Edit a ban 
		 * @since Version 3.4
		 * @param int $ban_id
		 * @param int $expire
		 * @return bool
		 */
		
		public function editUserBan($ban_id = false, $expire = false) {
			if (!$ban_id) {
				throw new Exception("Cannot change user ban - no ban ID given"); 
				return false;
			}
			
			/**
			 * Empty the cache
			 */
			
			try {
				$this->Memcached->delete("railpage:bancontrol.users"); 
				$this->Memcached->delete(self::CACHE_KEY_ALL);
			} catch (Exception $e) {
				// throw it away
			}
			
			try {
				$this->Redis->delete("railpage:bancontrol");
			} catch (Exception $e) {
				// throw it away
			}
			
			if (!$expire) {
				$expire = "0"; 
			}
			
			$data = array(
				"ban_expire" => $expire
			);
			
			$where = array(
				"id = ?" => $ban_id
			);
			
			$this->db->update("bancontrol", $data, $where);
			return true;
		}
		
		/**
		 * Lookup IP address
		 * @since Version 3.6
		 * @param string $ip
		 * @param boolean $activeOnly
		 */
		
		public function lookupIP($ip = false, $activeOnly = true) {
			if (!$ip) {
				throw new Exception("Cannot peform IP ban lookup - no IP address given"); 
				return false;
			}
			
			$query = "SELECT b.id, b.ip, b.ban_active, b.ban_time, b.ban_expire, b.ban_reason, b.banned_by, u.username AS banned_by_username FROM bancontrol AS b INNER JOIN nuke_users AS u ON b.banned_by = u.user_id WHERE b.ip = ?";
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query, $ip) as $row) {
				if ($activeOnly === false || ($activeOnly === true && $row['ban_active'] == 1)) {
					$row['ban_time']->setTimestamp($row['ban_time']);
					$row['ban_time_nice'] = $row['ban_time']->format("F j, Y");
					
					if ($ban_expire > 0) {
						$row['ban_expire']->setTimestamp($row['ban_expire']); 
						$row['ban_expire_nice'] = $row['ban_expire']->format("F j, Y");
					} else {
						$row['ban_expire'] = 0; 
					}
					
					$return[$row['ban_id']] = $row; 
				}
			}
			
			return $return;
		}
		
		/**
		 * Lookup IP user
		 * @since Version 3.6
		 * @param string $user_id
		 * @param boolean $activeOnly
		 */
		
		public function lookupUser($user_id = false, $activeOnly = true) {
			if (!$user_id) {
				throw new Exception("Cannot peform user ban lookup - no user ID given"); 
				return false;
			}
			
			$query = "SELECT b.id, b.user_id, un.username AS username, b.ban_active, b.ban_time, b.ban_expire, b.ban_reason, b.banned_by, u.username AS banned_by_username FROM bancontrol AS b INNER JOIN nuke_users AS u ON b.banned_by = u.user_id INNER JOIN nuke_users AS un ON b.user_id = un.user_id WHERE b.user_id = ?";
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query, $user_id) as $row) {
				if ($activeOnly === false || ($activeOnly === true && $row['ban_active'] == 1)) {
					$row['ban_time']->setTimestamp($row['ban_time']);
					$row['ban_time_nice'] = $row['ban_time']->format("F j, Y");
					
					if ($ban_expire > 0) {
						$row['ban_expire']->setTimestamp($row['ban_expire']); 
						$row['ban_expire_nice'] = $row['ban_expire']->format("F j, Y");
					} else {
						$row['ban_expire'] = 0; 
					}
					
					$return[$row['ban_id']] = $row; 
				}
			}
			
			return $return;
		}
		
		/**
		 * Check if an IP address is banned
		 * @since Version 3.9
		 * @param string $ip
		 * @return boolean
		 */
		
		public function isIPBanned($ip = false) {
			if (!$ip) {
				throw new Exception("Cannot check for banned IP address because no or an invaild IP address was given");
			}
			
			return isset($this->ip_addresses[$ip]);
		}
		
		/**
		 * Check if user ID is banned
		 * @since Version 3.9
		 * @param string|\Railpage\Users\User $user
		 * @return boolean
		 */
		
		public function isUserBanned($user = false) {
			if (!$user) {
				throw new Exception("Cannot check for banned IP address because no or an invaild user ID was given");
			}
			
			if ($user instanceof User) {
				$user = $user->id;
			}
			
			return isset($this->users[$user]);
		}
	}
?>