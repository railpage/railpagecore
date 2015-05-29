<?php
	/**
	 * Photo competition utility class
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images\Utility;
	
	use Railpage\Images\Image;
	use Railpage\Images\Images;
	use Railpage\ContentUtility;
	use Railpage\Debug;
	use Railpage\SiteMessages\SiteMessages;
	use Railpage\SiteMessages\SiteMessage;
	
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
	}