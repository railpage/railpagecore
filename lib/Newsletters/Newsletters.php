<?php
	/**
	 * Newsletter
	 * @since Version 3.9.1
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	
	namespace Railpage\Newsletters;
	
	use Exception;
	use DateTime;
	use Railpage\Url;
	use Railpage\AppCore;
	use Railpage\Module;
	use Railpage\Users\User;
	use Railpage\Notifications\Notification;
	use Railpage\Notifications\Transport\Email;
	
	/**
	 * Newsletters
	 */
	
	class Newsletters extends AppCore {
		
		/**
		 * Get available templates
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getTemplates() {
			return $this->db->fetchAll("SELECT * FROM newsletter_templates");
		}
		
		/**
		 * Get a single template
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getTemplate($id = false) {
			if (!filter_var($id, FILTER_VALIDATE_INT)) {
				throw new Exception("Cannnot fetch template from the database - invalid template ID specified");
			}
			
			return $this->db->fetchRow("SELECT * FROM newsletter_templates WHERE id = ?", $id);
		}
		
		/**
		 * Get newsletters
		 * @since Version 3.9.1
		 * @return array
		 */
		
		public function getNewsletters() {
			$return = array(); 
			
			foreach ($this->db->fetchAll("SELECT * FROM newsletter") as $row) {
				$Newsletter = new Newsletter($row['id']); 
				$return[] = $Newsletter->getArray(); 
			}
			
			return $return;
		}
	}
	