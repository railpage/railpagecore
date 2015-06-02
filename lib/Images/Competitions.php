<?php
	/**
	 * Photo competitions
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images;
	
	use Railpage\Users\User;
	use Railpage\Config\Base as Config;
	use Railpage\AppCore;
	use Railpage\Module;
	use Exception;
	use DateTime;
	use DateInterval;
	use DateTimeZone;
	
	/**
	 * Competitions
	 */
	
	class Competitions extends AppCore {
		
		/**
		 * Competition status: open
		 * @since Version 3.9.1
		 * @const int STATUS_OPEN
		 */
		
		const STATUS_OPEN = 0;
		
		/**
		 * Competition status: closed to entries
		 * @since Version 3.9.1
		 * @const int STATUS_CLOSED
		 */
		
		const STATUS_CLOSED = 1;
		
		/**
		 * Competition status: Future event (for UI purposes only)
		 * @since Version 3.9.1
		 * @const int STATUS_FUTURE
		 */
		
		const STATUS_FUTURE = 99;
		
		/**
		 * Photo submission: approved
		 * @since Version 3.9.1
		 * @const int PHOTO_APPROVED
		 */
		
		const PHOTO_APPROVED = 1;
		
		/**
		 * Photo submission: unapproved
		 * @since Version 3.9.1
		 * @const int PHOTO_UNAPPROVED
		 */
		
		const PHOTO_UNAPPROVED = 0;
		
		/**
		 * Photo submission: rejected
		 * @since Version 3.9.1
		 * @const int PHOTO_REJECTED
		 */
		
		const PHOTO_REJECTED = 2;
		
		/**
		 * Default number of maximum votes per user per competition
		 * @since Version 3.9.1
		 * @const int MAX_VOTES_PER_USER
		 */
		
		const MAX_VOTES_PER_USER = 5;
		
		/**
		 * Constructor
		 * @since Version 3.9.1
		 */
		
		public function __construct() {
			parent::__construct(); 
			$this->Module = new Module("images.competitions"); 
		}
		
		/**
		 * Get the list of competitions, optionally filter by status
		 * @since Version 3.9.1
		 * @param int $status
		 * @return array
		 */
		
		public function getCompetitions($status = NULL) {
			$query = "SELECT id FROM image_competition";
			$where = array(); 
			
			if (!is_null($status)) {
				if ($status == self::STATUS_OPEN) {
					$query .= " WHERE status = ?";
					$where[] = $status;
				} elseif ($status == self::STATUS_CLOSED) {
					$query .= " WHERE status = ? AND voting_date_close < ?";
					$where[] = $status;
					$where[] = (new DateTime)->format("Y-m-d H:i:s");
				}
			}
			
			$comps = array(); 
			
			foreach ($this->db->fetchAll($query, $where) as $row) {
				$Competition = new Competition($row['id']);
				$comps[] = $Competition->getArray(); 
			}
			
			return $comps;
		}
		
		/**
		 * Get an associative array of users by submitted photos, votes and competitions won
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getLeagueTable() {
			/**
			 SELECT 
				s.user_id, u.username, c.title AS comp_title, c.id AS comp_id, c.slug AS comp_slug, 
				s.image_id, i.meta AS image_meta,
				(SELECT COUNT(v.id) AS votes FROM image_competition_votes AS v WHERE v.competition_id = c.id AND v.image_id = s.image_id) AS votes
				FROM image_competition_submissions AS s
				LEFT JOIN nuke_users AS u ON s.user_id = u.user_id
				LEFT JOIN image_competition AS c ON c.id = s.competition_id
				LEFT JOIN image AS i ON s.image_id = i.id
				ORDER BY votes DESC
			*/
			
			$query = "SELECT 
				u.user_id, u.username, 
				(SELECT COUNT(s.id) AS submissions FROM image_competition_submissions AS s WHERE s.user_id = u.user_id AND s.status = 1) AS submissions,
				(SELECT COUNT(v.id) AS votes FROM image_competition_votes AS v WHERE v.image_id = s.image_id AND s.user_id = u.user_id AND s.status = 1) AS votes,
				IFNULL((SELECT wins FROM (
					SELECT COUNT(wins.user_id) AS wins, wins.user_id FROM (
						SELECT winners.user_id, winners.competition_id FROM (
							SELECT COUNT(v.id) AS votes, sub.user_id, v.competition_id
								FROM image_competition_votes AS v 
								LEFT JOIN image_competition_submissions AS sub ON v.image_id = sub.image_id
								LEFT JOIN image_competition AS c ON sub.competition_id = c.id 
								WHERE c.status = 1 
								GROUP BY v.competition_id, sub.user_id
								ORDER BY v.competition_id, votes DESC
							) AS winners 
							GROUP BY winners.competition_id
						) AS wins
						GROUP BY wins.user_id
					) AS wins
					WHERE user_id = s.user_id
				), 0) AS wins
				FROM image_competition_submissions AS s
				LEFT JOIN nuke_users AS u ON s.user_id = u.user_id
				LEFT JOIN image_competition AS c ON c.id = s.competition_id
				GROUP BY user_id
				ORDER BY votes DESC, submissions DESC, username ASC";
			
			$return = array(); 
			
			foreach ($this->db->fetchAll($query) as $row) {
				if (!($row['submissions'] == 0 && $row['votes'] == 0)) {
					$return[] = $row;
				}
			}
			
			return $return;
		}
		
		/**
		 * Get competition theme suggestions
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getSuggestedThemes() {
			$Config = new Config;
			
			$themes = $Config->get("image.competition.suggestedthemes");
			
			return $themes === false ? array() : json_decode($themes, true); 
		}
		
		/**
		 * Suggest a theme to add
		 * @since Version 3.9.1
		 * @return \Railpage\Images\Competitions
		 * @param string $theme The short descriptive text for the theme (eg "At night", "Close up", etc)
		 * @param boolean $winner True/false flag indicating if this theme has been suggested by a competition winner
		 */
		
		public function suggestTheme($theme, $winner = false) {
			if (!$this->Author instanceof User) {
				throw new Exception("You have not set the author of this theme (hint: Competitions::setAuthor()");
			}
			
			if (empty($theme)) {
				throw new Exception("You haven't entered any text...");
			}
			
			if (function_exists("format_topictitle")) {
				$theme = format_topictitle($theme);
			}
			
			$themes = $this->getSuggestedThemes(); 
			
			$themes[] = array(
				"user" => array(
					"id" => $this->Author->id,
					"username" => $this->Author->username
				),
				"theme" => $theme,
				"winner" => $winner
			);
			
			$Config = new Config;
			$Config->set("image.competition.suggestedthemes", json_encode($themes), "Photo competition themes"); 
			
			return $this;
		}
		
		/**
		 * Auto generate a competition for the next calendar month
		 * @since Version 3.9.1
		 * @return \Railpage\Images\Competition
		 */
		
		public function autoPopulateNextComp() {
			$month = new DateTime("first day of next month"); 
			
			$title = $month->format("F Y");
			
			$Competition = new Competition($title);
			
			while (filter_var($Competition->id, FILTER_VALIDATE_INT)) {
				$month->add(new DateInterval("P1M"));
				$title = $month->format("F Y");
				$Competition = new Competition($title);
			}
			
			/**
			 * If the comp ID isn't a valid int assume we need to create a new comp
			 */
			
			if (!filter_var($Competition->id, FILTER_VALIDATE_INT)) {
				$Competition->title = $title;
				$Competition->SubmissionsDateOpen = clone $month;
				$Competition->SubmissionsDateClose = clone $month;
				$Competition->SubmissionsDateClose->add(new DateInterval("P14D"));
				$Competition->VotingDateOpen = clone $month;
				$Competition->VotingDateOpen->add(new DateInterval("P15D"));
				$Competition->VotingDateClose = clone $month;
				$Competition->VotingDateClose->add(new DateInterval("P1M"))->sub(new DateInterval("P1D"));
				$Competition->meta = array(
					"maxvotes" => self::MAX_VOTES_PER_USER
				);
				
				/**
				 * Get a suggested theme
				 */
				
				$themes = $this->getSuggestedThemes();
				$themes = array_reverse($themes, true);
				
				foreach ($themes as $theme) {
					if (!isset($theme['used']) || $theme['used'] == false) {
						if (function_exists("format_topictitle")) {
							$theme['theme'] = format_topictitle($theme['theme']);
						}
						
						$Competition->theme = $theme['theme']; #sprintf("%s (suggested by %s)", $theme['theme'], $theme['user']['username']);
						
						if ($theme['winner'] == true) {
							break;
						}
					}
				}
			}
			
			return $Competition;
		}
		
		/**
		 * Get contestants in previous competitions
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getPreviousContestants() {
			
			$query = "SELECT s.user_id, u.username, u.user_email AS contact_email, concat('/user/', s.user_id) AS url 
				FROM image_competition_submissions AS s 
					LEFT JOIN nuke_users AS u ON s.user_id = u.user_id 
				GROUP BY user_id";
			
			return $this->db->fetchAll($query);

		}
	}