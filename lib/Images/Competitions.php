<?php
	/**
	 * Photo competitions
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Images;
	
	use Railpage\AppCore;
	use Railpage\Module;
	use Exception;
	use DateTime;
	
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
		 * Get the list of competitions, optionally filter by status
		 * @since Version 3.9.1
		 * @param int $status
		 * @return array
		 */
		
		public function getCompetitions($status = false) {
			$query = "SELECT id FROM image_competition";
			$where = array(); 
			
			if ($status != false) {
				$query .= " WHERE status = ?";
				$where[] = $status;
			}
			
			$comps = array(); 
			
			foreach ($this->db->fetchAll($query) as $row) {
				$Competition = new Competition($row['id']);
				$comps[] = $Competition->getArray(); 
			}
			
			return $comps;
		}
	}