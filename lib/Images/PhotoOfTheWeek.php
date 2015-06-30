<?php
	/** 
	 * Photo of the week nomination/management
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images;
	
	use Exception;
	use InvalidArgumentException;
	use DateTime;
	use DateTimeZone;
	use DateInterval;
	use Railpage\AppCore;
	use Railpage\Debug;
	use Railpage\Url;
	use Railpage\Users\User;
	use Railpage\Users\Factory as UserFactory;
	
	class PhotoOfTheWeek extends AppCore {
		
		/**
		 * Nominate a photo 
		 * @since Version 3.9.1
		 * @param \Railpage\Images\Image $Image
		 * @param \DateTime $Week
		 * @param \Railpage\Users\User $User
		 * @return boolean
		 */
		
		public function NominateImage(Image $Image, DateTime $Week, User $User) {
			
			$query = "SELECT id FROM image_weekly WHERE datefrom = ?";
			
			if ($this->db->fetchOne($query, $Week->format("Y-m-d"))) {
				$Week->add(new DateInterval("P7D"));
			}
			
			if ($this->db->fetchOne($query, $Week->format("Y-m-d"))) {
				throw new Exception("We already have an image nominated for the week starting " . $Week->format("Y-m-d")); 
			}
			
			if ($this->db->fetchOne("SELECT id FROM image_weekly WHERE image_id = ?", $Image->id)) {
				throw new Exception("This photo has already been nominated for Photo of the Week"); 
			}
			
			$data = [
				"image_id" => $Image->id,
				"datefrom" => $Week->format("Y-m-d"),
				"added_by" => $User->id
			];
			
			$this->db->insert("image_weekly", $data); 
			
			return true;
			
		}
		
		/**
		 * Get the start of week from any given date
		 * @since Version 3.9.1
		 * @param string $week
		 * @return \DateTime
		 */
		
		public static function getStartOfWeek($week = false) {
			
			if (!$week) {
				$week = new DateTime;
			}
			
			if (filter_var($week, FILTER_VALIDATE_INT)) {
				$week = new DateTime("@" . $week);
			}
			
			if (!$week instanceof DateTime) {
				$week = new DateTime($week);
			}
			
			$ts = strtotime('sunday last week', $week->getTimestamp()); 
			
			$Date = new DateTime("@" . $ts);
			$Date->setTimezone(new DateTimeZone("Australia/Melbourne")); 
			
			return $Date;
		}
		
		/**
		 * Get the image of the week
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getImageOfTheWeek($week = false) {
			
			$Date = self::getStartOfWeek($week);
			
			$query = "SELECT i.*, u.username, iw.added_by AS user_id FROM image_weekly AS iw
						LEFT JOIN image AS i ON iw.image_id = i.id
						LEFT JOIN nuke_users AS u ON u.user_id = iw.added_by
						WHERE iw.datefrom = ? LIMIT 1";
			
			$result = $this->db->fetchRow($query, $Date->format("Y-m-d")); 
			
			$result['meta'] = json_decode($result['meta'], true); 
			$result['meta']['sizes'] = Images::normaliseSizes($result['meta']['sizes']);
			$result['url'] = Utility\Url::CreateFromImageID($result['id'])->getURLs();
			
			return $result;
			
		}
		
		/** 
		 * Determine if an image has been photo of the week, and return the date range if found
		 * @since Version 3.9.1
		 * @param \Railpage\Images\Image $Image
		 * @return array
		 */
		
		public function isPhotoOfTheWeek(Image $Image) {
			
			$query = "SELECT iw.datefrom, u.user_id, u.username, iw.image_id 
				FROM image_weekly AS iw 
				LEFT JOIN nuke_users AS u ON iw.added_by = u.user_id
				WHERE iw.image_id = ?";
			
			$result = $this->db->fetchRow($query, $Image->id); 
			
			return $result;
			
		}
	}