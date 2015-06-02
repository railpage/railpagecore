<?php
	/**
	 * Utility class for user timeline
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Users\Timeline\Utility;
	
	use stdClass;
	use Exception;
	use DateTime;
	use DateTimeZone;
	
	use Railpage\AppCore;
	use Railpage\BanControl\BanControl;
	use Railpage\Module;
	use Railpage\Url;
	use Railpage\Forums\Thread;
	use Railpage\Forums\Forum;
	use Railpage\Forums\Forums;
	use Railpage\Forums\Index;
	
	class Grammar {
		
		/**
		 * Process the "to" preposition
		 * @since Version 3.9.1
		 * @param array $row
		 * @return string
		 */
		
		static public function getPrepositionTo($row) {
			if (preg_match("@(added|add|linked)@Di", $row['event']['action']) && preg_match("@(locos)@Di", $row['module'])) {
				$row['event']['preposition'] = "to";
			}
			
			return $row['event']['preposition'];
		}
		
		/**
		 * Process the "from" preposition
		 * @since Version 3.9.1
		 * @param array $row
		 * @return string
		 */
		
		static public function getPrepositionFrom($row) {
			if (preg_match("@(removed)@Di", $row['title'])) {
				$row['event']['preposition'] = "from";
			}
			
			if (preg_match("@(unlinked)@Di", $row['title']) && preg_match("@(locos)@Di", $row['module'])) {
				$row['event']['preposition'] = "from";
			}
			
			return $row['event']['preposition'];
		}
		
		/**
		 * Process the "of" preposition
		 * @since Version 3.9.1
		 * @param array $row
		 * @return string
		 */
		
		static public function getPrepositionOf($row) {
			if (preg_match("@(correction|re-ordered|sorted|sort|tagged|tag|changed|modified)@Di", $row['title'])) {
				$row['event']['preposition'] = "of";
			}
			
			return $row['event']['preposition'];
		}
		
		/**
		 * Process the "from" preposition
		 * @since Version 3.9.1
		 * @param array $row
		 * @return string
		 */
		
		static public function getPrepositionIn($row) {
			if (preg_match("@(added|add|edited|edit|deleted|delete|rejected|reject|created|create)@Di", $row['title']) && preg_match("@(forums|news)@Di", $row['module'])) {
				$row['event']['preposition'] = "in";
			}
			
			return $row['event']['preposition'];
		}
		
		/**
		 * Process the "action"
		 * @since Version 3.9.1
		 * @param array $row
		 * @return string
		 */
		
		static public function getAction($row) {
			if (preg_match("@(favourited|suggested|ignored|accepted|closed|commented|removed|re-ordered|edited|edit|added|add|sorted|sort|deleted|delete|rejected|reject|tagged|tag|changed|modified|linked|created|create)@Di", $row['title'], $matches)) {
				$row['event']['action'] = strtolower($matches[1]);
			}
			
			return $row['event']['action'];
		}
		
		/**
		 * Process the "object"
		 * @since Version 3.9.1
		 * @param array $row
		 * @return string
		 */
		
		static public function getObject($row) {
			if (preg_match("@(idea|suggestion|correction|sighting|date|post|thread|digital asset|loco photo|loco class|loco|class|location|grouping|owners|owner|operators|operator|article|story|topic|railcam photo|photo|railcam|download|event|calendar|image)@Di", $row['title'], $matches)) {
				$row['event']['object'] = strtolower($matches[1]);
			}
			
			return $row['event']['object'];
		}
		
		/**
		 * Process the "article" where the proposition is "of" or "in"
		 * @since Version 3.9.1
		 * @param arrray $row
		 * @return string
		 */
		
		static public function getArticle_OfIn($row) {
			if ($row['event']['preposition'] == "of") {
				$row['event']['article'] = "the";
			}
			
			if ($row['event']['preposition'] == "in") {
				$row['event']['article'] = "a";
			}
			
			return $row['event']['article'];
		}
		
		/**
		 * Process the "article" where the proposition is "an" or "a"
		 * @since Version 3.9.1
		 * @param arrray $row
		 * @return string
		 */
		
		static public function getArticle_AnA($row) {
			
			if (preg_match("@(correction|date|post|thread|digital asset|loco|class|location|story|topic|railcam photo|photo|railcam|download)@Di", $row['event']['object'], $matches)) {
				if (!($matches[1] == "loco" && $row['event']['action'] == "edited")) {
					$row['event']['article'] = "a";
				}
			}
			
			if (preg_match("@(operator)@Di", $row['event']['object'], $matches)) {
				$row['event']['article'] = "an";
			}
			
			return $row['event']['article'];
			
		}
		
		/**
		 * Process the "article" where the proposition is "the"
		 * @since Version 3.9.1
		 * @param arrray $row
		 * @return array
		 */
		
		static public function getArticle_The($row) {
			if (preg_match("@(cover photo)@Di", $row['event']['object'], $matches)) {
				$row['event']['article'] = "the";
			}
			
			if ($row['event']['action'] == "re-ordered" && preg_match("@(owners|owner|operators|operator)@Di", $row['title'], $matches)) {
				$row['event']['object'] = "owners/operators";
				$row['event']['article'] = "the";
			}
			
			return $row;
			
		}

	}
