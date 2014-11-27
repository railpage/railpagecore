<?php
	/**
	 * Suggestions for side ideas and improvements, ala Wordpress.org/ideas
	 * @since Version 3.8.7
	 * @author Michael Greenhill
	 * @package Railpage
	 */
	
	namespace Railpage\Ideas;
	
	use Railpage\Users\User;
	use Railpage\AppCore;
	use Railpage\Module;
	use Railpage\SiteEvent;
	use Railpage\Url;
	use Exception;
	use DateTime;
	
	/**
	 * Idea class
	 */
	
	class Idea extends AppCore {
		
		/**
		 * Idea ID
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Idea title
		 * @var string $title
		 */
		
		public $title;
		
		/**
		 * Idea URL slug
		 * @var string $slug
		 */
		
		private $slug;
		
		/**
		 * Idea URL
		 * @var string $url
		 */
		
		public $url;
		
		/**
		 * Description
		 * @var string $description
		 */
		
		public $description;
		
		/**
		 * Number of votes for this idea
		 * @var array $votes
		 */
		
		private $votes = array();
		
		/**
		 * Status of this idea
		 * @var int $status
		 */
		
		public $status = 1;
		
		/**
		 * Author of this idea
		 * @var \Railpage\Users\User $Author
		 */
		
		public $Author;
		
		/**
		 * Creation date
		 * @var \DateTime $Date
		 */
		
		public $Date;
		
		/**
		 * Idea category
		 * @var \Railpage\Ideas\Category $Category
		 */
		
		public $Category;
		
		/**
		 * Constructor
		 * @param int $id
		 */
		
		public function __construct($id = false) {
			
			parent::__construct();
			
			$this->Module = new Module("ideas");
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
				
				$query = "SELECT * FROM idea_ideas WHERE id = ?";
				
				if ($row = $this->db->fetchRow($query, $this->id)) {
					$this->title = $row['title'];
					$this->slug = $row['slug'];
					$this->description = $row['description'];
					$this->Author = new User($row['author']);
					$this->Date = new DateTime($row['date']);
					$this->Category = new Category($row['category_id']);
					$this->status = $row['status'];
					
					$this->url = new Url(sprintf("%s/%s", $this->Category->url, $this->slug));
				}
			} elseif (is_string($id) && strlen($id) > 1) {
				$this->slug = $id;
				
				$query = "SELECT * FROM idea_ideas WHERE slug = ?";
				
				if ($row = $this->db->fetchRow($query, $this->slug)) {
					$this->title = $row['title'];
					$this->id = $row['id'];
					$this->description = $row['description'];
					$this->Author = new User($row['author']);
					$this->Date = new DateTime($row['date']);
					$this->Category = new Category($row['category_id']);
					$this->status = $row['status'];
					
					$this->url = new Url(sprintf("%s/%s", $this->Category->url, $this->slug));
				}
			}
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$this->fetchVotes();
				$this->url->implemented = sprintf("%s?id=%d&mode=idea.implemented", $this->Module->url, $this->id);
				$this->url->vote = sprintf("%s?mode=idea.vote&id=%d", $this->Module->url, $this->id);
			}
			
		}
		
		/**
		 * Validate changes to this idea
		 * @since Version 3.8.7
		 * @return boolean
		 * @throws \Exception if $this->title is empty
		 * @throws \Exception if $this->description is empty
		 * @throws \Exception if $this->Author is not an instance of \Railpage\Users\User
		 * @throws \Exception if $this->Category is not an instance of \Railpage\Ideas\Category
		 */
		
		private function validate() {
			
			if (empty($this->title)) {
				throw new Exception("Title of the idea cannot be empty");
			}
			
			if (strlen($this->title) >= 64) {
				throw new Exception("The title for this idea is too long");
			}
			
			if (empty($this->description)) {
				throw new Exception("Description for the idea cannot be empty");
			}
			
			if (!$this->Author instanceof User) {
				throw new Exception("There must be a valid author specified for this idea");
			}
			
			if (!$this->Category instanceof Category) {
				throw new Exception("Each idea must belong to a valid category");
			}
			
			if (!$this->Date instanceof DateTime) {
				$this->Date = new DateTime;
			}
			
			if (empty($this->votes)) {
				$this->votes = 0;
			}
			
			if (empty($this->slug)) {
				$this->createSlug();
			}
			
			return true;
			
		}
		
		/**
		 * Create a URL slug
		 * @since Version 3.8.7
		 */
		
		private function createSlug() {
			
			$proposal = substr(create_slug($this->title), 0, 30);
			
			$result = $this->db->fetchAll("SELECT id FROM idea_ideas WHERE slug = ?", $proposal); 
			
			if (count($result)) {
				$proposal .= count($result);
			}
			
			$this->slug = $proposal;
			
		}
		
		/**
		 * Commit changes to this idea
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		public function commit() {
			
			$this->validate();
			
			$data = array(
				"title" => $this->title,
				"description" => $this->description,
				"slug" => $this->slug,
				"votes" => $this->votes,
				"author" => $this->Author->id,
				"category_id" => $this->Category->id,
				"date" => $this->Date->format("Y-m-d H:i:s"),
				"status" => $this->status
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("idea_ideas", $data, $where);
			} else {
				$this->db->insert("idea_ideas", $data);
				$this->id = $this->db->lastInsertId();
			
				$this->Author->wheat(5);
				
				/**
				 * Log the creation of this idea
				 */
				
				try {
					$Event = new SiteEvent;
					$Event->title = "Suggested an idea";
					$Event->user_id = $this->Author->id;
					$Event->module_name = strtolower($this->Module->name);
					$Event->key = "idea_id";
					$Event->value = $this->id;
					
					$Event->commit();
				} catch (Exception $e) {
					die($e->getMessage());
				}
				
			}
			
			return $this;
			
		}
		
		/**
		 * Update the votes for this idea
		 * @since Version 3.8.7
		 * @return $this
		 */
		
		public function fetchVotes() {
			
			$query = "SELECT * FROM idea_votes WHERE idea_id = ? ORDER BY date DESC";
			
			foreach ($this->db->fetchAll($query, $this->id) as $row) {
				$this->votes[] = array(
					"user_id" => $row['user_id'],
					"date" => new DateTime($row['date']),
					"id" => $row['id']
				);
			}
			
		}
		
		/**
		 * Get the number of votes for this idea
		 * @since Version 3.8.7
		 * @return int
		 */
		
		public function getVotes() {
			
			return count($this->votes);
			
		}
		
		/**
		 * Get the voters for this idea
		 * @since Version 3.8.7
		 * @return array
		 */
		
		public function getVoters() {
			
			return $this->votes;
			
		}
		
		/**
		 * Check if this user can vote for this idea or not
		 * @since Version 3.8.7
		 * @param \Railpage\Users\User $User
		 * @return boolean
		 */
		
		public function canVote(User $User) {
			
			if ($this->status != 1) {
				return false;
			}
			
			if ($User->id === 0 || $User->guest === true) {
				return false;
			}
			
			if ($User->id == $this->Author->id) {
				return false;
			}
			
			foreach ($this->votes as $vote) {
				if ($vote['user_id'] == $User->id) {
					return false;
				}
			}
			
			return true;
			
		}
		
		/**
		 * Add a vote for this idea
		 * @param \Railpage\Users\User $User
		 * @return $this
		 */
		
		public function vote(User $User) {
			
			if (!$this->canVote($User)) {
				throw new Exception("We couldn't add your vote to this idea. You must be logged in and not already voted for this idea");
			}
			
			$Date = new DateTime;
			
			$data = array(
				"idea_id" => $this->id,
				"user_id" => $User->id,
				"date" => $Date->format("Y-m-d H:i:s")
			);
			
			$this->db->insert("idea_votes", $data);
			
			$this->fetchVotes();
			
			$User->wheat();
			
			return $this;
			
		}
	}
?>