<?php
	/**
	 * Photo competition!
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images;
	
	use Railpage\AppCore;
	use Railpage\Url;
	use Railpage\Module;
	use Railpage\Users\User;
	use Exception;
	use DateTime;
	
	/**
	 * Competition
	 */
	
	class Competition extends AppCore {
		
		/**
		 * Competition ID
		 * @since Version 3.9.1
		 * @var int $id
		 */
		
		public $id;
		
		/**
		 * Competition title
		 * @since Version 3.9.1
		 * @var string $title
		 */
		 
		public $title;
		
		/**
		 * The theme of this competition (eg at night, close up)
		 * @since Version 3.9.1
		 * @var string $theme
		 */
		
		public $theme;
		
		/**
		 * Competition description
		 * @since Version 3.9.1
		 * @var string $description
		 */
		
		public $description;
		
		/**
		 * Competition status
		 * @since Version 3.9.1
		 * @var int $status
		 */
		
		public $status;
		
		/**
		 * URL Slug
		 * @since Version 3.9.1
		 * @var string $slug
		 */
		
		private $slug;
		
		/**
		 * Date open
		 * @since Version 3.9.1
		 * @var \DateTime $DateOpen
		 */
		
		public $DateOpen;
		
		/**
		 * Date close
		 * @since Version 3.9.1
		 * @var \DateTime $DateClose
		 */
		
		public $DateClose;
		
		/**
		 * Author
		 * @since Version 3.9.1
		 * @var \Railpage\Users\User $Author
		 */
		
		public $Author;
		
		/**
		 * Constructor
		 * @since Version 3.9.1
		 * @param int|string $id
		 */
		
		public function __construct($id = false) {
			if (is_string($id)) {
				$query = "SELECT id FROM image_competition WHERE slug = ?";
				$id = $this->db->fetchOne($query, $id);
			}
			
			if (filter_var($id, FILTER_VALIDATE_INT)) {
				$this->id = $id;
				$this->load();
			}
		}
		
		/**
		 * Populate this object
		 * @since Version 3.9.1
		 * @return \Railpage\Images\Competition
		 */
		
		public function load() {
			
			$query = "SELECT * FROM image_competition WHERE id = ?";
			
			$row = $this->db->fetchRow($query, $this->id);
			
			$this->title = $row['title'];
			$this->description = $row['description'];
			$this->status = $row['status'];
			$this->slug = $row['slug'];
			$this->theme = $row['theme'];
			
			if ($row['date_open'] != "0000-00-00 00:00:00") {
				$this->DateOpen = new DateTime($row['date_open']);
			}
			
			if ($row['date_closed'] != "0000-00-00 00:00:00") {
				$this->DateClose = new DateTime($row['date_closed']);
			}
			
			$this->Author = new User($row['author']);
			
			$this->url = new Url(sprintf("/gallery?mode=competition&id=%d", $this->id));
			
			return $this;
		}
		
		/**
		 * Validate changes to this competition
		 * @since Version 3.9.1
		 * @return boolean
		 */
		
		private function validate() {
			if (empty($this->title)) {
				throw new Exception("Competition title cannot be empty");
			}
			
			if (empty($this->theme)) {
				throw new Exception("Competition theme cannot be empty");
			}
			
			if (empty($this->description)) {
				throw new Exception("Competition description cannot be empty");
			}
			
			if (empty($this->status) || !filter_var($this->status, FILTER_VALIDATE_INT)) {
				$this->status = Competitions::STATUS_CLOSED;
			}
			
			if (empty($this->slug)) {
				$proposal = create_slug($this->title); 
				
				$query = "SELECT id FROM image_competition WHERE slug = ?";
				$num = count($this->db->fetchAll($query, $proposal));
				
				if ($num >= 0) {
					$proposal .= $num;
				}
				
				$this->slug = $proposal;
			}
			
			if (!$this->Author instanceof User) {
				throw new Exception("Author is not set (hint: setAuthor(User))");
			}
			
			return true;
		}
		
		/**
		 * Commit changes to this competition
		 * @since Version 3.9.1
		 * @return \Railpage\Images\Competition
		 */
		
		public function commit() {
			$this->validate(); 
			
			$data = array(
				"title" => $this->title,
				"theme" => $this->theme,
				"description" => $this->description,
				"slug" => $this->slug,
				"status" => $this->status,
				"author" => $this->Author->id,
				"date_open" => $this->DateOpen instanceof DateTime ? $this->DateOpen->format("Y-m-d H:i:s") : "0000-00-00 00:00:00",
				"date_closed" => $this->DateClose instanceof DateTime ? $this->DateClose->format("Y-m-d H:i:s") : "0000-00-00 00:00:00"
			);
			
			if (filter_var($this->id, FILTER_VALIDATE_INT)) {
				$where = array(
					"id = ?" => $this->id
				);
				
				$this->db->update("image_competition", $data, $where); 
			} else {
				$this->db->insert("image_competition", $data);
				$this->id = $this->db->lastInsertId(); 
			}
			
			return $this;
		}
		
		/**
		 * Get approved photos in this competition
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getPhotos() {
			$query = "SELECT * FROM image_competition_submissions WHERE competition_id = ? AND status = ? ORDER BY date_added DESC";
			$where = array(
				$this->id,
				Competitions::PHOTO_APPROVED
			);
			
			return $this->db->fetchAll($query, $where);
		}
		
		/**
		 * Can a user vote in this competition?
		 * @since Version 3.9.1
		 * @return boolean
		 * @param \Railpage\Users\User $User
		 */
		
		public function canUserVote(User $User) {
			$query = "SELECT id FROM image_competition_votes WHERE competition_id = ? AND user_id = ?";
			$where = array(
				$this->id,
				$User->id
			);
			
			if ($this->db->fetchAll($query, $where)) {
				return false;
			}
			
			return true;
		}
		
		/**
		 * Can a user submit a photo to this competition?
		 * @since Version 3.9.1
		 * @return boolean
		 * @param \Railpage\Users\User $User
		 */
		
		public function canUserSubmitPhoto(User $User) {
			$query = "SELECT id FROM image_competition_submissions WHERE competition_id = ? AND user_id = ?";
			$where = array(
				$this->id,
				$User->id
			);
			
			if ($this->db->fetchAll($query, $where)) {
				return false;
			}
			
			return true;
		}
		
		/**
		 * Submit a photo to this competition
		 * @since Version 3.9.1
		 * @param \Railpage\Images\Image $Image
		 * @param \Railpage\Users\User $User
		 * @param array $meta
		 * @return int
		 */
		
		public function submitPhoto(Image $Image, User $User, $meta = array()) {
			$data = array(
				"competition_id" => $this->id,
				"user_id" => $User->id,
				"image_id" => $Image->id,
				"meta" => json_encode($meta),
				"date_added" => date("Y-m-d H:i:s"),
				"status" => Competitions::PHOTO_UNAPPROVED
			);
			
			$this->db->insert("image_competition_submissions", $data);
			
			return $this->db->lastInsertId();
		}
		
		/**
		 * Vote on a photo
		 * @since Version 3.9.1
		 * @param \Railpage\Users\User $User
		 * @param \Railpage\Images\Image $Image
		 * @return \Railpage\Images\Competition
		 */
		
		public function submitVote(User $User, Image $Image) {
			$data = array(
				"competition_id" => $this->id,
				"user_id" => $User->id,
				"image_id" => $Image->id,
				"date" => date("Y-m-d H:i:s"),
				"amount" => 1
			);
			
			return $this;
		}
		
		/**
		 * Check if an image is in this competition
		 * @since Version 3.9.1
		 * @param \Railpage\Images\Image $Image
		 * @return boolean
		 */
		
		public function isImageInCompetition(Image $Image) {
			$query = "SELECT id FROM image_competition_submissions WHERE competition_id = ? AND image_id = ?";
			$where = array(
				$this->id,
				$Image->id
			);
			
			if ($this->db->fetchAll($query, $where)) {
				return true;
			}
			
			return false;
		}
		
		/**
		 * Get winning photo
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getWinningPhoto() {
			$query = "SELECT * FROM image_competition_submissions WHERE image_id = (
			    SELECT image_id FROM image_competition_votes WHERE competition_id = ? AND ORDER BY COUNT(image_id) DESC LIMIT 1
			)";
			
			$where = array(
				$this->id
			);
			
			return $this->db->fetchRow($query, $where);
		}
		
		/**
		 * Get votes 
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getVotes() {
			$query = "SELECT * FROM image_competition_votes WHERE competition_id = ? ORDER BY date DESC";
			
			return $this->db->fetchAll($query, $this->id);
		}
		
		/**
		 * Get submissions pending approval
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getPendingSubmissions() {
			$query = "SELECT * FROM image_competition_submissions WHERE competition_id = ? AND status = ? ORDER BY date_added DESC";
			$where = array(
				$this->id,
				Competitions::PHOTO_UNAPPROVED
			);
			
			return $this->db->fetchAll($query, $where);
		}
		
		/**
		 * Approve a queued submission
		 * @since Version 3.9.1
		 * @return \Railpage\Images\Competition
		 * @param \Railpage\Images\Image $Image
		 */
		
		public function approveSubmission(Image $Image) {
			$data = array(
				"status" => Competitions::PHOTO_APPROVED
			);
			
			$where = array(
				"image_id" => $Image->id,
				"competition_id" => $this->id
			);
			
			$this->db->update("image_competition_submissions", $data, $where);
			
			return $this;
		}
		
		/**
		 * Reject a queued submission
		 * @since Version 3.9.1
		 * @since Version 3.9.1
		 * @return \Railpage\Images\Competition
		 * @param \Railpage\Images\Image $Image
		 */
		
		public function rejectSubmission(Image $Image) {
			$data = array(
				"status" => Competitions::PHOTO_REJECTED
			);
			
			$where = array(
				"image_id" => $Image->id,
				"competition_id" => $this->id
			);
			
			$this->db->update("image_competition_submissions", $data, $where);
			
			return $this;
		}
		
		/**
		 * Get this competition information as an associative array
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getArray() {
			$return = array(
				"id" => $this->id,
				"title" => $this->title,
				"theme" => $this->theme,
				"description" => $this->description,
				"status" => array(
					"id" => $this->status,
					"name" => $this->status == Competitions::STATUS_OPEN ? "Open" : "Closed"
				),
			);
			
			return $return;
		}
	}
	