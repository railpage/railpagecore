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
		 * Status: Implemented
		 * @const STATUS_IMPLEMENTED
		 */
		
		const STATUS_IMPLEMENTED = 3;
		
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
			
			$query = "SELECT count(v.idea_id) AS num, v.idea_id FROM idea_votes AS v JOIN idea_ideas AS i ON v.idea_id = i.id GROUP BY v.idea_id ORDER BY i.title LIMIT 0, ?";
			
			foreach ($this->db->fetchAll($query, $num) as $row) {
				yield new Idea($row['idea_id']);
			}
			
		}
	}
?>