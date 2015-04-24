<?php
	/**
	 * Suggestions for side ideas and improvements, ala Wordpress.org/ideas
	 * @since Version 3.8.7
	 * @author Michael Greenhill
	 * @package Railpage
	 */
	
	namespace Railpage\Ideas;
	
	use Railpage\AppCore;
	use Railpage\Module;
	use Railpage\Users\User;
	use Exception;
	use DateTime;
	
	/**
	 * Ideas class
	 */
	
	class Ideas extends AppCore {
		
		/**
		 * Status: Deleted
		 * @const STATUS_DELETED
		 */
		
		const STATUS_DELETED = 0;
		
		/**
		 * Status: Active
		 * @const STATUS_ACTIVE
		 */
		
		const STATUS_ACTIVE = 1;
		
		/**
		 * Status: Will not implement
		 * @const STATUS_NO
		 */
		
		const STATUS_NO = 2;
		
		/**
		 * Status: Implemented
		 * @const STATUS_IMPLEMENTED
		 */
		
		const STATUS_IMPLEMENTED = 3;
		
		/**
		 * Status: In progress
		 * @const STATUS_INPROGRESS
		 */
		
		const STATUS_INPROGRESS = 4;
		
		/**
		 * Status: Under consideration
		 * @const STATUS_UNDERCONSIDERATION
		 */
		
		const STATUS_UNDERCONSIDERATION = 5;
		
		/**
		 * Constructor
		 */
		
		public function __construct() {
			
			parent::__construct();
			
			$this->Module = new Module("ideas");
			
		}
		
		/**
		 * Get a list of categories
		 * @since Version 3.8.7
		 * @yield \Railpage\Ideas\Category
		 */
		
		public function getCategories() {
			
			$query = "SELECT id FROM idea_categories ORDER BY title";
			
			foreach ($this->db->fetchAll($query) as $row) {
				yield new Category($row['id']);
			}
			
		}
		
		/**
		 * Get a list of new ideas
		 * @since Version 3.8.7
		 * @yield \Railpage\Ideas\Idea
		 * @param int $num Number of ideas to return
		 */
		
		public function getNewIdeas($num = 10) {
			
			$query = "SELECT id FROM idea_ideas ORDER BY date DESC LIMIT 0, ?";
			
			foreach ($this->db->fetchAll($query, $num) as $row) {
				yield new Idea($row['id']);
			}
			
		}
		
		/**
		 * Get ideas sorted by most votes
		 * @since Version 3.8.7
		 * @yield \Railpage\Ideas\Idea
		 * @param int $num Number of ideas to return
		 */
		
		public function getMostVoted($num = 10) {
			
			$query = "SELECT count(v.idea_id) AS num, v.idea_id FROM idea_votes AS v JOIN idea_ideas AS i ON v.idea_id = i.id GROUP BY v.idea_id ORDER BY num DESC LIMIT 0, ?";
			
			foreach ($this->db->fetchAll($query, $num) as $row) {
				yield new Idea($row['idea_id']);
			}
			
		}
		
		/**
		 * Get ideas by user
		 * @since Version 3.9.1
		 * @return array
		 * @param int $page Page number
		 * @param int $items_per_page Number of results to return
		 */
		
		public function getIdeasByUser($page = 1, $items_per_page = 25) {
			if (!$this->User instanceof User) {
				throw new Exception("You must set a valid user before you can find ideas created by a user");
			}
			
			$query = "SELECT SQL_CALC_FOUND_ROWS id, title, date FROM idea_ideas WHERE author = ? ORDER BY date DESC LIMIT ?, ?";
			
			$params = array(
				$this->User->id,
				($page - 1) * $items_per_page, 
				$items_per_page 
			);
			
			$return = array(
				"total" => 0,
				"page" => $page,
				"perpage" => $items_per_page,
				"ideas" => array()
			);
			
			if ($result = $this->db->fetchAll($query, $params)) {
				$return['total'] = $this->db_readonly->fetchOne("SELECT FOUND_ROWS() AS total"); 
				
				foreach ($result as $row) {
					$Idea = new Idea($row['id']);
					
					$return['ideas'][] = $Idea->getArray();
				}
			}
			
			return $return;
		}
		
		/**
		 * Get the idea status with fancy formatting and stuff
		 * @since Version 3.9.1
		 * @param int $status_id
		 * @return array
		 */
		
		public static function getStatusDescription($status_id = false) {
			if (filter_var($status_id, FILTER_VALIDATE_INT)) {
				$badge = '<span class="label label-%s"><span class="glyphicon glyphicon-%s" style="top:2px;"></span>&nbsp;&nbsp;%s</span>';
				
				switch ($status_id) {
					case self::STATUS_DELETED :
						$text = "Deleted";
						$badge = sprintf($badge, "danger", "remove", $text);
						break;
						
					case self::STATUS_ACTIVE :
						$text = "";
						$badge = "";
						break;
						
					case self::STATUS_NO :
						$text = "Will not implement";
						$badge = sprintf($badge, "warning", "remove", $text);
						break;
						
					case self::STATUS_IMPLEMENTED :
						$text = "Implemented!";
						$badge = sprintf($badge, "success", "ok", $text);
						break;
						
					case self::STATUS_INPROGRESS :
						$text = "In progress";
						$badge = sprintf($badge, "info", "wrench", $text);
						break;
						
					case self::STATUS_UNDERCONSIDERATION :
						$text = "Under consideration";
						$badge = sprintf($badge, "info", "tasks", $text);
						break;
					
					default :
						$text = "";
						$badge = "";
						
				}
				
				return array(
					"id" => $status_id, 
					"text" => $text,
					"badge" => $badge
				);
			}
		}
	}
	
	