<?php
	/**
	 * Newsletter
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Newsletters;
	
	use Exception;
    use InvalidArgumentException;
	use DateTime;
	use Railpage\Url;
	use Railpage\AppCore;
	use Railpage\Module;
	use Railpage\Users\User;
	use Railpage\Notifications\Notification;
	use Railpage\Notifications\Transport\Email;
    use ReflectionClass;
	
	/**
	 * Newsletters
	 */
	
	class Newsletters extends AppCore {
        
        /**
         * Newsletter: daily
         * @since Version 3.10.0
         * @const string NEWSLETTER_DAILY
         */
        
        const NEWSLETTER_DAILY = "daily";
        
        /**
         * Newsletter: weekly
         * @since Version 3.10.0
         * @const string NEWSLETTER_WEEKLY
         */
        
        const NEWSLETTER_WEEKLY = "weekly";
        
        /**
         * Newsletter: monthly
         * @since Version 3.10.0
         * @const string NEWSLETTER_MONTHLY
         */
        
        const NEWSLETTER_MONTHLY = "monthly";
		
		/**
		 * Get available templates
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getTemplates() {
			return $this->db->fetchAll("SELECT * FROM newsletter_templates");
		}
		
		/**
		 * Get a single template
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getTemplate($id = false) {
			if (!filter_var($id, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannnot fetch template from the database - invalid template ID specified");
			}
			
			return $this->db->fetchRow("SELECT * FROM newsletter_templates WHERE id = ?", $id);
		}
		
		/**
		 * Get newsletters
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getNewsletters() {
			$return = array(); 
			
			foreach ($this->db->fetchAll("SELECT * FROM newsletter") as $row) {
				$Newsletter = new Newsletter($row['id']); 
				$return[] = $Newsletter->getArray(); 
			}
			
			return $return;
		}
        
        /**
         * Subscribe a user to a newsletter
         * @since Version 3.10.0
         * @param \Railpage\Users\User $User
         * @param string $newsletter
         * @return \Railpage\Newsletters\Newsletters
         */
        
        public function subscribeUser(User $User, $newsletter) {
            
            $this->changeSubscription($User, $newsletter, 1); 
            
            return $this;
            
        }
        
        /**
         * Unsubscribe a user from a newsletter
         * @since Version 3.10.0
         * @param \Railpage\Users\User $User
         * @param string $newsletter
         * @return \Railpage\Newsletters\Newsletters
         */
        
        public function unSubscribeUser(User $User, $newsletter) {
            
            $this->changeSubscription($User, $newsletter, 0); 
            
            return $this;
            
        }
        
        /**
         * Change the newsleter subscription status
         * @since Version 3.10.0
         * @param \Railpage\Users\User $User
         * @param string $newsletter
         * @return void
         */
        
        private function changeSubscription(User $User, $newsletter, $value) {
            
            if (!filter_var($User->id, FILTER_VALIDATE_INT)) {
                throw new InvalidArgumentException("The provided user is invalid - no user ID"); 
            }
            
            $r = new ReflectionClass(__CLASS__);
            
            if (strpos($newsletter, "newsletter_") === false) {
                $newsletter = "newsletter_" . $newsletter;
            }
            
            $newsletter = strtoupper($newsletter); 
            $column = strtolower($newsletter); 
            
            if (!$r->getConstant($newsletter)) {
                throw new InvalidArgumentException("The provided newsletter " . $newsletter . " is not a valid class constant"); 
            }
            
            $query = "INSERT INTO nuke_users_flags (user_id, " . $column . ") VALUES(" . $User->id . ", " . $value . ") ON DUPLICATE KEY UPDATE " . $column . " = VALUES(" . $column . ")";
            
    		$this->db->query($query); 
            
        }
        
        /**
         * Get the subscription statuses for a given user
         * @since Version 3.10.0
         * @param \Railpage\Users\User $User
         * @return array
         */
        
        public static function getSubscriptionFlags(User $User) {
            
            $Database = AppCore::GetDatabase(); 
            
            $query = "SELECT COALESCE(newsletter_daily, 1) AS newsletter_daily,
                             COALESCE(newsletter_weekly, 1) AS newsletter_weekly,
                             COALESCE(newsletter_monthly, 1) AS newsletter_monthly,
                             COALESCE(notify_photocomp, 1) AS notify_photocomp,
                             COALESCE(notify_pm, 1) AS notify_pm,
                             COALESCE(notify_forums, 1) AS notify_forums
                      FROM nuke_users_flags
                      WHERE user_id = ?";
            
            return $Database->fetchRow($query, $User->id); 
            
        }
        
        /**
         * Get last newsletter dispatch date
         * @since Version 3.10.0
         * @param \Railpage\Users\User $User
         * @return \DateTime
         */
        
        public static function lastWeeklyDispatchDate(User $User) {
            
            $query = "SELECT newsletter_weekly_last FROM nuke_users_flags WHERE user_id = ?";
            
            $date = AppCore::getDatabase()->fetchOne($query, $User->id); 
            
            if ($date) {
                return new DateTime($date); 
            }
            
            return false;
            
        }
        
	}
	