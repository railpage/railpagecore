<?php
	/**
	 * Photo competition utility class
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images\Utility;
	
	use Railpage\Images\Competition;
	use Railpage\Images\Competitions;
	use Railpage\Images\Image;
	use Railpage\Images\Images;
	use Railpage\ContentUtility;
	use Railpage\Debug;
	use Railpage\SiteMessages\SiteMessages;
	use Railpage\SiteMessages\SiteMessage;
	use Railpage\Notifications\Notification;
	use DateTime;
	use Railpage\AppCore;
	
	class CompetitionUtility {
		
		/**
		 * Find the photo context from a specified array
		 * @since Version 3.9.1
		 * @param array $photos
		 * @param \Railpage\Images\Image $Image
		 * @return array
		 */
		
		public static function getPhotoContext($photos, $Image) {
			
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
			
			$return = array_slice($tmp, -2);
			
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
			
			$return = array_merge($return, array_slice($tmp, 0, 2));
			
			return array_reverse($return);
			
		}
		
		/**
		 * Create a site message targeted to the competition winner
		 * @since Version 3.9.1
		 * @param \Railpage\Images\Competition $Comp
		 * @return void
		 */
		
		public static function createSiteNotificationForWinner(Competition $Comp) {
			$Photo = $Comp->getWinningPhoto();
			
			if (!$SiteMessage = (new SiteMessages)->getMessageForObject($Comp)) {
				$SiteMessage = new SiteMessage; 
				
				$SiteMessage->title = sprintf("Photo competition: %s", $Comp->title); 
				$SiteMessage->text = sprintf("You won the %s photo competition! <a href='%s'>Set the subject of next month's competition</a>.", $Comp->title, $Comp->url->suggestsubject); 
				$SiteMessage->Object = $Comp;
				$SiteMessage->targetUser($Photo->Author)->commit(); 
			}
		}
		
		/**
		 * Check if the image submission window is open
		 * @since Version 3.9.1
		 * @param \Railpage\Images\Competition $Comp
		 * @return boolean
		 */
		
		public static function isSubmissionWindowOpen(Competition $Comp) {
			
			return self::compareWindows($Comp, "Submissions");
			
		}
		
		/**
		 * Check if the voting window is open
		 * @since Version 3.9.1
		 * @param \Railpage\Images\Competition $Comp
		 * @return boolean
		 */
		
		public static function isVotingWindowOpen(Competition $Comp) {
			
			return self::compareWindows($Comp, "Voting");
			
		}
		
		/**
		 * Compare voting / submission windows to current time
		 * @since Version 3.9.1
		 * @param \Railpage\Images\Competition $Comp
		 * @param string $window
		 * @return boolean
		 */
		
		private static function compareWindows(Competition $Comp, $window) {
			
			$Now = new DateTime;
			
			$open = sprintf("%sDateOpen", $window);
			$close = sprintf("%sDateClose", $window);
			
			if (!($Comp->$open instanceof DateTime && $Comp->$open <= $Now) || 
				!($Comp->$close instanceof DateTime && $Comp->$close >= $Now)) {
					return false;
			}
			
			return true;
			
		}
		
		/**
		 * Process the recipients of the notification email
		 * @since Version 3.9.1
		 * @param \Railpage\Notifications\Notification $Notification
		 * @return \Railpage\Notifications\Notification
		 */
		 
		public static function notificationDoRecipients(Notification $Notification) {
			
			$contestants = (new Competitions)->getPreviousContestants(); 
			
			$replacements = array(); 
			
			foreach ($contestants as $row) {
				$Notification->AddRecipient($row['user_id'], $row['username'], $row['contact_email']);
				
				// Add to the decorator
				$replacements[$row['contact_email']] = array(
					"[username]" => $row['username']
				);
			}
			
			$Notification->meta['decoration'] = $replacements;
			
			return $Notification;

		}
		
		/**
		 * Send an email notification for this competition
		 * @since Version 3.9.1
		 * @param \Railpage\Images\Competition $Comp
		 * @param array $notificationOptions
		 * @return void
		 */
		
		public static function sendNotification(Competition $Comp, $notificationOptions) {
			
			$flag = $notificationOptions['flag'];
			
			/**
			 * Check if the notification sent flag has been set
			 */
			
			if (!isset($Comp->meta[$flag]) || !filter_var($Comp->meta[$flag]) || $Comp->meta[$flag] === false) {
				
				/**
				 * Create the notification
				 */
				
				$Notification = new Notification;
				$Notification->subject = $notificationOptions['subject'];
				
				/**
				 * Add recipients and decoration
				 */
				
				$Notification = self::notificationDoRecipients($Notification); 
								
				/**
				 * Set our email body
				 */
				
				if (function_exists("wpautop") && function_exists("format_post")) {
					$notificationOptions['body'] = wpautop(format_post($notificationOptions['body']));
				}
				
				/**
				 * Assemble some template vars for our email
				 */
				
				$Smarty = AppCore::getSmarty(); 
				
				$Smarty->Assign("email", array(
					"subject" => $notificationOptions['subject'],
					"body" => $notificationOptions['body']
				));
				
				/**
				 * Set the body, submit the notification to the dispatch queue
				 */
				
				$Notification->body = $Smarty->Fetch($Smarty->ResolveTemplate("template.generic"));
				$Notification->commit();
				
				/**
				 * Set the notification flag
				 */
				
				$Comp->meta[$flag] = true;
				$Comp->commit(); 
				
			}

		}
		
		/**
		 * Get the competition ID
		 * @since Version 3.9.1
		 * @return int
		 * @param string $slug
		 */
		
		public static function getIDFromSlug($slug) {
			
			$Database = (new AppCore)->getDatabaseConnection(); 
			
			if (filter_var($slug, FILTER_VALIDATE_INT)) {
				return $slug; 
			}
			
			$query = "SELECT id FROM image_competition WHERE slug = ?";
			$tempid = $Database->fetchOne($query, $slug);
			
			if (filter_var($tempid, FILTER_VALIDATE_INT)) {
				return $tempid;
			}
			
			$query = "SELECT ID from image_competition WHERE title = ?";
			return $Database->fetchOne($query, $slug);
			
		}
	}