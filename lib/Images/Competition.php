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
	use stdClass;
	
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
		 * Meta config array (various options I can't be bothered splitting into separate vars
		 * @since Version 3.9.1
		 * @var array $meta
		 */
		
		public $meta;
		
		/**
		 * URL Slug
		 * @since Version 3.9.1
		 * @var string $slug
		 */
		
		private $slug;
		
		/**
		 * Submissions Date open
		 * @since Version 3.9.1
		 * @var \DateTime $SubmissionsDateOpen
		 */
		
		public $SubmissionsDateOpen;
		
		/**
		 * Submissions Date close
		 * @since Version 3.9.1
		 * @var \DateTime $SubmissionsDateClose
		 */
		
		public $SubmissionsDateClose;
		
		/**
		 * Date open
		 * @since Version 3.9.1
		 * @var \DateTime $VotingDateOpen
		 */
		
		public $VotingDateOpen;
		
		/**
		 * Date close
		 * @since Version 3.9.1
		 * @var \DateTime $VotingDateClose
		 */
		
		public $VotingDateClose;
		
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
			parent::__construct(); 
			
			if (is_string($id) && !filter_var($id, FILTER_VALIDATE_INT)) {
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
			$this->meta = json_decode($row['meta'], true);
			
			if ($row['voting_date_open'] != "0000-00-00 00:00:00") {
				$this->VotingDateOpen = new DateTime($row['voting_date_open']);
			}
			
			if ($row['voting_date_close'] != "0000-00-00 00:00:00") {
				$this->VotingDateClose = new DateTime($row['voting_date_close']);
			}
			
			if ($row['submissions_date_open'] != "0000-00-00 00:00:00") {
				$this->SubmissionsDateOpen = new DateTime($row['submissions_date_open']);
			}
			
			if ($row['submissions_date_close'] != "0000-00-00 00:00:00") {
				$this->SubmissionsDateClose = new DateTime($row['submissions_date_close']);
			}
			
			if ($this->VotingDateClose->format("H:i:s") == "00:00:00") {
				$this->VotingDateClose = new DateTime($this->VotingDateClose->format("Y-m-d 23:59:59"));
			}
			
			if ($this->SubmissionsDateClose->format("H:i:s") == "00:00:00") {
				$this->SubmissionsDateClose = new DateTime($this->SubmissionsDateClose->format("Y-m-d 23:59:59"));
			}
			
			$this->Author = new User($row['author']);
			
			$this->url = new Url(sprintf("/gallery/comp/%s", $this->slug));
			$this->url->submitphoto = sprintf("%s/submit", $this->url->url);
			$this->url->edit = sprintf("/gallery?mode=competitions.new&id=%d", $this->id);
			$this->url->pending = sprintf("/gallery?mode=competition.pendingphotos&id=%d", $this->id);
			
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
				
				if ($num > 0) {
					$proposal .= $num;
				}
				
				$this->slug = $proposal;
			}
			
			if (!$this->Author instanceof User) {
				throw new Exception("Author is not set (hint: setAuthor(User))");
			}
			
			if (!$this->VotingDateOpen instanceof DateTime) {
				throw new Exception("VotingDateOpen must be an instance of DateTime");
			}
			
			if (!$this->VotingDateClose instanceof DateTime) {
				throw new Exception("VotingDateClose must be an instance of DateTime");
			}
			
			if (!$this->SubmissionsDateOpen instanceof DateTime) {
				throw new Exception("SubmissionsDateOpen must be an instance of DateTime");
			}
			
			if (!$this->SubmissionsDateClose instanceof DateTime) {
				throw new Exception("SubmissionsDateClose must be an instance of DateTime");
			}
			
			if ($this->VotingDateOpen > $this->VotingDateClose) {
				throw new Exception("VotingDateOpen is greater than VotingDateClose");
			}
			
			if ($this->SubmissionsDateOpen > $this->SubmissionsDateClose) {
				throw new Exception("SubmissionsDateOpen is greater than SubmissionsDateClose");
			}
			
			if ($this->SubmissionsDateClose > $this->VotingDateOpen) {
				throw new Exception("SubmissionsDateClose is greater than VotingDateOpen");
			}
			
			if ($this->SubmissionsDateOpen <= new DateTime) {
				$this->status = Competitions::STATUS_OPEN;
			}
			
			if ($this->VotingDateClose <= new DateTime) {
				$this->status = Competitions::STATUS_CLOSED;
			}
			
			if ($this->VotingDateClose->format("H:i:s") == "00:00:00") {
				$this->VotingDateClose = new DateTime($this->VotingDateClose->format("Y-m-d 23:59:59"));
			}
			
			if ($this->SubmissionsDateClose->format("H:i:s") == "00:00:00") {
				$this->SubmissionsDateClose = new DateTime($this->SubmissionsDateClose->format("Y-m-d 23:59:59"));
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
				"voting_date_open" => $this->VotingDateOpen instanceof DateTime ? $this->VotingDateOpen->format("Y-m-d H:i:s") : "0000-00-00 00:00:00",
				"voting_date_close" => $this->VotingDateClose instanceof DateTime ? $this->VotingDateClose->format("Y-m-d H:i:s") : "0000-00-00 00:00:00",
				"submissions_date_open" => $this->SubmissionsDateOpen instanceof DateTime ? $this->SubmissionsDateOpen->format("Y-m-d H:i:s") : "0000-00-00 00:00:00",
				"submissions_date_close" => $this->SubmissionsDateClose instanceof DateTime ? $this->SubmissionsDateClose->format("Y-m-d H:i:s") : "0000-00-00 00:00:00",
				"meta" => json_encode($this->meta)
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
			$params = array(
				$this->id,
				Competitions::PHOTO_APPROVED
			);
			
			$photos = array(); 
			
			foreach ($this->db->fetchAll($query, $params) as $row) {
				/*
				$Author = new User($row['user_id']);
				$Image = new Image($row['image_id']);
				$Date = new DateTime($row['date_added']); 
				
				$return = new stdClass;
				$return->id = $row['id']; 
				$return->Author = $Author;
				$return->Image = $Image;
				$return->DateAdded = $Date;
				$return->Meta = json_decode($row['meta'], true);
				$return->url = new Url(sprintf("%s/%d", $this->url->url, $Image->id));
				$return->url->vote = sprintf("%s/vote", $return->url);
				
				yield $return;
				*/
				
				yield $this->getPhoto($row);
			}
		}
		
		/**
		 * Get a single photo from this competition
		 * @since Version 3.9.1
		 * @return stdClass
		 * @param array|\Railpage\Images\Image $image
		 */
		
		public function getPhoto($image) {
			if ($image instanceof Image) {
				$query = "SELECT * FROM image_competition_submissions WHERE competition_id = ? AND image_id = ? ORDER BY date_added DESC";
				$params = array(
					$this->id,
					$image->id
				);
				
				$row = $this->db->fetchRow($query, $params);
			} else {
				$row = $image;
			}
			
			$Photo = new stdClass;
			$Photo->id = $row['id'];
			$Photo->Author = new User($row['user_id']);
			$Photo->Image = new Image($row['image_id']);
			$Photo->DateAdded = new DateTime($row['date_added']);
			$Photo->Meta = json_decode($row['meta'], true);
			$Photo->url = new Url(sprintf("%s/%d", $this->url->url, $Photo->Image->id));
			$Photo->url->vote = sprintf("%s/vote", $Photo->url);
			
			return $Photo;
		}
		
		/**
		 * Check if this image was submitted by this user
		 * @since Version 3.9.1
		 * @param \Railpage\Users\User $User
		 * @param \Railpage\Images\Image $Image
		 * @return boolean
		 */
		
		public function isImageOwnedBy(User $User, Image $Image) {
			$query = "SELECT id FROM image_competition_submissions WHERE competition_id = ? AND user_id = ? AND image_id = ?";
			
			$params = array(
				$this->id,
				$User->id,
				$Image->id
			);
			
			$rs = $this->db->fetchAll($query, $params); 
			
			if (count($rs)) {
				return true;
			}
			
			return false;
		}
		
		/**
		 * Get the number of votes made by this user
		 * @since Version 3.9.1
		 * @param \Railpage\Users\User $User
		 * @return array
		 */
		
		public function getNumVotesForUser(User $User) {
			$query = "SELECT id FROM image_competition_votes WHERE competition_id = ? AND user_id = ?";
			
			$params = array(
				$this->id,
				$User->id
			);
			
			$rs = $this->db->fetchAll($query, $params); 
			
			$max_votes = isset($this->meta['maxvotes']) && filter_var($this->meta['maxvotes'], FILTER_VALIDATE_INT) ? $this->meta['maxvotes'] : Competitions::MAX_VOTES_PER_USER;
			
			$return = array(
				"cast" => count($rs),
				"free" => $max_votes - count($rs)
			);
			
			return $return;
		}
		
		/**
		 * Can a user vote in this competition?
		 * @since Version 3.9.1
		 * @return boolean
		 * @param \Railpage\Users\User $User
		 */
		
		public function canUserVote(User $User, $Image = false) {
			$now = new DateTime;
			
			if (!($this->VotingDateOpen instanceof DateTime && $this->VotingDateOpen <= $now) || 
				!($this->VotingDateClose instanceof DateTime && $this->VotingDateClose >= $now)) {
					return false;
			}
			
			$query = "SELECT id FROM image_competition_votes WHERE competition_id = ? AND user_id = ?";
			$params = array(
				$this->id,
				$User->id
			);
			
			if ($Image instanceof Image) {
				if ($this->isImageOwnedBy($User, $Image)) {
					return false;
				}
				
				$query .= " AND image_id = ?";
				$params[] = $Image->id;
			}
			
			$rs = $this->db->fetchAll($query, $params);
			
			if ($Image instanceof Image) {
				if ($rs != false || count($rs)) {
					return false;
				}
			} else {
				if (isset($rs[0]) && isset($rs[0]['user_id']) && $rs[0]['user_id'] == $User->id) {
					return false;
				}
				
				$max_votes = isset($this->meta['maxvotes']) && filter_var($this->meta['maxvotes'], FILTER_VALIDATE_INT) ? $this->meta['maxvotes'] : Competitions::MAX_VOTES_PER_USER;
				
				if (count($rs) >= $max_votes) {
					return false;
				}
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
			
			if (!filter_var($User->id, FILTER_VALIDATE_INT)) {
				return false;
			}
			
			$now = new DateTime;
			
			if (!($this->SubmissionsDateOpen instanceof DateTime && $this->SubmissionsDateOpen <= $now) || 
				!($this->SubmissionsDateClose instanceof DateTime && $this->SubmissionsDateClose >= $now)) {
					return false;
			}
			
			$query = "SELECT id FROM image_competition_submissions WHERE competition_id = ? AND user_id = ? AND status != ?";
			$where = array(
				$this->id,
				$User->id,
				Competitions::PHOTO_REJECTED
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
			
			$this->db->insert("image_competition_votes", $data);
			
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
			if ($this->VotingDateClose < new DateTime) {
				$query = "SELECT * FROM image_competition_submissions WHERE image_id = (
					SELECT image_id FROM image_competition_votes WHERE competition_id = ? ORDER BY COUNT(image_id) DESC LIMIT 1
				)";
				
				$where = array(
					$this->id
				);
				
				return $this->getPhoto($this->db->fetchRow($query, $where));
			} else {
				return false;
			}
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
			
			foreach ($this->db->fetchAll($query, $where) as $row) {
				$Author = new User($row['user_id']);
				$Image = new Image($row['image_id']);
				$Date = new DateTime($row['date_added']); 
				
				$return = new stdClass;
				$return->id = $row['id']; 
				$return->Author = $Author;
				$return->Image = $Image;
				$return->DateAdded = $Date;
				$return->Meta = json_decode($row['meta'], true);
				$return->url = new Url(sprintf("/gallery?mode=competition.image&comp_id=%d&image_id=%d", $this->id, $Image->id));
				$return->url->approve = sprintf("/gallery?mode=competition.photo.manage&comp_id=%d&image_id=%d&action=approve", $this->id, $Image->id);
				$return->url->reject = sprintf("/gallery?mode=competition.photo.manage&comp_id=%d&image_id=%d&action=reject", $this->id, $Image->id);
				
				yield $return;
			}
		}
		
		/**
		 * Approve a queued submission
		 * @since Version 3.9.1
		 * @return \Railpage\Images\Competition
		 * @param \Railpage\Images\Image $Image
		 */
		
		public function approveSubmission(Image $Image) {
			if (!filter_var($Image->id, FILTER_VALIDATE_INT)) {
				throw new Exception("The supplied image appears to be invalid...");
			}
			
			$data = array(
				"status" => Competitions::PHOTO_APPROVED
			);
			
			$where = array(
				"image_id = ?" => $Image->id,
				"competition_id = ?" => $this->id
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
			if (!filter_var($Image->id, FILTER_VALIDATE_INT)) {
				throw new Exception("The supplied image appears to be invalid...");
			}
			
			$data = array(
				"status" => Competitions::PHOTO_REJECTED
			);
			
			$where = array(
				"image_id = ?" => $Image->id,
				"competition_id = ?" => $this->id
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
			$now = new DateTime;
			
			$voting_open = true;
			
			if (!($this->VotingDateOpen instanceof DateTime && $this->VotingDateOpen <= $now) || 
				!($this->VotingDateClose instanceof DateTime && $this->VotingDateClose >= $now)) {
					$voting_open = false;
			}
			
			$submissions_open = true;
			
			if (!($this->SubmissionsDateOpen instanceof DateTime && $this->SubmissionsDateOpen <= $now) || 
				!($this->SubmissionsDateClose instanceof DateTime && $this->SubmissionsDateClose >= $now)) {
					$submissions_open = false;
			}
			
			$return = array(
				"id" => $this->id,
				"title" => $this->title,
				"theme" => $this->theme,
				"description" => $this->description,
				"status" => array(
					"id" => $this->status,
					"name" => $this->status == Competitions::STATUS_OPEN ? "Open" : "Closed"
				),
				"url" => $this->url->getURLs(),
				"voting" => array(
					"status" => $voting_open,
					"open" => array(
						"absolute" => $this->VotingDateOpen->format("Y-m-d H:i:s"),
						"formatted" => $this->VotingDateOpen->format("F jS"),
						"us" => $this->VotingDateOpen->format("m/d/Y")
					),
					"close" => array(
						"absolute" => $this->VotingDateClose->format("Y-m-d H:i:s"),
						"formatted" => $this->VotingDateClose->format("F jS"),
						"us" => $this->VotingDateClose->format("m/d/Y")
					)
				),
				"submissions" => array(
					"status" => $submissions_open,
					"open" => array(
						"absolute" => $this->SubmissionsDateOpen->format("Y-m-d H:i:s"),
						"formatted" => $this->SubmissionsDateOpen->format("F jS"),
						"us" => $this->SubmissionsDateOpen->format("m/d/Y")
					),
					"close" => array(
						"absolute" => $this->SubmissionsDateClose->format("Y-m-d H:i:s"),
						"formatted" => $this->SubmissionsDateClose->format("F jS"),
						"us" => $this->SubmissionsDateClose->format("m/d/Y")
					)
				),
				"meta" => $this->meta
			);
			
			return $return;
		}
		
		/**
		 * Get author of a submitted photo
		 * @since Version 3.9.1
		 * @return \Railpage\Users\User
		 * @param \Railpage\Images\Image $Image
		 */
		
		public function getPhotoAuthor(Image $Image) {
			$query = "SELECT user_id FROM image_competition_submissions WHERE competition_id = ? AND image_id = ?";
			
			$params = array(
				$this->id,
				$Image->id
			);
			
			$user_id = $this->db->fetchOne($query, $params);
			
			return new User($user_id);
		}
		
		/**
		 * Get photo context
		 * @since Version 3.9.1
		 * @return array
		 * @param \Railpage\Images\Image $Image
		 */
		
		public function getPhotoContext(Image $Image) {
			$query = "SELECT *, 0 AS current FROM image_competition_submissions WHERE competition_id = ? AND status = ? ORDER BY date_added ASC";
			$where = array(
				$this->id,
				Competitions::PHOTO_APPROVED
			);
			
			$photos = $this->db->fetchAll($query, $where);
			
			/**
			 * Loop through the array once until we find the provided image. Add photos to a temporary array, and then we slice it to return the last 4 entries to the array
			 */
			
			$return = array(); 
			$split = 0;
			$tmp = array(); 
			
			foreach ($photos as $key => $data) {
				
				if ($data['image_id'] == $Image->id) {
					$split = $key;
					break;
				}
				
				$tmp[] = $data; 
			}
			
			$return = array_slice($tmp, -4);
			
			/**
			 * Add the current photo to the array
			 */
			
			$return[] = array_merge($photos[$split], array("current" => true));
			
			/**
			 * Loop through the array starting at $split
			 */
			
			$tmp = array(); 
			
			foreach ($photos as $key => $data) {
				if ($key >= $split + 1) {
					$tmp[] = $data;
				}
			}
			
			$return = array_merge($return, array_slice($tmp, 0, 4));
			
			/**
			 * Loop through the context and return a stdClass photo
			 */
			
			foreach ($return as $data) {
				$Photo = new stdClass;
				$Photo->id = $data['id']; 
				$Photo->Author = new User($data['user_id']);
				$Photo->Image = new Image($data['image_id']);
				$Photo->DateAdded = new DateTime($data['date_added']);
				$Photo->Meta = json_decode($data['meta'], true);
				$Photo->url = new Url(sprintf("%s/%d", $this->url->url, $Photo->Image->id));
				$Photo->url->vote = sprintf("%s/vote", $Photo->url);
				$Photo->current = (bool) $data['current'];
				
				yield $Photo;
			}
		}
	}
	
	